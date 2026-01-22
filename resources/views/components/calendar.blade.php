<!-- 
    CALENDAR COMPONENT
    This component uses Alpine.js for a reactive, client-side calendar experience.
    The 'x-data="calendarApp()"' initializes the component with the JavaScript object 
    returned by the calendarApp() function defined in the <script> section.
-->
<div class="calendar-component" x-data="calendarApp()">
    <style>
        .day-cell:hover { transform: scale(1.03); transition: all 0.15s; }
        .modal { animation: fadeIn 0.2s ease-out; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(8px); } to { opacity: 1; transform: translateY(0); } }
        .time-slot:hover { transform: translateX(3px); background-color: #eef2ff; }
        .ferie { background-color: #fee2e2 !important; border-color: #ef4444; color: #991b1b; font-weight: 600; }
        
        /* Status Colors mapped from meeting statuses */
        .dot-brouillon { background-color: #9ca3af; } /* Gray 400 */
        .dot-planifiee { background-color: #3b82f6; } /* Blue 500 */
        .dot-en_cours { background-color: #22c55e; }  /* Green 500 */
        .dot-terminee { background-color: #4f46e5; }  /* Indigo 600 */
        .dot-annulee { background-color: #ef4444; }   /* Red 500 */
    </style>

    <div class=" flex items-center justify-center p-4">
        <div id="calendarContainer" class="w-full max-w-md bg-white rounded-2xl shadow-xl overflow-hidden border border-gray-200">
            
            <!-- 
                MONTHLY HEADER 
                Contains the current date display and administrative controls for filtering.
            -->
            <div class="bg-gray-50 px-4 py-3 border-b">
                <!-- x-text: Dynamically updates the text content from the getTodayDisplay() method -->
                <h2 class="text-xl font-bold text-center text-gray-800 mb-2" x-text="getTodayDisplay()"></h2>
                
                <!-- 
                    ADMIN FILTERS 
                    x-if: Only renders this block if the 'isAdmin' property is true.
                -->
                <template x-if="isAdmin">
                    <div class="flex justify-center mb-3">
                        <!-- x-model: Two-way data binding for the selected organization -->
                        <select x-model="selectedOrg" @change="fetchEvents()" class="px-3 py-1.5 bg-white border border-gray-300 rounded-lg shadow-sm text-sm w-64">
                            <option value="">Toutes les organisations</option>
                            <!-- x-for: Loops through 'organisations' array. :key provides a unique identifier for performance -->
                            <template x-for="org in organisations" :key="org.id">
                                <!-- :value binds the option value to org.id -->
                                <option :value="org.id" x-text="org.nom"></option>
                            </template>
                        </select>
                    </div>
                </template>

                <!-- NAVIGATION CONTROLS -->
                <div class="flex items-center justify-between gap-2 text-sm">
                    <!-- @click: Event listener shorthand for x-on:click -->
                    <button @click="prevMonth()" class="px-3 py-1.5 bg-white border border-gray-300 rounded-lg shadow-sm hover:bg-gray-50 transition">
                        ◄ <span x-text="monthNames[currentMonth === 1 ? 12 : currentMonth - 1].substring(0,3)"></span>
                    </button>
                    
                    <!-- Select bindings to current state -->
                    <select x-model="currentMonth" @change="renderCalendar()" class="px-3 py-1.5 bg-white border border-gray-300 rounded-lg shadow-sm">
                        <template x-for="(m, i) in monthNames" :key="i">
                            <option x-show="i > 0" :value="i" x-text="m" :selected="i == currentMonth"></option>
                        </template>
                    </select>
                    
                    <select x-model="currentYear" @change="fetchEvents()" class="px-3 py-1.5 bg-white border border-gray-300 rounded-lg shadow-sm">
                        <template x-for="y in yearRange" :key="y">
                            <option :value="y" x-text="y" :selected="y == currentYear"></option>
                        </template>
                    </select>
                    
                    <button @click="nextMonth()" class="px-3 py-1.5 bg-white border border-gray-300 rounded-lg shadow-sm hover:bg-gray-50 transition">
                        <span x-text="monthNames[currentMonth === 12 ? 1 : currentMonth + 1].substring(0,3)"></span> ►
                    </button>
                </div>
            </div>

            <!-- 
                MONTHLY GRID 
                Displays the days of the week and the individual day cells.
            -->
            <div class="grid grid-cols-7 gap-px bg-gray-200 text-center text-sm">
                <!-- Loop for weekdays header -->
                <template x-for="w in weekdays">
                    <div class="py-2 bg-gray-300 font-medium text-gray-800" x-text="w"></div>
                </template>

                <!-- 
                    DAY CELLS 
                    Dynamically generated based on the current month's layout.
                -->
                <template x-for="day in calendarDays" :key="day.id">
                    <!-- 
                        :class: Dynamically applies CSS classes defined in the JS 'day' object (e.g., today, weekend, ferie).
                        @click: Triggers modal opening for specific day details.
                    -->
                    <div :class="day.classes" 
                         @click="day.isEmpty ? null : openDayModal(day.day, currentMonth, currentYear, day.ferieName)">
                        <span x-text="day.day"></span>
                        
                        <!-- 
                            EVENT DOTS 
                            x-show: Only shows the container if the day has meetings.
                        -->
                        <div x-show="day.events.length" class="flex gap-1 mt-1 flex-wrap justify-center px-1">
                            <template x-for="e in day.events" :key="e.id">
                                <!-- :class binds background color based on meeting status -->
                                <span class="w-1.5 h-1.5 rounded-full" :class="'bg-' + getStatusColor(e.status)"></span>
                            </template>
                        </div>
                    </div>
                </template>
            </div>
        </div>

        <!-- 
            REUNION DETAIL MODAL (List View) 
            x-show: Controls visibility based on 'showModal' boolean.
            @click.away: Closes the modal when clicking outside of the white box.
        -->
        <div x-show="showModal" style="display: none;" class="fixed inset-0 bg-black/65 flex items-center justify-center z-50 p-4">
            <div @click.away="closeModal()" class="bg-white rounded-2xl shadow-2xl w-full max-w-md sm:max-w-lg max-h-[90vh] overflow-y-auto modal">
                <div class="sticky top-0 bg-white px-5 py-3 border-b flex justify-between items-center z-10">
                    <h3 class="text-lg font-bold text-gray-900" x-text="modalTitle"></h3>
                    <button @click="closeModal()" class="text-2xl text-gray-500 hover:text-gray-800">×</button>
                </div>

                <div class="p-5">
                    <!-- Display holiday name if applicable -->
                    <div x-show="ferieName" class="mb-4 p-3 bg-red-50 text-red-700 rounded-lg border border-red-100 font-semibold text-center">
                        🎉 <span x-text="ferieName"></span>
                    </div>

                    <div class="flex justify-between items-center mb-4">
                        <h4 class="text-gray-700 font-medium">Réunions du jour</h4>
                    </div>
                    
                    <!-- 
                        TIME SLOTS 
                        A scrollable timeline view of the day's events.
                    -->
                    <div class="space-y-2 mb-4">
                        <div class="h-96 overflow-y-auto border border-gray-200 rounded-lg relative bg-white">
                             <template x-for="slot in timeSlots" :key="slot.time">
                                <div class="flex border-b border-gray-100 min-h-[24px]">
                                    <!-- Time Column - Also clickable for time selection -->
                                    <div class="w-16 flex-shrink-0 text-[10px] text-gray-500 bg-gray-50 flex items-start justify-center pt-1 border-r border-gray-200 select-none cursor-pointer hover:bg-indigo-50 transition-colors"
                                         @click="handleTimeClick(slot.fullDateTime)"
                                         :class="isSlotSelected(slot.fullDateTime) ? 'bg-indigo-100 text-indigo-700' : ''">
                                        <span x-show="slot.minute % 30 === 0" x-text="slot.time" class="font-bold"></span>
                                        <span x-show="slot.minute % 30 !== 0" x-text="slot.time" class="opacity-50"></span>
                                    </div>
                                    
                                    <!-- 
                                        EVENTS COLUMN 
                                        Handles click-to-create meeting functionality with range selection.
                                    -->
                                    <div class="flex-1 relative bg-white group hover:bg-gray-50 transition-colors cursor-pointer"
                                         @click="handleTimeClick(slot.fullDateTime)"
                                         :class="isSlotSelected(slot.fullDateTime) ? 'bg-indigo-100' : ''">
                                        
                                        <!-- Visual selection indicator for ranges -->
                                        <div x-show="selectionStart && !selectionEnd && isSlotSelected(slot.fullDateTime)" 
                                             class="absolute inset-0 bg-indigo-200/50 pointer-events-none"></div>

                                        <!-- Render actual event blocks inside the timeline -->
                                        <template x-for="event in getEventsInSlot(slot.fullDateTime)" :key="event.id">
                                            <div class="mx-1 relative z-10"
                                                 :class="[
                                                    'bg-' + getStatusColor(event.status).replace('500','100').replace('600','100'),
                                                    'border-' + getStatusColor(event.status),
                                                    event.isStart ? 'mt-0.5 rounded-t border-t border-x p-1' : 'border-x',
                                                    event.isEnd ? 'mb-0.5 rounded-b border-b' : ''
                                                 ]">
                                                <template x-if="event.isStart">
                                                    <div>
                                                        <div class="font-bold text-gray-800 text-[10px] leading-tight" x-text="event.title"></div>
                                                        <div class="text-[9px] text-gray-600">
                                                            <span x-text="formatTime(event.start)"></span> - <span x-text="formatTime(event.end)"></span>
                                                        </div>
                                                    </div>
                                                </template>
                                                <template x-if="!event.isStart">
                                                    <div class="h-full flex items-center justify-center">
                                                        <div class="w-full h-1 opacity-20" :class="'bg-' + getStatusColor(event.status)"></div>
                                                    </div>
                                                </template>
                                            </div>
                                        </template>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- REUSABLE FORM COMPONENT: Injected here to share Alpine scope if needed, though they usually communicate via events -->
        <x-reunion-form />
    </div>

    <!-- 
        ALPINE.JS CONTROLLER 
        This script returns the state and methods that drive the calendar UI.
    -->
    <script>
        document.addEventListener('alpine:init', () => {
             // Listener for global events if needed
             window.addEventListener('open-create-modal', event => {
                // Implementation for remote triggering of the creation form
             });
        });

        function calendarApp() {
            return {
                // INTERNAL STATE
                currentYear: new Date().getFullYear(),
                currentMonth: new Date().getMonth() + 1,
                monthNames: ['', 'Janvier','Février','Mars','Avril','Mai','Juin','Juillet','Août','Septembre','Octobre','Novembre','Décembre'],
                weekdays: ['Lun','Mar','Mer','Jeu','Ven','Sam','Dim'],
                
                // STATIC DATA: Holidays and reference dates for lunar calculation
                feries: {
                    '01-01': 'Jour de l’An', '20-03': 'Indépendance', '09-04': 'Martyrs',
                    '01-05': 'Fête du Travail', '25-07': 'République', '13-08': 'Fête de la Femme',
                    '15-10': 'Évacuation', '17-12': 'Révolution',
                },
                refDates: {
                    2025: { fitr: '2025-03-31', kebir: '2025-06-06', mawlid: '2025-09-04' },
                    2026: { fitr: '2026-03-20', kebir: '2026-05-26', mawlid: '2026-08-26' },
                },
                
                // REACTIVE STATE
                selectedOrg: '',
                organisations: [],
                userRole: '{{ Auth::user()->getAttributes()['role'] ?? 'membre' }}',
                isAdmin: {{ (Auth::user()->hasRole('admin') || Auth::user()->role_id == 1) ? 'true' : 'false' }},
                canCreate: {{ (Auth::user()->hasRole('admin') || Auth::user()->role_id == 1 || (Auth::user()->getAttributes()['role'] ?? '') === 'chef_organisation') ? 'true' : 'false' }},
                events: [],
                allFeries: {},
                showModal: false,
                modalTitle: '',
                ferieName: '',
                dayEvents: [],
                calendarDays: [],
                yearRange: [],
                targetDateToOpen: null, // Used to auto-open a specific date from URL
                slotEventsMap: {}, // Pre-computed events per time slot to prevent re-render issues

                // Lifecycle method: Called automatically by Alpine on startup
                init() {
                    // 1. Prepare year selection based on current date
                    const y = new Date().getFullYear();
                    for(let i = y-5; i <= y+5; i++) this.yearRange.push(i);

                    // 2. Check for date query parameter to navigate to specific date
                    const urlParams = new URLSearchParams(window.location.search);
                    const targetDate = urlParams.get('date');
                    if (targetDate) {
                        const parts = targetDate.split('-');
                        if (parts.length === 3) {
                            this.currentYear = parseInt(parts[0]);
                            this.currentMonth = parseInt(parts[1]);
                            this.targetDateToOpen = targetDate; // Store for after events load
                        }
                        // Clean URL without reload
                        window.history.replaceState({}, document.title, window.location.pathname);
                    }

                    // 3. Fetch data from backend
                    this.fetchOrganisations();
                    this.renderCalendar(); // Render grid skeleton immediately
                    this.fetchEvents();    // Load async meeting data
                    
                    // 4. Listen for success events from the form component
                    window.addEventListener('reunion-created', () => {
                        setTimeout(() => {
                            this.fetchEvents();
                            this.showModal = false; 
                        }, 300);
                    });
                },

                // Logic for selecting a time range in the modal to create a meeting
                handleTimeClick(dateTime) {
                    if (!this.canCreate) return; 
                    
                    if (!this.selectionStart) {
                        this.selectionStart = dateTime;
                    } else {
                        let start = this.selectionStart;
                        let end = dateTime;
                        
                        // Ensure chronological order
                        if (new Date(end) < new Date(start)) [start, end] = [end, start];

                        // Set minimum 5min duration if same slot clicked
                        if (start === end) {
                            const endDate = new Date(new Date(end).getTime() + 5 * 60000);
                            end = endDate.toISOString().split('.')[0]; // Sync format
                        }

                        this.openReunionFormWithRange(start, end);
                        
                        // Reset local selection state
                        this.selectionStart = null;
                        this.selectionEnd = null;
                    }
                },

                // Helper to check if a specific time slot should be highlighted during drag/selection
                isSlotSelected(dateTime) {
                    if (!this.selectionStart) return false;
                    const start = new Date(this.selectionStart);
                    const current = new Date(dateTime);
                    
                    if (!this.selectionEnd) return dateTime === this.selectionStart;
                    
                    const end = new Date(this.selectionEnd);
                    const [low, high] = start < end ? [start, end] : [end, start];
                    return current >= low && current <= high;
                },

                // Triggers the reusable form component (defined in x-reunion-form)
                openReunionFormWithRange(start, end) {
                    if (window.openReunionModal) {
                        window.openReunionModal(start, end);
                    }
                },

                // Generates 5-minute intervals for the day's timeline
                generateTimeSlots(dateStr) {
                    const slots = [];
                    for (let h = 7; h <= 20; h++) { 
                        for (let m = 0; m < 60; m += 5) {
                            const timeStr = `${String(h).padStart(2,'0')}:${String(m).padStart(2,'0')}`;
                            const fullDateTime = `${dateStr}T${timeStr}:00`; 
                            slots.push({ time: timeStr, hour: h, minute: m, fullDateTime: fullDateTime });
                        }
                    }
                    return slots;
                },

                // Calculates which meetings overlap with a specific 5-minute visual slot
                getEventsInSlot(slotDateTime) {
                    const slotStart = new Date(slotDateTime);
                    const slotEnd = new Date(slotStart.getTime() + 5 * 60000);
                    
                    return this.dayEvents.filter(e => {
                        const eventStart = new Date(e.start);
                        const eventEnd = e.end ? new Date(e.end) : new Date(eventStart.getTime() + 30 * 60000);
                        return eventStart < slotEnd && eventEnd > slotStart;
                    }).map(e => {
                        const eventStart = new Date(e.start);
                        const eventEnd = e.end ? new Date(e.end) : new Date(eventStart.getTime() + 30 * 60000);
                        return {
                            ...e,
                            isStart: slotStart.getTime() <= eventStart.getTime() && eventStart.getTime() < slotEnd.getTime(),
                            isEnd: slotStart.getTime() <= (eventEnd.getTime() - 1) && (eventEnd.getTime() - 1) < slotEnd.getTime()
                        };
                    });
                },

                // Formatting for the main header
                getTodayDisplay() {
                    const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
                    const date = new Date().toLocaleDateString('fr-FR', options);
                    return date.charAt(0).toUpperCase() + date.slice(1);
                },

                // Color mapping for TailWind CSS classes
                getStatusColor(status) {
                    const colors = {
                        'brouillon': 'gray-400',
                        'planifiee': 'blue-500', 
                        'en_cours': 'green-500',
                        'terminee': 'indigo-600',
                        'annulee': 'red-500'
                    };
                    return colors[status] || 'blue-500';
                },

                // BACKEND API CALLS
                async fetchOrganisations() {
                    try {
                        const res = await fetch('/organisations/data');
                        this.organisations = await res.json();
                    } catch(e) { console.error('Error fetching orgs', e); }
                },

                async fetchEvents() {
                    try {
                        const start = `${this.currentYear}-01-01`;
                        const end = `${this.currentYear}-12-31`;
                        let url = `/reunions/list?start=${start}&end=${end}`;
                        if (this.selectedOrg) url += `&organisation_id=${this.selectedOrg}`;
                        const res = await fetch(url);
                        this.events = await res.json();
                        this.renderCalendar();
                        
                        // Auto-open modal for target date from URL (e.g., from notification click)
                        if (this.targetDateToOpen) {
                            const parts = this.targetDateToOpen.split('-');
                            if (parts.length === 3) {
                                const day = parseInt(parts[2]);
                                const month = parseInt(parts[1]);
                                const year = parseInt(parts[0]);
                                const key = `${String(month).padStart(2,'0')}-${String(day).padStart(2,'0')}`;
                                const ferieName = this.allFeries[key] || '';
                                
                                // Small delay to ensure DOM is ready
                                setTimeout(() => {
                                    this.openDayModal(day, month, year, ferieName);
                                }, 100);
                            }
                            this.targetDateToOpen = null; // Clear after use
                        }
                    } catch(e) { console.error(e); }
                },

                // ISLAMIC CALENDAR ESTIMATION (For Tunisian Holidays)
                estimateIslamicDate(year, refYear, refDateStr, daysPerYear = 10.875) {
                    const diffYears = year - refYear;
                    const refDate = new Date(refDateStr);
                    const estimatedMs = refDate.getTime() + diffYears * daysPerYear * 24 * 60 * 60 * 1000;
                    const estDate = new Date(estimatedMs);
                    estDate.setDate(estDate.getDate() + Math.round(diffYears * 0.1));
                    return estDate;
                },

                getFeriesMobiles(year) {
                    const mobiles = {};
                    let refYear = 2026;
                    let ref = this.refDates[2026];
                    if (year <= 2025) { refYear = 2025; ref = this.refDates[2025]; }

                    const add = (name, d) => {
                        const key = `${String(d.getMonth()+1).padStart(2,'0')}-${String(d.getDate()).padStart(2,'0')}`;
                        mobiles[key] = name;
                    };

                    let fitr = this.estimateIslamicDate(year, refYear, ref.fitr);
                    add('Aïd el-Fitr', fitr);
                    let fitr2 = new Date(fitr); fitr2.setDate(fitr.getDate()+1); add('Aïd el-Fitr (2ᵉ jour)', fitr2);
                    let fitr3 = new Date(fitr); fitr3.setDate(fitr.getDate()+2); 
                    if(fitr3.getFullYear() === year) add('Aïd el-Fitr (3ᵉ jour)', fitr3);

                    let kebir = this.estimateIslamicDate(year, refYear, ref.kebir);
                    add('Aïd el-Kébir', kebir);
                    let kebir2 = new Date(kebir); kebir2.setDate(kebir.getDate()+1); add('Aïd el-Kébir (2ᵉ jour)', kebir2);

                    let mawlid = this.estimateIslamicDate(year, refYear, ref.mawlid);
                    add('Mawlid al-Nabi', mawlid);

                    return mobiles;
                },

                formatTime(isoStr) {
                    if(!isoStr) return '';
                    const date = new Date(isoStr);
                    return date.toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' });
                },

                // Prepares and shows the specific day details
                openDayModal(day, month, year, ferieName) {
                    this.selectedDateStr = `${year}-${String(month).padStart(2,'0')}-${String(day).padStart(2,'0')}`;
                    this.ferieName = ferieName;
                    this.modalTitle = `${String(day).padStart(2,'0')} ${this.monthNames[month]} ${year}`;
                    
                    // Filter events for this day
                    this.dayEvents = this.events.filter(e => e.start.startsWith(this.selectedDateStr));
                    this.timeSlots = this.generateTimeSlots(this.selectedDateStr);
                    this.showModal = true;
                },

                closeModal() {
                    this.showModal = false;
                },

                // CALENDAR GENERATION LOGIC
                changeMonth(m) { this.currentMonth = parseInt(m); this.renderCalendar(); },
                changeYear(y) { this.currentYear = parseInt(y); this.fetchEvents(); }, 
                prevMonth() {
                    this.currentMonth--;
                    if(this.currentMonth < 1) { this.currentMonth = 12; this.currentYear--; this.fetchEvents(); }
                    else { this.renderCalendar(); }
                },
                nextMonth() {
                    this.currentMonth++;
                    if(this.currentMonth > 12) { this.currentMonth = 1; this.currentYear++; this.fetchEvents(); }
                    else { this.renderCalendar(); }
                },

                // The heart of the grid logic: calculates where days fall on the L-M-M-J-V-S-D grid
                renderCalendar() {
                    const year = this.currentYear;
                    const month = this.currentMonth;
                    
                    const date = new Date(year, month - 1, 1);
                    const firstWeekday = date.getDay() === 0 ? 7 : date.getDay(); // Adjust for Monday start
                    const daysInMonth = new Date(year, month, 0).getDate();
                    const today = new Date();
                    const isTodayMonth = (month === today.getMonth() + 1 && year === today.getFullYear());
                    const todayDay = today.getDate();
                    
                    this.allFeries = { ...this.feries, ...this.getFeriesMobiles(year) };
                    const days = [];

                    // 1. Create empty padding cells at the start of the month
                    for (let i = 1; i < firstWeekday; i++) {
                        days.push({ id: 'pad-' + i, isEmpty: true, classes: 'py-3 bg-gray-50' });
                    }

                    // 2. Create actual day objects with styling and meeting data
                    for (let day = 1; day <= daysInMonth; day++) {
                        const key = `${String(month).padStart(2,'0')}-${String(day).padStart(2,'0')}`;
                        const ferieName = this.allFeries[key] || '';
                        const isFerie = !!ferieName;
                        const isToday = isTodayMonth && day === todayDay;
                        const isWeekend = (new Date(year, month-1, day).getDay() % 7) >= 5;
                        const dateStr = `${year}-${String(month).padStart(2,'0')}-${String(day).padStart(2,'0')}`;
                        
                        let classes = 'py-3 cursor-pointer transition day-cell border-t border-r border-gray-200 relative min-h-[4rem] flex flex-col items-center justify-start pt-1';
                        if (isToday) classes += ' bg-indigo-50 font-bold';
                        else classes += ' bg-white';
                        if (isWeekend) classes += ' text-red-700';
                        if (isFerie) classes += ' ferie';

                        days.push({
                            id: dateStr,
                            day: day,
                            dateStr: dateStr,
                            isFerie: isFerie,
                            ferieName: ferieName,
                            classes: classes,
                            // Direct filtering of meetings for this cell
                            events: this.events.filter(e => e.start.startsWith(dateStr))
                        });
                    }
                    
                    this.calendarDays = days;
                }
            }
        }
    </script>
</div>

