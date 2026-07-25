<?php

namespace App\Services\Auth;

use App\Models\User;
use App\Repositories\UserLoginRepositoryInterface;
use App\Repositories\UserRepositoryInterface;
use App\Services\AuditActions;
use App\Services\AuditLoggerInterface;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class LoginService
{
    public function __construct(
        private UserRepositoryInterface $userRepo,
        private UserLoginRepositoryInterface $userLoginRepo,
        private AuditLoggerInterface $audit,
    ) {}

    public function attempt(string $email, string $password, string $ip): string
    {
        $user = $this->userRepo->findByEmail($email);

        if (! $user || ! Hash::check($password, $user->password ?? '')) {
            return 'invalid';
        }

        if ($user->is_suspended) {
            return 'suspended';
        }

        Auth::login($user, remember: true);
        $this->recordLogin($user, $ip, 'password');

        return 'ok';
    }

    public function recordLogin(User $user, string $ip, string $method): void
    {
        $this->userLoginRepo->log($user->id, $ip, $method);
        $this->audit->log(AuditActions::USER_LOGIN, 'user', (string) $user->id, "Logged in via $method: {$user->name}");
    }

    public function recordLogout(User $user): void
    {
        $this->audit->log(AuditActions::USER_LOGOUT, 'user', (string) $user->id, "Logged out: {$user->name}");
    }
}
