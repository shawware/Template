<!DOCTYPE html>
<html lang="en" class="theme-default">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name') }} — @yield('title', 'Home')</title>

    {{-- CDN-only Tailwind + Alpine — no build step. See docs/claude/ui.md. --}}
    <script src="https://cdn.tailwindcss.com"></script>
    {{-- Generate an SRI hash at https://www.srihash.org before deploying and add integrity="..." here. --}}
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <style>
        :root {
            --color-bg-page: #f9fafb;
            --color-bg-surface: #ffffff;
            --color-bg-muted: #f3f4f6;
            --color-bg-nav: #1f2937;
            --color-bg-nav-hover: #374151;
            --color-text-heading: #1f2937;
            --color-text-body: #374151;
            --color-text-secondary: #4b5563;
            --color-text-label: #6b7280;
            --color-text-subtle: #9ca3af;
            --color-text-inverse: #d1d5db;
            --color-border-edge: #e5e7eb;
            --color-border-divider: #f3f4f6;
            --color-border-separator: #d1d5db;
            --color-text-action: #4f46e5;
            --color-text-action-hover: #3730a3;
            --color-bg-btn: #4f46e5;
            --color-bg-btn-hover: #4338ca;
            --color-focus-ring: #818cf8;
            --color-bg-highlight: #eef2ff;
            --color-border-highlight: #e0e7ff;
            --color-bg-badge: #e0e7ff;
            --color-text-badge-fg: #3730a3;
        }
    </style>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        page: 'var(--color-bg-page)',
                        surface: 'var(--color-bg-surface)',
                        muted: 'var(--color-bg-muted)',
                        nav: 'var(--color-bg-nav)',
                        'nav-hover': 'var(--color-bg-nav-hover)',
                        heading: 'var(--color-text-heading)',
                        body: 'var(--color-text-body)',
                        secondary: 'var(--color-text-secondary)',
                        label: 'var(--color-text-label)',
                        subtle: 'var(--color-text-subtle)',
                        inverse: 'var(--color-text-inverse)',
                        edge: 'var(--color-border-edge)',
                        divider: 'var(--color-border-divider)',
                        separator: 'var(--color-border-separator)',
                        action: 'var(--color-text-action)',
                        'action-hover': 'var(--color-text-action-hover)',
                        btn: 'var(--color-bg-btn)',
                        'btn-hover': 'var(--color-bg-btn-hover)',
                        'focus-ring': 'var(--color-focus-ring)',
                        highlight: 'var(--color-bg-highlight)',
                        'highlight-border': 'var(--color-border-highlight)',
                        badge: 'var(--color-bg-badge)',
                        'badge-fg': 'var(--color-text-badge-fg)',
                    },
                },
            },
        };
    </script>
</head>
<body class="bg-page text-body min-h-screen flex flex-col">

    <nav class="bg-nav text-inverse" x-data="{ mobileOpen: false }">
        <div class="max-w-6xl mx-auto px-4 flex items-center justify-between h-14">
            <a href="/" class="font-semibold text-white">{{ config('app.name') }}</a>

            @auth
                <div class="hidden sm:flex items-center gap-4 text-sm">
                    @if ($canSee('admin.users') || $canSee('admin.roles') || $canSee('audit.view'))
                        <div class="relative" x-data="{ open: false }">
                            <button @click="open = !open" @click.outside="open = false" class="hover:bg-nav-hover px-3 py-2 rounded">
                                Admin
                            </button>
                            <div x-show="open" x-cloak class="absolute right-0 mt-1 w-48 bg-surface text-body border border-edge rounded shadow-lg py-1 z-10">
                                @if ($canSee('admin.users'))
                                    <a href="{{ url('/admin/users') }}" class="block px-4 py-2 hover:bg-muted">Users</a>
                                @endif
                                @if ($canSee('admin.roles'))
                                    <a href="{{ url('/admin/roles') }}" class="block px-4 py-2 hover:bg-muted">Roles</a>
                                @endif
                                @if ($canSee('audit.view'))
                                    <a href="{{ url('/admin/audit') }}" class="block px-4 py-2 hover:bg-muted">Audit log</a>
                                @endif
                            </div>
                        </div>
                    @endif
                    <a href="{{ url('/profile') }}" class="hover:bg-nav-hover px-3 py-2 rounded">{{ auth()->user()->initials() }}</a>
                    <form method="POST" action="{{ url('/user/logout') }}">
                        @csrf
                        <button type="submit" class="hover:bg-nav-hover px-3 py-2 rounded">Log out</button>
                    </form>
                </div>

                <button @click="mobileOpen = !mobileOpen" class="sm:hidden p-2 -mr-2 rounded hover:bg-nav-hover" aria-label="Toggle menu">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path x-show="!mobileOpen" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path x-show="mobileOpen" x-cloak stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            @endauth
        </div>

        @auth
            <div x-show="mobileOpen" x-cloak @click.outside="mobileOpen = false" class="sm:hidden border-t border-nav-hover px-4 py-3 space-y-1 text-sm">
                @if ($canSee('admin.users') || $canSee('admin.roles') || $canSee('audit.view'))
                    @if ($canSee('admin.users'))
                        <a href="{{ url('/admin/users') }}" class="block px-3 py-2 rounded hover:bg-nav-hover">Users</a>
                    @endif
                    @if ($canSee('admin.roles'))
                        <a href="{{ url('/admin/roles') }}" class="block px-3 py-2 rounded hover:bg-nav-hover">Roles</a>
                    @endif
                    @if ($canSee('audit.view'))
                        <a href="{{ url('/admin/audit') }}" class="block px-3 py-2 rounded hover:bg-nav-hover">Audit log</a>
                    @endif
                    <div class="border-t border-nav-hover my-1"></div>
                @endif
                <a href="{{ url('/profile') }}" class="block px-3 py-2 rounded hover:bg-nav-hover">{{ auth()->user()->initials() }}</a>
                <form method="POST" action="{{ url('/user/logout') }}">
                    @csrf
                    <button type="submit" class="w-full text-left px-3 py-2 rounded hover:bg-nav-hover">Log out</button>
                </form>
            </div>
        @endauth
    </nav>

    <main class="flex-1 max-w-6xl mx-auto w-full px-4 py-8">
        @if (session('success'))
            <div class="mb-6 rounded border border-green-200 bg-green-50 text-green-700 px-4 py-3">
                {{ session('success') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="mb-6 rounded border border-red-200 bg-red-50 text-red-700 px-4 py-3">
                <ul class="list-disc list-inside">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @yield('content')
    </main>

    <footer class="bg-nav text-inverse text-xs">
        <div class="max-w-6xl mx-auto px-4 py-2 sm:h-10 sm:py-0 flex flex-col sm:flex-row items-center justify-between gap-1 sm:gap-0 text-center sm:text-left">
            <span>{{ config('app.name') }}</span>
            <span>{{ app()->environment() }} · {{ config('app.version') }}</span>
            <span>&copy; {{ date('Y') }}</span>
        </div>
    </footer>

</body>
</html>
