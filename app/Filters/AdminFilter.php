<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Restricts access to platform-admin-only routes.
 * Requires session('is_platform_admin') to be true (set at login).
 */
class AdminFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        if (! session()->get('user_id')) {
            return redirect()->to(site_url('login'));
        }

        if (! session()->get('is_platform_admin')) {
            return redirect()->to(site_url('dashboard'))
                ->with('error', 'Access denied. Platform administrator rights required.');
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // nothing
    }
}
