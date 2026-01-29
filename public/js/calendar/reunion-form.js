// public/js/calendar/reunion-form.js
class ReunionForm {
    constructor() {
        this.show = false;
        this.types = [];
        this.statuses = [];
        this.organisations = [];
        this.minDate = '';
        this.errorDate = false;

        this.formData = {
            id: null,
            objet: '',
            description: '',
            date_debut: '',
            date_fin: '',
            type: 'presentiel',
            statut: 'planifiee',
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
        // Sync all fields with formData
        const inputs = [
            'objet', 'description', 'date_debut', 'date_fin', 'type', 'statut'
        ];

        inputs.forEach(field => {
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

            this.updateFormOptions();
        } catch (e) {
            console.error('Error fetching options:', e);
        }
    }

    updateFormOptions() {
        const typeSelect = document.getElementById('formData.type');
        if (typeSelect && this.types.length > 0) {
            const current = this.formData.type;
            typeSelect.innerHTML = this.types.map(t =>
                `<option value="${t.id}" ${t.id === current ? 'selected' : ''}>${t.label}</option>`
            ).join('');
        }

        const statusSelect = document.getElementById('formData.statut');
        if (statusSelect && this.statuses.length > 0) {
            const current = this.formData.statut;
            statusSelect.innerHTML = this.statuses.map(s =>
                `<option value="${s.id}" ${s.id === current ? 'selected' : ''}>${s.label}</option>`
            ).join('');
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

    validateDates() {
        if (this.formData.date_debut && this.formData.date_fin) {
            this.errorDate = new Date(this.formData.date_fin) <= new Date(this.formData.date_debut);
        } else {
            this.errorDate = false;
        }

        if (this.els.dateError) {
            this.els.dateError.style.display = this.errorDate ? 'block' : 'none';
        }
    }

    open(start = null, end = null, editData = null) {
        if (editData) {
            this.formData = {
                id: editData.id,
                objet: editData.title || editData.objet || '',
                description: editData.description || '',
                date_debut: editData.start ? editData.start.slice(0, 16).replace(' ', 'T') : '',
                date_fin: editData.end ? editData.end.slice(0, 16).replace(' ', 'T') : '',
                type: editData.type || 'presentiel',
                statut: editData.status || editData.statut || 'planifiee',
                organisation_id: editData.organisation_id || '',
                participants: editData.participants || []
            };
            this.els.title.textContent = 'Modifier la Réunion';
        } else {
            this.resetFormData();
            if (start) {
                this.formData.date_debut = start.slice(0, 16);
                this.formData.date_fin = end ? end.slice(0, 16) : new Date(new Date(start).getTime() + 3600000).toISOString().slice(0, 16);
            }
            this.els.title.textContent = 'Nouvelle Réunion';
        }

        this.show = true;
        this.updateUIFromData();
        this.els.modal.style.display = 'flex';

        if (window.Laravel?.userRole === 'chef_organisation' && !window.Laravel?.isAdmin) {
            this.minDate = new Date().toISOString().slice(0, 16);
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
            type: 'presentiel',
            statut: 'planifiee',
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
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

        if (email && emailRegex.test(email) && !this.formData.participants.includes(email)) {
            this.formData.participants.push(email);
            this.els.emailInput.value = '';
            this.updateParticipantsDisplay();
        }
    }

    removeParticipant(index) {
        this.formData.participants.splice(index, 1);
        this.updateParticipantsDisplay();
    }

    updateParticipantsDisplay() {
        if (!this.els.participantsList) return;

        this.els.participantsList.innerHTML = this.formData.participants.map((email, index) => `
            <div class="flex items-center gap-1.5 bg-indigo-50 text-indigo-700 px-2 py-1 rounded-full text-xs font-medium border border-indigo-100 group">
                <span>${email}</span>
                <button type="button" onclick="reunionFormInstance.removeParticipant(${index})" class="hover:text-red-500 transition-colors">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>
        `).join('');
    }

    async submit() {
        if (this.errorDate) {
            alert('Veuillez corriger la date de fin.');
            return;
        }

        this.setLoading(true);

        const isEdit = !!this.formData.id;
        const url = isEdit ? `/reunions/${this.formData.id}` : '/reunions';
        const method = isEdit ? 'PUT' : 'POST';

        try {
            const res = await fetch(url, {
                method: method,
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify(this.formData)
            });

            const data = await res.json();

            if (res.ok) {
                this.close();
                window.dispatchEvent(new CustomEvent(isEdit ? 'reunion-updated' : 'reunion-created'));
            } else {
                alert(data.message || 'Erreur lors de l\'enregistrement');
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
            const res = await fetch(`/reunions/${id}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json'
                }
            });

            if (res.ok) {
                window.dispatchEvent(new CustomEvent('reunion-deleted'));
            } else {
                const data = await res.json();
                alert(data.message || 'Erreur lors de la suppression');
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