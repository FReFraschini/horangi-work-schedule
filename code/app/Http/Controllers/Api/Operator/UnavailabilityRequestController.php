<?php

namespace App\Http\Controllers\Api\Operator;

use App\Http\Controllers\Controller;
use App\Models\UnavailabilityRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class UnavailabilityRequestController extends Controller
{
    public function index(Request $request)
    {
        $archived = filter_var($request->query('archived', false), FILTER_VALIDATE_BOOLEAN);

        return Auth::user()
            ->unavailabilityRequests()
            ->where('archived', $archived)
            ->orderByDesc('date')
            ->get();
    }

    public function store(Request $request)
    {
        $userId = Auth::id();

        $validatedData = $request->validate([
            'date' => [
                'required',
                'date',
                Rule::unique('unavailability_requests')
                    ->where(fn($q) => $q->where('user_id', $userId)),
            ],
            'preference' => 'required|string|in:mattina,pomeriggio,tutto il giorno',
            'note'       => 'nullable|string|max:500',
        ], [
            'date.unique' => 'Hai già inviato una richiesta per questa data.',
        ]);

        $createdRequest = Auth::user()->unavailabilityRequests()->create($validatedData);

        return response()->json($createdRequest, 201);
    }

    public function archive($id)
    {
        $unavailabilityRequest = UnavailabilityRequest::where('id', $id)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        $unavailabilityRequest->update(['archived' => true]);

        return response()->json(null, 204);
    }

    public function destroy($id)
    {
        $unavailabilityRequest = UnavailabilityRequest::where('id', $id)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        if ($unavailabilityRequest->status !== 'in attesa' && !$unavailabilityRequest->archived) {
            return response()->json(['message' => 'Puoi eliminare solo le richieste in attesa o archiviate.'], 422);
        }

        $unavailabilityRequest->delete();

        return response()->json(null, 204);
    }
}
