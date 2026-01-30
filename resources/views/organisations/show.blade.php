<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Détails de l'Organisation - {{ $organisation->nom }}</title>
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
                        @if($organisation->image)
                            <img src="{{ str_starts_with($organisation->image, 'http') ? $organisation->image : asset('storage/' . $organisation->image) }}" 
                                 alt="{{ $organisation->nom }}" 
                                 class="w-full h-full object-cover">
                        @else
                            <div class="w-full h-full flex items-center justify-center bg-indigo-50 text-indigo-500 text-4xl font-bold">
                                {{ $organisation->short_name }}
                            </div>
                        @endif
                    </div>
                </div>
                <div class="flex-1 text-white pb-2 text-center sm:text-left">
                    <h1 class="text-2xl sm:text-3xl font-extrabold">{{ $organisation->nom }}</h1>
                    <p class="text-white/80 font-medium">Organisation</p>
                </div>
            </div>
        </div>

        @if(session('success'))
            <div class="mb-6 p-4 bg-green-50 border border-green-200 text-green-700 rounded-2xl flex items-center gap-3">
                <i class="fas fa-check-circle"></i>
                {{ session('success') }}
            </div>
        @endif
        @if(session('error'))
            <div class="mb-6 p-4 bg-red-50 border border-red-200 text-red-700 rounded-2xl flex items-center gap-3">
                <i class="fas fa-exclamation-circle"></i>
                {{ session('error') }}
            </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Sidebar Stats/Info -->
            <div class="lg:col-span-1 space-y-6">
               <!-- u can add here like the next part -->
                <div class="glass rounded-3xl p-6 shadow-sm border-white/40">
                    <h3 class="text-sm font-bold text-gray-400 uppercase tracking-wider mb-2">Activités</h3>
                    <div class="flex items-center justify-between py-2 border-b border-gray-100">
                        <span class="text-sm text-gray-600">Réunions</span>
                        <span class="font-bold text-indigo-600">{{ $organisation->reunions()->count() ?? 0 }}</span>
                    </div>
                    <div class="flex items-center justify-between py-2 border-b border-gray-100">
                        <span class="text-sm text-gray-600">Membres</span>
                        <span class="font-bold text-indigo-600">{{ $organisation->members()->count() ?? 0 }}</span>
                    </div>
                </div>

            <!-- Admin Actions -->
                @if($isAdmin)
                <div class="glass rounded-3xl p-6 shadow-xl border-white/40 mb-6">
                    <h3 class="text-sm font-bold text-gray-400 uppercase tracking-wider mb-4">Actions Admin</h3>
                    <div class="flex flex-wrap gap-3">
                        <!-- Toggle Active Status -->
                        <form action="{{ route('organisations.toggleActive', $organisation->id) }}" method="POST">
                            @csrf
                            @method('PATCH')
                            @if($organisation->active)
                                <button type="submit" class="px-4 py-2 bg-orange-100 text-orange-700 text-sm font-bold rounded-xl hover:bg-orange-200 transition flex items-center gap-2" onclick="return confirm('Désactiver cette organisation ?')">
                                    <i class="fas fa-pause-circle"></i> Désactiver
                                </button>
                            @else
                                <button type="submit" class="px-4 py-2 bg-green-100 text-green-700 text-sm font-bold rounded-xl hover:bg-green-200 transition flex items-center gap-2">
                                    <i class="fas fa-play-circle"></i> Activer
                                </button>
                            @endif
                        </form>

                        <!-- Delete Organisation -->
                        <form action="{{ route('organisations.destroy', $organisation->id) }}" method="POST">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="px-4 py-2 bg-red-100 text-red-700 text-sm font-bold rounded-xl hover:bg-red-200 transition flex items-center gap-2" onclick="return confirm('Êtes-vous sûr de vouloir supprimer cette organisation ? Cette action est irréversible.')">
                                <i class="fas fa-trash-alt"></i> Supprimer
                            </button>
                        </form>
                    </div>

                    @if(!$organisation->active)
                        <div class="mt-4 p-3 bg-red-50 border border-red-200 text-red-700 rounded-xl text-sm flex items-center gap-2">
                            <i class="fas fa-exclamation-triangle"></i>
                            <span>Cette organisation est actuellement <strong>inactive</strong>.</span>
                        </div>
                    @endif
                </div>
                @endif
            </div>

            <!-- Main Content: Edit Form -->
            <div class="lg:col-span-2 space-y-8">
                <div class="glass rounded-3xl p-8 shadow-xl border-white/40">
                    <div class="flex items-center justify-between mb-8">
                        <h2 class="text-xl font-bold text-gray-900">{{ ($isAdmin || $isChef) ? 'Éditer l\'Organisation' : 'Détails de l\'Organisation' }}</h2>
                        <i class="fas {{ ($isAdmin || $isChef) ? 'fa-edit' : 'fa-info-circle' }} text-indigo-500 text-xl"></i>
                    </div>

                    <form action="{{ route('organisations.update', $organisation->id) }}" method="POST" enctype="multipart/form-data" class="space-y-6">
                        @csrf
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="space-y-2">
                                <i class="fa-solid fa-building w-5 text-indigo-500"></i>
                                <label for="nom" class="text-xs font-bold text-gray-500 uppercase ml-1">Nom de l'organisation</label>
                                <input type="text" name="nom" id="nom" value="{{ old('nom', $organisation->nom) }}" 
                                       {{ (!$isAdmin && !$isChef) ? 'readonly' : '' }}
                                       class="w-full px-4 py-3 rounded-2xl {{ (!$isAdmin && !$isChef) ? 'bg-gray-50' : 'bg-white/50' }} border border-gray-200 focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 transition-all outline-none font-medium text-gray-700">
                                @error('nom') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                            </div>

                            <div class="space-y-2">
                                <i class="fas fa-envelope w-5 text-indigo-500"></i>
                                <label for="email_contact" class="text-xs font-bold text-gray-500 uppercase ml-1">Email de contact</label>
                                <input type="email" name="email_contact" id="email_contact" value="{{ old('email_contact', $organisation->email_contact) }}" 
                                       {{ (!$isAdmin && !$isChef) ? 'readonly' : '' }}
                                       class="w-full px-4 py-3 rounded-2xl {{ (!$isAdmin && !$isChef) ? 'bg-gray-50' : 'bg-white/50' }} border border-gray-200 focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 transition-all outline-none font-medium text-gray-700">
                                @error('email_contact') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                            </div>
                        </div>

                        @if(Auth::user()->isAdmin())
                        <div class="space-y-2">
                            <i class="fas fa-user-tie w-5 text-indigo-500"></i>
                            <label for="chef_organisation_id" class="text-xs font-bold text-gray-500 uppercase ml-1">Gérant (Chef d'Organisation)</label>
                            <div class="relative">
                                <input type="text" class="absolute z-10 top-0 left-0 opacity-0 w-full"/>
                                <select name="chef_organisation_id" id="chef_organisation_id" 
                                        class="w-full px-4 py-3 rounded-2xl bg-white/50 border border-gray-200 focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 transition-all outline-none font-medium text-gray-700">
                                    @foreach($allUsers as $u)
                                        @if (Str::contains(strtolower($u->name), strtolower($organisation->chef_organisation_id ?? '')))
                                            <option value="{{ $u->id }}" {{ $organisation->chef_organisation_id == $u->id ? 'selected' : '' }}>
                                                {{ $u->name }} ({{ $u->email }})
                                            </option>
                                        @endif
                                    @endforeach
                                </select>
                            </div>
                            <p class="text-[10px] text-gray-400 ml-1">Seul un administrateur peut changer le chef d'une organisation.</p>
                        </div>
                        @endif

                        <div class="space-y-2">
                            <i class="fas fa-map-marker-alt w-5 mt-1 text-indigo-500"></i>
                            <label for="adresse" class="text-xs font-bold text-gray-500 uppercase ml-1">Adresse</label>
                            <input type="text" name="adresse" id="adresse" value="{{ old('adresse', $organisation->adresse) }}" 
                                   {{ (!$isAdmin && !$isChef) ? 'readonly' : '' }}
                                   class="w-full px-4 py-3 rounded-2xl {{ (!$isAdmin && !$isChef) ? 'bg-gray-50' : 'bg-white/50' }} border border-gray-200 focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 transition-all outline-none font-medium text-gray-700">
                            @error('adresse') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div class="space-y-2">
                            <label for="description" class="text-xs font-bold text-gray-500 uppercase ml-1">Description</label>
                            <textarea name="description" id="description" rows="4" 
                                      {{ (!$isAdmin && !$isChef) ? 'readonly' : '' }}
                                      class="w-full px-4 py-3 rounded-2xl {{ (!$isAdmin && !$isChef) ? 'bg-gray-50' : 'bg-white/50' }} border border-gray-200 focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 transition-all outline-none font-medium resize-none text-gray-700">{{ old('description', $organisation->description) }}</textarea>
                            @error('description') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>

                        @if($isAdmin || $isChef)
                        <div class="space-y-2">
                            <label class="text-xs font-bold text-gray-500 uppercase ml-1">Logo de l'organisation</label>
                            <div class="relative group cursor-pointer">
                                <input type="file" name="image" id="image" class="hidden" onchange="previewImage(event)">
                                <label for="image" class="flex flex-col items-center justify-center w-full h-32 border-2 border-dashed border-gray-300 rounded-3xl hover:border-indigo-500 hover:bg-indigo-50/30 transition-all cursor-pointer">
                                    <div class="flex flex-col items-center justify-center pt-5 pb-6">
                                        <i class="fas fa-cloud-upload-alt text-2xl text-gray-400 group-hover:text-indigo-500 transition-colors mb-2"></i>
                                        <p class="text-sm text-gray-500 font-medium">Cliquez pour changer le logo</p>
                                        <p class="text-xs text-gray-400">PNG, JPG up to 2MB</p>
                                    </div>
                                </label>
                            </div>
                        </div>

                        <div class="pt-4">
                            <button type="submit" class="w-full py-4 bg-indigo-600 hover:bg-indigo-700 text-white font-bold rounded-2xl shadow-xl shadow-indigo-200 transform hover:-translate-y-1 transition-all active:scale-95 flex items-center justify-center gap-2">
                                <i class="fas fa-save font-normal"></i>
                                Enregistrer les modifications
                            </button>
                        </div>
                        @else
                        <div class="pt-4 p-4 bg-indigo-50 rounded-2xl text-indigo-700 text-center text-sm font-medium">
                            <i class="fas fa-lock mr-2"></i> Vous consultez cette organisation en tant que membre.
                        </div>
                        @endif
                    </form>
                </div>

                <!-- Members Section -->
                <div class="glass rounded-3xl p-8 shadow-xl border-white/40">
                    <div class="flex items-center justify-between mb-8">
                        <h2 class="text-xl font-bold text-gray-900">Membres</h2>
                        @if($canManageMembers)
                        <button onclick="document.getElementById('addMemberModal').classList.remove('hidden')" class="px-4 py-2 bg-indigo-50 text-indigo-700 text-xs font-bold rounded-xl hover:bg-indigo-100 transition">
                            <i class="fas fa-plus mr-1"></i> Ajouter
                        </button>
                        @endif
                    </div>

                    <div class="space-y-4">
                        @forelse($organisation->members as $member)
                        <div class="flex items-center justify-between p-4 rounded-2xl bg-white/30 border border-white/50 hover:bg-white/50 transition-colors">
                            <div class="flex items-center gap-3">
                                <img src="{{ Voyager::image($member->avatar) }}" class="w-10 h-10 rounded-full border border-gray-100">
                                <div>
                                    <p class="text-sm font-bold text-gray-900">{{ $member->name }}</p>
                                    <p class="text-[10px] text-gray-500">{{ $member->pivot->fonction ?: 'Membre' }}</p>
                                </div>
                            </div>
                            @if($canManageMembers)
                             @php
                                    $isChef = in_array(strtolower(trim($member->pivot->fonction ?? '')), ['chef', "chef d'organisation", 'gérant', 'gerant']);
                                @endphp
                            @if(!$isChef)
                            <div class="flex items-center gap-2">
                                <button onclick="openEditMember('{{ $member->id }}', '{{ $member->pivot->fonction }}')" class="p-2 text-indigo-400 hover:text-indigo-600 transition">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <form action="{{ route('organisations.members.remove', [$organisation->id, $member->id]) }}" method="POST" onsubmit="return confirm('Retirer ce membre ?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="p-2 text-red-400 hover:text-red-600 transition">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                </form>
                            </div>
                            @endif
                            @endif
                        </div>
                        @empty
                        <p class="text-center py-8 text-gray-400 text-sm italic">Aucun membre dans cette organisation.</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if($canManageMembers)
    <!-- Add Member Modal -->
    <div id="addMemberModal" class="fixed inset-0 z-[100] hidden">
        <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" onclick="this.parentElement.classList.add('hidden')"></div>
        <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[90%] max-w-md">
            <div class="glass rounded-3xl p-8 shadow-2xl border-white/40">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-lg font-bold text-gray-900">Ajouter un Membre</h3>
                    <button onclick="document.getElementById('addMemberModal').classList.add('hidden')" class="p-2 text-gray-400 hover:text-gray-900 transition">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <form action="{{ route('organisations.members.add', $organisation->id) }}" method="POST" class="space-y-4">
                    @csrf
                    <div class="space-y-2">
                        <label for="email_membre_add" class="text-xs font-bold text-gray-500 uppercase ml-1">Email du membre</label>
                        <input id="email_membre_add" type="email" name="email" required placeholder="exemple@test.com" 
                               class="w-full px-4 py-3 rounded-2xl bg-white border border-gray-200 focus:border-indigo-500 outline-none font-medium text-gray-700">
                        <p class="text-[10px] text-gray-400 ml-1">Si l'utilisateur n'existe pas, un compte sera créé automatiquement.</p>
                    </div>
                    <div class="space-y-2">
                        <label class="text-xs font-bold text-gray-500 uppercase ml-1">Fonction</label>
                        <input type="text" name="fonction" placeholder="Ex: Développeur, Designer, Manager..." class="w-full px-4 py-3 rounded-2xl bg-white border border-gray-200 focus:border-indigo-500 outline-none font-medium">
                        <p class="text-xs text-gray-400 mt-1">Les fonctions de type 'chef' sont gérées via la modification de l'organisation.</p>
                    </div>
                    <button type="submit" class="w-full py-4 bg-indigo-600 text-white font-bold rounded-2xl shadow-lg hover:bg-indigo-700 transition transform active:scale-95 mt-4">
                        Ajouter à l'organisation
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Member Modal -->
    <div id="editMemberModal" class="fixed inset-0 z-[100] hidden">
        <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" onclick="this.parentElement.classList.add('hidden')"></div>
        <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[90%] max-w-md">
            <div class="glass rounded-3xl p-8 shadow-2xl border-white/40">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-lg font-bold text-gray-900">Éditer le Membre</h3>
                    <button onclick="document.getElementById('editMemberModal').classList.add('hidden')" class="p-2 text-gray-400 hover:text-gray-900 transition">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <form id="editMemberForm" action="" method="POST" class="space-y-4">
                    @csrf
                    <div class="space-y-2">
                        <label class="text-xs font-bold text-gray-500 uppercase ml-1">Nouvelle Fonction</label>
                        <input type="text" name="fonction" id="edit_fonction" class="w-full px-4 py-3 rounded-2xl bg-white border border-gray-200 focus:border-indigo-500 outline-none font-medium">
                        <p class="text-xs text-gray-400 mt-1">Les fonctions de type 'chef' sont gérées via la modification de l'organisation.</p>
                    </div>
                    <button type="submit" class="w-full py-4 bg-indigo-600 text-white font-bold rounded-2xl shadow-lg hover:bg-indigo-700 transition transform active:scale-95 mt-4">
                        Enregistrer
                    </button>
                </form>
            </div>
        </div>
    </div>
    @endif {{-- canManageMembers --}}

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

        @if($canManageMembers)
        function openEditMember(memberId, currentFonction) {
            const modal = document.getElementById('editMemberModal');
            const form = document.getElementById('editMemberForm');
            const input = document.getElementById('edit_fonction');
            
            form.action = `/organisations/{{ $organisation->id }}/members/${memberId}`;
            input.value = currentFonction;
            
            modal.classList.remove('hidden');
        }
        @endif
    </script>
    
</body>
</html>