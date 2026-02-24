<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Protects any route that requires an authenticated user.
 * Reads user_id from the CI session; redirects to /login if absent.
 */
class AuthFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        // Not logged in → always go to login
        if (! session()->get('user_id')) {
            return redirect()->to('/login')->with('error', 'Please log in to continue.');
        }

        // Logged in but no tenant yet → onboarding routes are always allowed.
        // Any other protected route requires a tenant context.
        $path = '/' . ltrim($request->getUri()->getPath(), '/');

        if (str_starts_with($path, '/onboarding')) {
            return; // pass through — no tenant required
        }

        if (! session()->get('tenant_id')) {
            return redirect()->to('/onboarding')
                ->with('info', 'Please create or join an organisation first.');
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // nothing
    }
}
