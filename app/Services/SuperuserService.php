<?php

namespace App\Services;

use App\Models\User;
use App\Repositories\RoleRepositoryInterface;
use App\Repositories\UserRepositoryInterface;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class SuperuserService
{
    public function __construct(
        private UserRepositoryInterface $userRepo,
        private RoleRepositoryInterface $roleRepo,
    ) {}

    public function create(string $name, string $email, string $password): User
    {
        if ($this->userRepo->findByEmail($email)) {
            throw ValidationException::withMessages(['email' => "A user with email {$email} already exists."]);
        }

        $role = $this->roleRepo->findSuperuserRole()
            ?? $this->roleRepo->create(['name' => 'superuser', 'display_name' => 'Superuser', 'is_superuser' => true]);

        return $this->userRepo->create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($password),
            'role_id' => $role->id,
            'auto_registered' => false,
            'reviewed_at' => now(),
        ]);
    }
}
