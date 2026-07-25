@extends('layouts.app')

@section('title', 'Roles')

@section('content')
<div class="space-y-6">
    <div class="bg-surface border border-edge rounded-lg shadow-sm overflow-hidden">
        <div class="bg-muted px-4 py-3 border-b border-edge">
            <h1 class="text-heading font-semibold">Roles</h1>
        </div>
        <table class="w-full text-sm">
            <thead class="bg-muted text-label text-left">
                <tr>
                    <th class="px-4 py-2">Role</th>
                    <th class="px-4 py-2">Permissions</th>
                    <th class="px-4 py-2"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-divider">
                @foreach ($roles as $role)
                    <tr>
                        <td class="px-4 py-2 text-body">
                            {{ $role->display_name }}
                            @if ($role->is_superuser)
                                <span class="bg-badge text-badge-fg text-xs px-2 py-0.5 rounded ml-1">all permissions</span>
                            @endif
                        </td>
                        <td class="px-4 py-2 text-secondary">
                            {{ $role->is_superuser ? '—' : ($role->rolePermissions->pluck('permission')->implode(', ') ?: 'none') }}
                        </td>
                        <td class="px-4 py-2 text-right">
                            @unless ($role->is_superuser)
                                <form method="POST" action="{{ url('/admin/roles/'.$role->id) }}" class="inline"
                                      onsubmit="return confirm('Delete this role?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:text-red-800">Delete</button>
                                </form>
                            @endunless
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="bg-surface border border-edge rounded-lg shadow-sm p-6">
        <h2 class="text-heading font-semibold mb-4">New role</h2>
        <form method="POST" action="{{ url('/admin/roles') }}" class="space-y-4">
            @csrf
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label for="name" class="block text-label text-sm mb-1">Key (lowercase, no spaces)</label>
                    <input id="name" name="name" type="text" required pattern="[a-z0-9_]+"
                           class="w-full border border-edge rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-focus-ring">
                </div>
                <div>
                    <label for="display_name" class="block text-label text-sm mb-1">Display name</label>
                    <input id="display_name" name="display_name" type="text" required
                           class="w-full border border-edge rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-focus-ring">
                </div>
            </div>
            <div>
                <span class="block text-label text-sm mb-2">Permissions</span>
                @foreach ($permissions as $group => $groupPermissions)
                    <div class="mb-3">
                        <p class="text-subtle text-xs uppercase tracking-wide mb-1">{{ $group }}</p>
                        <div class="flex flex-wrap gap-x-4 gap-y-1">
                            @foreach ($groupPermissions as $key => $label)
                                <label class="flex items-center gap-1.5 text-sm text-body">
                                    <input type="checkbox" name="permissions[]" value="{{ $key }}"
                                           class="rounded border-edge focus:ring-focus-ring">
                                    {{ $label }}
                                </label>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
            <button type="submit" class="bg-btn hover:bg-btn-hover text-white rounded px-4 py-2">Create role</button>
        </form>
    </div>
</div>
@endsection
