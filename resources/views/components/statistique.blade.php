<script src="{{ asset('js/statistique.js') }}"></script>
<link rel="stylesheet" href="{{ asset('css/statistique.css') }}">
    <!-- Statistics Widget -->
    <div id="stats-widget" class="fixed left-0 top-1/2 -translate-y-1/2 z-[60] group cursor-pointer">
        <div class="relative w-12 h-12 flex items-center justify-center">
            <!-- Background circle with spin animation -->
            <div class="absolute inset-0 border-4 border-t-indigo-600 border-r-transparent border-b-purple-500 border-l-transparent rounded-full animate-stats-spin group-hover:pause"></div>
            <!-- Inner content (icon) -->
            <div class="bg-white/90 backdrop-blur shadow-lg rounded-full w-8 h-8 flex items-center justify-center transition-transform group-hover:scale-110">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 002 2h2a2 2 0 002-2" />
                </svg>
            </div>
        </div>
    </div>

    <!-- Statistics Panel (Left Blanket) -->
    <div id="stats-panel" class="fixed inset-y-0 left-0 w-full md:w-96 bg-white/95 backdrop-blur-xl shadow-2xl z-[70] -translate-x-full transition-transform duration-700 ease-in-out border-r border-gray-200">
        <div class="h-full flex flex-col p-8 overflow-y-auto">
            <div class="flex justify-between items-center mb-8">
                <h2 class="text-2xl font-bold bg-clip-text text-transparent bg-gradient-to-r from-indigo-600 to-purple-600">Statistiques</h2>
                <button id="close-stats" class="p-2 text-gray-500 hover:text-gray-900 transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <div class="space-y-6">
                <!-- Stat Item 1 -->
                <div class="p-6 bg-indigo-50 rounded-2xl border border-indigo-100">
                    <div class="text-sm text-indigo-600 font-semibold mb-1">Total Réunions</div>
                    <div class="text-4xl font-black text-gray-900">42</div>
                </div>
                <!-- Stat Item 2 -->
                <div class="p-6 bg-purple-50 rounded-2xl border border-purple-100">
                    <div class="text-sm text-purple-600 font-semibold mb-1">Organisations</div>
                    <div class="text-4xl font-black text-gray-900">12</div>
                </div>
                <!-- Stat Item 3 -->
                <div class="p-6 bg-green-50 rounded-2xl border border-green-100">
                    <div class="text-sm text-green-600 font-semibold mb-1">Réunions Terminées</div>
                    <div class="text-4xl font-black text-gray-900">89%</div>
                </div>
                
                <!-- Chart Placeholder -->
                <div class="mt-8">
                    <div class="h-48 w-full bg-gray-50 rounded-2xl border border-gray-100 flex items-end justify-between p-4 gap-2">
                        <div class="bg-indigo-400 w-full rounded-t-lg" style="height: 40%"></div>
                        <div class="bg-indigo-500 w-full rounded-t-lg" style="height: 70%"></div>
                        <div class="bg-indigo-600 w-full rounded-t-lg" style="height: 55%"></div>
                        <div class="bg-purple-500 w-full rounded-t-lg" style="height: 90%"></div>
                        <div class="bg-purple-600 w-full rounded-t-lg" style="height: 35%"></div>
                    </div>
                    <div class="text-center text-xs text-gray-400 mt-2 font-medium uppercase tracking-widest">Activité Hebdomadaire</div>
                </div>
            </div>
        </div>
    </div>

