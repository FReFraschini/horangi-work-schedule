<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Absence;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class AbsenceController extends Controller
{
    public function index(Request $request)
    {
        Gate::authorize('is-gestore');

        $query = Absence::with('user');

        if ($request->has('start_date') && $request->has('end_date')) {
            $query->whereBetween('date', [$request->start_date, $request->end_date]);
        }

        return $query->get();
    }

    public function store(Request $request)
    {
        Gate::authorize('is-gestore');

        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'date'    => 'required|date_format:Y-m-d',
            'type'    => 'required|in:ferie,permesso,compensativo,altra_assenza',
            'note'    => 'nullable|string|max:255',
        ]);

        $absence = Absence::create($validated);

        return response()->json($absence->load('user'), 201);
    }

    public function update(Request $request, Absence $absence)
    {
        Gate::authorize('is-gestore');

        $validated = $request->validate([
            'user_id' => 'sometimes|required|exists:users,id',
            'date'    => 'sometimes|required|date_format:Y-m-d',
            'type'    => 'sometimes|required|in:ferie,permesso,compensativo,altra_assenza',
            'note'    => 'nullable|string|max:255',
        ]);

        $absence->update($validated);

        return response()->json($absence->load('user'));
    }

    public function destroy(Absence $absence)
    {
        Gate::authorize('is-gestore');

        $absence->delete();

        return response()->json(null, 204);
    }
}
