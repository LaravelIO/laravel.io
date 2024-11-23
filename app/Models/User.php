<?php

namespace App\Models;

use App\Concerns\HasTimestamps;
use App\Concerns\PreparesSearch;
use App\Enums\NotificationType;
use App\Policies\UserPolicy;
use Carbon\Carbon;
use Filament\Facades\Filament;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Auth;
use Laravel\Sanctum\HasApiTokens;
use Laravel\Scout\Searchable;

final class User extends Authenticatable implements MustVerifyEmail, FilamentUser
{
    use HasApiTokens;
    use HasFactory;
    use HasTimestamps;
    use Notifiable;
    use PreparesSearch;
    use Searchable;

    const TABLE = 'users';

    const DEFAULT = 1;

    const MODERATOR = 2;

    const ADMIN = 3;

    /**
     * {@inheritdoc}
     */
    protected $table = self::TABLE;

    /**
     * {@inheritdoc}
     */
    protected $fillable = [
        'name',
        'email',
        'twitter',
        'website',
        'username',
        'password',
        'ip',
        'github_id',
        'github_username',
        'type',
        'remember_token',
        'bio',
        'banned_reason',
    ];

    /**
     * {@inheritdoc}
     */
    protected $hidden = ['password', 'remember_token'];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'allowed_notifications' => 'array',
        ];
    }

    public function id(): int
    {
        return $this->id;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function emailAddress(): string
    {
        return $this->email;
    }

    public function username(): string
    {
        return $this->username;
    }

    public function bio(): string
    {
        return $this->bio;
    }

    public function githubUsername(): string
    {
        return $this->github_username ?? '';
    }

    public function twitter(): ?string
    {
        return $this->twitter;
    }

    public function website(): ?string
    {
        return $this->website;
    }

    public function hasTwitterAccount(): bool
    {
        return ! empty($this->twitter());
    }

    public function hasWebsite(): bool
    {
        return ! empty($this->website());
    }

    public function isBanned(): bool
    {
        return ! is_null($this->banned_at);
    }

    public function bannedReason(): ?string
    {
        return $this->banned_reason;
    }

    public function type(): int
    {
        return (int) $this->type;
    }

    public function isModerator(): bool
    {
        return $this->type() === self::MODERATOR;
    }

    public function isAdmin(): bool
    {
        return $this->type() === self::ADMIN;
    }

    public function isLoggedInUser(): bool
    {
        return $this->id() === Auth::id();
    }

    public function hasPassword(): bool
    {
        $password = $this->getAuthPassword();

        return $password !== '' && $password !== null;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function threads()
    {
        return $this->threadsRelation;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function latestThreads(int $amount = 5)
    {
        return $this->threadsRelation()->latest()->limit($amount)->get();
    }

    public function deleteThreads()
    {
        // We need to explicitly iterate over the threads and delete them
        // separately because all related models need to be deleted.
        foreach ($this->threads() as $thread) {
            $thread->delete();
        }
    }

    public function threadsRelation(): HasMany
    {
        return $this->hasMany(Thread::class, 'author_id');
    }

    public function countThreads(): int
    {
        return $this->threadsRelation()->count();
    }

    public function countThreadsFromToday(): int
    {
        $today = Carbon::today();

        return $this->threadsRelation()
            ->whereBetween('created_at', [$today, $today->copy()->endOfDay()])
            ->count();
    }

    public function hasTooManyThreadsToday(): bool
    {
        return $this->countThreadsFromToday() >= 5;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function replies()
    {
        return $this->replyAble;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function latestReplies(int $amount = 10)
    {
        return $this->replyAble()->latest()->limit($amount)->get();
    }

    public function deleteReplies()
    {
        // We need to explicitly iterate over the replies and delete them
        // separately because all related models need to be deleted.
        foreach ($this->replyAble()->get() as $reply) {
            $reply->delete();
        }
    }

    public function countReplies(): int
    {
        return $this->replyAble()->count();
    }

    public function replyAble(): HasMany
    {
        return $this->hasMany(Reply::class, 'author_id');
    }

    public function blockedUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'blocked_users', 'user_id', 'blocked_user_id');
    }

    public function articles(): HasMany
    {
        return $this->hasMany(Article::class, 'author_id');
    }

    public function latestArticles(int $amount = 10)
    {
        return $this->articles()->approved()->latest()->limit($amount)->get();
    }

    public function countArticles(): int
    {
        return $this->articles()->approved()->count();
    }

    public static function findByUsername(string $username): self
    {
        return self::where('username', $username)->firstOrFail();
    }

    public static function findByEmailAddress(string $emailAddress): self
    {
        return self::where('email', $emailAddress)->firstOrFail();
    }

    public static function findByGitHubId(string $githubId): self
    {
        return self::where('github_id', $githubId)->firstOrFail();
    }

    public function delete()
    {
        $this->deleteThreads();
        $this->deleteReplies();

        parent::delete();
    }

    public function countSolutions(): int
    {
        return $this->replyAble()->isSolution()->count();
    }

    public function scopeMostSolutions(Builder $query, ?int $inLastDays = null)
    {
        return $query->withCount(['replyAble as solutions_count' => function ($query) use ($inLastDays) {
            $query->where('replyable_type', 'threads')
                ->join('threads', function ($join) {
                    $join->on('threads.solution_reply_id', '=', 'replies.id')
                        ->on('threads.author_id', '!=', 'replies.author_id');
                });

            if ($inLastDays) {
                $query->where('replies.created_at', '>', now()->subDays($inLastDays));
            }

            return $query;
        }])->orderBy('solutions_count', 'desc');
    }

    public function scopeMostSubmissions(Builder $query, ?int $inLastDays = null)
    {
        return $query->withCount(['articles as articles_count' => function ($query) use ($inLastDays) {
            if ($inLastDays) {
                $query->where('articles.approved_at', '>', now()->subDays($inLastDays));
            }

            return $query;
        }])->orderBy('articles_count', 'desc');
    }

    public function scopeMostSolutionsInLastDays(Builder $query, int $days)
    {
        return $query->mostSolutions($days);
    }

    public function scopeMostSubmissionsInLastDays(Builder $query, int $days)
    {
        return $query->mostSubmissions($days);
    }

    public function shouldBeSearchable()
    {
        return true;
    }

    public function toSearchableArray(): array
    {
        return [
            'id' => $this->id(),
            'name' => $this->name(),
            'username' => $this->username(),
        ];
    }

    public function scopeWithCounts(Builder $query)
    {
        return $query->withCount([
            'threadsRelation as threads_count',
            'replyAble as replies_count',
            'replyAble as solutions_count' => function (Builder $query) {
                return $query->join('threads', 'threads.solution_reply_id', '=', 'replies.id')
                    ->where('replyable_type', 'threads');
            },
        ]);
    }

    public function scopeHasActivity(Builder $query)
    {
        return $query->where(function ($query) {
            $query->has('threadsRelation')
                ->orHas('replyAble');
        });
    }

    public function scopeModerators(Builder $query)
    {
        return $query->whereIn('type', [
            self::ADMIN,
            self::MODERATOR,
        ]);
    }

    public function scopeNotBanned(Builder $query)
    {
        return $query->whereNull('banned_at');
    }

    public function hasBlocked(User $user): bool
    {
        return $this->blockedUsers()->where('blocked_user_id', $user->getKey())->exists();
    }

    public function hasUnblocked(User $user): bool
    {
        return ! $this->hasBlocked($user);
    }

    public function scopeWithUsersWhoDoesntBlock(Builder $query, User $user)
    {
        return $query->whereDoesntHave('blockedUsers', function ($query) use ($user) {
            $query->where('blocked_user_id', $user->getKey());
        });
    }

    public function scopeWithUsersWhoArentBlockedBy(Builder $query, User $user)
    {
        return $query->whereDoesntHave('blockedUsers', function ($query) use ($user) {
            $query->where('user_id', $user->getKey());
        });
    }

    public function isNotificationAllowed(string $notification): bool
    {
        return collect($this->allowed_notifications ?? [])
            ->contains(function ($notificationType) use ($notification) {
                return NotificationType::from($notificationType)->getClass() === $notification;
            });
    }

    public function canAccessPanel(Panel $panel): bool
    {
        $guard = Filament::getAuthGuard();

        return Auth::guard($guard)->user()->can(UserPolicy::ADMIN, User::class);
    }
}
