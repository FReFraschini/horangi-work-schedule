<?php

namespace App\Http\Controllers\Api\Operator;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AbsenceController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();

        $query = $user->absences()->getQuery();

        if ($request->has('date')) {
            $query->whereDate('date', $request->date);
        }

        return $query->get();
    }
}
