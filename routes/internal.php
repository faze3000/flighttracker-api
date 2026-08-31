<?php

// Parser callbacks. Not part of the public API — guarded by the
// EnsureInternalRequest middleware (shared-secret header), registered
// under the 'internal' alias in bootstrap/app.php.

use App\Http\Controllers\Internal\EmailParsedController;
use Illuminate\Support\Facades\Route;

Route::patch('emails/{email}/parsed', [EmailParsedController::class, 'update']);
