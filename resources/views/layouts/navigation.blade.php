<nav class="bg-white border-b border-gray-200">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex">
                <!-- Logo -->
                <div class="shrink-0 flex items-center">
                    <a href="{{ route('dashboard') }}" class="text-xl font-bold text-gray-800">
                        SSMS
                    </a>
                </div>

                <!-- Navigation Links -->
                <div class="hidden space-x-8 sm:-my-px sm:ml-10 sm:flex">
                    <a href="{{ route('dashboard') }}" 
                       class="inline-flex items-center px-1 pt-1 border-b-2 {{ request()->routeIs('dashboard') ? 'border-gray-900' : 'border-transparent' }} text-sm font-medium text-gray-900">
                        Dashboard
                    </a>

                    @can('complaints.view')
                    <a href="{{ route('complaints.index') }}" 
                       class="inline-flex items-center px-1 pt-1 border-b-2 {{ request()->routeIs('complaints.*') ? 'border-gray-900' : 'border-transparent' }} text-sm font-medium text-gray-700 hover:text-gray-900">
                        Complaints
                    </a>
                    @endcan

                    @if(Auth::user()->hasPermission('maintenance.view') || Auth::user()->isResident())
                    <a href="{{ route('maintenance.index') }}" 
                       class="inline-flex items-center px-1 pt-1 border-b-2 {{ request()->routeIs('maintenance.*') ? 'border-gray-900' : 'border-transparent' }} text-sm font-medium text-gray-700 hover:text-gray-900">
                        Maintenance
                    </a>
                    @endif

                    @can('notices.view')
                    <a href="{{ route('notices.index') }}" 
                       class="inline-flex items-center px-1 pt-1 border-b-2 {{ request()->routeIs('notices.*') ? 'border-gray-900' : 'border-transparent' }} text-sm font-medium text-gray-700 hover:text-gray-900">
                        Notices
                    </a>
                    @endcan

                    @can('visitors.view')
                    <a href="{{ route('visitors.index') }}" 
                       class="inline-flex items-center px-1 pt-1 border-b-2 {{ request()->routeIs('visitors.*') ? 'border-gray-900' : 'border-transparent' }} text-sm font-medium text-gray-700 hover:text-gray-900">
                        Visitors
                    </a>
                    @endcan

                    @if(Auth::user()->isSuperAdmin() || Auth::user()->isSocietyAdmin())
                    <div class="relative" x-data="{ open: false }">
                        <button @click="open = !open" 
                                class="inline-flex items-center px-1 pt-1 border-b-2 border-transparent text-sm font-medium text-gray-700 hover:text-gray-900">
                            Management
                            <svg class="ml-1 h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                            </svg>
                        </button>

                        <div x-show="open" 
                             @click.away="open = false"
                             class="absolute left-0 mt-2 w-48 bg-white rounded-md shadow-lg py-1 z-10">
                            @can('societies.view')
                            <a href="{{ route('societies.index') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Societies</a>
                            @endcan
                            @can('flats.view')
                            <a href="{{ route('flats.index') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Flats</a>
                            @endcan
                            @can('users.view')
                            <a href="{{ route('users.index') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Users</a>
                            @endcan
                        </div>
                    </div>
                    @endif
                </div>
            </div>

            <!-- Settings Dropdown -->
            <div class="hidden sm:flex sm:items-center sm:ml-6">
                <div class="relative" x-data="{ open: false }">
                    <button @click="open = !open" 
                            class="flex items-center text-sm font-medium text-gray-700 hover:text-gray-900">
                        {{ Auth::user()->name }}
                        <svg class="ml-1 h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                        </svg>
                    </button>

                    <div x-show="open" 
                         @click.away="open = false"
                         class="absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg py-1 z-10">
                        <a href="{{ route('profile.edit') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Profile</a>
                        
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="w-full text-left block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                Logout
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</nav>
