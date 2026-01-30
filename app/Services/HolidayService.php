<?php

namespace App\Services;

use Carbon\Carbon;

class HolidayService
{
    private static $feriesFixes = [
        '01-01' => 'Jour de l\'An',
        '20-03' => 'Indépendance', 
        '09-04' => 'Martyrs',
        '01-05' => 'Fête du Travail',
        '25-07' => 'République',
        '13-08' => 'Fête de la Femme',
        '15-10' => 'Évacuation',
        '17-12' => 'Révolution',
    ];

    private static $refDates = [
        2025 => ['fitr' => '2025-03-31', 'kebir' => '2025-06-06', 'mawlid' => '2025-09-04'],
        2026 => ['fitr' => '2026-03-20', 'kebir' => '2026-05-26', 'mawlid' => '2026-08-26'],
        2027 => ['fitr' => '2027-03-10', 'kebir' => '2027-05-16', 'mawlid' => '2027-08-15'],
    ];

    public static function getHolidaysForYear(int $year): array
    {
        $holidays = self::$feriesFixes;
        $mobiles = self::calculateMobileHolidays($year);
        
        return array_merge($holidays, $mobiles);
    }

    public static function isHoliday(int $year, int $month, int $day): bool
    {
        $key = sprintf('%02d-%02d', $month, $day);
        $holidays = self::getHolidaysForYear($year);
        
        return isset($holidays[$key]);
    }

    public static function getHolidayName(int $year, int $month, int $day): ?string
    {
        $key = sprintf('%02d-%02d', $month, $day);
        $holidays = self::getHolidaysForYear($year);
        
        return $holidays[$key] ?? null;
    }

    private static function calculateMobileHolidays(int $year): array
    {
        $mobiles = [];
        $refYear = $year <= 2025 ? 2025 : 2026;
        $ref = self::$refDates[$refYear];

        $estimate = function (string $refDateStr, int $year, int $refYear): Carbon {
            $diff = $year - $refYear;
            $refDate = Carbon::parse($refDateStr);
            return $refDate->copy()->addDays($diff * 10);
        };

        $add = function (string $name, Carbon $date) use (&$mobiles) {
            $key = $date->format('m-d');
            $mobiles[$key] = $name;
        };

        // Aïd el-Fitr
        $fitr = $estimate($ref['fitr'], $year, $refYear);
        $add('Aïd el-Fitr', $fitr);
        $add('Aïd el-Fitr (2)', $fitr->copy()->addDay());

        // Aïd el-Kébir
        $kebir = $estimate($ref['kebir'], $year, $refYear);
        $add('Aïd el-Kébir', $kebir);
        $add('Aïd el-Kébir (2)', $kebir->copy()->addDay());

        // Mawlid
        $add('Mawlid', $estimate($ref['mawlid'], $year, $refYear));

        return $mobiles;
    }
}
