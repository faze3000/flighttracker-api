<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\LoungeResource;
use App\Models\Lounge;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LoungeController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $request->validate(['airport' => ['required', 'string', 'size:3']]);

        $lounges = Lounge::query()
            ->where('airport_iata', strtoupper($request->string('airport')->toString()))
            ->get();

        return LoungeResource::collection($lounges)->response();
    }
}
