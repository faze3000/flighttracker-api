<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Trips\TripStatsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StatsController extends Controller
{
    public function __construct(private readonly TripStatsService $stats) {}

    public function lifetime(Request $request): JsonResponse
    {
        return response()->json(['data' => $this->stats->lifetime($request->user())]);
    }

    public function recap(Request $request): JsonResponse
    {
        $year = (int) $request->integer('year', now()->year);

        return response()->json(['data' => $this->stats->recap($request->user(), $year)]);
    }
}
