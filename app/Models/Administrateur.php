<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Administrateur extends Model
{
    protected $fillable = ['user_id', 'nom', 'prenom', 'service'];

    /** Replaces findByUser() and the JOIN on utilisateur: $administrateur->user */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /** The announcements written by this admin: $administrateur->publications */
    public function publications(): HasMany
    {
        return $this->hasMany(Publication::class);
    }
}
