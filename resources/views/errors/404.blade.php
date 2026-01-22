<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Page Non Trouvée</title>
    <!-- Fonts -->
     <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700&display=swap" rel="stylesheet">
   <style>
        body {
            font-family: 'Outfit', sans-serif;
            background: radial-gradient(circle at top right, #1e293b, #0f172a);
        }
        .glass {
            background: rgba(255, 255, 255, 0.03);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.05);
            box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.37);
        }
        .animate-pulse-slow {
            animation: pulse 8s cubic-bezier(0.4, 0, 0.6, 1) infinite;
        }
        @keyframes pulse {
            0%, 100% { opacity: 0.2; transform: scale(1); }
            50% { opacity: 0.4; transform: scale(1.1); }
        }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center p-6 text-white overflow-hidden">
    <div class="absolute inset-0 z-0">
        <div class="absolute top-1/4 left-1/4 w-[32rem] h-[32rem] bg-blue-600/20 rounded-full blur-[120px] animate-pulse-slow text-blue-500/20"></div>
        <div class="absolute bottom-1/4 right-1/4 w-[32rem] h-[32rem] bg-purple-600/20 rounded-full blur-[120px] animate-pulse-slow text-purple-500/20" style="animation-delay: 4s;"></div>
    </div>

    <div class="glass max-w-lg w-full p-12 rounded-3xl text-center relative z-10">
        <div class="mb-8">
            <h1 class="text-9xl font-bold bg-clip-text text-transparent bg-gradient-to-r from-blue-400 to-purple-500 mb-2">404</h1>
            <div class="h-1 w-24 bg-gradient-to-r from-blue-500 to-purple-500 mx-auto rounded-full"></div>
        </div>
        
        <h2 class="text-3xl font-semibold mb-6">Oups ! Page Introuvable</h2>
        <p class="text-gray-400 mb-10 text-lg leading-relaxed">
            La page que vous recherchez semble avoir disparu dans le vide sidéral ou n'a jamais existé.
        </p>
        
        <div class="flex flex-col gap-4">
            @auth
                <a href="{{ url('/reunion') }}" class="group inline-flex items-center justify-center px-8 py-4 bg-gradient-to-r from-blue-600 to-purple-700 rounded-2xl font-semibold text-lg hover:from-blue-500 hover:to-purple-600 hover:scale-[1.02] transition-all duration-300 shadow-xl shadow-blue-900/40">
                    <span>Page D'acceuil</span>
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 ml-2 group-hover:translate-x-1 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                    </svg>
                </a>
            @else
                <a href="{{ url('/admin/login') }}" class="group inline-flex items-center justify-center px-8 py-4 bg-gradient-to-r from-blue-600 to-purple-700 rounded-2xl font-semibold text-lg hover:from-blue-500 hover:to-purple-600 hover:scale-[1.02] transition-all duration-300 shadow-xl shadow-blue-900/40">
                    <span>Page D'acceuil</span>
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 ml-2 group-hover:-translate-x-1 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14" />
                    </svg>
                </a>
            @endauth
            
            <button onclick="window.history.back()" class="text-gray-500 hover:text-white transition-colors text-sm font-medium">
                Retour à la page précédente
            </button>
        </div>
    </div>
    
    <div class="absolute bottom-10 text-gray-600 text-xs font-medium tracking-widest uppercase">
        &copy; {{ date('Y') }} {{ config('app.name') }}. Tous droits réservés.
    </div>
</body>
</html>
