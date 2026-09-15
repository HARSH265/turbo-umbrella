<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-gray-50">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'SSMS') }} - @yield('title')</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700" rel="stylesheet" />

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    
    <!-- Alpine.js for interactions -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body class="h-full">
    <div x-data="{ sidebarOpen: false }" class="h-full">
        <!-- Mobile sidebar overlay -->
        <div x-show="sidebarOpen" 
             x-cloak
             @click="sidebarOpen = false"
             class="fixed inset-0 z-40 bg-gray-600 bg-opacity-75 lg:hidden"
             x-transition:enter="transition-opacity ease-linear duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition-opacity ease-linear duration-300"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0">
        </div>

        <!-- Mobile sidebar -->
        <div x-show="sidebarOpen"
             x-cloak
             @click.away="sidebarOpen = false"
             class="fixed inset-y-0 left-0 z-50 w-64 bg-gray-900 lg:hidden"
             x-transition:enter="transition ease-in-out duration-300 transform"
             x-transition:enter-start="-translate-x-full"
             x-transition:enter-end="translate-x-0"
             x-transition:leave="transition ease-in-out duration-300 transform"
             x-transition:leave-start="translate-x-0"
             x-transition:leave-end="-translate-x-full">
            @include('layouts.sidebar')
        </div>

        <!-- Desktop sidebar -->
        <div class="hidden lg:fixed lg:inset-y-0 lg:flex lg:w-64 lg:flex-col">
            <div class="flex min-h-0 flex-1 flex-col bg-gray-900">
                @include('layouts.sidebar')
            </div>
        </div>

        <!-- Main content area -->
        <div class="flex flex-1 flex-col lg:pl-64">
            <!-- Top navigation bar -->
            <div class="sticky top-0 z-10 flex h-16 flex-shrink-0 bg-white shadow">
                <button @click="sidebarOpen = true" 
                        type="button" 
                        class="border-r border-gray-200 px-4 text-gray-500 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-gray-900 lg:hidden">
                    <span class="sr-only">Open sidebar</span>
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                    </svg>
                </button>

                <div class="flex flex-1 justify-between px-4">
                    <div class="flex flex-1 items-center">
                        <h1 class="text-xl font-semibold text-gray-900">@yield('page-title', 'Dashboard')</h1>
                    </div>
                    
                    <!-- User menu -->
                    <div class="ml-4 flex items-center gap-3 md:ml-6">
                        <div class="relative" x-data="{ open: false }">
                            <button @click="open = !open"
                                    type="button"
                                    class="relative inline-flex h-10 w-10 items-center justify-center rounded-full border border-gray-200 bg-white text-gray-600 transition hover:border-gray-300 hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-gray-900 focus:ring-offset-2">
                                <span class="sr-only">Open notifications</span>
                                <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0018 9.75v-.7V9a6 6 0 10-12 0v.05-.05.7a8.967 8.967 0 00-2.312 6.022 23.848 23.848 0 005.454 1.31m5.715 0a24.255 24.255 0 01-5.715 0m5.715 0a3 3 0 11-5.715 0" />
                                </svg>

                                @if(($notificationMenu['unreadCount'] ?? 0) > 0)
                                    <span class="absolute -right-1 -top-1 inline-flex min-w-[1.35rem] items-center justify-center rounded-full bg-emerald-500 px-1.5 py-0.5 text-[11px] font-bold leading-none text-white">
                                        {{ $notificationMenu['unreadCount'] > 99 ? '99+' : $notificationMenu['unreadCount'] }}
                                    </span>
                                @endif
                            </button>

                            <div x-show="open"
                                 x-cloak
                                 @click.away="open = false"
                                 class="absolute right-0 mt-3 w-[22rem] overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-xl">
                                <div class="flex items-center justify-between border-b border-gray-100 px-4 py-3">
                                    <div>
                                        <p class="text-sm font-semibold text-gray-900">Notifications</p>
                                        <p class="text-xs text-gray-500">
                                            {{ $notificationMenu['unreadCount'] ?? 0 }} unread
                                        </p>
                                    </div>

                                    @if(($notificationMenu['unreadCount'] ?? 0) > 0)
                                        <form method="POST" action="{{ route('notifications.read-all') }}">
                                            @csrf
                                            <button type="submit" class="text-xs font-medium text-emerald-600 hover:text-emerald-700">
                                                Mark all read
                                            </button>
                                        </form>
                                    @endif
                                </div>

                                <div class="max-h-96 overflow-y-auto">
                                    @forelse(($notificationMenu['recent'] ?? collect()) as $notification)
                                        @php($data = $notification->data)
                                        <div class="border-b border-gray-100 last:border-b-0 {{ is_null($notification->read_at) ? 'bg-emerald-50/40' : 'bg-white' }}">
                                            <div class="flex items-start gap-3 px-4 py-3">
                                                <div class="mt-0.5 h-2.5 w-2.5 flex-shrink-0 rounded-full {{ is_null($notification->read_at) ? 'bg-emerald-500' : 'bg-gray-300' }}"></div>

                                                <div class="min-w-0 flex-1">
                                                    <p class="truncate text-sm font-semibold text-gray-900">
                                                        {{ $data['title'] ?? 'Notification' }}
                                                    </p>
                                                    <p class="mt-1 line-clamp-2 text-sm text-gray-600">
                                                        {{ $data['message'] ?? '' }}
                                                    </p>
                                                    <p class="mt-1 text-xs text-gray-400">
                                                        {{ $notification->created_at->diffForHumans() }}
                                                    </p>
                                                </div>

                                                <div class="flex flex-col items-end gap-2">
                                                    @if (!empty($data['url']))
                                                        <a href="{{ route('notifications.open', $notification->id) }}"
                                                           class="text-xs font-medium text-gray-700 hover:text-gray-900">
                                                            Open
                                                        </a>
                                                    @endif

                                                    @if (is_null($notification->read_at))
                                                        <form method="POST" action="{{ route('notifications.read', $notification->id) }}">
                                                            @csrf
                                                            <button type="submit" class="text-xs font-medium text-emerald-600 hover:text-emerald-700">
                                                                Read
                                                            </button>
                                                        </form>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    @empty
                                        <div class="px-4 py-8 text-center text-sm text-gray-500">
                                            No personal notifications yet.
                                        </div>
                                    @endforelse
                                </div>

                                <div class="border-t border-gray-100 bg-gray-50 px-4 py-3">
                                    <a href="{{ route('notifications.index') }}"
                                       class="block text-center text-sm font-medium text-gray-700 hover:text-gray-900">
                                        View all notifications
                                    </a>
                                </div>
                            </div>
                        </div>

                        <div class="relative" x-data="{ open: false }">
                            <button @click="open = !open" 
                                    type="button" 
                                    class="flex max-w-xs items-center rounded-full bg-white text-sm focus:outline-none focus:ring-2 focus:ring-gray-900 focus:ring-offset-2">
                                <span class="sr-only">Open user menu</span>
                                <div class="flex items-center">
                                    <div class="h-8 w-8 rounded-full bg-gray-900 flex items-center justify-center text-white font-semibold">
                                        {{ substr(Auth::user()->name, 0, 1) }}
                                    </div>
                                    <span class="ml-3 text-sm font-medium text-gray-700 hidden md:block">{{ Auth::user()->name }}</span>
                                    <svg class="ml-2 h-5 w-5 text-gray-400" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                            </button>

                            <div x-show="open"
                                 x-cloak
                                 @click.away="open = false"
                                 class="absolute right-0 mt-2 w-48 origin-top-right rounded-md bg-white py-1 shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none">
                                <div class="px-4 py-2 border-b">
                                    <p class="text-sm font-medium text-gray-900">{{ Auth::user()->name }}</p>
                                    <p class="text-xs text-gray-500">{{ Auth::user()->email }}</p>
                                </div>
                                
                                <a href="{{ route('profile.edit') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                    <svg class="inline-block w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                    </svg>
                                    Profile
                                </a>
                                
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit" class="w-full text-left block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                        <svg class="inline-block w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                                        </svg>
                                        Logout
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main content -->
            <main class="flex-1">
                <div class="py-6">
                    <div class="mx-auto max-w-7xl px-4 sm:px-6 md:px-8">
                        <!-- Flash Messages -->
                        @if (session('success'))
                            <div class="mb-4 rounded-md bg-green-50 p-4">
                                <div class="flex">
                                    <svg class="h-5 w-5 text-green-400" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                    </svg>
                                    <p class="ml-3 text-sm font-medium text-green-800">{{ session('success') }}</p>
                                </div>
                            </div>
                        @endif

                        @if (session('error'))
                            <div class="mb-4 rounded-md bg-red-50 p-4">
                                <div class="flex">
                                    <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                                    </svg>
                                    <p class="ml-3 text-sm font-medium text-red-800">{{ session('error') }}</p>
                                </div>
                            </div>
                        @endif

                        @if ($errors->any())
                            <div class="mb-4 rounded-md bg-red-50 p-4">
                                <div class="flex">
                                    <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                                    </svg>
                                    <div class="ml-3">
                                        <h3 class="text-sm font-medium text-red-800">There were errors with your submission</h3>
                                        <ul class="mt-2 list-disc list-inside text-sm text-red-700">
                                            @foreach ($errors->all() as $error)
                                                <li>{{ $error }}</li>
                                            @endforeach
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        @endif

                        @yield('content')
                    </div>
                </div>
            </main>
        </div>
    </div>
</body>
</html>
