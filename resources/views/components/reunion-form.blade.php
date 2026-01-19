<div x-data="reunionForm()" x-show="show" style="display: none;" class="fixed inset-0 bg-black/65 flex items-center justify-center z-50 p-4">
    <div @click.away="close()" class="bg-white rounded-2xl shadow-2xl w-full max-w-lg max-h-[90vh] overflow-y-auto modal">
        
        <!-- Header -->
        <div class="sticky top-0 bg-white px-5 py-3 border-b flex justify-between items-center z-10">
            <h3 class="text-lg font-bold text-gray-900">Nouvelle Réunion</h3>
            <button @click="close()" class="text-2xl text-gray-500 hover:text-gray-800">×</button>
        </div>

        <div class="p-5">
            <form @submit.prevent="submit">
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Objet</label>
                        <input type="text" x-model="formData.objet" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm border p-2">
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Début</label>
                            <input type="datetime-local" x-model="formData.date_debut" :min="minDate" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm border p-2">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Fin</label>
                            <input type="datetime-local" x-model="formData.date_fin" :min="minDate" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm border p-2">
                            <p x-show="errorDate" class="text-red-500 text-xs mt-1">La date de fin doit être après le début.</p>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Description</label>
                        <textarea x-model="formData.description" rows="2" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm border p-2"></textarea>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Type</label>
                            <select x-model="formData.type" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm border p-2">
                                <template x-for="type in types" :key="type.id">
                                    <option :value="type.id" x-text="type.label"></option>
                                </template>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Statut</label>
                            <select x-model="formData.statut" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm border p-2">
                                <template x-for="status in statuses" :key="status.id">
                                    <option :value="status.id" x-text="status.label"></option>
                                </template>
                            </select>
                        </div>
                    </div>

                    <!-- Participants -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Participants (Email)</label>
                        <div class="flex gap-2 mt-1">
                            <input type="email" x-model="newParticipantEmail" @keydown.enter.prevent="addParticipant" placeholder="exemple@mail.com" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm border p-2">
                            <button type="button" @click="addParticipant" class="px-3 py-2 bg-gray-100 text-gray-700 rounded-md hover:bg-gray-200 border text-sm font-bold">+</button>
                        </div>
                        <div class="mt-2 flex flex-wrap gap-2">
                            <template x-for="(email, index) in formData.participants" :key="index">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-indigo-100 text-indigo-800">
                                    <span x-text="email"></span>
                                    <button type="button" @click="removeParticipant(index)" class="ml-1.5 inline-flex items-center justify-center text-indigo-400 hover:text-indigo-600 focus:outline-none">
                                        <svg class="h-2 w-2" stroke="currentColor" fill="none" viewBox="0 0 8 8"><path stroke-linecap="round" stroke-width="1.5" d="M1 1l6 6m0-6L1 1" /></svg>
                                    </button>
                                </span>
                            </template>
                        </div>
                    </div>
                </div>
                
                <div class="mt-5 flex justify-end gap-3">
                    <button type="button" @click="close()" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50">
                        Annuler
                    </button>
                    <button type="submit" :disabled="errorDate" class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 rounded-md hover:bg-indigo-700 disabled:opacity-50">
                        Enregistrer
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function reunionForm() {
            return {
                show: false,
                types: [],
                statuses: [],
                newParticipantEmail: '',
                userRole: '{{ Auth::user()->getAttributes()['role'] ?? 'membre' }}',
                isAdmin: {{ (Auth::user()->hasRole('admin') || Auth::user()->role_id == 1) ? 'true' : 'false' }},
                minDate: '',
                formData: {
                    objet: '', description: '', date_debut: '', date_fin: '', type: 'presentiel', statut: 'planifiee', participants: []
                },
                errorDate: false,

                init() {
                    this.$watch('show', value => {
                        if (value) {
                            this.fetchOptions();
                            this.validateDates();
                            // If Chef, set minDate to today
                            if (this.userRole === 'chef_organisation' && !this.isAdmin) {
                                this.minDate = new Date().toISOString().substring(0, 16);
                            } else {
                                this.minDate = '';
                            }
                        }
                    });
                    this.$watch('formData.date_debut', () => this.validateDates());
                    this.$watch('formData.date_fin', () => this.validateDates());

                    // Expose to global for calendar interaction
                    window.openReunionModal = (start, end) => {
                        this.open(start, end);
                    };
                },

                addParticipant() {
                    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                    if (this.newParticipantEmail && emailRegex.test(this.newParticipantEmail)) {
                        if (!this.formData.participants.includes(this.newParticipantEmail)) {
                            this.formData.participants.push(this.newParticipantEmail);
                        }
                        this.newParticipantEmail = '';
                    } else {
                        alert("Veuillez entrer une adresse email valide.");
                    }
                },

                removeParticipant(index) {
                    this.formData.participants.splice(index, 1);
                },

                async fetchOptions() {
                    try {
                        const res = await fetch('/reunion-options'); 
                        const data = await res.json();
                        this.types = data.types;
                        this.statuses = data.statuses;
                    } catch(e) {
                         // Fallback defaults if API fails
                         this.types = [{id: 'presentiel', label: 'Présentiel'}, {id: 'visio', label: 'Visio'}, {id: 'hybride', label: 'Hybride'}];
                         this.statuses = [{id: 'brouillon', label: 'Brouillon'}, {id: 'planifiee', label: 'Planifiée'}, {id: 'en_cours', label: 'En Cours'}];
                    }
                },

                validateDates() {
                    if (this.formData.date_debut && this.formData.date_fin) {
                        this.errorDate = new Date(this.formData.date_fin) <= new Date(this.formData.date_debut);
                    } else {
                        this.errorDate = false;
                    }
                },

                open(start = null, end = null) {
                    // Restriction date passée pour le Chef
                    if (this.userRole === 'chef_organisation' && !this.isAdmin) {
                        const now = new Date();
                        if (start && new Date(start) < now) {
                           // If it's today, we might want to allow it if it's the current hour, 
                           // but the user said "date alrdy pass"
                           // We'll jump to 'now' if 'start' is past
                           start = now.toISOString().substring(0, 16);
                        }
                    }

                    this.show = true;
                    if (start) {
                        // Expecting YYYY-MM-DDTHH:mm:ss format
                        this.formData.date_debut = start.substring(0, 16);
                        if (end) {
                            this.formData.date_fin = end.substring(0, 16);
                        } else {
                            // Default 1h later if only start provided
                            const endD = new Date(new Date(start).getTime() + 3600000);
                            this.formData.date_fin = endD.toISOString().substring(0, 16);
                        }
                    }
                },

                close() {
                    this.show = false;
                    this.newParticipantEmail = '';
                    this.formData = {
                        objet: '', description: '', date_debut: '', date_fin: '', type: 'presentiel', statut: 'planifiee', participants: []
                    };
                },

                async submit() {
                    if (this.errorDate) return;
                    
                    const res = await fetch('/reunions', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
                        },
                        body: JSON.stringify(this.formData)
                    });
                    
                    if (res.ok) {
                        this.close();
                        window.dispatchEvent(new CustomEvent('reunion-created')); // Global event
                    } else {
                        try {
                            const errData = await res.json();
                            alert("Erreur: " + (errData.message || "Inconnue"));
                        } catch(e) {
                            alert("Erreur serveur (500)");
                        }
                    }
                }
            }
        }
    </script>
</div>
