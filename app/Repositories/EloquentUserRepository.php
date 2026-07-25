<?php

namespace App\Repositories;

use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;

class EloquentUserRepository implements UserRepositoryInterface
{
    public function paginate(int $perPage = 25): LengthAwarePaginator
    {
        return User::orderBy('name')->paginate($perPage);
    }

    public function findById(int $id): ?User
    {
        return User::find($id);
    }

    public function findByEmail(string $email): ?User
    {
        return User::where('email', $email)->first();
    }

    public function findByGoogleId(string $googleId): ?User
    {
        return User::where('google_id', $googleId)->first();
    }

    public function create(array $data): User
    {
        return User::create($data);
    }

    public function update(int $id, array $data): bool
    {
        return User::whereKey($id)->update($data) > 0;
    }

    public function delete(int $id): bool
    {
        return User::destroy($id) > 0;
    }

    public function unreviewedCount(): int
    {
        return User::where('auto_registered', true)->whereNull('reviewed_at')->count();
    }
}
