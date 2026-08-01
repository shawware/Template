@extends('layouts.app')

@section('title', 'Audit log')

@section('content')
<div class="bg-surface border border-edge rounded-lg shadow-sm overflow-hidden">
    <div class="bg-muted px-4 py-3 border-b border-edge">
        <h1 class="text-heading text-xl font-semibold">Audit log</h1>
    </div>
    <div class="overflow-x-auto">
    <table class="w-full text-sm">
        <thead class="bg-muted text-label text-left">
            <tr>
                <th class="px-4 py-2">When</th>
                <th class="px-4 py-2">User</th>
                <th class="px-4 py-2">Action</th>
                <th class="px-4 py-2">Description</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-divider">
            @foreach ($entries as $entry)
                <tr>
                    <td class="px-4 py-2 text-secondary whitespace-nowrap">{{ $entry->created_at->format('Y-m-d H:i') }}</td>
                    <td class="px-4 py-2 text-body">{{ $entry->user?->name ?? '—' }}</td>
                    <td class="px-4 py-2 text-secondary">{{ $entry->action }}</td>
                    <td class="px-4 py-2 text-secondary">{{ $entry->description }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
    </div>
    <div class="px-4 py-3 border-t border-edge">
        {{ $entries->links() }}
    </div>
</div>
@endsection
