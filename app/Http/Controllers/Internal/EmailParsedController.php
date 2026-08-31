<?php

namespace App\Http\Controllers\Internal;

use App\Http\Controllers\Controller;
use App\Http\Requests\Internal\StoreParsedEmailRequest;
use App\Http\Resources\FlightSegmentResource;
use App\Models\RawEmail;
use App\Services\Parsing\ParsedEmailProcessor;
use Illuminate\Http\JsonResponse;

class EmailParsedController extends Controller
{
    public function __construct(private readonly ParsedEmailProcessor $processor) {}

    public function update(StoreParsedEmailRequest $request, RawEmail $email): JsonResponse
    {
        $segments = $this->processor->process($email, $request->validated());

        return response()->json([
            'raw_email_id' => $email->id,
            'status' => $email->status,
            'flight_segments' => FlightSegmentResource::collection($segments),
        ]);
    }
}
