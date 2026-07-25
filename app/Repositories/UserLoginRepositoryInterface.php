<?php

namespace App\Repositories;

interface UserLoginRepositoryInterface
{
    public function log(int $userId, string $ip, string $method): void;
}
