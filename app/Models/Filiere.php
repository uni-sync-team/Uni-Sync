<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Filiere extends Model
{
    // Table "filieres", key "id" and timestamps follow the conventions,
    // so the model only needs the fillable list.
    protected $fillable = ['departement_id', 'nom', 'description'];

    /** Replaces the JOIN on departement: $filiere->departement->nom */
    public function departement(): BelongsTo
    {
        return $this->belongsTo(Departement::class);
    }

    /**
     * Replaces getFormations($id): $filiere->formations
     * Needs a filiere_id column on the formations table.
     */
    public function formations(): HasMany
    {
        return $this->hasMany(Formation::class);
    }

    /**
     * Replaces findAllWithDepartement():
     * Filiere::withDepartementNom()->get()  (each row has ->departement)
     */
    public function scopeWithDepartementNom(Builder $query): Builder
    {
        return $query
            ->join('departements', 'departements.id', '=', 'filieres.departement_id')
            ->select('filieres.*', 'departements.nom as departement')
            ->orderBy('departements.nom')
            ->orderBy('filieres.nom');
    }
}
