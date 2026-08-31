<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Trips\StoreTripRequest;
use App\Http\Requests\Trips\UpdateTripRequest;
use App\Http\Resources\TripResource;
use App\Models\Trip;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TripController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $trips = $request->user()->trips()
            ->orderByDesc('start_date')
            ->paginate(20);

        return TripResource::collection($trips)->response();
    }

    public function show(Request $request, Trip $trip): JsonResponse
    {
        $this->authorize('view', $trip);

        return (new TripResource($trip->load('flightSegments')))->response();
    }

    public function store(StoreTripRequest $request): JsonResponse
    {
        $trip = $request->user()->trips()->create($request->validated());

        return (new TripResource($trip))->response()->setStatusCode(201);
    }

    public function update(UpdateTripRequest $request, Trip $trip): JsonResponse
    {
        $this->authorize('update', $trip);

        $trip->update($request->validated());

        return (new TripResource($trip))->response();
    }

    public function destroy(Request $request, Trip $trip): JsonResponse
    {
        $this->authorize('delete', $trip);

        $trip->delete();

        return response()->json(status: 204);
    }
}
