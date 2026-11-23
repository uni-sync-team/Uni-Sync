<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Module extends Model
{
    protected $fillable = ['semestre_id', 'code', 'intitule', 'volume_horaire', 'coefficient'];

    protected $casts = [
        'volume_horaire' => 'integer',
        'coefficient'    => 'float',
    ];

    /** Replaces findBySemestre($id): $semestre->modules */
    public function semestre(): BelongsTo
    {
        return $this->belongsTo(Semestre::class);
    }

    /** Replaces getEvaluations($id): $module->evaluations (ordered by date) */
    public function evaluations(): HasMany
    {
        return $this->hasMany(Evaluation::class)->orderBy('date_evaluation');
    }

    /** Uses the module_id column of the seances table. */
    public function seances(): HasMany
    {
        return $this->hasMany(Seance::class);
    }

    /**
     * Replaces getSeances($id): sessions with teacher and room.
     *   $module->seancesDetaillees()   ->  $s->enseignant->nom, $s->salle->nom
     */
    public function seancesDetaillees(): Collection
    {
        return $this->seances()->with(['enseignant', 'salle'])->orderBy('date_debut')->get();
    }

    /** Pivot table "inscription_modules" (inscription_id, module_id, statut). */
    public function inscriptions(): BelongsToMany
    {
        return $this->belongsToMany(Inscription::class, 'inscription_modules')
            ->withPivot('statut')
            ->withTimestamps();
    }

    /**
     * Replaces getEtudiantsInscrits($id): $module->etudiantsInscrits()
     * Each student has ->statut_module (your old statutModule).
     */
    public function etudiantsInscrits(): Collection
    {
        return Etudiant::query()
            ->select('etudiants.*', 'inscription_modules.statut as statut_module')
            ->join('inscriptions', 'inscriptions.etudiant_id', '=', 'etudiants.id')
            ->join('inscription_modules', 'inscription_modules.inscription_id', '=', 'inscriptions.id')
            ->where('inscription_modules.module_id', $this->id)
            ->orderBy('etudiants.nom')
            ->orderBy('etudiants.prenom')
            ->get();
    }

    /**
     * Replaces findAllWithContext() (select lists): Module::withContext()->get()
     * Each row has ->semestre (label) and ->formation (title) as plain strings.
     */
    public function scopeWithContext(Builder $query): Builder
    {
        return $query
            ->join('semestres', 'semestres.id', '=', 'modules.semestre_id')
            ->join('formations', 'formations.id', '=', 'semestres.formation_id')
            ->select('modules.id', 'modules.code', 'modules.intitule',
                     'semestres.libelle as semestre', 'formations.intitule as formation')
            ->orderBy('formations.intitule')
            ->orderBy('semestres.numero')
            ->orderBy('modules.code');
    }
}
