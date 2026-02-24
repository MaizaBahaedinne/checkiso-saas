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

    // Org admin — join request management
    $routes->get('/org/requests',                        'Web\JoinRequestController::index');
    $routes->post('/org/requests/(:num)/approve',        'Web\JoinRequestController::approve/$1');
    $routes->post('/org/requests/(:num)/reject',         'Web\JoinRequestController::reject/$1');

    // Org settings
    $routes->get('/org/settings',                        'Web\OrgController::settings');
    $routes->post('/org/settings',                       'Web\OrgController::settingsPost');

    // Org members
    $routes->get('/org/members',                         'Web\OrgController::members');
    $routes->post('/org/members/(:num)/role',            'Web\OrgController::memberRole/$1');
    $routes->post('/org/members/(:num)/remove',          'Web\OrgController::memberRemove/$1');
});
