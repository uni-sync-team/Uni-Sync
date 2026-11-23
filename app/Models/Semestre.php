<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Semestre extends Model
{
    protected $fillable = ['formation_id', 'libelle', 'numero'];

    protected $casts = [
        'numero' => 'integer',
    ];

    public function formation(): BelongsTo
    {
        return $this->belongsTo(Formation::class);
    }

    public function modules(): HasMany
    {
        return $this->hasMany(Module::class);
    }

    public function resultats(): HasMany
    {
        return $this->hasMany(ResultatSemestre::class);
    }

    /**
     * Replaces findAllWithFormation(): Semestre::withFormation()->get()
     * Each row has ->formation->intitule (your old "formation" column).
     */
    public function scopeWithFormation(Builder $query): Builder
    {
        return $query
            ->with('formation')
            ->join('formations', 'formations.id', '=', 'semestres.formation_id')
            ->select('semestres.*')
            ->orderBy('formations.intitule')
            ->orderBy('semestres.numero');
    }
}
