<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Note extends Model
{
    protected $fillable = ['etudiant_id', 'evaluation_id', 'valeur', 'session'];

    protected $casts = [
        'valeur' => 'float',
    ];

    public function etudiant(): BelongsTo
    {
        return $this->belongsTo(Etudiant::class);
    }

    public function evaluation(): BelongsTo
    {
        return $this->belongsTo(Evaluation::class);
    }

    /**
     * Replaces saveNote(): insert or update (unique key: student + evaluation + session).
     *   Note::saveNote($idEtudiant, $idEvaluation, 14.5, 'NORMALE');
     */
    public static function saveNote(int $etudiantId, int $evaluationId, ?float $valeur, string $session = 'NORMALE'): self
    {
        return self::updateOrCreate(
            ['etudiant_id' => $etudiantId, 'evaluation_id' => $evaluationId, 'session' => $session],
            ['valeur' => $valeur]
        );
    }

    /**
     * Replaces moyenneModule(): weighted average of a student in a module
     * (weighted by evaluation coefficient, brought back to 20), or null.
     *   Note::moyenneModule($idEtudiant, $idModule)
     */
    public static function moyenneModule(int $etudiantId, int $moduleId, string $session = 'NORMALE'): ?float
    {
        $row = self::query()
            ->join('evaluations', 'evaluations.id', '=', 'notes.evaluation_id')
            ->where('notes.etudiant_id', $etudiantId)
            ->where('evaluations.module_id', $moduleId)
            ->where('notes.session', $session)
            ->whereNotNull('notes.valeur')
            ->selectRaw('SUM((notes.valeur / evaluations.bareme) * 20 * evaluations.coefficient) / SUM(evaluations.coefficient) AS moyenne')
            ->first();

        return $row?->moyenne !== null ? round((float) $row->moyenne, 2) : null;
    }
}
