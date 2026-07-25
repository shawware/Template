<?php

namespace App\Repositories;

use App\Models\UserLogin;

class EloquentUserLoginRepository implements UserLoginRepositoryInterface
{
    public function log(int $userId, string $ip, string $method): void
    {
        UserLogin::create([
            'user_id' => $userId,
            'logged_in_at' => now(),
            'ip_address' => $ip,
            'auth_method' => $method,
        ]);
    }
}
