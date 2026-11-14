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

// ---------- Logged-in users (customer + staff + admin) ----------
$routes->group('', ['filter' => 'auth'], static function ($routes) {
    $routes->get('dashboard', 'Dashboard::index');

    // Facilities list
    $routes->get('facilities', 'Facilities::index');                       // step 1: activity
    $routes->get('facilities/court/(:num)', 'Facilities::court/$1');       // step 3: availability
    $routes->get('facilities/(:segment)', 'Facilities::category/$1');      // step 2: court

    // Calendar view
    $routes->get('calendar', 'Calendar::index');
    $routes->get('calendar/events', 'Calendar::events');

    // Booking request (customers)
    $routes->get('bookings/new', 'Bookings::create');
    $routes->post('bookings', 'Bookings::store');
    $routes->get('my-bookings', 'Bookings::mine');
    $routes->post('bookings/(:num)/cancel', 'Bookings::cancel/$1');

    // Notifications
    $routes->get('notifications', 'Notifications::index');
    $routes->get('notifications/feed', 'Notifications::feed');
    $routes->get('notifications/(:num)/read', 'Notifications::read/$1');
    $routes->post('notifications/read-all', 'Notifications::readAll');
});

// ---------- Admin + Staff: run the bookings ----------
$routes->group('manage', ['filter' => 'staff', 'namespace' => 'App\Controllers\Admin'], static function ($routes) {
    $routes->get('bookings', 'Bookings::index');
    $routes->post('bookings/(:num)/approve', 'Bookings::approve/$1');
    $routes->post('bookings/(:num)/reject', 'Bookings::reject/$1');
    $routes->post('bookings/(:num)/cancel', 'Bookings::cancel/$1');
});

// ---------- Admin only: facilities and user accounts ----------
$routes->group('admin', ['filter' => 'admin', 'namespace' => 'App\Controllers\Admin'], static function ($routes) {
    $routes->get('facilities', 'Facilities::index');
    $routes->get('facilities/new', 'Facilities::new');
    $routes->post('facilities', 'Facilities::create');
    $routes->get('facilities/(:num)/edit', 'Facilities::edit/$1');
    $routes->post('facilities/(:num)', 'Facilities::update/$1');
    $routes->post('facilities/(:num)/delete', 'Facilities::delete/$1');

    $routes->get('users', 'Users::index');
    $routes->get('users/new', 'Users::new');
    $routes->post('users', 'Users::create');
    $routes->get('users/(:num)/edit', 'Users::edit/$1');
    $routes->post('users/(:num)', 'Users::update/$1');
    $routes->post('users/(:num)/toggle', 'Users::toggle/$1');
});
