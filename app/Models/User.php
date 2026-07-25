<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable([
    'name',
    'email',
    'password',
    'role_id',
    'google_id',
    'avatar_url',
    'is_suspended',
    'auto_registered',
    'reviewed_at',
])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

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
            'reviewed_at' => 'datetime',
            'is_suspended' => 'boolean',
            'auto_registered' => 'boolean',
        ];
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function hasPermission(string $permission): bool
    {
        if (! $this->relationLoaded('role')) {
            $this->load('role.rolePermissions');
        }
        if (! $this->role) {
            return false;
        }
        if ($this->role->is_superuser) {
            return true;
        }

        return $this->role->rolePermissions->contains('permission', $permission);
    }

    public function isSuperuser(): bool
    {
        return $this->role?->is_superuser ?? false;
    }

    public function initials(): string
    {
        return collect(explode(' ', $this->name))
            ->map(fn ($word) => strtoupper(substr($word, 0, 1)))
            ->take(2)
            ->implode('');
    }

    public function hasPassword(): bool
    {
        return $this->password !== null;
    }
}
