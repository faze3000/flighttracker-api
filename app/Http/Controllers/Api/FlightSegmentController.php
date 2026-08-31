<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\FlightSegments\UpdateFlightSegmentRequest;
use App\Http\Resources\FlightSegmentResource;
use App\Models\FlightSegment;
use Illuminate\Http\JsonResponse;

class FlightSegmentController extends Controller
{
    /**
     * A user edit/confirmation always resolves the review flag — a PATCH
     * is how the app implements "confirming" a low-confidence extraction.
     */
    public function update(UpdateFlightSegmentRequest $request, FlightSegment $segment): JsonResponse
    {
        $this->authorize('update', $segment);

        $segment->update([
            ...$request->validated(),
            'needs_review' => false,
        ]);

        return (new FlightSegmentResource($segment))->response();
    }
}
