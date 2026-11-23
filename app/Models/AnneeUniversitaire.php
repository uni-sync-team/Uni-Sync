<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AnneeUniversitaire extends Model
{
    // Laravel turns AnneeUniversitaire into "annee_universitaires",
    // which is exactly the name of your table.

    protected $fillable = ['libelle', 'date_debut', 'date_fin'];

    protected $casts = [
        'date_debut' => 'date',
        'date_fin'   => 'date',
    ];

    public function inscriptions(): HasMany
    {
        return $this->hasMany(Inscription::class);
    }

    /** Replaces findAll(): AnneeUniversitaire::recentFirst()->get() */
    public function scopeRecentFirst(Builder $query): Builder
    {
        return $query->orderByDesc('date_debut');
    }
}
