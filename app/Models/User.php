<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Fortify\TwoFactorAuthenticatable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, TwoFactorAuthenticatable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'is_admin',
        'image_path',
        'locale',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'remember_token',
    ];

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
            'is_admin' => 'boolean',
        ];
    }

    public function isAdmin(): bool
    {
        return $this->is_admin;
    }

    public function cookbooks(): HasMany
    {
        return $this->hasMany(Cookbook::class)
            ->orderBy('position');
    }

    public function communityCookbooks(): HasMany
    {
        return $this->cookbooks()->where('community', true);
    }

    public function personalCookbooks(): HasMany
    {
        return $this->cookbooks()->where('community', false);
    }

    public function plannedRecipes(): BelongsToMany
    {
        return $this->belongsToMany(Recipe::class, 'planned_recipes')
            ->withPivot(['date', 'type', 'position'])
            ->orderByPivot('position');
    }

    public function commentNotifications(): HasMany
    {
        return $this->hasMany(CommentNotification::class)->latest();
    }

    public function unreadCommentNotifications(): HasMany
    {
        return $this->hasMany(CommentNotification::class)
            ->where('read', false)
            ->latest();
    }

    /**
     * Get the user's initials
     */
    public function initials(): string
    {
        return Str::of($this->name)
            ->explode(' ')
            ->take(2)
            ->map(fn ($word) => Str::substr($word, 0, 1))
            ->implode('');
    }

    public function hasImage(): bool
    {
        return !empty($this->image_path);
    }

    public function profileImageUrl(string $size = '128'): ?string
    {
        if (!$this->hasImage()) return null;

        return route('user-images.show', $this)
            . '?size=' . $size
            . '&v=' . $this->image_path;
    }
}
