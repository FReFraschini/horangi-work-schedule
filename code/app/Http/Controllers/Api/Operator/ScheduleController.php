<?php

namespace App\Http\Controllers\Api\Operator;

use App\Http\Controllers\Controller;
use App\Models\Absence;
use App\Models\Shift;
use App\Models\User;
use Illuminate\Http\Request;

class ScheduleController extends Controller
{
    public function shifts(Request $request)
    {
        $query = Shift::with('user');

        if ($request->has('start_date') && $request->has('end_date')) {
            $query->whereBetween('start_time', [$request->start_date, $request->end_date]);
        }

        return $query->get();
    }

    public function absences(Request $request)
    {
        $query = Absence::query();

        if ($request->has('start_date') && $request->has('end_date')) {
            $query->whereBetween('date', [$request->start_date, $request->end_date]);
        }

        return $query->get();
    }

    public function team()
    {
        return User::where('role', 'operatore')
            ->select('id', 'name', 'color', 'weekly_hours')
            ->orderBy('name')
            ->get();
    }
}
