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
});
