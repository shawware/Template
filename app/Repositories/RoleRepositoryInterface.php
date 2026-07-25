<?php

namespace App\Repositories;

use App\Models\Role;
use Illuminate\Support\Collection;

interface RoleRepositoryInterface
{
    public function findById(int $id): ?Role;

    public function findByName(string $name): ?Role;

    public function findSuperuserRole(): ?Role;

    /**
     * @return Collection<int, Role>
     */
    public function all(): Collection;

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Role;

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(int $id, array $data): bool;

    public function delete(int $id): bool;

    public function nameExistsExcept(string $name, ?int $exceptId = null): bool;

    public function syncPermissions(int $roleId, array $permissions): void;
}
