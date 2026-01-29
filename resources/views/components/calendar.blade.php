
<script src="{{ asset('js/calendar/calendar-app.js') }}" defer></script>
<link rel="stylesheet" href="{{ asset('css/calendar-app.css') }}">
<div class="calendar-component" id="calendarApp">


    <div class="flex items-center justify-center p-4 overflow-visible">
        <div id="calendarContainer" class="w-full max-w-md bg-white rounded-2xl shadow-xl overflow-hidden border border-gray-200">
            
            <!-- MONTHLY HEADER -->
            <div class="bg-gray-50 px-4 py-3 border-b relative">
                <h2 id="todayDisplay" class="text-xl font-bold text-center text-gray-800 mb-2"></h2>
                
                <!-- Export Dropdown -->
                <div class="absolute right-4 top-3 group dropdown">
                    <button class="p-1.5 text-gray-400 hover:text-indigo-600 border border-transparent hover:border-indigo-200 rounded-lg transition-all" title="Exporter la liste">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                    </button>
                    <div class="invisible group-hover:visible absolute right-0 mt-2 w-44 bg-white border border-gray-100 rounded-xl shadow-2xl py-2 z-[60] opacity-0 group-hover:opacity-100 transition-all transform origin-top-right scale-95 group-hover:scale-100">
                        <div class="px-3 py-1 text-[10px] font-bold text-gray-400 uppercase tracking-wider">Format d'export</div>
                        <button onclick="calendar.exportReunions('excel')" class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-indigo-50 hover:text-indigo-600 transition-colors flex items-center gap-2">
                            <svg class="h-4 w-4 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                            Excel (.csv)
                        </button>
                        <button onclick="calendar.exportReunions('pdf')" class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-indigo-50 hover:text-indigo-600 transition-colors flex items-center gap-2">
                            <svg class="h-4 w-4 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" /></svg>
                            PDF (.pdf)
                        </button>
                    </div>
                </div>
                
                <!-- Admin filters (will be shown/hidden via JS) -->
                <div id="adminFilters" class="flex justify-center mb-3" style="display: none;">
                    <select id="orgSelect" class="px-3 py-1.5 bg-white border border-gray-300 rounded-lg shadow-sm text-sm w-64">
                        <option value="">Toutes les organisations</option>
                        <!-- options added by JS -->
                    </select>
                </div>

                <!-- Navigation controls -->
                <div class="flex items-center justify-between gap-2 text-sm">
                    <button id="prevMonthBtn" class="px-3 py-1.5 bg-white border border-gray-300 rounded-lg shadow-sm hover:bg-gray-50 transition">
                        ◄ <span id="prevMonthName"></span>
                    </button>
                    
                    <select id="monthSelect" class="px-3 py-1.5 bg-white border border-gray-300 rounded-lg shadow-sm">
                        <!-- options added by JS -->
                    </select>
                    
                    <select id="yearSelect" class="px-3 py-1.5 bg-white border border-gray-300 rounded-lg shadow-sm">
                        <!-- options added by JS -->
                    </select>
                    
                    <button id="nextMonthBtn" class="px-3 py-1.5 bg-white border border-gray-300 rounded-lg shadow-sm hover:bg-gray-50 transition">
                        <span id="nextMonthName"></span> ►
                    </button>
                </div>
            </div>

            <!-- MONTHLY GRID -->
            <div class="grid grid-cols-7 gap-px bg-gray-200 text-center text-sm">
                <!-- Weekdays header -->
                <div class="py-2 bg-gray-300 font-medium text-gray-800">Lun</div>
                <div class="py-2 bg-gray-300 font-medium text-gray-800">Mar</div>
                <div class="py-2 bg-gray-300 font-medium text-gray-800">Mer</div>
                <div class="py-2 bg-gray-300 font-medium text-gray-800">Jeu</div>
                <div class="py-2 bg-gray-300 font-medium text-gray-800">Ven</div>
                <div class="py-2 bg-gray-300 font-medium text-gray-800">Sam</div>
                <div class="py-2 bg-gray-300 font-medium text-gray-800">Dim</div>

                <!-- Day cells – will be filled by JS -->
                <div id="calendarDays" class="col-span-7 grid grid-cols-7 gap-px"></div>
            </div>
        </div>

        <!-- MODAL -->
        <div id="reunionModal" class="fixed inset-0 bg-black/65 flex items-center justify-center z-50 p-4" style="display: none;">
            <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md sm:max-w-lg max-h-[90vh] overflow-y-auto modal">
                <div class="sticky top-0 bg-white px-5 py-3 border-b flex justify-between items-center z-10">
                    <h3 id="modalTitle" class="text-lg font-bold text-gray-900"></h3>
                    <button id="closeModalBtn" class="text-2xl text-gray-500 hover:text-gray-800">×</button>
                </div>

                <div class="p-5">
                    <!-- Holiday name -->
                    <div id="ferieDisplay" class="mb-4 p-3 bg-red-50 text-red-700 rounded-lg border border-red-100 font-semibold text-center" style="display: none;">
                        🎉 <span id="ferieName"></span>
                    </div>

                    <div class="flex justify-between items-center mb-4">
                        <h4 class="text-gray-700 font-medium">Réunions du jour</h4>
                    </div>
                    
                    <!-- Time slots container -->
                    <div class="space-y-2 mb-4">
                        <div id="timeSlotsContainer" class="h-96 overflow-y-auto border border-gray-200 rounded-lg relative bg-white">
                            <!-- Rows generated by JS -->
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Your Livewire / Blade reunion form component -->
        <x-reunion-form />
    </div>
</div>