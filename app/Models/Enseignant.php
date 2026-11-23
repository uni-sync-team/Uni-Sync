<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Support\Facades\DB;

class Enseignant extends Model
{
    // fixed grades of the professor
    public const GRADES = [
        'Professeur de l\'Enseignement Supérieur',
        'Professeur Habilité',
        'Professeur Assistant',
        'Vacataire',
    ];

    protected $fillable = ['departement_id', 'user_id', 'nom', 'prenom', 'grade'];

    /* ------------------------------------------------------------------
     | Relations
     * ------------------------------------------------------------------ */

    /** Replaces the JOIN on departement: $enseignant->departement->nom */
    public function departement(): BelongsTo
    {
        return $this->belongsTo(Departement::class);
    }

    /** Replaces findByUser() and the JOIN on utilisateur: $enseignant->user */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /** Needs an enseignant_id column on the seances table. */
    public function seances(): HasMany
    {
        return $this->hasMany(Seance::class);
    }

    /**
     * Replaces modules($id): modules taught, derived from the sessions.
     * Needs enseignant_id and module_id columns on the seances table.
     */
    public function modules(): HasManyThrough
    {
        return $this->hasManyThrough(Module::class, Seance::class, 'enseignant_id', 'id', 'id', 'module_id')
            ->distinct()
            ->orderBy('modules.code');
    }

    /**
     * Replaces seances($id, $from, $to): sessions in a date range, with module and room.
     *   $enseignant->seancesBetween('2026-11-01 00:00:00', '2026-11-30 23:59:59')
     */
    public function seancesBetween(?string $from = null, ?string $to = null): Collection
    {
        return $this->seances()
            ->with(['module', 'salle'])
            ->when($from, fn (Builder $q) => $q->where('date_debut', '>=', $from))
            ->when($to, fn (Builder $q) => $q->where('date_fin', '<=', $to))
            ->orderBy('date_debut')
            ->get();
    }

    /* ------------------------------------------------------------------
     | Scopes
     * ------------------------------------------------------------------ */

    /**
     * Replaces findAll($q): Enseignant::search($q)->with(['departement', 'user'])
     *     ->orderBy('nom')->orderBy('prenom')->limit(100)->get()
     */
    public function scopeSearch(Builder $query, ?string $q): Builder
    {
        return $query->when($q !== null && $q !== '', function (Builder $qb) use ($q) {
            $like = '%' . addcslashes($q, '%_\\') . '%';
            $qb->where(fn (Builder $w) => $w
                ->where('nom', 'like', $like)
                ->orWhere('prenom', 'like', $like));
        });
    }

    /* ------------------------------------------------------------------
     | Replaces update($id, $data, $actif): teacher + account status in one transaction
     |   $enseignant->updateWithActif($data, $actif);   returns false on a DB error
     * ------------------------------------------------------------------ */
    public function updateWithActif(array $data, bool $actif): bool
    {
        try {
            DB::transaction(function () use ($data, $actif) {
                $this->update($data);
                $this->user->update(['actif' => $actif]);
            });
            return true;
        } catch (\Illuminate\Database\QueryException) {
            return false;
        }
    }

    /* ------------------------------------------------------------------
     | Replaces createWithUser($d): user + role + teacher in one transaction
     |   $enseignant = Enseignant::createWithUser($data);
     * ------------------------------------------------------------------ */
    public static function createWithUser(array $d): self
    {
        return DB::transaction(function () use ($d) {
            $user = User::create([
                'login'    => $d['login'],
                'email'    => $d['email'],
                'password' => $d['password'],
            ]);

            // Your old code looked for the role named 'prof' (the error message
            // said 'enseignant'). I kept 'prof': use whichever name is in your roles table.
            $user->roles()->attach(Role::where('nom', 'prof')->firstOrFail());

            return self::create([
                'departement_id' => $d['departement_id'],
                'user_id'        => $user->id,
                'nom'            => $d['nom'],
                'prenom'         => $d['prenom'],
                'grade'          => $d['grade'] ?? null,
            ]);
        });
    }
}
