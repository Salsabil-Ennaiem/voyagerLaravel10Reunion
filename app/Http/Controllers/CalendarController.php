// app/Http/Controllers/Admin/CalendarController.php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Reunion;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CalendarController extends Controller
{
    public function index(Request $request)
    {
        // Get current date with filters
        $currentDate = Carbon::now()
            ->year($request->input('year', now()->year))
            ->month($request->input('month', now()->month))
            ->day($request->input('day', now()->day));
        
        // Get meetings for the current month (with filters)
        $query = Reunion::query();
        
        if ($request->filled('year')) {
            $query->whereYear('date_debut', $request->year);
        }
        
        if ($request->filled('month')) {
            $query->whereMonth('date_debut', $request->month);
        }
        
        if ($request->filled('day')) {
            $query->whereDay('date_debut', $request->day);
        }
        
        $filteredMeetings = $query->orderBy('date_debut')->get();
        
        // Group meetings by date
        $meetingsByDate = $filteredMeetings->groupBy(function($meeting) {
            return $meeting->date_debut->format('Y-m-d');
        });
        
        // Generate calendar days
        $startOfMonth = $currentDate->copy()->startOfMonth();
        $endOfMonth = $currentDate->copy()->endOfMonth();
        $startDay = $startOfMonth->copy()->subDays($startOfMonth->dayOfWeek);
        $endDay = $endOfMonth->copy()->addDays(6 - $endOfMonth->dayOfWeek);
        
        $calendarDays = [];
        $currentDay = $startDay->copy();
        
        while ($currentDay <= $endDay) {
            $calendarDays[] = [
                'date' => $currentDay->copy(),
                'isCurrentMonth' => $currentDay->month === $currentDate->month,
                'isToday' => $currentDay->isSameDay(now()),
                'day' => $currentDay->day
            ];
            $currentDay->addDay();
        }
        
        return view('admin.calendar', compact(
            'currentDate', 
            'calendarDays', 
            'meetingsByDate',
            'filteredMeetings'
        ));
    }
}
