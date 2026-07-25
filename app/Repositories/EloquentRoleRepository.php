<?php

namespace App\Repositories;

use App\Models\Role;
use App\Models\RolePermission;
use Illuminate\Support\Collection;

class EloquentRoleRepository implements RoleRepositoryInterface
{
    public function findById(int $id): ?Role
    {
        return Role::find($id);
    }

    public function findByName(string $name): ?Role
    {
        return Role::where('name', $name)->first();
    }

    public function findSuperuserRole(): ?Role
    {
        return Role::where('is_superuser', true)->first();
    }

    public function all(): Collection
    {
        return Role::orderBy('display_name')->get();
    }

    public function create(array $data): Role
    {
        return Role::create($data);
    }

    public function update(int $id, array $data): bool
    {
        return Role::whereKey($id)->update($data) > 0;
    }

    public function delete(int $id): bool
    {
        return Role::destroy($id) > 0;
    }

    public function nameExistsExcept(string $name, ?int $exceptId = null): bool
    {
        return Role::where('name', $name)
            ->when($exceptId, fn ($query) => $query->whereKeyNot($exceptId))
            ->exists();
    }

    public function syncPermissions(int $roleId, array $permissions): void
    {
        RolePermission::where('role_id', $roleId)->delete();
        foreach ($permissions as $permission) {
            RolePermission::create(['role_id' => $roleId, 'permission' => $permission]);
        }
    }
}
