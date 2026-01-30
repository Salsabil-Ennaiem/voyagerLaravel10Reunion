<!-- resources/views/components/reunion-form.blade.php -->
<div id="reunionFormModal" style="display: none;" class="fixed inset-0 bg-black/65 flex items-center justify-center z-50 p-4">
    <div id="reunionFormContent" class="bg-white rounded-2xl shadow-2xl w-full max-w-lg max-h-[90vh] overflow-y-auto modal">
        
        <!-- Header -->
        <div class="sticky top-0 bg-white px-5 py-3 border-b flex justify-between items-center z-10">
            <h3 id="formTitle" class="text-lg font-bold text-gray-900">Nouvelle Réunion</h3>
            <button type="button" onclick="reunionFormInstance.close()" class="text-2xl text-gray-500 hover:text-gray-800">×</button>
        </div>

        <div class="p-5">
            <form id="reunionForm">
                <div class="space-y-4">
                    <!-- Admin: Organisation Selector -->
                    <div id="orgSelector" style="display: none;">
                        <label class="block text-sm font-medium text-gray-700">Organisation (Admin)</label>
                        <select id="orgSelectForm" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm border p-2">
                            <option value="">Sélectionner une organisation...</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Objet</label>
                        <input type="text" id="formData.objet" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm border p-2">
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Début</label>
                            <input type="datetime-local" id="formData.date_debut" :min="minDate" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm border p-2">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Fin</label>
                            <input type="datetime-local" id="formData.date_fin" :min="minDate" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm border p-2">
                            <div id="dateError" class="text-red-500 text-xs mt-1" style="display: none;">La date de fin doit être après le début.</div>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Description</label>
                        <textarea id="formData.description" rows="2" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm border p-2"></textarea>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Type</label>
                            <select id="formData.type" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm border p-2">
                                <option value="presentiel">Présentiel</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Statut</label>
                            <select id="formData.statut" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm border p-2">
                                <option value="planifiee">Planifiée</option>
                            </select>
                        </div>
                    </div>

                    <!-- Participants -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Participants (Email)</label>
                        <div class="flex gap-2 mt-1">
                            <input type="email" id="formData.newParticipantEmail" placeholder="exemple@mail.com" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm border p-2">
                            <button type="button" onclick="reunionFormInstance.addParticipant()" class="px-3 py-2 bg-gray-100 text-gray-700 rounded-md hover:bg-gray-200 border text-sm font-bold">+</button>
                        </div>
                        <div id="participantsList" class="mt-2 flex flex-wrap gap-2"></div>
                    </div>
                </div>
                
                <div class="mt-5 flex justify-end gap-3">
                    <button type="button" onclick="reunionFormInstance.close()" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50">
                        Annuler
                    </button>
                    <button type="submit" id="submitBtn" class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 rounded-md hover:bg-indigo-700 disabled:opacity-50 flex items-center gap-2">
                        <span id="loadingSpinner" class="animate-spin h-4 w-4 border-2 border-white border-t-transparent rounded-full" style="display: none;"></span>
                        <span id="submitText">Enregistrer</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="{{ asset('js/calendar/reunion-form.js') }}"></script>
<script>
    // Wait for the reunion form to be initialized before setting up event listeners
    document.addEventListener('DOMContentLoaded', () => {
        // Wait a bit for the reunion form to initialize
        setTimeout(() => {
            // Handle form submission
            document.getElementById('reunionForm')?.addEventListener('submit', (e) => {
                e.preventDefault();
                if (window.reunionFormInstance) {
                    reunionFormInstance.submit();
                } else {
                    console.error('reunionFormInstance not found');
                }
            });

            // Handle date validation
            document.getElementById('date_debut')?.addEventListener('change', () => {
                if (window.reunionFormInstance) {
                    reunionFormInstance.validateDates();
                }
            });

            document.getElementById('date_fin')?.addEventListener('change', () => {
                if (window.reunionFormInstance) {
                    reunionFormInstance.validateDates();
                }
            });

            // Handle enter key in email input
            document.getElementById('newParticipantEmail')?.addEventListener('keydown', (e) => {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    if (window.reunionFormInstance) {
                        reunionFormInstance.addParticipant();
                    }
                }
            });

            // Close modal when clicking outside
            document.getElementById('reunionFormModal')?.addEventListener('click', (e) => {
                if (e.target.id === 'reunionFormModal') {
                    if (window.reunionFormInstance) {
                        reunionFormInstance.close();
                    }
                }
            });
        }, 100);
    });
</script>