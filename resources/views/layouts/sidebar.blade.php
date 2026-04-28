<!-- Logo and Brand -->
<div class="flex h-16 flex-shrink-0 items-center px-4 bg-gray-800">
    <a href="{{ route('dashboard') }}" class="text-xl font-bold text-white">SSMS</a>
</div>

<!-- Sidebar Navigation -->
<div class="flex flex-1 flex-col overflow-y-auto">
    <nav class="flex-1 space-y-1 px-2 py-4">

        <!-- Dashboard -->
        <a href="{{ route('dashboard') }}"
           class="group flex items-center px-2 py-2 text-sm font-medium rounded-md {{ request()->routeIs('dashboard') ? 'bg-gray-800 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white' }}">
            <!-- Home (outline) -->
            <svg class="mr-3 h-6 w-6 flex-shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12l8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75V19.5A2.25 2.25 0 006.75 21.75h3.75v-6a2.25 2.25 0 012.25-2.25h.5A2.25 2.25 0 0115.5 15.75v6h3.75A2.25 2.25 0 0021.75 19.5V9.75" />
            </svg>
            Dashboard
        </a>

        <!-- Complaints -->
        @if(Auth::user()->hasPermission('complaints.view') || Auth::user()->isResident())
            <a href="{{ route('complaints.index') }}"
               class="group flex items-center px-2 py-2 text-sm font-medium rounded-md {{ request()->routeIs('complaints.*') ? 'bg-gray-800 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white' }}">
                <!-- Exclamation Triangle (outline) -->
                <svg class="mr-3 h-6 w-6 flex-shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m0 3.75h.008v.008H12v-.008z" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.29 3.86c.78-1.34 2.64-1.34 3.42 0l8.4 14.4c.78 1.34-.19 3.02-1.71 3.02H3.6c-1.52 0-2.49-1.68-1.71-3.02l8.4-14.4z" />
                </svg>
                Complaints
            </a>
        @endif

        <!-- Maintenance (DROPDOWN) -->
        @if(Auth::user()->hasPermission('maintenance.view') || Auth::user()->isResident())
            @php
                $maintenanceActive = request()->routeIs('maintenance.*') || request()->routeIs('maintenance.policies.*');
            @endphp

            <div x-data="{ open: {{ $maintenanceActive ? 'true' : 'false' }} }" class="space-y-1">
                <button type="button"
                        @click="open = !open"
                        class="w-full group flex items-center justify-between px-2 py-2 text-sm font-medium rounded-md
                        {{ $maintenanceActive ? 'bg-gray-800 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white' }}">
                    <div class="flex items-center">
                        <!-- Wallet (outline) -->
                        <svg class="mr-3 h-6 w-6 flex-shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 12.75V8.625c0-1.036-.84-1.875-1.875-1.875H5.625A2.625 2.625 0 003 9.375v9A2.625 2.625 0 005.625 21h13.5A1.875 1.875 0 0021 19.125v-4.125m0 0h-4.5a1.875 1.875 0 010-3.75H21" />
                        </svg>
                        Maintenance
                    </div>

                    <!-- Chevron -->
                    <svg class="h-5 w-5 flex-shrink-0 transform transition-transform duration-150"
                         :class="open ? 'rotate-180' : ''"
                         xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>

                <div x-show="open" x-cloak class="ml-10 space-y-1">
                    <a href="{{ route('maintenance.index') }}"
                       class="block px-2 py-2 text-sm rounded-md {{ request()->routeIs('maintenance.index') ? 'text-emerald-400 font-semibold' : 'text-gray-300 hover:text-white' }}">
                        All Bills
                    </a>

                    {{-- Policies (if you have these routes/permissions) --}}
                    @if(
                        request()->routeIs('maintenance.policies.*')
                        || Auth::user()->hasPermission('maintenance.policy.view')
                        || Auth::user()->hasPermission('maintenance.policy.create')
                        || Auth::user()->hasPermission('maintenance.create')
                        || Auth::user()->isSuperAdmin()
                        || Auth::user()->isSocietyAdmin()
                    )
                        <a href="{{ route('maintenance.policies.index') }}"
                           class="block px-2 py-2 text-sm rounded-md {{ request()->routeIs('maintenance.policies.*') ? 'text-emerald-400 font-semibold' : 'text-gray-300 hover:text-white' }}">
                            Policies
                        </a>
                    @endif
                </div>
            </div>
        @endif

        <!-- Notices -->
        @if(Auth::user()->hasPermission('notices.view') || Auth::user()->isResident())
            <a href="{{ route('notices.index') }}"
               class="group flex items-center px-2 py-2 text-sm font-medium rounded-md {{ request()->routeIs('notices.*') ? 'bg-gray-800 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white' }}">
                <!-- Megaphone (outline) -->
                <svg class="mr-3 h-6 w-6 flex-shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 6.75H18a3 3 0 013 3v1.5a3 3 0 01-3 3h-7.5m0-9L3.75 9.75m6.75-3v12m0-3.75L3.75 14.25" />
                </svg>
                Notices
            </a>
        @endif

        <a href="{{ route('notifications.index') }}"
           class="group flex items-center justify-between px-2 py-2 text-sm font-medium rounded-md {{ request()->routeIs('notifications.*') ? 'bg-gray-800 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white' }}">
            <span class="flex items-center">
                <svg class="mr-3 h-6 w-6 flex-shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0018 9.75v-.7V9a6 6 0 10-12 0v.05-.05.7a8.967 8.967 0 00-2.312 6.022 23.848 23.848 0 005.454 1.31m5.715 0a24.255 24.255 0 01-5.715 0m5.715 0a3 3 0 11-5.715 0" />
                </svg>
                Notifications
            </span>

            @if(($notificationMenu['unreadCount'] ?? 0) > 0)
                <span class="inline-flex min-w-[1.5rem] items-center justify-center rounded-full bg-emerald-500 px-2 py-0.5 text-xs font-bold text-white">
                    {{ $notificationMenu['unreadCount'] }}
                </span>
            @endif
        </a>

        <!-- Visitors -->
        @if(Auth::user()->hasPermission('visitors.view') || Auth::user()->isResident())
            <a href="{{ route('visitors.index') }}"
               class="group flex items-center px-2 py-2 text-sm font-medium rounded-md {{ request()->routeIs('visitors.*') ? 'bg-gray-800 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white' }}">
                <!-- Users (outline) -->
                <svg class="mr-3 h-6 w-6 flex-shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.949 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.785-3.07M15 19.128a9.372 9.372 0 01-3.75.372 9.337 9.337 0 01-4.121-.949 4.125 4.125 0 017.533-2.493M9 7.5a3.75 3.75 0 117.5 0 3.75 3.75 0 01-7.5 0z" />
                </svg>
                Visitors
            </a>
        @endif

        <!-- Divider + Management -->
        @if(Auth::user()->isSuperAdmin() || Auth::user()->isSocietyAdmin())
            @php
                $managementActive = request()->routeIs('societies.*')
                    || request()->routeIs('towers.*')
                    || request()->routeIs('flats.*')
                    || request()->routeIs('users.*')
                    || request()->routeIs('activity-logs.*');
            @endphp

            <div class="border-t border-gray-700 my-3"></div>

            <!-- Management Section Header + Dropdown -->
            <div x-data="{ open: {{ $managementActive ? 'true' : 'false' }} }" class="space-y-1">
                <button type="button"
                        @click="open = !open"
                        class="w-full flex items-center justify-between px-3 py-2 text-xs font-semibold uppercase tracking-wider rounded-md
                        {{ $managementActive ? 'text-white bg-gray-800' : 'text-gray-400 hover:bg-gray-700 hover:text-white' }}">
                    <span>Management</span>
                    <svg class="h-5 w-5 flex-shrink-0 transform transition-transform duration-150"
                         :class="open ? 'rotate-180' : ''"
                         xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>

                <div x-show="open" x-cloak class="space-y-1 px-1">
                    @if(Auth::user()->hasPermission('societies.view'))
                        <a href="{{ route('societies.index') }}"
                           class="group flex items-center px-2 py-2 text-sm font-medium rounded-md {{ request()->routeIs('societies.*') ? 'bg-gray-800 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white' }}">
                            <!-- Building office (outline) -->
                            <svg class="mr-3 h-6 w-6 flex-shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21h16.5M4.5 3.75h15A1.5 1.5 0 0121 5.25V21H3V5.25A1.5 1.5 0 014.5 3.75zM7.5 7.5h3m-3 3h3m-3 3h3m3-6h3m-3 3h3m-3 3h3" />
                            </svg>
                            Societies
                        </a>
                    @endif

                    @if(Auth::user()->hasPermission('societies.view'))
                        <a href="{{ route('towers.index') }}"
                           class="group flex items-center px-2 py-2 text-sm font-medium rounded-md {{ request()->routeIs('towers.*') ? 'bg-gray-800 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white' }}">
                            <!-- Squares 2x2 (outline) -->
                            <svg class="mr-3 h-6 w-6 flex-shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 3.75h7.5v7.5h-7.5v-7.5zM12.75 3.75h7.5v7.5h-7.5v-7.5zM3.75 12.75h7.5v7.5h-7.5v-7.5zM12.75 12.75h7.5v7.5h-7.5v-7.5z" />
                            </svg>
                            Towers
                        </a>
                    @endif

                    @if(Auth::user()->hasPermission('flats.view'))
                        <a href="{{ route('flats.index') }}"
                           class="group flex items-center px-2 py-2 text-sm font-medium rounded-md {{ request()->routeIs('flats.*') ? 'bg-gray-800 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white' }}">
                            <!-- Key (outline) -->
                            <svg class="mr-3 h-6 w-6 flex-shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 5.25a4.5 4.5 0 00-4.5 4.5c0 .66.141 1.287.396 1.852L3 20.25V21h.75l3.3-3.3h2.1l1.8-1.8v-2.1l2.598-2.598c.565.255 1.192.396 1.852.396a4.5 4.5 0 000-9z" />
                            </svg>
                            Flats
                        </a>
                    @endif

                    @if(Auth::user()->hasPermission('users.view'))
                        <a href="{{ route('users.index') }}"
                           class="group flex items-center px-2 py-2 text-sm font-medium rounded-md {{ request()->routeIs('users.*') ? 'bg-gray-800 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white' }}">
                            <!-- User group (outline) -->
                            <svg class="mr-3 h-6 w-6 flex-shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 003.741-.479 3.375 3.375 0 00-6.482-1.135M15 12a3 3 0 11-6 0 3 3 0 016 0zm-9 8.25a6 6 0 0112 0V21H6v-.75z" />
                            </svg>
                            Users
                        </a>
                    @endif

                    @if(Auth::user()->hasPermission('societies.view'))
                        <a href="{{ route('activity-logs.index') }}"
                           class="group flex items-center px-2 py-2 text-sm font-medium rounded-md {{ request()->routeIs('activity-logs.*') ? 'bg-gray-800 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white' }}">
                            <!-- Clipboard list (outline) -->
                            <svg class="mr-3 h-6 w-6 flex-shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 3.75h6m-6 0A2.25 2.25 0 006.75 6v14.25A2.25 2.25 0 009 22.5h6A2.25 2.25 0 0017.25 20.25V6A2.25 2.25 0 0015 3.75m-6 0h6" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9.75 9.75h4.5M9.75 13.5h4.5M9.75 17.25h4.5" />
                            </svg>
                            Activity Logs
                        </a>
                    @endif
                </div>
            </div>
        @endif

    </nav>

    <!-- User info at bottom (mobile only) -->
    <div class="flex-shrink-0 flex border-t border-gray-700 p-4 lg:hidden">
        <div class="flex items-center">
            <div class="h-10 w-10 rounded-full bg-gray-800 flex items-center justify-center text-white font-semibold">
                {{ substr(Auth::user()->name, 0, 1) }}
            </div>
            <div class="ml-3">
                <p class="text-sm font-medium text-white">{{ Auth::user()->name }}</p>
                <p class="text-xs font-medium text-gray-400">{{ Auth::user()->email }}</p>
            </div>
        </div>
    </div>
</div>
