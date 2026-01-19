<div x-data="{ mobileMenuOpen: false }">
    <!-- Alpine.js & Tailwind -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script src="https://cdn.tailwindcss.com"></script>

    <header 
        class="fixed top-4 left-1/2 -translate-x-1/2 w-[95%] max-w-7xl z-50 transition-all duration-300 bg-white/10 backdrop-blur-2xl shadow-2xl z-[60]  border border-white/40 rounded-full"
    >
        <div class="px-5 py-2 flex items-center justify-between">
            <!-- Logo -->
            <div class="flex-shrink-0">
                <a href="{{ url('/accueil') }}">
                    <img src="{{ asset('images/iconPg.png') }}" alt="Logo" class="h-10 w-auto">
                </a>
            </div>

            <!-- Desktop Navigation -->
            <div class="hidden md:flex items-center gap-4">
                <a href="/reunion" 
                   class="px-5 py-2 text-sm font-bold transition-all duration-300 rounded-full shadow-sm hover:shadow-md hover:-translate-y-0.5 border
                   {{ request()->is('reunion*') ? 'bg-indigo-600 text-white border-indigo-600 shadow-indigo-100' : 'text-gray-700 bg-white/30 backdrop-blur-md hover:bg-white/50 border-white/60' }}">
                    Réunion
                </a>
                @if(Auth::check() && (Auth::user()->isAdmin() || Auth::user()->isChef()))
                    <a href="{{ route('organisations.list') }}" 
                       class="px-5 py-2 text-sm font-bold transition-all duration-300 rounded-full shadow-sm hover:shadow-md hover:-translate-y-0.5 border
                       {{ request()->is('organisations*') ? 'bg-indigo-600 text-white border-indigo-600 shadow-indigo-100' : 'text-gray-700 bg-white/30 backdrop-blur-md hover:bg-white/50 border-white/60' }}">
                        Organisation{{ Auth::user()->isAdmin() ? 's' : '' }}
                    </a>
                @endif
            </div>

            <!-- Right Side Actions -->
            <div class="flex items-center gap-2 md:gap-4">
                <!-- Notification Bell -->
                <div class="relative" x-data="{ 
                    open: false, 
                    notifications: [],
                    unreadCount: 0,
                    async fetchNotifications() {
                        try {
                            const res = await fetch('/notifications');
                            this.notifications = await res.json();
                            this.unreadCount = this.notifications.length;
                        } catch(e) {}
                    },
                    async markAsRead(id) {
                        await fetch(`/notifications/${id}/read`, {
                            method: 'POST',
                            headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=&quot;csrf-token&quot;]').getAttribute('content') }
                        });
                        this.fetchNotifications();
                    },
                    init() {
                        this.fetchNotifications();
                        setInterval(() => this.fetchNotifications(), 15000);
                        window.addEventListener('reunion-created', () => setTimeout(() => this.fetchNotifications(), 1000));
                    }
                }">
                    <button @click="open = !open" type="button" class="p-2 rounded-full text-gray-700 bg-white/40 hover:bg-white/80 border border-white/60 transition relative shadow-sm">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                        </svg>
                        <template x-if="unreadCount > 0">
                            <span class="absolute top-0 right-0 inline-flex items-center justify-center px-1.5 py-0.5 text-xs font-bold leading-none text-white transform translate-x-1/2 -translate-y-1/2 bg-red-600 rounded-full" x-text="unreadCount"></span>
                        </template>
                    </button>

                    <div x-show="open" @click.away="open = false" x-transition class="absolute right-0 mt-3 w-80 bg-white shadow-2xl rounded-xl border border-gray-100 overflow-hidden z-50 overflow-y-auto max-h-96" style="display: none;">
                        <div class="px-5 py-4 border-b flex justify-between items-center bg-gray-50/50">
                            <h3 class="text-sm font-bold text-gray-900">Notifications</h3>
                            <span class="text-xs text-gray-500" x-text="unreadCount + ' nouvelles'"></span>
                        </div>
                        <div class="divide-y divide-gray-100">
                            <template x-if="notifications.length === 0">
                                <div class="p-8 text-center text-gray-400 text-sm">Aucune notification</div>
                            </template>
                            <template x-for="n in notifications" :key="n.id">
                                <div class="block px-5 py-4 hover:bg-indigo-50/30 transition cursor-pointer" @click="markAsRead(n.id)">
                                    <p class="text-sm font-medium text-gray-900" x-text="n.data.message"></p>
                                    <p class="text-xs text-gray-500 mt-1" x-text="n.created_at"></p>
                                </div>
                            </template>
                        </div>
                        @auth
                        <div class="px-5 py-3 bg-gray-50/60 text-center border-t text-xs font-bold text-indigo-600">
                            <a href="/calendrier">Voir le calendrier</a>
                        </div>
                        @endauth
                    </div>
                </div>

                <!-- Profile Dropdown (Desktop) -->
                @auth
                <div class="relative" x-data="{ profileOpen: false }">
                    <button @click="profileOpen = !profileOpen" @click.away="profileOpen = false" class="flex items-center focus:outline-none transition max-w-xs rounded-full bg-white/40 hover:bg-white/80 border border-white/60 p-1 shadow-sm">
                        <img src="{{ Voyager::image(Auth::user()->avatar) }}" alt="{{ Auth::user()->name }}" class="h-9 w-9 rounded-full border border-gray-200 object-cover">
                        <span class="ml-2 text-sm font-semibold text-gray-700 hidden lg:block">{{ Auth::user()->name }}</span>
                    </button>
                    <div x-show="profileOpen" x-transition class="absolute right-0 mt-2 w-48 bg-white/95 backdrop-blur-md rounded-xl shadow-2xl border border-gray-100 py-1 z-50 overflow-hidden" style="display: none;">
                        <div class="px-4 py-3 border-b text-sm font-medium text-gray-900 truncate">
                            {{ Auth::user()->email }}
                        </div>
                        @if(Auth::user()->hasPermission('browse_admin'))
                            <a href="{{ route('voyager.profile') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-indigo-50">Profil</a>
                        @endif
                        <form action="{{ route('logout') }}" method="POST">@csrf
                            <button type="submit" class="w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-red-50">Déconnexion</button>
                        </form>
                    </div>
                </div>
                @endauth

                @guest
                <a href="{{ url('/admin/login') }}" class="hidden sm:inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-xs font-bold rounded-full shadow-lg hover:bg-indigo-700 transition">Se Connecter</a>
                @endguest

                <!-- Mobile Burger Button -->
                <button @click="mobileMenuOpen = true" class="md:hidden p-2 rounded-full text-gray-700 hover:bg-white/40">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16m-7 6h7"></path></svg>
                </button>
            </div>
        </div>
    </header>

    <!-- Mobile Sidebar / Drawer -->
    <div x-show="mobileMenuOpen" 
         @click.away="mobileMenuOpen = false"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 translate-x-full"
         x-transition:enter-end="opacity-100 translate-x-0"
         x-transition:leave="transition ease-in duration-300"
         x-transition:leave-start="opacity-100 translate-x-0"
         x-transition:leave-end="opacity-0 translate-x-full"
         class="fixed inset-y-0 right-0 w-72 bg-white/98 backdrop-blur-2xl shadow-2xl z-[60] md:hidden flex flex-col p-6 rounded-l-3xl border-l border-white/30"
         style="display: none;"
    >
        <div class="flex justify-between items-center mb-8">
            <h2 class="text-xl font-bold text-indigo-600">Menu</h2>
            <button @click="mobileMenuOpen = false" class="p-2 text-gray-400 hover:text-gray-900 transition-colors">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
        </div>

        <!-- Navigation Links -->
        <nav class="flex flex-col gap-3 flex-1 text-base">
            <a href="/reunion" 
               class="flex items-center gap-3 px-4 py-3 font-bold rounded-xl transition shadow-sm border
               {{ request()->is('reunion*') ? 'text-indigo-700 bg-indigo-100/80 border-indigo-200' : 'text-gray-800 bg-indigo-50/50 hover:bg-indigo-100/50 border-indigo-100/20' }}">
                <svg class="w-5 h-5 {{ request()->is('reunion*') ? 'text-indigo-700' : 'text-indigo-600' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                Réunion
            </a>
            
            @if(Auth::check() && (Auth::user()->isAdmin() || Auth::user()->isChef()))
            <a href="{{ route('organisations.list') }}" 
               class="flex items-center gap-3 px-4 py-3 font-bold rounded-xl transition shadow-sm border
               {{ request()->is('organisations*') ? 'text-indigo-700 bg-indigo-100/80 border-indigo-200' : 'text-gray-800 bg-indigo-50/50 hover:bg-indigo-100/50 border-indigo-100/20' }}">
                <svg class="w-5 h-5 {{ request()->is('organisations*') ? 'text-indigo-700' : 'text-indigo-600' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                Organisation{{ Auth::user()->isAdmin() ? 's' : '' }}
            </a>
            @endif
        </nav>

    </div>

    <!-- Background Overlay -->
    <div x-show="mobileMenuOpen" 
         @click="mobileMenuOpen = false" 
         x-transition.opacity
         class="fixed inset-0 bg-black/20 backdrop-blur-sm z-[55] md:hidden"
         style="display: none;"
    ></div>
</div>

