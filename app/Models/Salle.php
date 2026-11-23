<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Salle extends Model
{
    /** Allowed room types (each must fit in 50 characters). Adjust to your faculty. */
    public const TYPES = ['Salle de cours', 'Amphithéâtre', 'Laboratoire', 'Salle informatique'];

    // Column renamed: typeSalle is now type_salle
    protected $fillable = ['nom', 'capacite', 'type_salle'];

    protected $casts = [
        'capacite' => 'integer',
    ];

    /** Uses the salle_id column of the seances table. */
    public function seances(): HasMany
    {
        return $this->hasMany(Seance::class);
    }
}
