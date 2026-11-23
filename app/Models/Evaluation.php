<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Evaluation extends Model
{
    public const TYPES = ['Contrôle continu', 'TP', 'Examen final', 'Rattrapage'];

    protected $fillable = ['module_id', 'type', 'date_evaluation', 'bareme', 'coefficient'];

    protected $casts = [
        'date_evaluation' => 'date',
        'bareme'          => 'float',
        'coefficient'     => 'float',
    ];

    /** Replaces findByModule($id): $module->evaluations */
    public function module(): BelongsTo
    {
        return $this->belongsTo(Module::class);
    }

    public function notes(): HasMany
    {
        return $this->hasMany(Note::class);
    }

    /**
     * Replaces findAllDetailed($idModule, $type): evaluations with module / formation
     * labels and the number of grades, newest first.
     *   Evaluation::detailed($idModule, $type)->get()
     * In a view: $ev->module->code, $ev->module->intitule,
     *            $ev->module->semestre->formation->intitule, $ev->notes_count
     */
    public function scopeDetailed(Builder $query, int $idModule = 0, string $type = ''): Builder
    {
        return $query
            ->with('module.semestre.formation')
            ->withCount('notes')
            ->when($idModule > 0, fn (Builder $q) => $q->where('module_id', $idModule))
            ->when($type !== '', fn (Builder $q) => $q->where('type', $type))
            ->orderByDesc('date_evaluation')
            ->orderByDesc('id');
    }

    /** Replaces findDetailed($id): Evaluation::with('module.semestre.formation')->withCount('notes')->find($id) */

    /** Replaces getNotes($id, $session): $evaluation->notesAvecEtudiants('NORMALE') */
    public function notesAvecEtudiants(?string $session = null): Collection
    {
        return $this->notes()
            ->join('etudiants', 'etudiants.id', '=', 'notes.etudiant_id')
            ->select('notes.*')
            ->with('etudiant')
            ->when($session !== null, fn (Builder $q) => $q->where('notes.session', $session))
            ->orderBy('etudiants.nom')
            ->orderBy('etudiants.prenom')
            ->get();
    }

    /** Replaces getStats($id): $evaluation->stats() gives nb_notes, moyenne, minimum, maximum */
    public function stats(): ?Model
    {
        return $this->notes()
            ->selectRaw('COUNT(valeur) AS nb_notes, AVG(valeur) AS moyenne, MIN(valeur) AS minimum, MAX(valeur) AS maximum')
            ->first();
    }

    /**
     * Replaces getFeuilleNotes($id, $session): every student validly enrolled in the
     * evaluation's formation, with their current grade (if any) in ->valeur.
     */
    public function feuilleNotes(string $session = 'NORMALE'): Collection
    {
        $formationId = $this->module->semestre->formation_id;

        return Etudiant::query()
            ->select('etudiants.*', 'notes.valeur')
            ->join('inscriptions', function ($join) use ($formationId) {
                $join->on('inscriptions.etudiant_id', '=', 'etudiants.id')
                    ->where('inscriptions.formation_id', $formationId)
                    ->where('inscriptions.statut', Inscription::STATUT_VALIDEE);
            })
            ->leftJoin('notes', function ($join) use ($session) {
                $join->on('notes.etudiant_id', '=', 'etudiants.id')
                    ->where('notes.evaluation_id', $this->id)
                    ->where('notes.session', $session);
            })
            ->distinct()
            ->orderBy('etudiants.nom')
            ->orderBy('etudiants.prenom')
            ->get();
    }
}
