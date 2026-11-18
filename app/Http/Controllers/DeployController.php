<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Post-upload hook for hosts without SSH (FTP deploys): runs migrations and rebuilds caches.
 * Disabled unless DEPLOY_TOKEN is set in .env; the caller must send it in the X-Deploy-Token header.
 */
class DeployController extends Controller
{
    public function finish(Request $request): JsonResponse
    {
        $token = (string) config('tieco.deploy_token');
        abort_if(strlen($token) < 32, 404);
        abort_unless(hash_equals($token, (string) $request->header('X-Deploy-Token')), 404);

        $output = [];
        try {
            foreach ([['migrate', ['--force' => true]], ['optimize:clear', []], ['optimize', []], ['queue:restart', []]] as [$cmd, $args]) {
                Artisan::call($cmd, $args);
                $output[$cmd] = trim(Artisan::output());
            }
        } catch (Throwable $e) {
            Log::error('deploy hook failed', ['error' => $e->getMessage()]);

            return response()->json(['ok' => false, 'error' => $e->getMessage(), 'output' => $output], 500);
        }

        return response()->json(['ok' => true, 'output' => $output]);
    }
}
