<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

class ResultatSemestre extends Model
{
    public const DECISIONS = ['VALIDE', 'NON_VALIDE', 'AJOURNE'];
    public const SEUIL     = 10.0;

    protected $fillable = ['etudiant_id', 'semestre_id', 'moyenne', 'decision'];

    protected $casts = [
        'moyenne' => 'float',
    ];

    public function etudiant(): BelongsTo
    {
        return $this->belongsTo(Etudiant::class);
    }

    public function semestre(): BelongsTo
    {
        return $this->belongsTo(Semestre::class);
    }

    /**
     * Replaces findBySemestre($id): results with the student loaded, ordered by name.
     *   ResultatSemestre::forSemestre($id)   ->  $r->etudiant->cne, $r->etudiant->nom ...
     */
    public static function forSemestre(int $semestreId): Collection
    {
        return self::query()
            ->join('etudiants', 'etudiants.id', '=', 'resultat_semestres.etudiant_id')
            ->where('resultat_semestres.semestre_id', $semestreId)
            ->select('resultat_semestres.*')
            ->with('etudiant')
            ->orderBy('etudiants.nom')
            ->orderBy('etudiants.prenom')
            ->get();
    }

    /**
     * Replaces calculer($idSemestre): recomputes the semester average of every student
     * who has at least one grade (NORMALE session): module average weighted by evaluation
     * coefficient (out of 20), then semester average weighted by module coefficient.
     * Decision: VALIDE if >= 10. Returns the number of students computed.
     */
    public static function calculer(int $semestreId): int
    {
        $parModule = DB::table('notes')
            ->join('evaluations', 'evaluations.id', '=', 'notes.evaluation_id')
            ->whereNotNull('notes.valeur')
            ->where('notes.session', 'NORMALE')
            ->groupBy('notes.etudiant_id', 'evaluations.module_id')
            ->selectRaw('notes.etudiant_id, evaluations.module_id,
                SUM((notes.valeur / evaluations.bareme) * 20 * evaluations.coefficient) / SUM(evaluations.coefficient) AS moyenne');

        $rows = DB::query()
            ->fromSub($parModule, 'mm')
            ->join('modules', 'modules.id', '=', 'mm.module_id')
            ->where('modules.semestre_id', $semestreId)
            ->groupBy('mm.etudiant_id')
            ->selectRaw('mm.etudiant_id, SUM(mm.moyenne * modules.coefficient) / SUM(modules.coefficient) AS moyenne')
            ->get();

        DB::transaction(function () use ($rows, $semestreId) {
            foreach ($rows as $r) {
                $moyenne = round((float) $r->moyenne, 2);

                self::updateOrCreate(
                    ['etudiant_id' => $r->etudiant_id, 'semestre_id' => $semestreId],
                    ['moyenne' => $moyenne, 'decision' => $moyenne >= self::SEUIL ? 'VALIDE' : 'NON_VALIDE']
                );
            }
        });

        return $rows->count();
    }
}
