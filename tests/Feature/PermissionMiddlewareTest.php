<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\RolePermission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PermissionMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/admin/users')->assertRedirect('/user/login');
    }

    public function test_user_without_permission_is_forbidden(): void
    {
        $role = Role::factory()->create();
        $user = User::factory()->create(['role_id' => $role->id]);

        $this->actingAs($user)
            ->get('/admin/users')
            ->assertForbidden();
    }

    public function test_user_with_permission_is_allowed(): void
    {
        $role = Role::factory()->create();
        RolePermission::create(['role_id' => $role->id, 'permission' => 'admin.users']);
        $user = User::factory()->create(['role_id' => $role->id]);

        $this->actingAs($user)
            ->get('/admin/users')
            ->assertOk();
    }

    public function test_superuser_bypasses_permission_check(): void
    {
        $role = Role::factory()->superuser()->create();
        $user = User::factory()->create(['role_id' => $role->id]);

        $this->actingAs($user)
            ->get('/admin/users')
            ->assertOk();
    }

    public function test_user_with_no_role_is_forbidden(): void
    {
        $user = User::factory()->create(['role_id' => null]);

        $this->actingAs($user)
            ->get('/admin/users')
            ->assertForbidden();
    }

    public function test_auth_disabled_bypasses_both_auth_and_permission_checks(): void
    {
        config(['app.auth_enabled' => false]);

        $this->get('/admin/users')->assertOk();
    }
}
