<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Créer une Organisation</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap');
        body { font-family: 'Plus+Jakarta+Sans', sans-serif; background: #f8fafc; }
        .glass { background: rgba(255, 255, 255, 0.7); backdrop-filter: blur(12px); border: 1px solid rgba(255, 255, 255, 0.3); }
        .gradient-bg { background: linear-gradient(135deg, #6366f1 0%, #a855f7 100%); }
    </style>
</head>
<body class="min-h-screen pb-12">
    <x-navbar />

    <div class="max-w-4xl mx-auto pt-24 px-4 sm:px-6 lg:px-8">
        <!-- Header / Banner -->
        <div class="relative rounded-3xl overflow-hidden shadow-2xl mb-8 group">
            <div class="h-48 sm:h-64 gradient-bg relative overflow-hidden">
                <div class="absolute inset-0 bg-black/10"></div>
                <!-- Abstract decorations -->
                <div class="absolute top-0 right-0 -mr-16 -mt-16 w-64 h-64 bg-white/10 rounded-full blur-3xl"></div>
                <div class="absolute bottom-0 left-0 -ml-16 -mb-16 w-48 h-48 bg-purple-500/20 rounded-full blur-3xl"></div>
            </div>
            
            <div class="absolute bottom-0 left-0 w-full p-6 flex flex-col sm:flex-row items-end sm:items-center gap-6 bg-gradient-to-t from-black/60 to-transparent">
                <div class="relative">
                    <div id="orgLogoContainer" class="w-24 h-24 sm:w-32 sm:h-32 rounded-2xl border-4 border-white shadow-xl overflow-hidden bg-white">
                        <div class="w-full h-full flex items-center justify-center bg-indigo-50 text-indigo-500 text-4xl font-bold">
                            <i class="fas fa-building"></i>
                        </div>
                    </div>
                </div>
                <div class="flex-1 text-white pb-2 text-center sm:text-left">
                    <h1 class="text-2xl sm:text-3xl font-extrabold">Nouvelle Organisation</h1>
                    <p class="text-white/80 font-medium">Créer une nouvelle organisation</p>
                </div>
            </div>
        </div>

        @if ($errors->any())
            <div class="mb-6 p-4 bg-red-50 border border-red-200 text-red-700 rounded-2xl">
                <ul class="list-disc list-inside">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="glass rounded-3xl p-8 shadow-xl border-white/40">
            <div class="flex items-center justify-between mb-8">
                <h2 class="text-xl font-bold text-gray-900">Informations de l'Organisation</h2>
                <i class="fas fa-plus-circle text-indigo-500 text-xl"></i>
            </div>

            <form action="{{ route('organisations.store') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
                @csrf
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="space-y-2">
                        <i class="fa-solid fa-building w-5 text-indigo-500"></i>
                        <label for="nom" class="text-xs font-bold text-gray-500 uppercase ml-1">Nom de l'organisation <span class="text-red-500">*</span></label>
                        <input type="text" name="nom" id="nom" value="{{ old('nom') }}" required
                               class="w-full px-4 py-3 rounded-2xl bg-white/50 border border-gray-200 focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 transition-all outline-none font-medium text-gray-700">
                        @error('nom') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div class="space-y-2">
                        <i class="fas fa-envelope w-5 text-indigo-500"></i>
                        <label for="email_contact" class="text-xs font-bold text-gray-500 uppercase ml-1">Email de contact</label>
                        <input type="email" name="email_contact" id="email_contact" value="{{ old('email_contact') }}" 
                               class="w-full px-4 py-3 rounded-2xl bg-white/50 border border-gray-200 focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 transition-all outline-none font-medium text-gray-700">
                        @error('email_contact') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="space-y-2">
                    <i class="fas fa-user-tie w-5 text-indigo-500"></i>
                    <label for="chef_organisation_id" class="text-xs font-bold text-gray-500 uppercase ml-1">Gérant (Chef d'Organisation) <span class="text-red-500">*</span></label>
                    <select name="chef_organisation_id" id="chef_organisation_id" required
                            class="w-full px-4 py-3 rounded-2xl bg-white/50 border border-gray-200 focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 transition-all outline-none font-medium text-gray-700">
                        @foreach($users as $u)
                            <option value="{{ $u->id }}" {{ old('chef_organisation_id') == $u->id ? 'selected' : '' }}>
                                {{ $u->name }} ({{ $u->email }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="space-y-2">
                    <i class="fas fa-map-marker-alt w-5 mt-1 text-indigo-500"></i>
                    <label for="adresse" class="text-xs font-bold text-gray-500 uppercase ml-1">Adresse</label>
                    <input type="text" name="adresse" id="adresse" value="{{ old('adresse') }}" 
                           class="w-full px-4 py-3 rounded-2xl bg-white/50 border border-gray-200 focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 transition-all outline-none font-medium text-gray-700">
                    @error('adresse') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                <div class="space-y-2">
                    <label for="description" class="text-xs font-bold text-gray-500 uppercase ml-1">Description</label>
                    <textarea name="description" id="description" rows="4" 
                              class="w-full px-4 py-3 rounded-2xl bg-white/50 border border-gray-200 focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 transition-all outline-none font-medium resize-none text-gray-700">{{ old('description') }}</textarea>
                    @error('description') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                <div class="space-y-2">
                    <label class="text-xs font-bold text-gray-500 uppercase ml-1">Logo de l'organisation</label>
                    <div class="relative group cursor-pointer">
                        <input type="file" name="image" id="image" class="hidden" onchange="previewImage(event)">
                        <label for="image" class="flex flex-col items-center justify-center w-full h-32 border-2 border-dashed border-gray-300 rounded-3xl hover:border-indigo-500 hover:bg-indigo-50/30 transition-all cursor-pointer">
                            <div class="flex flex-col items-center justify-center pt-5 pb-6">
                                <i class="fas fa-cloud-upload-alt text-2xl text-gray-400 group-hover:text-indigo-500 transition-colors mb-2"></i>
                                <p class="text-sm text-gray-500 font-medium">Cliquez pour ajouter un logo</p>
                                <p class="text-xs text-gray-400">PNG, JPG up to 2MB</p>
                            </div>
                        </label>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <input type="checkbox" name="active" id="active" value="1" checked
                           class="w-5 h-5 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                    <label for="active" class="text-sm font-medium text-gray-700">Organisation active</label>
                </div>

                <div class="pt-4 flex gap-4">
                    <a href="{{ route('organisations.index') }}" class="px-6 py-4 bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold rounded-2xl transition-all flex items-center justify-center gap-2">
                        <i class="fas fa-arrow-left font-normal"></i>
                        Annuler
                    </a>
                    <button type="submit" class="flex-1 py-4 bg-indigo-600 hover:bg-indigo-700 text-white font-bold rounded-2xl shadow-xl shadow-indigo-200 transform hover:-translate-y-1 transition-all active:scale-95 flex items-center justify-center gap-2">
                        <i class="fas fa-save font-normal"></i>
                        Créer l'organisation
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function previewImage(event) {
            const input = event.target;
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const container = document.getElementById('orgLogoContainer');
                    container.innerHTML = `<img src="${e.target.result}" class="w-full h-full object-cover">`;
                };
                reader.readAsDataURL(input.files[0]);
            }
        }
    </script>
    
</body>
</html>
