class CalendarApp {
    constructor() {
        // État
        this.currentYear = new Date().getFullYear();
        this.currentMonth = new Date().getMonth() + 1;
        this.monthNames = ['', 'Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin', 'Juillet', 'Août', 'Septembre', 'Octobre', 'Novembre', 'Décembre'];
        this.weekdays = ['Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam', 'Dim'];

        this.feries = {
            '01-01': 'Jour de l\'An', '20-03': 'Indépendance', '09-04': 'Martyrs',
            '01-05': 'Fête du Travail', '25-07': 'République', '13-08': 'Fête de la Femme',
            '15-10': 'Évacuation', '17-12': 'Révolution',
        };

        this.refDates = {
            2025: { fitr: '2025-03-31', kebir: '2025-06-06', mawlid: '2025-09-04' },
            2026: { fitr: '2026-03-20', kebir: '2026-05-26', mawlid: '2026-08-26' },
            2027: { fitr: '2027-03-10', kebir: '2027-05-16', mawlid: '2027-08-15' },
        };

        this.selectedOrg = '';
        this.organisations = [];
        this.userRole = window.Laravel?.userRole || 'membre';
        this.isAdmin = window.Laravel?.isAdmin || false;
        this.canCreate = window.Laravel?.canCreate || false;
        this.events = [];
        this.allFeries = {};
        this.dayEvents = [];
        this.timeSlots = [];
        this.selectedDateStr = '';
        this.selectionStart = null;
        this.selectionEnd = null;

        this.modalOpen = false;
        this.currentModalDay = null;
        this.currentModalMonth = null;
        this.currentModalYear = null;

        this.init();
    }

    init() {
        this.cacheElements();
        this.populateYearRange();
        this.populateMonthSelect();
        this.populateOrgSelect();
        this.setupEventListeners();
        this.fetchOrganisations();
        this.fetchEvents();

        // Support URL param ?date=...
        const params = new URLSearchParams(location.search);
        const dateParam = params.get('date');
        if (dateParam) {
            const [y, m, d] = dateParam.split('-').map(Number);
            this.currentYear = y;
            this.currentMonth = m;
            this.targetDateToOpen = dateParam;
            history.replaceState({}, '', location.pathname);
        }

        this.renderCalendar();
    }

    cacheElements() {
        this.els = {
            todayDisplay: document.getElementById('todayDisplay'),
            adminFilters: document.getElementById('adminFilters'),
            orgSelect: document.getElementById('orgSelect'),
            monthSelect: document.getElementById('monthSelect'),
            yearSelect: document.getElementById('yearSelect'),
            prevMonthBtn: document.getElementById('prevMonthBtn'),
            nextMonthBtn: document.getElementById('nextMonthBtn'),
            prevMonthName: document.getElementById('prevMonthName'),
            nextMonthName: document.getElementById('nextMonthName'),
            calendarDays: document.getElementById('calendarDays'),
            reunionModal: document.getElementById('reunionModal'),
            modalTitle: document.getElementById('modalTitle'),
            ferieDisplay: document.getElementById('ferieDisplay'),
            ferieNameEl: document.getElementById('ferieName'),
            timeSlotsContainer: document.getElementById('timeSlotsContainer'),
            closeModalBtn: document.getElementById('closeModalBtn'),
        };
    }

    setupEventListeners() {
        this.els.prevMonthBtn?.addEventListener('click', () => this.prevMonth());
        this.els.nextMonthBtn?.addEventListener('click', () => this.nextMonth());

        this.els.monthSelect?.addEventListener('change', e => {
            this.currentMonth = parseInt(e.target.value);
            this.renderCalendar();
        });

        this.els.yearSelect?.addEventListener('change', e => {
            this.currentYear = parseInt(e.target.value);
            this.fetchEvents();
        });

        this.els.orgSelect?.addEventListener('change', e => {
            this.selectedOrg = e.target.value;
            this.fetchEvents();
        });

        this.els.closeModalBtn?.addEventListener('click', () => this.closeModal());
        this.els.reunionModal?.addEventListener('click', e => {
            if (e.target === this.els.reunionModal) this.closeModal();
        });

        // Livewire / custom event
        window.addEventListener('reunion-created', () => {
            setTimeout(() => this.fetchEvents(), 600);
        });
        window.addEventListener('reunion-updated', () => {
            setTimeout(() => this.fetchEvents(), 600);
        });
        window.addEventListener('reunion-deleted', () => {
            if (this.els.reunionModal) this.els.reunionModal.style.display = 'none';
            setTimeout(() => this.fetchEvents(), 600);
        });
    }

