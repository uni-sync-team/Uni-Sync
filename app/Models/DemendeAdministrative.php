<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DemandeAdministrative extends Model
{
    public const STATUT_EN_ATTENTE = 'EN_ATTENTE';
    public const STATUT_TRAITEE    = 'TRAITEE';
    public const STATUT_REFUSEE    = 'REFUSEE';

    protected $fillable = ['etudiant_id', 'administrateur_id', 'type_demande', 'date_demande', 'statut'];

    protected $casts = [
        'date_demande' => 'datetime',
    ];

    public function etudiant(): BelongsTo
    {
        return $this->belongsTo(Etudiant::class);
    }

    /** The admin who handled the request (null while pending). */
    public function administrateur(): BelongsTo
    {
        return $this->belongsTo(Administrateur::class);
    }

    /** Replaces findByStatut($statut): DemandeAdministrative::statut('EN_ATTENTE')->get() */
    public function scopeStatut(Builder $query, string $statut): Builder
    {
        return $query->with('etudiant')->where('statut', $statut)->orderBy('date_demande');
    }

    /**
     * Replaces search() + countSearch() (admin screen): requests with the student and the
     * admin who handled them; pending first, then newest.
     * Filters: q (student name / CNE / type), statut.
     *   DemandeAdministrative::search($request->only(['q', 'statut']))->paginate(15)
     */
    public function scopeSearch(Builder $query, array $f): Builder
    {
        return $query
            ->with(['etudiant', 'administrateur'])
            ->when(($f['q'] ?? '') !== '', function (Builder $qb) use ($f) {
                $like = '%' . addcslashes($f['q'], '%_\\') . '%';
                $qb->where(fn (Builder $w) => $w
                    ->where('type_demande', 'like', $like)
                    ->orWhereHas('etudiant', fn (Builder $e) => $e
                        ->where('nom', 'like', $like)
                        ->orWhere('prenom', 'like', $like)
                        ->orWhere('cne', 'like', $like)));
            })
            ->when(
                in_array($f['statut'] ?? '', [self::STATUT_EN_ATTENTE, self::STATUT_TRAITEE, self::STATUT_REFUSEE], true),
                fn (Builder $qb) => $qb->where('statut', $f['statut'])
            )
            ->orderByRaw('(statut = ?) DESC', [self::STATUT_EN_ATTENTE])
            ->orderByDesc('date_demande');
    }

    /**
     * Replaces traiter(): assigns the request to an admin and changes its status.
     *   $demande->traiter($administrateurId)
     */
    public function traiter(int $administrateurId, string $statut = self::STATUT_TRAITEE): bool
    {
        return $this->update(['administrateur_id' => $administrateurId, 'statut' => $statut]);
    }

    /**
     * Replaces decide(): decides a pending request. False if it was already decided
     * (or does not exist). The single UPDATE is atomic, like your old query.
     */
    public static function decide(int $id, ?int $administrateurId, string $statut): bool
    {
        return self::where('id', $id)
            ->where('statut', self::STATUT_EN_ATTENTE)
            ->update(['statut' => $statut, 'administrateur_id' => $administrateurId]) > 0;
    }

    /**
     * Replaces findWithStudent($id): the request with the student and their account.
     *   DemandeAdministrative::with('etudiant.user')->find($id)   ->  ->etudiant->user_id
     */

    /** Replaces hasPending(): does this student already have a pending request of that type? */
    public static function hasPending(int $etudiantId, string $type): bool
    {
        return self::where('etudiant_id', $etudiantId)
            ->where('type_demande', $type)
            ->where('statut', self::STATUT_EN_ATTENTE)
            ->exists();
    }
}
