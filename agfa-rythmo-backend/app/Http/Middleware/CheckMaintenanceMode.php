<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckMaintenanceMode
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $maintenanceFile = storage_path('framework/maintenance');
        
        if (file_exists($maintenanceFile)) {
            return response()->json([
                'maintenance' => true,
                'message' => 'Application en maintenance. Merci de revenir plus tard.'
            ], 503);
        }

        return $next($request);
    }
}
