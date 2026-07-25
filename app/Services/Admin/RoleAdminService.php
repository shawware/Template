<?php

namespace App\Services\Admin;

use App\Models\Role;
use App\Repositories\RoleRepositoryInterface;
use App\Services\AuditActions;
use App\Services\AuditLoggerInterface;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class RoleAdminService
{
    public function __construct(
        private RoleRepositoryInterface $roleRepo,
        private AuditLoggerInterface $audit,
    ) {}

    /**
     * @return Collection<int, Role>
     */
    public function list(): Collection
    {
        return $this->roleRepo->all();
    }

    /**
     * @param  string[]  $permissions
     */
    public function create(string $name, string $displayName, array $permissions): Role
    {
        if ($this->roleRepo->nameExistsExcept($name)) {
            throw ValidationException::withMessages(['name' => 'A role with that name already exists.']);
        }

        $role = $this->roleRepo->create([
            'name' => $name,
            'display_name' => $displayName,
            'is_superuser' => false,
        ]);

        $this->roleRepo->syncPermissions($role->id, $permissions);
        $this->audit->log(AuditActions::ROLE_CREATED, 'role', (string) $role->id, "Created role: {$displayName}");

        return $role;
    }

    /**
     * @param  string[]  $permissions
     */
    public function update(int $roleId, string $displayName, array $permissions): void
    {
        $role = $this->roleRepo->findById($roleId);
        if (! $role) {
            throw ValidationException::withMessages(['role' => 'Role not found.']);
        }
        if ($role->is_superuser) {
            throw ValidationException::withMessages(['role' => 'The superuser role cannot be edited.']);
        }

        $this->roleRepo->update($roleId, ['display_name' => $displayName]);
        $this->roleRepo->syncPermissions($roleId, $permissions);
        $this->audit->log(AuditActions::ROLE_UPDATED, 'role', (string) $roleId, "Updated role: {$displayName}");
    }

    public function delete(int $roleId): void
    {
        $role = $this->roleRepo->findById($roleId);
        if (! $role) {
            throw ValidationException::withMessages(['role' => 'Role not found.']);
        }
        if ($role->is_superuser) {
            throw ValidationException::withMessages(['role' => 'The superuser role cannot be deleted.']);
        }

        $this->roleRepo->delete($roleId);
        $this->audit->log(AuditActions::ROLE_DELETED, 'role', (string) $roleId, "Deleted role: {$role->display_name}");
    }
}
