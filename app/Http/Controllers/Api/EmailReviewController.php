<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\FlightSegmentResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EmailReviewController extends Controller
{
    /**
     * Low-confidence extractions awaiting confirmation. The unit of review
     * is the flight segment (what PATCH /segments/{id} resolves), not the
     * raw email — one email can produce several segments, only some of
     * which might need a look.
     */
    public function index(Request $request): JsonResponse
    {
        $segments = $request->user()->flightSegments()
            ->where('needs_review', true)
            ->orderBy('departure_time_local')
            ->paginate(20);

        return FlightSegmentResource::collection($segments)->response();
    }
}
