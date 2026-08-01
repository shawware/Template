@extends('layouts.app')

@section('title', 'Users')

@section('content')
<div class="bg-surface border border-edge rounded-lg shadow-sm overflow-hidden">
    <div class="bg-muted px-4 py-3 border-b border-edge">
        <h1 class="text-heading text-xl font-semibold">Users</h1>
    </div>
    <div class="overflow-x-auto">
    <table class="w-full text-sm">
        <thead class="bg-muted text-label text-left">
            <tr>
                <th class="px-4 py-2">Name</th>
                <th class="px-4 py-2">Email</th>
                <th class="px-4 py-2">Role</th>
                <th class="px-4 py-2">Status</th>
                <th class="px-4 py-2"></th>
            </tr>
        </thead>
        <tbody class="divide-y divide-divider">
            @foreach ($users as $user)
                <tr>
                    <td class="px-4 py-2 text-body">
                        {{ $user->name }}
                        @if ($user->auto_registered && ! $user->reviewed_at)
                            <span class="bg-badge text-badge-fg text-xs px-2 py-0.5 rounded ml-1">needs review</span>
                        @endif
                    </td>
                    <td class="px-4 py-2 text-secondary">{{ $user->email }}</td>
                    <td class="px-4 py-2">
                        <form method="POST" action="{{ url('/admin/users/'.$user->id) }}">
                            @csrf
                            @method('PATCH')
                            <select name="role_id" onchange="this.form.submit()"
                                    class="border border-edge rounded px-2 py-1 text-sm">
                                <option value="">— none —</option>
                                @foreach ($roles as $role)
                                    <option value="{{ $role->id }}" @selected($user->role_id === $role->id)>{{ $role->display_name }}</option>
                                @endforeach
                            </select>
                        </form>
                    </td>
                    <td class="px-4 py-2">
                        @if ($user->is_suspended)
                            <span class="text-red-700 bg-red-50 text-xs px-2 py-0.5 rounded">Suspended</span>
                        @else
                            <span class="text-green-700 bg-green-50 text-xs px-2 py-0.5 rounded">Active</span>
                        @endif
                    </td>
                    <td class="px-4 py-2 text-right whitespace-nowrap">
                        @if ($user->auto_registered && ! $user->reviewed_at)
                            <form method="POST" action="{{ url('/admin/users/'.$user->id.'/review') }}" class="inline">
                                @csrf
                                <button type="submit" class="text-action hover:text-action-hover">Mark reviewed</button>
                            </form>
                        @endif
                        <form method="POST" action="{{ url('/admin/users/'.$user->id) }}" class="inline">
                            @csrf
                            @method('PATCH')
                            <input type="hidden" name="is_suspended" value="{{ $user->is_suspended ? 0 : 1 }}">
                            <button type="submit" class="text-action hover:text-action-hover ml-2">
                                {{ $user->is_suspended ? 'Unsuspend' : 'Suspend' }}
                            </button>
                        </form>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
    </div>
    <div class="px-4 py-3 border-t border-edge">
        {{ $users->links() }}
    </div>
</div>
@endsection