    // ────────────────────────────────────────────────────────────────
    //  Fetch & Data
    // ────────────────────────────────────────────────────────────────

    async fetchOrganisations() {
        try {
            const res = await fetch('/organisations/data');
            this.organisations = await res.json();
            this.populateOrgSelect();
        } catch (err) {
            console.error(err);
        }
    }

    async fetchEvents() {
        try {
            let url = `/reunions/list?start=${this.currentYear}-01-01&end=${this.currentYear}-12-31`;
            if (this.selectedOrg) url += `&organisation_id=${this.selectedOrg}`;

            const res = await fetch(url);
            this.events = await res.json();

            // FIX: Update allFeries before rendering calendar
            this.allFeries = { ...this.feries, ...this.getFeriesMobiles(this.currentYear) };
            this.renderCalendar();

            if (this.targetDateToOpen) {
                const [y, m, d] = this.targetDateToOpen.split('-').map(Number);
                const key = `${String(m).padStart(2, '0')}-${String(d).padStart(2, '0')}`;
                const ferie = this.allFeries[key] || '';
                setTimeout(() => this.openDayModal(d, m, y, ferie), 100);
                this.targetDateToOpen = null;
            }
        } catch (err) {
            console.error(err);
        }
    }

    // ────────────────────────────────────────────────────────────────
    //  Select population
    // ────────────────────────────────────────────────────────────────

    populateOrgSelect() {
        if (!this.els.orgSelect) return;

        this.els.orgSelect.innerHTML = '';

        // Always add "All" first
        const allOption = document.createElement('option');
        allOption.value = '';
        allOption.textContent = 'Toutes les organisations';
        this.els.orgSelect.appendChild(allOption);

        // Add organizations only if user is admin
        if (this.isAdmin && this.organisations.length > 0) {
            this.organisations.forEach(org => {
                const opt = document.createElement('option');
                opt.value = org.id;
                opt.textContent = org.nom;
                this.els.orgSelect.appendChild(opt);
            });
        }

        // Reset selectedOrg
        this.selectedOrg = '';
    }

    populateYearRange() {
        const current = new Date().getFullYear();
        const years = Array.from({ length: 11 }, (_, i) => current - 5 + i);

        if (!this.els.yearSelect) return;
        this.els.yearSelect.innerHTML = '';
        years.forEach(y => {
            const opt = document.createElement('option');
            opt.value = y;
            opt.textContent = y;
            if (y === this.currentYear) opt.selected = true;
            this.els.yearSelect.appendChild(opt);
        });
    }

    populateMonthSelect() {
        if (!this.els.monthSelect) return;
        this.els.monthSelect.innerHTML = '';
        this.monthNames.forEach((name, i) => {
            if (i === 0) return;
            const opt = document.createElement('option');
            opt.value = i;
            opt.textContent = name;
            if (i === this.currentMonth) opt.selected = true;
            this.els.monthSelect.appendChild(opt);
        });
    }

    // ────────────────────────────────────────────────────────────────
    //  Navigation
    // ────────────────────────────────────────────────────────────────

    prevMonth() {
        this.currentMonth--;
        if (this.currentMonth < 1) {
            this.currentMonth = 12;
            this.currentYear--;
            this.fetchEvents();
        } else {
            this.renderCalendar();
        }
        this.updateNavLabels();
    }

    nextMonth() {
        this.currentMonth++;
        if (this.currentMonth > 12) {
            this.currentMonth = 1;
            this.currentYear++;
            this.fetchEvents();
        } else {
            this.renderCalendar();
        }
        this.updateNavLabels();
    }

