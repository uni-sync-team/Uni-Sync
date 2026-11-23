<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Seance extends Model
{
    public const TYPES = ['Cours', 'TD', 'TP'];

    // Column renamed: typeSeance is now type_seance
    protected $fillable = [
        'module_id', 'enseignant_id', 'salle_id', 'date_debut', 'date_fin', 'type_seance',
    ];

    protected $casts = [
        'date_debut' => 'datetime',
        'date_fin'   => 'datetime',
    ];

    /**
     * The old SELECT joined module, enseignant and salle. Now use the relations:
     *   $seance->module->code, $seance->module->intitule,
     *   $seance->enseignant->nom, $seance->enseignant->prenom, $seance->salle->nom
     */
    public function module(): BelongsTo
    {
        return $this->belongsTo(Module::class);
    }

    public function enseignant(): BelongsTo
    {
        return $this->belongsTo(Enseignant::class);
    }

    public function salle(): BelongsTo
    {
        return $this->belongsTo(Salle::class);
    }

    /**
     * Replaces search($filters, $limit, $offset) + count($filters): soonest first.
     * Filters: module_id, enseignant_id, salle_id, du / au (Y-m-d, "au" is inclusive).
     *   Seance::filtered($request->only([...]))->paginate(15)
     * (findById($id) is now Seance::with(['module', 'enseignant', 'salle'])->find($id))
     */
    public function scopeFiltered(Builder $query, array $f): Builder
    {
        return $query
            ->with(['module', 'enseignant', 'salle'])
            ->when((int) ($f['module_id'] ?? 0) > 0, fn (Builder $q) => $q->where('module_id', (int) $f['module_id']))
            ->when((int) ($f['enseignant_id'] ?? 0) > 0, fn (Builder $q) => $q->where('enseignant_id', (int) $f['enseignant_id']))
            ->when((int) ($f['salle_id'] ?? 0) > 0, fn (Builder $q) => $q->where('salle_id', (int) $f['salle_id']))
            ->when(($f['du'] ?? '') !== '', fn (Builder $q) => $q->where('date_debut', '>=', $f['du'] . ' 00:00:00'))
            ->when(($f['au'] ?? '') !== '', fn (Builder $q) => $q->where('date_debut', '<', Carbon::parse($f['au'])->addDay()->toDateString()))
            ->orderBy('date_debut')
            ->orderBy('id');
    }

    /**
     * Replaces conflict(): another session overlapping [debut, fin) in the same room
     * or with the same teacher. Returns 'salle', 'enseignant' or null.
     *   Seance::conflict($salleId, $enseignantId, $debut, $fin, $excludeId)
     */
    public static function conflict(int $salleId, int $enseignantId, string $debut, string $fin, ?int $excludeId = null): ?string
    {
        $checks = [
            'salle'      => ['salle_id', $salleId],
            'enseignant' => ['enseignant_id', $enseignantId],
        ];

        foreach ($checks as $kind => [$column, $value]) {
            $overlap = self::where($column, $value)
                ->where('date_debut', '<', $fin)
                ->where('date_fin', '>', $debut)
                ->when($excludeId !== null, fn (Builder $q) => $q->where('id', '<>', $excludeId))
                ->exists();

            if ($overlap) {
                return $kind;
            }
        }

        return null;
    }
}
