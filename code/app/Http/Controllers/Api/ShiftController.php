<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Shift;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ShiftController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        Gate::authorize('is-gestore');

        $query = Shift::with('user');

        if ($request->has('start_date') && $request->has('end_date')) {
            $query->whereBetween('start_time', [$request->start_date, $request->end_date]);
        }

        return $query->get();
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        Gate::authorize('is-gestore');

        $validatedData = $request->validate([
            'user_id' => 'required|exists:users,id',
            'start_time' => 'required|date',
            'end_time' => 'required|date|after:start_time',
        ]);

        $shift = Shift::create($validatedData);

        return response()->json($shift, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Shift $shift)
    {
        Gate::authorize('is-gestore');
        return $shift->load('user');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Shift $shift)
    {
        Gate::authorize('is-gestore');

        $validatedData = $request->validate([
            'user_id' => 'sometimes|required|exists:users,id',
            'start_time' => 'sometimes|required|date',
            'end_time' => 'sometimes|required|date|after:start_time',
        ]);

        $shift->update($validatedData);

        return response()->json($shift);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Shift $shift)
    {
        Gate::authorize('is-gestore');
        
        $shift->delete();

        return response()->json(null, 204);
    }
}
