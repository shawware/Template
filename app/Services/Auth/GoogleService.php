<?php

namespace App\Services\Auth;

use App\Models\User;
use App\Repositories\RoleRepositoryInterface;
use App\Repositories\UserRepositoryInterface;
use Illuminate\Validation\ValidationException;

class GoogleService
{
    public function __construct(
        private UserRepositoryInterface $userRepo,
        private RoleRepositoryInterface $roleRepo,
    ) {}

    public function handleCallback(string $googleId, string $email, string $name, ?string $avatarUrl): User
    {
        $this->checkDomain($email);

        $user = $this->userRepo->findByGoogleId($googleId)
             ?? $this->userRepo->findByEmail($email);

        if ($user) {
            if ($user->is_suspended) {
                throw ValidationException::withMessages(['email' => 'Your account has been suspended. Please contact your system administrator.']);
            }

            $updates = [];
            if (! $user->google_id) {
                $updates['google_id'] = $googleId;
            }
            if (! $user->avatar_url && $avatarUrl) {
                $updates['avatar_url'] = $avatarUrl;
            }
            if ($updates) {
                $this->userRepo->update($user->id, $updates);
                $user = $this->userRepo->findById($user->id);
            }

            return $user;
        }

        $defaultRole = $this->roleRepo->findByName(config('auth.default_role'));

        return $this->userRepo->create([
            'name' => $name,
            'email' => $email,
            'password' => null,
            'google_id' => $googleId,
            'avatar_url' => $avatarUrl,
            'role_id' => $defaultRole?->id,
            'auto_registered' => true,
            'reviewed_at' => null,
        ]);
    }

    private function checkDomain(string $email): void
    {
        $allowedDomain = config('services.google.allowed_domain');

        if (! $allowedDomain) {
            return;
        }

        $emailDomain = substr(strrchr($email, '@'), 1);

        if ($emailDomain !== $allowedDomain) {
            throw ValidationException::withMessages(['email' => 'Your Google account is not authorised to access this application.']);
        }
    }
}
