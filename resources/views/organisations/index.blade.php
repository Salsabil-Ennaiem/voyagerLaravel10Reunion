<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Liste des Organisations</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap');
        body { font-family: 'Plus+Jakarta+Sans', sans-serif; background: #f8fafc; }
        .glass { background: rgba(255, 255, 255, 0.7); backdrop-filter: blur(12px); border: 1px solid rgba(255, 255, 255, 0.3); }
        .card-hover:hover { transform: translateY(-5px); box-shadow: 0 20px 25px -5px rgb(0 0 0 / 0.1), 0 8px 10px -6px rgb(0 0 0 / 0.1); }
    </style>
</head>
<body class="min-h-screen pb-12">
    <x-navbar />

    <div class="max-w-7xl mx-auto pt-24 px-4 sm:px-6 lg:px-8">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-10">
            <div>
                <h1 class="text-3xl font-extrabold text-gray-900 tracking-tight">Organisations</h1>
                <p class="text-gray-500 font-medium mt-1">Gérez toutes les organisations et leurs gérants.</p>
            </div>
            <div class="flex items-center gap-3">
                 <span class="px-4 py-2 bg-indigo-50 text-indigo-700 rounded-full text-sm font-bold border border-indigo-100 italic">
                    {{ $organisations->count() }} au total
                </span>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            @foreach($organisations as $org)
            <div class="glass rounded-[2rem] overflow-hidden shadow-sm hover:shadow-xl transition-all duration-300 card-hover flex flex-col group border-white/40">
                <!-- Org Image / Mock Header -->
                <div class="h-32 bg-gradient-to-r from-indigo-500 to-purple-600 relative overflow-hidden">
                    <div class="absolute inset-0 bg-black/10 group-hover:bg-transparent transition-colors"></div>
                    @if($org->image)
                        <img src="{{ str_starts_with($org->image, 'http') ? $org->image : asset('storage/' . $org->image) }}" class="w-full h-full object-cover opacity-60">
                    @endif
                </div>

                <div class="p-8 pt-0 -mt-10 relative flex-1 flex flex-col">
                    <div class="w-20 h-20 rounded-2xl bg-white p-1 shadow-lg border-4 border-white mb-4">
                        <div class="w-full h-full rounded-xl flex items-center justify-center bg-indigo-50 text-indigo-600 font-bold text-2xl overflow-hidden">
                            @if($org->image)
                                 <img src="{{ str_starts_with($org->image, 'http') ? $org->image : asset('storage/' . $org->image) }}" class="w-full h-full object-cover">
                            @else
                                {{ $org->short_name }}
                            @endif
                        </div>
                    </div>

                    <h2 class="text-xl font-bold text-gray-900 mb-2 truncate group-hover:text-indigo-600 transition-colors">{{ $org->nom }}</h2>
                    
                    <div class="space-y-3 mb-6 flex-1">
                        <div class="flex items-center gap-2 text-sm text-gray-600">
                             <i class="fas fa-user-circle text-gray-400"></i>
                             <span class="font-medium">Chef: <span class="text-gray-900">{{ $org->chef->name ?? 'Aucun' }}</span></span>
                        </div>
                        <div class="flex items-center gap-2 text-sm text-gray-600">
                             <i class="fas fa-calendar-check text-gray-400"></i>
                             <span class="font-medium">Réunions: <span class="text-gray-900">{{ $org->reunions()->count() }}</span></span>
                        </div>
                    </div>

                    <a href="{{ route('organisations.show', $org->id) }}" class="block w-full py-3 bg-white border border-gray-200 text-gray-700 font-bold text-center rounded-2xl hover:bg-indigo-600 hover:text-white hover:border-indigo-600 transition-all shadow-sm">
                        Gérer l'organisation
                    </a>
                    {{-- Note: update route is used for both view and submit, but for index we just need to link to show page logic --}}
                    {{-- Actually I'll make index link to a show page even for admin --}}
                </div>
            </div>
            @endforeach
        </div>
    </div>
</body>
</html>
