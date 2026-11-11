<?php

namespace App\Models;

use App\Enums\UserRole;
use App\Jobs\DeleteStoragePaths;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Laravel\Fortify\Contracts\PasskeyUser;
use Laravel\Fortify\PasskeyAuthenticatable;
use Laravel\Fortify\TwoFactorAuthenticatable;

/**
 * @property bool $notify_by_email Also e-mail replies, besides the bell.
 * @property int $id
 * @property string $name
 * @property string $email
 * @property string|null $avatar_path
 * @property-read string|null $avatar Signed URL of the profile photo.
 * @property Carbon|null $email_verified_at
 * @property string $password
 * @property UserRole $role
 * @property Carbon|null $blocked_at
 * @property string|null $two_factor_secret
 * @property string|null $two_factor_recovery_codes
 * @property Carbon|null $two_factor_confirmed_at
 * @property string|null $remember_token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection<int, LessonProgress> $lessonProgress
 */
#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token', 'blocked_at', 'avatar_path'])]
class User extends Authenticatable implements MustVerifyEmail, PasskeyUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, PasskeyAuthenticatable, TwoFactorAuthenticatable;

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'notify_by_email' => true,
    ];

    /**
     * @var list<string>
     */
    protected $appends = ['avatar'];

    /**
     * Remove the profile photo from storage when the account is deleted.
     */
    protected static function booted(): void
    {
        static::deleted(function (User $user) {
            if ($user->avatar_path !== null) {
                DeleteStoragePaths::dispatch([$user->avatar_path]);
            }
        });
    }

    /**
     * The profile photo as a short-lived signed URL, used wherever the user is shown.
     *
     * @return Attribute<string|null, never>
     */
    protected function avatar(): Attribute
    {
        return Attribute::get(fn () => $this->avatar_path !== null
            ? Storage::temporaryUrl($this->avatar_path, now()->addHours(2))
            : null);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'two_factor_confirmed_at' => 'datetime',
            'role' => UserRole::class,
            'blocked_at' => 'datetime',
            'notify_by_email' => 'boolean',
        ];
    }

    /**
     * @return HasMany<LessonProgress, $this>
     */
    public function lessonProgress(): HasMany
    {
        return $this->hasMany(LessonProgress::class);
    }

    /**
     * Determine whether the user is an administrator.
     */
    public function isAdmin(): bool
    {
        return $this->role === UserRole::Admin;
    }

    /**
     * Determine whether the user has been blocked by an administrator.
     */
    public function isBlocked(): bool
    {
        return $this->blocked_at !== null;
    }
}
