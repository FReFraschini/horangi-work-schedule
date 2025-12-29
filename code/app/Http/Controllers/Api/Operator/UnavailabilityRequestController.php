<?php

namespace App\Http\Controllers\Api\Operator;

use App\Http\Controllers\Controller;
use App\Models\UnavailabilityRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UnavailabilityRequestController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return Auth::user()->unavailabilityRequests;
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'date' => 'required|date',
            'preference' => 'required|string|in:mattina,pomeriggio,tutto il giorno',
        ]);

        $createdRequest = Auth::user()->unavailabilityRequests()->create($validatedData);

        return response()->json($createdRequest, 201);
    }
}
