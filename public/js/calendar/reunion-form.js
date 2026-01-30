// public/js/calendar/reunion-form.js
class ReunionForm {
    constructor() {
        this.show = false;
        this.types = [];
        this.statuses = [];
        this.organisations = [];
        this.userInfo = {};
        this.errorDate = false;

        this.formData = {
            id: null,
            objet: '',
            description: '',
            date_debut: '',
            date_fin: '',
            type: '',
            statut: '',
            organisation_id: '',
            participants: []
        };

        this.init();
    }

    init() {
        window.openReunionModal = (start, end) => this.open(start, end);
        // Ensure DOM is ready before caching elements
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => this.cacheAndSetup());
        } else {
            this.cacheAndSetup();
        }
    }

    cacheAndSetup() {
        this.els = {
            modal: document.getElementById('reunionFormModal'),
            form: document.getElementById('reunionForm'),
            title: document.getElementById('formTitle'),
            submitText: document.getElementById('submitText'),
            loadingSpinner: document.getElementById('loadingSpinner'),
            orgSelector: document.getElementById('orgSelector'),
            orgSelect: document.getElementById('orgSelectForm'),
            participantsList: document.getElementById('participantsList'),
            emailInput: document.getElementById('formData.newParticipantEmail'),
            dateError: document.getElementById('dateError')
        };

        this.setupEventListeners();
        this.fetchOptions();
    }

    setupEventListeners() {
        // Sync all fields with formData using extracted helper
        const inputs = [
            'objet', 'description', 'date_debut', 'date_fin', 'type', 'statut'
        ];
        this.setupFieldListeners(inputs);

        // Organisation sync
        if (this.els.orgSelect) {
            this.els.orgSelect.addEventListener('change', (e) => {
                this.formData.organisation_id = e.target.value;
            });
        }

        // Email input enter key
        if (this.els.emailInput) {
            this.els.emailInput.addEventListener('keydown', (e) => {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    this.addParticipant();
                }
            });
        }

        // Form submit
        if (this.els.form) {
            this.els.form.addEventListener('submit', (e) => {
                e.preventDefault();
                this.submit();
            });
        }
    }

    async fetchOptions() {
        try {
            const res = await fetch('/reunion-options');
            const data = await res.json();
            this.types = data.types || [];
            this.statuses = data.statuses || [];
            this.organisations = data.organisations || [];
            this.userInfo = data.user_info || {};

            this.updateFormOptions();
        } catch (e) {
            console.error('Error fetching options:', e);
        }
    }

    updateFormOptions() {
        const typeSelect = document.getElementById('formData.type');
        if (typeSelect && this.types.length > 0) {
            typeSelect.innerHTML = this.createSelectOptions(this.types, this.formData.type);
        }

        const statusSelect = document.getElementById('formData.statut');
        if (statusSelect && this.statuses.length > 0) {
            statusSelect.innerHTML = this.createSelectOptions(this.statuses, this.formData.statut);
        }

        if (this.els.orgSelect && this.organisations.length > 0) {
            this.els.orgSelector.style.display = 'block';
            const current = this.formData.organisation_id;
            this.els.orgSelect.innerHTML = '<option value="">Sélectionner une organisation...</option>' +
                this.organisations.map(o =>
                    `<option value="${o.id}" ${o.id == current ? 'selected' : ''}>${o.nom}</option>`
                ).join('');
        }
    }

    // ────────────────────────────────────────────────────────────────
    //  Helper Methods
    // ────────────────────────────────────────────────────────────────

    /**
     * Get CSRF token with error handling
     */
    getCsrfToken() {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
        if (!csrfToken) {
            console.error('CSRF token not found');
            alert('Erreur: Jeton de sécurité manquant. Veuillez rafraîchir la page.');
            return null;
        }
        return csrfToken;
    }

    /**
     * Create select options HTML
     */
    createSelectOptions(options, currentValue = '') {
        return options.map(option =>
            `<option value="${option.id}" ${option.id === currentValue ? 'selected' : ''}>${option.label}</option>`
        ).join('');
    }

    /**
     * Setup form field event listeners
     */
    setupFieldListeners(fields) {
        fields.forEach(field => {
            const el = document.getElementById(`formData.${field}`);
            if (el) {
                el.addEventListener('change', (e) => {
                    this.formData[field] = e.target.value;
                    if (field === 'date_debut' || field === 'date_fin') {
                        this.validateDates();
                    }
                });
                el.addEventListener('input', (e) => {
                    this.formData[field] = e.target.value;
                });
            }
        });
    }

    /**
     * Make API request with common headers and error handling
     */
    async makeRequest(url, method, body = null) {
        const csrfToken = this.getCsrfToken();
        if (!csrfToken) return null;

        const options = {
            method,
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json'
            }
        };

        if (body) {
            options.headers['Content-Type'] = 'application/json';
            options.body = JSON.stringify(body);
        }

        try {
            const response = await fetch(url, options);
            return response;
        } catch (error) {
            console.error('Request failed:', error);
            throw error;
        }
    }

    /**
     * Handle API response with common error handling
     */
    handleApiResponse(response, successMessage) {
        if (response.ok) {
            return response.json().then(data => ({ success: true, data }));
        } else {
            return response.json().then(data => {
                const errorMessage = data.errors 
                    ? Object.values(data.errors).flat().join('\n')
                    : data.message || 'Erreur lors de l\'opération';
                return { success: false, error: errorMessage };
            });
        }
    }

    /**
     * Format date string consistently
     */
    formatDateTimeString(dateTime) {
        return dateTime ? dateTime.slice(0, 16).replace(' ', 'T') : '';
    }

    /**
     * Calculate end time (1 hour after start)
     */
    calculateEndTime(startTime) {
        return new Date(new Date(startTime).getTime() + 3600000).toISOString().slice(0, 16);
    }

    // ────────────────────────────────────────────────────────────────
    //  Form Methods
    // ────────────────────────────────────────────────────────────────

    validateDates() {
        const startEl = document.getElementById('formData.date_debut');
        const endEl = document.getElementById('formData.date_fin');

        if (this.formData.date_debut && this.formData.date_fin) {
            const start = new Date(this.formData.date_debut);
            const end = new Date(this.formData.date_fin);

            // Basic validation - backend will handle detailed validation
            this.errorDate = end <= start;
        } else {
            this.errorDate = false;
            this.sameDayError = false;
        }

        if (this.els.dateError) {
            if (this.errorDate) {
                this.els.dateError.textContent = 'La date de fin doit être après le début.';
                this.els.dateError.style.display = 'block';
            } else {
                this.els.dateError.style.display = 'none';
            }
        }
    }

    open(start = null, end = null, editData = null) {
        if (editData) {
            this.formData = {
                id: editData.id,
                objet: editData.title || editData.objet || '',
                description: editData.description || '',
                date_debut: this.formatDateTimeString(editData.start),
                date_fin: this.formatDateTimeString(editData.end),
                type: editData.type || this.userInfo.default_type || '',
                statut: editData.status || editData.statut || this.userInfo.default_status || '',
                organisation_id: editData.organisation_id || '',
                participants: editData.participants || []
            };
            this.els.title.textContent = 'Modifier la Réunion';
        } else {
            this.resetFormData();
            if (start) {
                this.formData.date_debut = start.slice(0, 16);
                this.formData.date_fin = end ? end.slice(0, 16) : this.calculateEndTime(start);
            }
            this.els.title.textContent = 'Nouvelle Réunion';
        }

        this.show = true;
        this.updateUIFromData();
        this.els.modal.style.display = 'flex';

        // Apply min date restriction from backend
        if (this.userInfo.min_date) {
            const dateInputs = ['date_debut', 'date_fin'].map(id => document.getElementById(`formData.${id}`));
            dateInputs.forEach(input => {
                if (input) input.min = this.userInfo.min_date;
            });
        }
    }

    updateUIFromData() {
        Object.keys(this.formData).forEach(key => {
            const el = document.getElementById(`formData.${key}`);
            if (el && key !== 'participants') {
                el.value = this.formData[key];
            }
        });

        if (this.els.orgSelect) {
            this.els.orgSelect.value = this.formData.organisation_id;
        }

        this.updateParticipantsDisplay();
        this.updateFormOptions();
        this.validateDates();
    }

    resetFormData() {
        this.formData = {
            id: null,
            objet: '',
            description: '',
            date_debut: '',
            date_fin: '',
            type: this.userInfo.default_type || '',
            statut: this.userInfo.default_status || '',
            organisation_id: '',
            participants: []
        };
    }

    close() {
        this.show = false;
        if (this.els.modal) this.els.modal.style.display = 'none';
        this.resetFormData();
    }

    addParticipant() {
        const email = this.els.emailInput.value.trim();

        // Basic UI validation - backend will handle detailed validation
        if (email && !this.formData.participants.includes(email)) {
            this.formData.participants.push(email);
            this.els.emailInput.value = '';
            this.updateParticipantsDisplay();
        }
    }

    removeParticipant(index) {
        this.formData.participants.splice(index, 1);
        this.updateParticipantsDisplay();
    }

    /**
     * Create participant display HTML
     */
    createParticipantDisplay(email, index) {
        return `
            <div class="flex items-center gap-1.5 bg-indigo-50 text-indigo-700 px-2 py-1 rounded-full text-xs font-medium border border-indigo-100 group">
                <span>${email}</span>
                <button type="button" onclick="reunionFormInstance.removeParticipant(${index})" class="hover:text-red-500 transition-colors">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>
        `;
    }

    updateParticipantsDisplay() {
        if (!this.els.participantsList) return;

        this.els.participantsList.innerHTML = this.formData.participants
            .map((email, index) => this.createParticipantDisplay(email, index))
            .join('');
    }

    async submit() {
        if (this.errorDate) {
            alert('Veuillez corriger les dates. La fin doit être après le début.');
            return;
        }

        this.setLoading(true);

        const isEdit = !!this.formData.id;
        const url = isEdit ? `/reunions/${this.formData.id}` : '/reunions';
        const method = isEdit ? 'PUT' : 'POST';

        try {
            const response = await this.makeRequest(url, method, this.formData);
            if (!response) return;

            const result = await this.handleApiResponse(response);
            
            if (result.success) {
                this.close();
                window.dispatchEvent(new CustomEvent(isEdit ? 'reunion-updated' : 'reunion-created'));
            } else {
                alert(result.error);
            }
        } catch (e) {
            console.error(e);
            alert('Erreur serveur');
        } finally {
            this.setLoading(false);
        }
    }

    async delete(id) {
        if (!confirm('Êtes-vous sûr de vouloir supprimer cette réunion ?')) return;

        try {
            const response = await this.makeRequest(`/reunions/${id}`, 'DELETE');
            if (!response) return;

            const result = await this.handleApiResponse(response);
            
            if (result.success) {
                window.dispatchEvent(new CustomEvent('reunion-deleted'));
            } else {
                alert(result.error);
            }
        } catch (e) {
            console.error(e);
            alert('Erreur serveur');
        }
    }

    setLoading(state) {
        if (this.els.submitText) this.els.submitText.style.display = state ? 'none' : 'inline';
        if (this.els.loadingSpinner) this.els.loadingSpinner.style.display = state ? 'inline-block' : 'none';
        const submitBtn = document.getElementById('submitBtn');
        if (submitBtn) submitBtn.disabled = state;
    }
}

// Global instance
window.reunionFormInstance = new ReunionForm();