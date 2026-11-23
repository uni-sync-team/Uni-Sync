<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class Etudiant extends Model
{
    protected $fillable = ['user_id', 'cne', 'nom', 'prenom', 'date_naissance', 'telephone'];

    protected $casts = [
        'date_naissance' => 'date',
    ];

    /* ------------------------------------------------------------------
     | Relations
     * ------------------------------------------------------------------ */

    /** Replaces findByUser() and the JOIN on utilisateur: $etudiant->user */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /** Replaces inscriptions($id): $etudiant->inscriptions (with formation + annee loaded) */
    public function inscriptions(): HasMany
    {
        return $this->hasMany(Inscription::class);
    }

    /** Same pivot as Formation::etudiants(). */
    public function formations(): BelongsToMany
    {
        return $this->belongsToMany(Formation::class, 'inscriptions')
            ->withPivot('statut', 'annee_universitaire_id', 'date_inscription');
    }

    /** Replaces notes($id): $etudiant->notes */
    public function notes(): HasMany
    {
        return $this->hasMany(Note::class);
    }

    /** Replaces resultats($id): $etudiant->resultats */
    public function resultats(): HasMany
    {
        return $this->hasMany(ResultatSemestre::class);
    }

    /** Replaces demandes($id): $etudiant->demandes */
    public function demandes(): HasMany
    {
        return $this->hasMany(DemandeAdministrative::class)->orderByDesc('date_demande');
    }

    /** Replaces reclamations($id): $etudiant->reclamations */
    public function reclamations(): HasMany
    {
        return $this->hasMany(ReclamationNote::class)->orderByDesc('id');
    }

    /* ------------------------------------------------------------------
     | Scopes
     * ------------------------------------------------------------------ */

    /**
     * Replaces findAll($q), search($term) and the OLD findAll_():
     * Etudiant::search($q)->with('user')->orderBy('cne')->limit(100)->get()
     * (searches nom, prenom and cne; each row has ->user->actif)
     */
    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        return $query->when($term !== null && $term !== '', function (Builder $qb) use ($term) {
            $like = '%' . addcslashes($term, '%_\\') . '%';
            $qb->where(fn (Builder $q) => $q
                ->where('nom', 'like', $like)
                ->orWhere('prenom', 'like', $like)
                ->orWhere('cne', 'like', $like));
        });
    }

    /* ------------------------------------------------------------------
     | Replaces isActive($id): $etudiant->actif
     * ------------------------------------------------------------------ */
    public function getActifAttribute(): bool
    {
        return (bool) $this->user?->actif;
    }

    /* ------------------------------------------------------------------
     | Replaces update($id, $data, $actif): student + account status in one transaction
     |   $etudiant->updateWithActif($data, $actif);   returns false on a DB error
     * ------------------------------------------------------------------ */
    public function updateWithActif(array $data, bool $actif): bool
    {
        try {
            DB::transaction(function () use ($data, $actif) {
                $this->update($data);
                $this->user->update(['actif' => $actif]);
            });
            return true;
        } catch (\Illuminate\Database\QueryException) {
            return false; // e.g. duplicate CNE
        }
    }

    /* ------------------------------------------------------------------
     | Replaces createWithUser($d): user + role + student in one transaction
     |   $etudiant = Etudiant::createWithUser($data);
     * ------------------------------------------------------------------ */
    public static function createWithUser(array $d): self
    {
        return DB::transaction(function () use ($d) {
            $user = User::create([
                'login'    => $d['login'],
                'email'    => $d['email'],
                'password' => $d['password'],
            ]);

            // firstOrFail() aborts the transaction if the role doesn't exist,
            // so an account without a role can never be created.
            $user->roles()->attach(Role::where('nom', 'etudiant')->firstOrFail());

            return self::create([
                'user_id'        => $user->id,
                'cne'            => $d['cne'],
                'nom'            => $d['nom'],
                'prenom'         => $d['prenom'],
                'date_naissance' => $d['date_naissance'] ?? null,
                'telephone'      => $d['telephone'] ?? null,
            ]);
        });
    }
}
