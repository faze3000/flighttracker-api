<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ForwardingAddressResource;
use App\Services\Email\ForwardingAddressGenerator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ForwardingAddressController extends Controller
{
    public function __construct(private readonly ForwardingAddressGenerator $generator) {}

    /**
     * Every account gets one generated at registration; generating here too
     * is just a safety net for any account that predates that, or a retry
     * of a registration whose address generation failed.
     */
    public function show(Request $request): JsonResponse
    {
        $address = $this->generator->generateFor($request->user());

        return (new ForwardingAddressResource($address))->response();
    }
}
