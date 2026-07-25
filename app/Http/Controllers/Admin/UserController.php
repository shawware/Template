<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Admin\RoleAdminService;
use App\Services\Admin\UserAdminService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(UserAdminService $service, RoleAdminService $roleService): View
    {
        return view('admin.users.index', [
            'users' => $service->list(),
            'roles' => $roleService->list(),
        ]);
    }

    public function update(Request $request, int $user, UserAdminService $service): RedirectResponse
    {
        $data = $request->validate([
            'role_id' => 'nullable|integer',
            'is_suspended' => 'sometimes|boolean',
        ]);

        $service->update($user, $data);

        return back()->with('success', 'User updated.');
    }

    public function review(int $user, UserAdminService $service): RedirectResponse
    {
        $service->review($user);

        return back()->with('success', 'Marked as reviewed.');
    }

    public function destroy(int $user, UserAdminService $service): RedirectResponse
    {
        $service->delete($user);

        return back()->with('success', 'User deleted.');
    }
}
