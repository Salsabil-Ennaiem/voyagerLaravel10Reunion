<!-- resources/views/calendrier-avec-filtres.blade.php -->

<!DOCTYPE html>
<html lang="fr" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Calendrier Tunisie avec Jours Fériés</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: { primary: '#6366f1', primaryDark: '#4f46e5' }
                }
            }
        }
    </script>
    <style>
        .day-cell:hover { transform: scale(1.04); transition: all 0.2s; }
        .modal { animation: fadeIn 0.25s ease-out; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
        .time-slot:hover { transform: translateX(4px); background-color: #eef2ff; }
        .ferie { background-color: #fee2e2 !important; border-color: #ef4444; color: #991b1b; font-weight: 600; }
    </style>
</head>
<body class="bg-gray-50 min-h-screen">

<div class="max-w-6xl mx-auto px-4 py-10">

    <h1 class="text-3xl md:text-4xl font-bold text-center text-gray-800 mb-2">Calendrier Tunisie</h1>
    <p class="text-center text-gray-600 mb-8">Jours fériés en rouge – Cliquez sur un jour pour détails</p>

    <?php
        $currentYear = (int) date('Y');
        $minYear = $currentYear - 10;
        $maxYear = $currentYear + 10;

        $month = max(1, min(12, (int)($_GET['month'] ?? date('n'))));
        $year  = max($minYear, min($maxYear, (int)($_GET['year']  ?? $currentYear)));

        $date = new DateTime("$year-$month-01");
        $firstWeekday = (int)$date->format('N');
        $daysInMonth  = (int)$date->format('t');

        $monthNames = ['', 'Janvier','Février','Mars','Avril','Mai','Juin','Juillet','Août','Septembre','Octobre','Novembre','Décembre'];
        $weekdays   = ['Lun','Mar','Mer','Jeu','Ven','Sam','Dim'];

        $prevMonth = $month - 1; $prevYear = $year; if ($prevMonth < 1) { $prevMonth = 12; $prevYear--; }
        $nextMonth = $month + 1; $nextYear = $year; if ($nextMonth > 12) { $nextMonth = 1; $nextYear++; }

        $today = new DateTime();
        $isTodayMonth = ($month == (int)$today->format('n') && $year == (int)$today->format('Y'));
        $todayDay = (int)$today->format('j');

        // Liste des jours fériés Tunisie (fixes + mobiles approx pour 2025/2026)
        $feries = [
            // Fixes (mêmes dates chaque année)
            '01-01' => 'Jour de l’An',
            '20-03' => 'Fête de l’Indépendance',
            '09-04' => 'Journée des Martyrs',
            '01-05' => 'Fête du Travail',
            '25-07' => 'Fête de la République',
            '13-08' => 'Fête de la Femme',
            '15-10' => 'Fête de l’Évacuation',
            '17-12' => 'Fête de la Révolution',

            // Mobiles 2025 (dates officielles / confirmées approx)
            '30-03' => 'Aïd el-Fitr (2025)',
            '31-03' => 'Aïd el-Fitr (2025)',
            '06-06' => 'Aïd el-Kébir (2025)',
            '26-06' => 'Ras el Am El Hijri (2025)',
            '04-09' => 'Mouled (2025)',

            // Mobiles 2026 (dates prévisionnelles / officielles approx)
            '21-03' => 'Aïd el-Fitr (2026)',
            '22-03' => 'Aïd el-Fitr (2026)',
            '26-05' => 'Aïd el-Kébir (2026)',
            '15-06' => 'Ras el Am El Hijri (2026)',
            '24-08' => 'Mouled (2026)',
        ];
    ?>

    <!-- Filtres -->
    <div class="flex flex-wrap justify-center items-center gap-4 mb-10">
        <a href="?month={{ $prevMonth }}&year={{ $prevYear }}" class="px-6 py-3 bg-white border border-gray-300 rounded-xl shadow hover:bg-gray-50 transition font-medium">◄ {{ $monthNames[$prevMonth] }}</a>

        <select onchange="updateCalendar(this.value, document.getElementById('yearSelect').value)" id="monthSelect" class="px-5 py-3 bg-white border border-gray-300 rounded-xl shadow focus:ring-2 focus:ring-indigo-400 focus:outline-none font-medium">
            @for ($m = 1; $m <= 12; $m++)
                <option value="{{ $m }}" {{ $m === $month ? 'selected' : '' }}>{{ $monthNames[$m] }}</option>
            @endfor
        </select>

        <select onchange="updateCalendar(document.getElementById('monthSelect').value, this.value)" id="yearSelect" class="px-5 py-3 bg-white border border-gray-300 rounded-xl shadow focus:ring-2 focus:ring-indigo-400 focus:outline-none font-medium">
            @for ($y = $minYear; $y <= $maxYear; $y++)
                <option value="{{ $y }}" {{ $y === $year ? 'selected' : '' }}>{{ $y }}</option>
            @endfor
        </select>

        <a href="?month={{ $nextMonth }}&year={{ $nextYear }}" class="px-6 py-3 bg-white border border-gray-300 rounded-xl shadow hover:bg-gray-50 transition font-medium">{{ $monthNames[$nextMonth] }} ►</a>
    </div>

    <h2 class="text-2xl font-semibold text-center text-gray-800 mb-6">{{ $monthNames[$month] }} {{ $year }}</h2>

    <!-- Grille -->
    <div class="grid grid-cols-7 gap-1.5 sm:gap-2 text-center bg-white rounded-2xl shadow-xl overflow-hidden border border-gray-200">

        @foreach($weekdays as $dayName)
            <div class="py-4 font-semibold text-gray-700 bg-gray-100">{{ $dayName }}</div>
        @endforeach

        <?php
            $day = 1;
            $cell = 0;

            for ($i = 1; $i < $firstWeekday; $i++) {
                echo '<div class="py-5 bg-gray-50/60"></div>';
                $cell++;
            }

            while ($day <= $daysInMonth) {
                $key = sprintf("%02d-%02d", $month, $day);
                $isFerie = isset($feries[$key]);
                $ferieName = $isFerie ? $feries[$key] : '';

                $isToday   = $isTodayMonth && $day == $todayDay;
                $isWeekend = in_array($cell % 7, [5, 6]);

                $class = 'py-5 sm:py-7 cursor-pointer transition-all day-cell border-b border-r border-gray-100 last:border-r-0';
                $class .= $isToday   ? ' bg-indigo-50 border-2 border-indigo-400 font-bold' : '';
                $class .= $isWeekend ? ' text-red-600 hover:bg-red-50' : ' text-gray-800 hover:bg-indigo-50';
                if ($isFerie) $class .= ' ferie';

                echo "<div class=\"$class\" onclick=\"showDay($day, $month, $year, '" . addslashes($ferieName) . "')\" title=\"" . ($ferieName ? $ferieName : '') . "\">";
                echo "<span class=\"text-lg sm:text-xl\">$day</span>";
                echo "</div>";

                $day++;
                $cell++;
            }

            while ($cell % 7 !== 0) {
                echo '<div class="py-5 bg-gray-50/60 border-b border-r border-gray-100 last:border-r-0"></div>';
                $cell++;
            }
        ?>
    </div>

</div>

<!-- MODAL -->
<div id="dayModal" class="fixed inset-0 bg-black/65 hidden flex items-center justify-center z-50 p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md sm:max-w-xl md:max-w-2xl max-h-[85vh] overflow-y-auto modal">
        <div class="sticky top-0 bg-white px-6 py-4 border-b flex justify-between items-center z-10">
            <h3 id="modalDayTitle" class="text-xl font-bold text-gray-900"></h3>
            <button onclick="closeModal()" class="text-3xl text-gray-500 hover:text-gray-800">×</button>
        </div>
        <div class="p-6">
            <div id="ferieInfo" class="mb-4 font-semibold text-red-700 text-lg"></div>
            <p class="text-center text-gray-600 mb-4">Créneaux toutes les 15 min – Cliquez pour sélectionner</p>
            <div id="timelineContainer" class="space-y-2"></div>
        </div>
    </div>
</div>

<script>
    function updateCalendar(month, year) {
        window.location = `?month=${month}&year=${year}`;
    }

    function showDay(day, month, year, ferieName) {
        const title = document.getElementById('modalDayTitle');
        const ferieDiv = document.getElementById('ferieInfo');
        const container = document.getElementById('timelineContainer');

        const monthName = new Date(year, month-1).toLocaleString('fr-FR', {month: 'long'});
        title.textContent = `${day.toString().padStart(2,'0')} ${monthName} ${year}`;

        if (ferieName) {
            ferieDiv.textContent = `🎉 Jour férié : ${ferieName}`;
            ferieDiv.classList.remove('hidden');
        } else {
            ferieDiv.textContent = '';
            ferieDiv.classList.add('hidden');
        }

        container.innerHTML = '';
        const interval = 15;
        for (let h = 0; h < 24; h++) {
            for (let m = 0; m < 60; m += interval) {
                const hour = h.toString().padStart(2, '0');
                const min  = m.toString().padStart(2, '0');
                const nextM = (m + interval) % 60;
                const nextH = (m + interval >= 60) ? (h + 1) % 24 : h;
                const nextMin = nextM.toString().padStart(2, '0');

                const slot = document.createElement('div');
                slot.className = 'time-slot bg-gray-50 border border-gray-200 rounded-lg p-3 flex justify-between items-center cursor-pointer shadow-sm hover:shadow-md';
                slot.innerHTML = `
                    <div class="font-medium text-indigo-600">${hour}:${min}</div>
                    <div class="text-gray-500">→ ${nextH.toString().padStart(2,'0')}:${nextMin}</div>
                    <div class="text-xs text-gray-400">Disponible</div>
                `;
                slot.onclick = () => alert(`Créneau : ${day}/${month}/${year} ${hour}:${min}`);
                container.appendChild(slot);
            }
        }

        document.getElementById('dayModal').classList.remove('hidden');
    }

    function closeModal() {
        document.getElementById('dayModal').classList.add('hidden');
    }

    document.addEventListener('keydown', e => { if (e.key === 'Escape') closeModal(); });
    document.getElementById('dayModal')?.addEventListener('click', e => {
        if (e.target === e.currentTarget) closeModal();
    });
</script>

</body>
</html>