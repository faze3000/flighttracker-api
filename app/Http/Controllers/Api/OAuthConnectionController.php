<?php

namespace App\Http\Controllers\Api;

use App\Enums\OAuthProvider;
use App\Http\Controllers\Controller;
use App\Http\Resources\OAuthConnectionResource;
use App\Services\OAuth\OAuthConnectionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OAuthConnectionController extends Controller
{
    public function __construct(private readonly OAuthConnectionService $connections) {}

    /**
     * Return the URL the client should open to grant mail-read access.
     * Kept as a JSON-returning API endpoint (not a redirect) since Flutter
     * opens it itself in a browser/webview.
     */
    public function connect(Request $request, OAuthProvider $provider): JsonResponse
    {
        $url = $this->connections->startConnect($request->user(), $provider);

        return response()->json(['url' => $url]);
    }

    /**
     * Public callback hit directly by Google/Microsoft after consent.
     * The state token (not a bearer token) identifies which user this is for.
     */
    public function callback(Request $request, OAuthProvider $provider): JsonResponse
    {
        if ($request->filled('error')) {
            return response()->json(['status' => 'denied', 'provider' => $provider], 422);
        }

        $request->validate([
            'state' => ['required', 'string'],
            'code' => ['required', 'string'],
        ]);

        $connection = $this->connections->handleCallback($provider->value, $request->string('state')->toString(), $request->string('code')->toString());

        return response()->json([
            'status' => 'connected',
            'connection' => new OAuthConnectionResource($connection),
        ]);
    }

    public function destroy(Request $request, OAuthProvider $provider): JsonResponse
    {
        $connection = $request->user()->oauthConnections()->where('provider', $provider)->firstOrFail();

        $this->connections->disconnect($connection);

        return response()->json(status: 204);
    }
}