    updateNavLabels() {
        this.els.prevMonthName.textContent = this.monthNames[this.currentMonth === 1 ? 12 : this.currentMonth - 1].substring(0, 3);
        this.els.nextMonthName.textContent = this.monthNames[this.currentMonth === 12 ? 1 : this.currentMonth + 1].substring(0, 3);
        this.els.monthSelect.value = this.currentMonth;
        this.els.yearSelect.value = this.currentYear;
    }

    // ────────────────────────────────────────────────────────────────
    //  Calendar rendering
    // ────────────────────────────────────────────────────────────────

    renderCalendar() {
        if (!this.els.calendarDays) return;
        this.els.calendarDays.innerHTML = '';

        const year = this.currentYear;
        const month = this.currentMonth - 1; // Convert to 0-based for Date functions
        const firstDay = new Date(year, month, 1).getDay() || 7;
        const daysInMonth = new Date(year, month + 1, 0).getDate();
        const today = new Date();
        const isCurrentMonth = today.getFullYear() === year && today.getMonth() === month;

        // FIX: Update allFeries before rendering
        this.allFeries = { ...this.feries, ...this.getFeriesMobiles(year) };

        // Empty cells avant le 1er
        for (let i = 1; i < firstDay; i++) {
            const empty = document.createElement('div');
            empty.className = 'py-3 bg-gray-50';
            this.els.calendarDays.appendChild(empty);
        }

        // Jours du mois
        for (let d = 1; d <= daysInMonth; d++) {
            const dateKey = `${year}-${String(this.currentMonth).padStart(2, '0')}-${String(d).padStart(2, '0')}`;
            const ferieKey = `${String(this.currentMonth).padStart(2, '0')}-${String(d).padStart(2, '0')}`;
            const ferieName = this.allFeries[ferieKey] || '';

            const isToday = isCurrentMonth && d === today.getDate();
            const isWeekend = new Date(year, month, d).getDay() % 6 === 0;

            let classes = 'py-3 cursor-pointer transition day-cell border-t border-r border-gray-200 relative min-h-[4rem] flex flex-col items-center justify-start pt-1';
            if (isToday) classes += ' bg-indigo-50 font-bold';
            else classes += ' bg-white';
            if (isWeekend) classes += ' text-red-700';
            if (ferieName) classes += ' ferie';

            const cell = document.createElement('div');
            cell.className = classes;
            cell.innerHTML = `<span>${d}</span>`;

            // Dots
            const dayEvents = this.events.filter(e => e.start.startsWith(dateKey));
            if (dayEvents.length > 0) {
                const dots = document.createElement('div');
                dots.className = 'flex gap-1 mt-1 flex-wrap justify-center px-1';
                dayEvents.forEach(e => {
                    const dot = document.createElement('span');
                    dot.className = `w-1.5 h-1.5 rounded-full bg-${this.getStatusColor(e.status)}`;
                    dots.appendChild(dot);
                });
                cell.appendChild(dots);
            }

            // FIX: Only add click handler if not a holiday
            if (!ferieName) {
                cell.addEventListener('click', () => this.openDayModal(d, this.currentMonth, year, ferieName));
            }

            this.els.calendarDays.appendChild(cell);
        }

        this.updateTodayDisplay();
        // FIX: Show admin filters only if admin has organizations
        this.els.adminFilters.style.display = this.isAdmin && this.organisations.length > 0 ? 'flex' : 'none';
        this.updateNavLabels();
    }

    updateTodayDisplay() {
        if (!this.els.todayDisplay) return;
        const today = new Date().toLocaleDateString('fr-FR', {
            weekday: 'long', year: 'numeric', month: 'long', day: 'numeric'
        });
        this.els.todayDisplay.textContent = today.charAt(0).toUpperCase() + today.slice(1);
    }

    exportReunions(format) {
        const url = new URL('/reunions/export', window.location.origin);
        url.searchParams.append('format', format);

        const orgSelect = document.getElementById('orgSelect');
        if (orgSelect && orgSelect.value) {
            url.searchParams.append('organisation_id', orgSelect.value);
        }

        window.location.href = url.toString();
    }

    // ────────────────────────────────────────────────────────────────
    //  Modal & Day view
    // ────────────────────────────────────────────────────────────────

