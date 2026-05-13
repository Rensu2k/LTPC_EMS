<?php
/**
 * LTPC Enrollment Management System (LTPC_EMS)
 *
 * @copyright  2025-2026 Clarence Buenaflor & Jester Pastor
 * @license    Proprietary - All Rights Reserved
 */

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DeveloperSignature
{
    /**
     * Attach developer signature headers to every HTTP response.
     *
     * These headers are invisible to end-users but are visible in browser
     * DevTools (Network tab) and in any HTTP client inspection. They serve
     * as a provenance marker identifying the original development team.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set('X-Powered-By', 'LTPC-EMS/1.0');
        $response->headers->set('X-Developed-By', 'Clarence Buenaflor, Jester Pastor');
        $response->headers->set(
            'X-Build-Signature',
            base64_encode('LTPC_EMS:ClarenceBuenaflor:JesterPastor:cbuenaflor2@ssct.edu.ph:pastorjester98@mail.com:2025')
        );

        return $response;
    }
}
