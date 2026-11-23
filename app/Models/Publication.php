<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Publication extends Model
{
    protected $fillable = [
        'administrateur_id', 'titre', 'contenu',
        'date_publication', 'date_expiration', 'actif',
    ];

    protected $casts = [
        'date_publication' => 'date',
        'date_expiration'  => 'date',
        'actif'            => 'boolean',
    ];

    /** The author. Needs an Administrateur model (table "administrateurs"). */
    public function administrateur(): BelongsTo
    {
        return $this->belongsTo(Administrateur::class);
    }

    /**
     * Replaces findActive($limit): active, already published, not expired,
     * newest first.   Publication::active()->take(4)->get()
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query
            ->where('actif', true)
            ->whereDate('date_publication', '<=', today())
            ->where(fn (Builder $q) => $q
                ->whereNull('date_expiration')
                ->orWhereDate('date_expiration', '>=', today()))
            ->orderByDesc('date_publication')
            ->orderByDesc('id');
    }

    /**
     * Replaces search($q, ...) for the admin list (all announcements, any status):
     * Publication::search($q)->with('administrateur')->latest('date_publication')->paginate(15)
     */
    public function scopeSearch(Builder $query, ?string $q): Builder
    {
        return $query->when($q !== null && $q !== '', function (Builder $qb) use ($q) {
            $qb->where('titre', 'like', '%' . addcslashes($q, '%_\\') . '%');
        });
    }

    /**
     * Replaces statut($row): Active, Programmée, Expirée or Désactivée.
     * Use it as a property: $publication->statut
     */
    protected function statut(): Attribute
    {
        return Attribute::get(function () {
            if (!$this->actif) {
                return 'Désactivée';
            }
            if ($this->date_publication->gt(today())) {
                return 'Programmée';
            }
            if ($this->date_expiration && $this->date_expiration->lt(today())) {
                return 'Expirée';
            }
            return 'Active';
        });
    }
}
