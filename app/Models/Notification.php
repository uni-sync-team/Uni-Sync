<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;

class Notification extends Model
{
    protected $fillable = ['user_id', 'message', 'date_creation', 'lu'];

    protected $casts = [
        'date_creation' => 'datetime',
        'lu'            => 'boolean',
    ];

    /** $notification->user->login */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /* ------------------------------------------------------------------
     | Admin screen
     * ------------------------------------------------------------------ */

    /**
     * Replaces search() + count(): newest first. Filters: q (login or message), statut ('lu' | 'non_lu').
     *   Notification::filtered($request->only(['q', 'statut']))->paginate(15)
     * (findById($id) is now Notification::with('user')->find($id))
     */
    public function scopeFiltered(Builder $query, array $f): Builder
    {
        return $query
            ->with('user')
            ->when(($f['q'] ?? '') !== '', function (Builder $qb) use ($f) {
                $like = '%' . addcslashes($f['q'], '%_\\') . '%';
                $qb->where(fn (Builder $w) => $w
                    ->where('message', 'like', $like)
                    ->orWhereHas('user', fn (Builder $u) => $u->where('login', 'like', $like)));
            })
            ->when(($f['statut'] ?? '') === 'lu', fn (Builder $qb) => $qb->where('lu', true))
            ->when(($f['statut'] ?? '') === 'non_lu', fn (Builder $qb) => $qb->where('lu', false))
            ->orderByDesc('date_creation')
            ->orderByDesc('id');
    }

    /** Replaces sendToUser(): returns the number of notifications created (1). */
    public static function sendToUser(int $userId, string $message): int
    {
        self::create(['user_id' => $userId, 'message' => $message]);

        return 1;
    }

    /** Replaces sendToRole(): one notification per active user holding the role; returns how many. */
    public static function sendToRole(string $role, string $message): int
    {
        $ids = User::where('actif', true)
            ->whereHas('roles', fn (Builder $q) => $q->where('nom', $role))
            ->pluck('id');

        return self::broadcast($ids, $message);
    }

    /** Replaces sendToAll(): one notification per active user; returns how many. */
    public static function sendToAll(string $message): int
    {
        return self::broadcast(User::where('actif', true)->pluck('id'), $message);
    }

    private static function broadcast(Collection $userIds, string $message): int
    {
        $now  = now();
        $rows = $userIds->map(fn ($id) => [
            'user_id'       => $id,
            'message'       => $message,
            'date_creation' => $now,
            'lu'            => false,
            'created_at'    => $now,
            'updated_at'    => $now,
        ])->all();

        // one INSERT per chunk instead of one per user
        foreach (array_chunk($rows, 500) as $chunk) {
            self::insert($chunk);
        }

        return count($rows);
    }

    /* ------------------------------------------------------------------
     | User inbox (student / teacher side)
     |   forUser($id, $limit, $offset)  ->  $user->notifications()->paginate(15)
     |   countForUser($id)              ->  $user->notifications()->count()
     |   countUnread($id)               ->  $user->unreadNotificationsCount()
     |   delete($id)                    ->  Notification::destroy($id)
     * ------------------------------------------------------------------ */

    /** Replaces markRead(): marks one notification as read, only if it belongs to the user. */
    public static function markRead(int $id, int $userId): bool
    {
        return self::where('id', $id)
            ->where('user_id', $userId)
            ->where('lu', false)
            ->update(['lu' => true]) > 0;
    }

    /** Replaces markAllRead(): returns how many were marked. */
    public static function markAllRead(int $userId): int
    {
        return self::where('user_id', $userId)->where('lu', false)->update(['lu' => true]);
    }
}
