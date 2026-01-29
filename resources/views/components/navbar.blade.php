<div x-data="{ mobileMenuOpen: false }">
    <!-- Alpine.js & Tailwind -->
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>    <script src="https://cdn.tailwindcss.com"></script>

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

            <!-- Modern Search -->
            <div class="flex-1 max-w-[40px] sm:max-w-md mx-2 sm:mx-4" x-data="{ expanded: false }">
                <div class="relative flex items-center bg-white/40 backdrop-blur-md border border-white/60 rounded-full px-4 py-1.5 transition-all duration-500 ease-in-out"
                     :class="expanded ? 'fixed inset-x-4 top-2 z-[70] shadow-2xl bg-white sm:relative sm:inset-auto sm:w-full' : 'w-10 overflow-hidden cursor-pointer group hover:bg-white/60'"
                     @click="expanded = true">
                    <button type="button" class="text-gray-600 group-hover:text-indigo-600 transition flex-shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                    </button>
                    <input type="text" placeholder="Rechercher..." 
                           class="ml-3 bg-transparent border-none focus:ring-0 text-sm w-full outline-none placeholder-gray-400"
                           x-show="expanded"
                           @click.away="expanded = false"
                           x-transition.opacity>
                </div>
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
                    async navigateToReunion(n) {
                        // Mark as read first
                        await this.markAsRead(n.id);
                        
                        // Navigate to calendar with the reunion date
                        if (n.data && n.data.date_debut) {
                            const dateStr = n.data.date_debut.split('T')[0]; // Extract YYYY-MM-DD
                            window.location.href = `/reunion?date=${dateStr}`;
                        } else {
                            // Fallback: just go to reunion page
                            window.location.href = '/reunion';
                        }
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
                                <div class="block px-5 py-4 hover:bg-indigo-50/30 transition cursor-pointer group" 
                                     @click="navigateToReunion(n)">
                                    <p class="text-sm font-medium text-gray-900 group-hover:text-indigo-600 transition" x-text="n.data.message"></p>
                                    <p class="text-xs text-gray-500 mt-1 flex items-center justify-between">
                                        <span x-text="n.created_at"></span>
                                        <span class="text-indigo-500 opacity-0 group-hover:opacity-100 transition text-[10px] flex items-center gap-1">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                            Voir
                                        </span>
                                    </p>
                                </div>
                            </template>
                        </div>
                    
                    </div>
                </div>

                <!-- Organization Switcher (Desktop Only) -->
                @auth
                @php
                    $uOrgs = Auth::user()->chefOfOrganisations->merge(Auth::user()->memberOfOrganisations)->unique('id');
                    $activeOrgId = session('active_organisation_id');
                    $currentOrg = $uOrgs->where('id', $activeOrgId)->first() ?: $uOrgs->first();
                @endphp
                
                @if(!Auth::user()->isAdmin() && $uOrgs->count() > 0)
                <div class="relative hidden md:block" x-data="{ switcherOpen: false }">
                    <button @click="switcherOpen = !switcherOpen" @click.away="switcherOpen = false" 
                            class="flex items-center gap-2 px-3 py-1.5 rounded-full bg-indigo-50/50 hover:bg-indigo-100 border border-indigo-100 transition shadow-sm">
                        <svg class="h-4 w-4 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                        <span class="text-xs font-bold text-indigo-700 max-w-[100px] truncate">
                            {{ $currentOrg->nom ?? 'Choisir org' }}
                        </span>
                        <svg class="w-3 h-3 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                    </button>
                    
                    <div x-show="switcherOpen" x-transition 
                         class="absolute right-0 mt-2 w-56 bg-white shadow-2xl rounded-2xl border border-gray-100 py-2 z-50 overflow-hidden" 
                         style="display: none;">
                        <div class="px-4 py-2 text-[10px] font-bold text-gray-400 uppercase tracking-widest border-b border-gray-50 mb-1">
                            Switcher d'organisation
                        </div>
                        @foreach($uOrgs as $org)
                        <form action="{{ route('organisations.switch') }}" method="POST">
                            @csrf
                            <input type="hidden" name="organisation_id" value="{{ $org->id }}">
                            <button type="submit" class="w-full text-left px-4 py-2.5 text-sm hover:bg-indigo-50 transition flex items-center justify-between group">
                                <span class="{{ $activeOrgId == $org->id ? 'font-bold text-indigo-600' : 'text-gray-700' }}">
                                    {{ $org->nom }}
                                </span>
                                @if($activeOrgId == $org->id)
                                    <svg class="w-4 h-4 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                @endif
                                <span class="text-[10px] opacity-0 group-hover:opacity-100 transition text-gray-400 italic ml-2">
                                    {{ Auth::user()->isChefIn($org->id) ? 'Chef' : 'Membre' }}
                                </span>
                            </button>
                        </form>
                        @endforeach
                    </div>
                </div>
                @endif
                @endauth

                <!-- Profile Dropdown (Desktop Only) -->
                @auth
                <div class="relative hidden md:block" x-data="{ profileOpen: false }">
                    <button @click="profileOpen = !profileOpen" @click.away="profileOpen = false" class="flex items-center focus:outline-none transition max-w-xs rounded-full bg-white/40 hover:bg-white/80 border border-white/60 p-1 shadow-sm">
                        <img src="{{ Voyager::image(Auth::user()->avatar) }}" alt="{{ Auth::user()->name }}" class="h-9 w-9 rounded-full border border-gray-200 object-cover">
                        <span class="ml-2 text-sm font-semibold text-gray-700 hidden lg:block">{{ Auth::user()->name }}</span>
                    </button>
                    <div x-show="profileOpen" x-transition class="absolute right-0 mt-2 w-48 bg-white/95 backdrop-blur-md rounded-xl shadow-2xl border border-gray-100 py-1 z-50 overflow-hidden" style="display: none;">
                        <div class="px-4 py-3 border-b text-sm font-medium text-gray-900 truncate">
                            {{ Auth::user()->email }}
                        </div>
                        @if(Auth::user()->hasPermission('browse_admin'))
                            <a href="{{ route('voyager.profile') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-indigo-50">Paramètres Admin</a>
                        @endif
                        @if(!Auth::user()->isAdmin())
                            <a href="{{ route('organisations.my') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-indigo-50">Paramètres de l'Organisation</a>
                        @endif
                        <a href="{{ route('voyager.profile') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-indigo-50">Paramètres</a>
                        <a href="{{ route('logout') }}" class="block px-4 py-2 text-sm text-red-600 hover:bg-red-50 font-medium">Déconnexion</a>
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
         class="fixed inset-y-0 right-0 w-72 bg-white/98 backdrop-blur-2xl shadow-2xl z-[60] md:hidden flex flex-col p-6 rounded-l-3xl border-l border-white/30 overflow-y-auto"
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
            <!-- Mobile User Profile -->
            @auth
            <div class="mb-4 p-4 bg-white/40 backdrop-blur-md rounded-2xl border border-gray-100 flex items-center gap-4 shadow-sm">
                <img src="{{ Voyager::image(Auth::user()->avatar) }}" class="w-12 h-12 rounded-full border border-white shadow-sm object-cover">
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-bold text-gray-900 truncate">{{ Auth::user()->name }}</p>
                    <p class="text-xs text-gray-500 truncate">{{ Auth::user()->email }}</p>
                </div>
            </div>

            <!-- Mobile Org Switcher (Moved here from Nav) -->
            @php
                $uOrgs = Auth::user()->chefOfOrganisations->merge(Auth::user()->memberOfOrganisations)->unique('id');
                $activeOrgId = session('active_organisation_id');
                $currentOrg = $uOrgs->where('id', $activeOrgId)->first() ?: $uOrgs->first();
            @endphp
            @if(Auth::user()->isAdmin())
                 {{-- No switcher for admin --}}
            @elseif($uOrgs->count() > 0)
            <div class="mb-4 p-4 bg-indigo-50/50 rounded-2xl border border-indigo-100">
                <p class="text-[10px] font-bold text-indigo-400 uppercase mb-2 ml-1">Organisation Active</p>
                <div x-data="{ mobSwitchOpen: false }">
                    <button @click="mobSwitchOpen = !mobSwitchOpen" class="w-full flex items-center justify-between text-indigo-700 font-bold text-sm">
                        <span class="truncate">{{ $currentOrg->nom ?? 'Choisir...' }}</span>
                        <i class="fas fa-chevron-down text-xs transition-transform" :class="mobSwitchOpen ? 'rotate-180' : ''"></i>
                    </button>
                    
                    <div x-show="mobSwitchOpen" x-transition class="mt-3 space-y-2 border-t border-indigo-100 pt-3">
                        @foreach($uOrgs as $org)
                        <form action="{{ route('organisations.switch') }}" method="POST">
                            @csrf
                            <input type="hidden" name="organisation_id" value="{{ $org->id }}">
                            <button type="submit" class="w-full text-left px-4 py-3 text-xs rounded-xl flex items-center justify-between transition-all {{ $activeOrgId == $org->id ? 'bg-indigo-600 text-white shadow-lg' : 'bg-white text-gray-600 border border-gray-100 hover:border-indigo-200' }}">
                                <span>{{ $org->nom }}</span>
                                <span class="text-[9px] opacity-70 italic">
                                    {{ Auth::user()->isChefIn($org->id) ? 'Chef' : 'Membre' }}
                                </span>
                            </button>
                        </form>
                        @endforeach
                    </div>
                </div>
            </div>
            @endif
            @endauth

            <!-- Search Field (Mobile) -->
            <div class="mb-4 sm:hidden">
                <div class="relative items-center bg-gray-50 border border-gray-200 rounded-2xl px-4 py-3 flex">
                    <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                    <input type="text" placeholder="Rechercher..." class="ml-3 bg-transparent border-none focus:ring-0 text-sm w-full outline-none">
                </div>
            </div>

            <a href="/reunion" 
               class="flex items-center gap-3 px-4 py-3 font-bold rounded-xl transition shadow-sm border
               {{ request()->is('reunion*') ? 'text-indigo-700 bg-indigo-100/80 border-indigo-200' : 'text-gray-800 bg-indigo-50/50 hover:bg-indigo-100/50 border-indigo-100/20' }}">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 {{ request()->is('reunion*') ? 'text-indigo-700' : 'text-indigo-600' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                </svg>
                Réunion
            </a>
            
            @if(Auth::check() && (Auth::user()->isAdmin() || Auth::user()->isChef()))
            <a href="{{ route('organisations.list') }}" 
               class="flex items-center gap-3 px-4 py-3 font-bold rounded-xl transition shadow-sm border
               {{ request()->is('organisations*') ? 'text-indigo-700 bg-indigo-100/80 border-indigo-200' : 'text-gray-800 bg-indigo-50/50 hover:bg-indigo-100/50 border-indigo-100/20' }}">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 {{ request()->is('organisations*') ? 'text-indigo-700' : 'text-indigo-600' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                </svg>
                Organisation{{ Auth::user()->isAdmin() ? 's' : '' }}
            </a>
            @endif

            <hr class="my-1 border-gray-100 italic opacity-50">

            <!-- Paramètres (Mobile Only) -->
            @if(Auth::user()->hasPermission('browse_admin'))
                <a href="{{ route('voyager.profile') }}" class="flex items-center gap-3 px-4 py-3 font-semibold text-gray-700 hover:bg-indigo-50 rounded-xl transition">
                    <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.544.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                    Paramètres Admin
                </a>
            @endif
            @if(!Auth::user()->isAdmin())
                <a href="{{ route('organisations.my') }}" class="flex items-center gap-3 px-4 py-3 font-semibold text-gray-700 hover:bg-indigo-50 rounded-xl transition">
                    <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"></path></svg>
                    Paramètres Organisation
                </a>
            @endif
            <a href="{{ route('voyager.profile') }}" class="flex items-center gap-3 px-4 py-3 font-semibold text-gray-700 hover:bg-indigo-50 rounded-xl transition">
                <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"></path></svg>
                Paramètres
            </a>

            <hr class="my-2 border-gray-100">
            
            <a href="{{ route('logout') }}" class="flex items-center gap-3 px-4 py-3 font-bold rounded-xl text-red-600 bg-red-50 hover:bg-red-100 transition shadow-sm border border-red-100">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
                Déconnexion
            </a>
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

