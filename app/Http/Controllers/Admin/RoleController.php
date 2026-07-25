<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Admin\RoleAdminService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RoleController extends Controller
{
    public function index(RoleAdminService $service): View
    {
        return view('admin.roles.index', [
            'roles' => $service->list(),
            'permissions' => config('permissions'),
        ]);
    }

    public function store(Request $request, RoleAdminService $service): RedirectResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:50|regex:/^[a-z0-9_]+$/',
            'display_name' => 'required|string|max:100',
            'permissions' => 'array',
            'permissions.*' => 'string',
        ]);

        $service->create($data['name'], $data['display_name'], $data['permissions'] ?? []);

        return back()->with('success', 'Role created.');
    }

    public function update(Request $request, int $role, RoleAdminService $service): RedirectResponse
    {
        $data = $request->validate([
            'display_name' => 'required|string|max:100',
            'permissions' => 'array',
            'permissions.*' => 'string',
        ]);

        $service->update($role, $data['display_name'], $data['permissions'] ?? []);

        return back()->with('success', 'Role updated.');
    }

    public function destroy(int $role, RoleAdminService $service): RedirectResponse
    {
        $service->delete($role);

        return back()->with('success', 'Role deleted.');
    }
}
