<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Departement extends Model
{
    // Table "departements", key "id" and timestamps all follow Laravel's
    // conventions now, so no $table / $primaryKey / $timestamps needed.

    protected $fillable = ['nom', 'description'];

    /** Needs a departement_id column on the filieres table. */
    public function filieres(): HasMany
    {
        return $this->hasMany(Filiere::class)->orderBy('nom');
    }

    /** Departement::search($q)->get() */
    public function scopeSearch(Builder $query, ?string $q): Builder
    {
        return $query->when($q !== null && $q !== '', fn ($qb) => $qb->where('nom', 'like', "%{$q}%"));
    }

    /** Departement::ordered()->get() */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('nom');
    }
}
