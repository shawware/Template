<?php

namespace App\Services;

use App\Models\User;
use App\Repositories\UserRepositoryInterface;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class ProfileService
{
    public function __construct(private UserRepositoryInterface $userRepo) {}

    /**
     * @param  array{name: string, email: string}  $data
     */
    public function updateProfile(User $user, array $data): void
    {
        $existing = $this->userRepo->findByEmail($data['email']);
        if ($existing && $existing->id !== $user->id) {
            throw ValidationException::withMessages(['email' => 'That email address is already in use.']);
        }

        $this->userRepo->update($user->id, $data);
    }

    public function updatePassword(User $user, string $currentPassword, string $newPassword): void
    {
        if ($user->hasPassword() && ! Hash::check($currentPassword, $user->password)) {
            throw ValidationException::withMessages(['current_password' => 'Your current password is incorrect.']);
        }

        $this->userRepo->update($user->id, ['password' => Hash::make($newPassword)]);
    }
}
