<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->get('/', 'Home::index');

// ---------------------------------------------------------------------------
// Auth (public)
// ---------------------------------------------------------------------------
$routes->get('/login',    'Web\AuthController::login');
$routes->post('/login',   'Web\AuthController::loginPost');
$routes->get('/register', 'Web\AuthController::register');
$routes->post('/register','Web\AuthController::registerPost');
$routes->get('/logout',   'Web\AuthController::logout');

// ---------------------------------------------------------------------------
// Protected — requires active session (AuthFilter)
// ---------------------------------------------------------------------------
$routes->group('', ['filter' => 'auth'], static function ($routes): void {
    $routes->get('/dashboard', 'Web\DashboardController::index');

    // Onboarding (logged in but no tenant yet)
    $routes->get('/onboarding',           'Web\OnboardingController::index');
    $routes->get('/onboarding/search',    'Web\OnboardingController::search');
    $routes->post('/onboarding/create',   'Web\OnboardingController::create');
    $routes->post('/onboarding/join',     'Web\OnboardingController::join');
    $routes->get('/onboarding/pending',   'Web\OnboardingController::pending');

    // Org — any member can view
    $routes->get('/org/members',          'Web\OrgController::members');

    // -----------------------------------------------------------------------
    // Admin-only routes (role: org.admin)
    // -----------------------------------------------------------------------
    $routes->group('', ['filter' => 'role:org.admin'], static function ($routes): void {
        // Settings
        $routes->get('/org/settings',                   'Web\OrgController::settings');
        $routes->post('/org/settings',                  'Web\OrgController::settingsPost');

        // Member management
        $routes->post('/org/members/(:num)/role',       'Web\OrgController::memberRole/$1');
        $routes->post('/org/members/(:num)/remove',     'Web\OrgController::memberRemove/$1');

        // Join requests
        $routes->get('/org/requests',                   'Web\JoinRequestController::index');
        $routes->post('/org/requests/(:num)/approve',   'Web\JoinRequestController::approve/$1');
        $routes->post('/org/requests/(:num)/reject',    'Web\JoinRequestController::reject/$1');

        // Invitations
        $routes->post('/org/invite',                    'Web\InvitationController::send');
        $routes->post('/org/invite/(:num)/cancel',      'Web\InvitationController::cancel/$1');
    });
});

// Public invite acceptance (no auth required — handled inside the controller)
$routes->get('/invite/(:hex)',  'Web\InvitationController::accept/$1');
$routes->post('/invite/(:hex)', 'Web\InvitationController::acceptPost/$1');

// ---------------------------------------------------------------------------
// Platform admin — requires is_platform_admin session flag (AdminFilter)
// ---------------------------------------------------------------------------
$routes->group('admin', ['filter' => 'admin'], static function ($routes): void {
    $routes->get('/',                           'Web\AdminController::index');
    $routes->get('/tenants',                    'Web\AdminController::tenants');
    $routes->post('/tenants/(:num)/toggle',     'Web\AdminController::tenantToggle/$1');
    $routes->get('/users',                      'Web\AdminController::users');
    $routes->post('/users/(:num)/toggle',       'Web\AdminController::userToggle/$1');
});
