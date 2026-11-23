<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Facades\DB;

class User extends Authenticatable
{
    // Session stores one role so we pick by this order
    private const ROLE_PRIORITY = ['admin', 'prof', 'etudiant'];

    protected $fillable = ['login', 'email', 'password', 'actif'];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            // 'hashed' hashes the password automatically when it is set,
            // so never call Hash::make() yourself (it would be hashed twice).
            'password' => 'hashed',
            'actif'    => 'boolean',
        ];
    }

    /* ------------------------------------------------------------------
     | Relations
     * ------------------------------------------------------------------ */

    /** Replaces roles($idUser): $user->roles->pluck('nom') gives ['prof'] */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class)->orderBy('roles.id');
    }

    public function etudiant(): HasOne
    {
        return $this->hasOne(Etudiant::class);
    }

    public function enseignant(): HasOne
    {
        return $this->hasOne(Enseignant::class);
    }

    public function administrateur(): HasOne
    {
        return $this->hasOne(Administrateur::class);
    }

    /** Replaces Notification::forUser() / countForUser(): $user->notifications()->paginate(15) */
    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class)->orderByDesc('date_creation')->orderByDesc('id');
    }

    /** Replaces Notification::countUnread($id) */
    public function unreadNotificationsCount(): int
    {
        return $this->notifications()->where('lu', false)->count();
    }

    /* ------------------------------------------------------------------
     | Roles
     * ------------------------------------------------------------------ */

    public function hasRole(string $role): bool
    {
        return $this->roles->contains('nom', $role);
    }

    /** Replaces primaryRole($roles): admin > prof > etudiant. Use: auth()->user()->primaryRole() */
    public function primaryRole(): ?string
    {
        $names = $this->roles->pluck('nom')->all();

        foreach (self::ROLE_PRIORITY as $role) {
            if (in_array($role, $names, true)) {
                return $role;
            }
        }
        return null;
    }

    /* ------------------------------------------------------------------
     | Scopes
     * ------------------------------------------------------------------ */

    /**
     * Replaces findAll($q): User::search($q)->with('roles')->orderBy('login')->limit(100)->get()
     * Roles as text in a view: $u->roles->pluck('nom')->join(', ')
     */
    public function scopeSearch(Builder $query, ?string $q): Builder
    {
        return $query->when($q !== null && $q !== '', function (Builder $qb) use ($q) {
            $like = '%' . addcslashes($q, '%_\\') . '%';
            $qb->where(fn (Builder $w) => $w
                ->where('login', 'like', $like)
                ->orWhere('email', 'like', $like));
        });
    }

    /* ------------------------------------------------------------------
     | Admin management
     * ------------------------------------------------------------------ */

    /**
     * Replaces create($login, $email, $password, $role): account + its single role.
     *   $user = User::createWithRole('jdupont', 'j@x.ma', 'secret', 'prof');
     */
    public static function createWithRole(string $login, string $email, string $password, string $role): self
    {
        return DB::transaction(function () use ($login, $email, $password, $role) {
            $user = self::create(['login' => $login, 'email' => $email, 'password' => $password]);

            // firstOrFail() aborts the transaction if the role doesn't exist
            $user->roles()->attach(Role::where('nom', $role)->firstOrFail());

            return $user;
        });
    }

    /**
     * Replaces update($id, $email, $actif, $role, $password): email, active flag,
     * role and (optionally) the password.
     *   $user->updateWithRole('new@x.ma', true, 'admin');
     */
    public function updateWithRole(string $email, bool $actif, string $role, ?string $password = null): void
    {
        DB::transaction(function () use ($email, $actif, $role, $password) {
            $data = ['email' => $email, 'actif' => $actif];

            if ($password !== null && $password !== '') {
                $data['password'] = $password;
            }

            $this->update($data);
            $this->roles()->sync([Role::where('nom', $role)->firstOrFail()->id]);
        });
    }
}
