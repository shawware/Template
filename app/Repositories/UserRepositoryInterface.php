<?php

namespace App\Repositories;

use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;

interface UserRepositoryInterface
{
    public function paginate(int $perPage = 25): LengthAwarePaginator;

    public function findById(int $id): ?User;

    public function findByEmail(string $email): ?User;

    public function findByGoogleId(string $googleId): ?User;

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): User;

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(int $id, array $data): bool;

    public function delete(int $id): bool;

    public function unreviewedCount(): int;
}
