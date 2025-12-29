<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\UnavailabilityRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class UnavailabilityRequestController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        Gate::authorize('is-gestore');
        return UnavailabilityRequest::with('user')->get();
    }

    /**
     * Display the specified resource.
     */
    public function show(UnavailabilityRequest $unavailabilityRequest)
    {
        Gate::authorize('is-gestore');
        return $unavailabilityRequest->load('user');
    }

    /**
     * Update the status of the specified resource in storage.
     */
    public function updateStatus(Request $request, UnavailabilityRequest $unavailabilityRequest)
    {
        Gate::authorize('is-gestore');

        $validatedData = $request->validate([
            'status' => 'required|string|in:approvata,rifiutata',
        ]);

        $unavailabilityRequest->update(['status' => $validatedData['status']]);

        return response()->json($unavailabilityRequest);
    }
}
