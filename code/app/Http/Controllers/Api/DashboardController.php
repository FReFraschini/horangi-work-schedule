<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Shift;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class DashboardController extends Controller
{
    public function totals(Request $request)
    {
        Gate::authorize('is-gestore');

        $validatedData = $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $startDate = $validatedData['start_date'];
        $endDate = $validatedData['end_date'];

        // Total hours per operator
        $hoursPerOperator = User::where('role', 'operatore')
            ->with(['shifts' => function ($query) use ($startDate, $endDate) {
                $query->whereBetween('start_time', [$startDate, $endDate]);
            }])
            ->get()
            ->map(function ($user) {
                $totalSeconds = $user->shifts->reduce(function ($carry, $shift) {
                    return $carry + $shift->start_time->diffInSeconds($shift->end_time);
                }, 0);
                return [
                    'user_id' => $user->id,
                    'name' => $user->name,
                    'total_hours' => round($totalSeconds / 3600, 2),
                    'weekly_hours' => $user->weekly_hours,
                ];
            });

        // Total hours per day
        $hoursPerDay = Shift::whereBetween('start_time', [$startDate, $endDate])
            ->select(
                DB::raw('DATE(start_time) as date'),
                DB::raw('SUM(TIMESTAMPDIFF(SECOND, start_time, end_time)) as total_seconds')
            )
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->map(function ($day) {
                return [
                    'date' => $day->date,
                    'total_hours' => round($day->total_seconds / 3600, 2),
                ];
            });

        return response()->json([
            'hours_per_operator' => $hoursPerOperator,
            'hours_per_day' => $hoursPerDay,
        ]);
    }
}
