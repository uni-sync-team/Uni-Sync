<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Inscription extends Model
{
    public const STATUT_EN_ATTENTE = 'EN_ATTENTE';
    public const STATUT_VALIDEE    = 'VALIDEE';
    public const STATUT_REFUSEE    = 'REFUSEE';

    protected $fillable = [
        'etudiant_id', 'formation_id', 'annee_universitaire_id', 'date_inscription', 'statut',
    ];

    protected $casts = [
        'date_inscription' => 'date',
    ];

    protected static function booted(): void
    {
        // Replaces: $data['dateInscription'] ??= date('Y-m-d') in create()
        static::creating(function (Inscription $inscription) {
            $inscription->date_inscription ??= today();
        });
    }

    public function etudiant(): BelongsTo
    {
        return $this->belongsTo(Etudiant::class);
    }

    public function formation(): BelongsTo
    {
        return $this->belongsTo(Formation::class);
    }

    public function anneeUniversitaire(): BelongsTo
    {
        return $this->belongsTo(AnneeUniversitaire::class);
    }

    /**
     * Replaces getModules($id): $inscription->modules  (->pivot->statut is statutModule)
     * Pivot table "inscription_modules" (inscription_id, module_id, statut).
     */
    public function modules(): BelongsToMany
    {
        return $this->belongsToMany(Module::class, 'inscription_modules')
            ->withPivot('statut')
            ->withTimestamps();
    }

    /**
     * Replaces findByStatut($statut), with student, formation and year loaded:
     * Inscription::statut('EN_ATTENTE')->get()
     */
    public function scopeStatut(Builder $query, string $statut): Builder
    {
        return $query
            ->where('statut', $statut)
            ->with(['etudiant', 'formation', 'anneeUniversitaire'])
            ->orderBy('date_inscription');
    }

    /* ------------------------------------------------------------------
     | Replaces the InscriptionModule class (a pivot, so no model of its own)
     |   findByInscription($id)  ->  $inscription->modules
     * ------------------------------------------------------------------ */

    /** Replaces enroll() (INSERT IGNORE): false if the student is already in that module. */
    public function enroll(int $moduleId, ?string $statut = null): bool
    {
        if ($this->modules()->where('modules.id', $moduleId)->exists()) {
            return false;
        }

        $this->modules()->attach($moduleId, ['statut' => $statut]);

        return true;
    }

    /** Replaces InscriptionModule::updateStatut(). */
    public function updateModuleStatut(int $moduleId, string $statut): bool
    {
        return $this->modules()->updateExistingPivot($moduleId, ['statut' => $statut]) > 0;
    }

    /** Replaces unenroll(). */
    public function unenroll(int $moduleId): bool
    {
        return $this->modules()->detach($moduleId) > 0;
    }
}
