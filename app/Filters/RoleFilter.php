<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Checks that the logged-in user has the required role for the current tenant.
 *
 * Usage in routes:
 *   ['filter' => 'role:org.admin']
 *
 * The role_code is stored in the session by AuthController / OnboardingController
 * / InvitationController whenever a membership is resolved.
 */
class RoleFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $required = $arguments[0] ?? null;

        if ($required === null) {
            return; // no role constraint specified — pass through
        }

        $current = session()->get('role_code');

        if ($current !== $required) {
            return redirect()->to(site_url('dashboard'))
                ->with('error', 'Access denied. You need the "' . $required . '" role to access this page.');
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // nothing
    }
}
