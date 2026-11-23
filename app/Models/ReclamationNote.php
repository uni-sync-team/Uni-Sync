<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A student's claim about a grade. There was no old model for this one, so this is
 * written from the migration and from how demandes work.
 */
class ReclamationNote extends Model
{
    public const STATUT_EN_ATTENTE = 'EN_ATTENTE';
    public const STATUT_ACCEPTEE   = 'ACCEPTEE';   // ASSUMED name: only EN_ATTENTE and REJETEE were in your seed
    public const STATUT_REJETEE    = 'REJETEE';

    public const STATUTS = [self::STATUT_EN_ATTENTE, self::STATUT_ACCEPTEE, self::STATUT_REJETEE];

    protected $fillable = ['etudiant_id', 'evaluation_id', 'motif', 'statut'];

    public function etudiant(): BelongsTo
    {
        return $this->belongsTo(Etudiant::class);
    }

    /** In a view: $r->evaluation->type, $r->evaluation->module->intitule */
    public function evaluation(): BelongsTo
    {
        return $this->belongsTo(Evaluation::class);
    }

    /** The grade being contested (first one found for that student and evaluation). */
    public function note(): ?Note
    {
        return Note::where('etudiant_id', $this->etudiant_id)
            ->where('evaluation_id', $this->evaluation_id)
            ->first();
    }

    /** ReclamationNote::statut('EN_ATTENTE')->get() : with student, evaluation and module, oldest first */
    public function scopeStatut(Builder $query, string $statut): Builder
    {
        return $query
            ->with(['etudiant', 'evaluation.module'])
            ->where('statut', $statut)
            ->orderBy('id');
    }

    /** Pending claims first, then newest: ReclamationNote::admin()->paginate(15) */
    public function scopeAdmin(Builder $query): Builder
    {
        return $query
            ->with(['etudiant', 'evaluation.module'])
            ->orderByRaw('(statut = ?) DESC', [self::STATUT_EN_ATTENTE])
            ->orderByDesc('id');
    }

    /** Does the student already have a pending claim on this evaluation? (the table has no unique key) */
    public static function hasPending(int $etudiantId, int $evaluationId): bool
    {
        return self::where('etudiant_id', $etudiantId)
            ->where('evaluation_id', $evaluationId)
            ->where('statut', self::STATUT_EN_ATTENTE)
            ->exists();
    }

    /** Decides a pending claim. False if it was already decided (atomic, like DemandeAdministrative::decide). */
    public static function decide(int $id, string $statut): bool
    {
        return self::where('id', $id)
            ->where('statut', self::STATUT_EN_ATTENTE)
            ->update(['statut' => $statut]) > 0;
    }
}
