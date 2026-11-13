<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */
$routes->setAutoRoute(false);

$routes->get('/', 'Home::index');

// ---------- Public ----------
$routes->get('login', 'Auth::login');
$routes->post('login', 'Auth::attemptLogin');
$routes->get('register', 'Auth::register');
$routes->post('register', 'Auth::attemptRegister');
$routes->get('logout', 'Auth::logout');

// ---------- Logged-in users (customer + admin) ----------
$routes->group('', ['filter' => 'auth'], static function ($routes) {
    $routes->get('dashboard', 'Dashboard::index');

    // Facilities list
    $routes->get('facilities', 'Facilities::index');

    // Calendar view
    $routes->get('calendar', 'Calendar::index');
    $routes->get('calendar/events', 'Calendar::events');

    // Booking request
    $routes->get('bookings/new', 'Bookings::create');
    $routes->post('bookings', 'Bookings::store');
    $routes->get('my-bookings', 'Bookings::mine');
    $routes->post('bookings/(:num)/cancel', 'Bookings::cancel/$1');

    // Notifications
    $routes->get('notifications', 'Notifications::index');
    $routes->get('notifications/(:num)/read', 'Notifications::read/$1');
    $routes->post('notifications/read-all', 'Notifications::readAll');
});

// ---------- Admin only ----------
$routes->group('admin', ['filter' => 'admin', 'namespace' => 'App\Controllers\Admin'], static function ($routes) {
    $routes->get('facilities', 'Facilities::index');
    $routes->get('facilities/new', 'Facilities::new');
    $routes->post('facilities', 'Facilities::create');
    $routes->get('facilities/(:num)/edit', 'Facilities::edit/$1');
    $routes->post('facilities/(:num)', 'Facilities::update/$1');
    $routes->post('facilities/(:num)/delete', 'Facilities::delete/$1');

    $routes->get('bookings', 'Bookings::index');
    $routes->post('bookings/(:num)/approve', 'Bookings::approve/$1');
    $routes->post('bookings/(:num)/reject', 'Bookings::reject/$1');
    $routes->post('bookings/(:num)/cancel', 'Bookings::cancel/$1');
});
