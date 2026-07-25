<?php

namespace Tests\Unit\Models;

use App\Models\Role;
use App\Models\RolePermission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserPermissionTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_with_no_role_has_no_permissions(): void
    {
        $user = User::factory()->create(['role_id' => null]);

        $this->assertFalse($user->hasPermission('admin.users'));
    }

    public function test_superuser_role_has_every_permission(): void
    {
        $role = Role::factory()->superuser()->create();
        $user = User::factory()->create(['role_id' => $role->id]);

        $this->assertTrue($user->hasPermission('admin.users'));
        $this->assertTrue($user->hasPermission('anything.at.all'));
    }

    public function test_user_has_only_granted_permissions(): void
    {
        $role = Role::factory()->create();
        RolePermission::create(['role_id' => $role->id, 'permission' => 'admin.users']);
        $user = User::factory()->create(['role_id' => $role->id]);

        $this->assertTrue($user->hasPermission('admin.users'));
        $this->assertFalse($user->hasPermission('admin.roles'));
    }

    public function test_is_superuser_reflects_role(): void
    {
        $superuserRole = Role::factory()->superuser()->create();
        $normalRole = Role::factory()->create();

        $superuser = User::factory()->create(['role_id' => $superuserRole->id]);
        $normalUser = User::factory()->create(['role_id' => $normalRole->id]);
        $noRoleUser = User::factory()->create(['role_id' => null]);

        $this->assertTrue($superuser->isSuperuser());
        $this->assertFalse($normalUser->isSuperuser());
        $this->assertFalse($noRoleUser->isSuperuser());
    }
}