    openDayModal(day, month, year, ferieName = '') {
        this.currentModalDay = day;
        this.currentModalMonth = month;
        this.currentModalYear = year;

        this.selectedDateStr = `${year}-${String(month).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
        this.dayEvents = this.events.filter(e => e.start.startsWith(this.selectedDateStr));
        this.timeSlots = this.generateTimeSlots(this.selectedDateStr);

        this.els.modalTitle.textContent = `${String(day).padStart(2, '0')} ${this.monthNames[month]} ${year}`;

        if (ferieName) {
            this.els.ferieNameEl.textContent = ferieName;
            this.els.ferieDisplay.style.display = 'block';
        } else {
            this.els.ferieDisplay.style.display = 'none';
        }

        this.renderTimeSlots();
        this.els.reunionModal.style.display = 'flex';
    }

    closeModal() {
        this.els.reunionModal.style.display = 'none';
        this.selectionStart = this.selectionEnd = null;
    }

    generateTimeSlots(dateStr) {
        const slots = [];
        for (let h = 7; h <= 20; h++) {
            for (let m = 0; m < 60; m += 5) {
                const time = `${String(h).padStart(2, '0')}:${String(m).padStart(2, '0')}`;
                slots.push({
                    time,
                    minute: m,
                    fullDateTime: `${dateStr}T${time}:00`
                });
            }
        }
        return slots;
    }

    renderTimeSlots() {
        if (!this.els.timeSlotsContainer) return;
        this.els.timeSlotsContainer.innerHTML = '';

        this.timeSlots.forEach(slot => {
            const row = document.createElement('div');
            row.className = 'flex border-b border-gray-100 min-h-[24px]';

            // Time column
            const timeCol = document.createElement('div');
            timeCol.className = 'w-16 flex-shrink-0 text-[10px] text-gray-500 bg-gray-50 flex items-start justify-center pt-1 border-r border-gray-200 select-none cursor-pointer hover:bg-indigo-50 transition-colors';
            timeCol.textContent = slot.minute % 30 === 0 ? slot.time : slot.time;
            if (slot.minute % 30 !== 0) timeCol.style.opacity = '0.5';

            timeCol.addEventListener('click', () => this.handleTimeClick(slot.fullDateTime));

            // Events column
            const eventsCol = document.createElement('div');
            eventsCol.className = 'flex-1 relative bg-white group hover:bg-gray-50 transition-colors cursor-pointer';
            eventsCol.addEventListener('click', () => this.handleTimeClick(slot.fullDateTime));

            const eventsHere = this.getEventsInSlot(slot.fullDateTime);
            eventsHere.forEach(ev => {
                const block = document.createElement('div');
                block.className = `mx-1 relative z-10 bg-${this.getStatusColor(ev.status).replace('500', '100').replace('600', '100')} border-${this.getStatusColor(ev.status)}`;

                if (ev.isStart) {
                    block.classList.add('mt-0.5', 'rounded-t', 'border-t', 'border-x', 'p-1');
                } else {
                    block.classList.add('border-x');
                }
                if (ev.isEnd) {
                    block.classList.add('mb-0.5', 'rounded-b', 'border-b');
                }

                if (ev.isStart) {
                    block.innerHTML = `
                        <div class="flex justify-between items-start h-full">
                            <div class="flex-1 overflow-y-auto px-1 py-0.5">
                                <div class="font-bold text-gray-800 text-[10px] leading-tight">${ev.title}</div>
                                <div class="text-[9px] text-gray-600">
                                    ${this.formatTime(ev.start)} - ${this.formatTime(ev.end)}
                                </div>
                            </div>
                            <div class="flex flex-col gap-0.5 items-center mr-0.5 mt-0.5 bg-white/50 rounded p-0.5 backdrop-blur-sm self-start shrink-0">
                                ${ev.can_edit ? `
                                    <button class="edit-btn text-blue-600 hover:text-blue-800 transition-colors" title="Modifier">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                    </button>
                                ` : ''}
                                ${ev.can_delete ? `
                                    <button class="delete-btn text-red-600 hover:text-red-800 transition-colors" title="Supprimer">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                    </button>
                                ` : ''}
                            </div>
                        </div>
                    `;

                    // Add event listeners (assuming they are already being queried below or I should re-add them)
                    setTimeout(() => {
                        const editBtn = block.querySelector('.edit-btn');
                        const deleteBtn = block.querySelector('.delete-btn');

                        if (editBtn) {
                            editBtn.addEventListener('click', (e) => {
                                e.stopPropagation();
                                window.reunionFormInstance?.open(null, null, ev);
                            });
                        }

                        if (deleteBtn) {
                            deleteBtn.addEventListener('click', (e) => {
                                e.stopPropagation();
                                if (confirm('Supprimer cette réunion ?')) {
                                    window.reunionFormInstance?.delete(ev.id);
                                }
                            });
                        }
                    }, 0);
                } else {
                    block.innerHTML = `
                        <div class="h-full flex items-center justify-center">
                            <div class="w-full h-1 opacity-20 bg-${this.getStatusColor(ev.status)}"></div>
                        </div>
                    `;
                }
                eventsCol.appendChild(block);
            });

            row.appendChild(timeCol);
            row.appendChild(eventsCol);
            this.els.timeSlotsContainer.appendChild(row);
        });
    }

    getEventsInSlot(slotDateTime) {
        const slotStart = new Date(slotDateTime);
        const slotEnd = new Date(slotStart.getTime() + 5 * 60 * 1000);

        return this.dayEvents
            .filter(e => {
                const s = new Date(e.start);
                const end = e.end ? new Date(e.end) : new Date(s.getTime() + 30 * 60 * 1000);
                return s < slotEnd && end > slotStart;
            })
            .map(e => {
                const s = new Date(e.start);
                const end = e.end ? new Date(e.end) : new Date(s.getTime() + 30 * 60 * 1000);
                return {
                    ...e,
                    isStart: slotStart <= s && s < slotEnd,
                    isEnd: slotStart <= new Date(end.getTime() - 1) && new Date(end.getTime() - 1) < slotEnd
                };
            });
    }

    handleTimeClick(dateTime) {
        if (!this.canCreate) return;

        if (!this.selectionStart) {
            this.selectionStart = dateTime;
        } else {
            let start = this.selectionStart;
            let end = dateTime;
            if (new Date(end) < new Date(start)) [start, end] = [end, start];

            if (start === end) {
                const d = new Date(start);
                d.setMinutes(d.getMinutes() + 30);
                end = d.toISOString().slice(0, 16);
            }

            // Ouvre le formulaire (Livewire / custom)
            window.openReunionModal?.(start.slice(0, 16), end.slice(0, 16));
            this.selectionStart = this.selectionEnd = null;
        }
    }

    getStatusColor(status) {
        const map = {
            brouillon: 'gray-400',
            planifiee: 'blue-500',
            en_cours: 'green-500',
            terminee: 'indigo-600',
            annulee: 'red-500'
        };
        return map[status] || 'blue-500';
    }

    formatTime(datetime) {
        const d = new Date(datetime);
        return `${String(d.getHours()).padStart(2, '0')}:${String(d.getMinutes()).padStart(2, '0')}`;
    }

    getFeriesMobiles(year) {
        // (même logique que l'original – estimation des fêtes mobiles)
        const mobiles = {};
        let refYear = year <= 2025 ? 2025 : 2026;
        const ref = this.refDates[refYear];

        const estimate = (refDateStr) => {
            const diff = year - refYear;
            const refDate = new Date(refDateStr);
            const est = new Date(refDate.getTime() + diff * 10.875 * 86400000);
            return est;
        };

        const add = (name, date) => {
            const key = `${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')}`;
            mobiles[key] = name;
        };

        const fitr = estimate(ref.fitr);
        add('Aïd el-Fitr', fitr);
        add('Aïd el-Fitr (2)', new Date(fitr.getTime() + 86400000));

        const kebir = estimate(ref.kebir);
        add('Aïd el-Kébir', kebir);
        add('Aïd el-Kébir (2)', new Date(kebir.getTime() + 86400000));

        add('Mawlid', estimate(ref.mawlid));

        return mobiles;
    }
}

// Lancement
document.addEventListener('DOMContentLoaded', () => {
    window.calendar = new CalendarApp();
});
