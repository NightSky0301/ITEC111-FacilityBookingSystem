<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */
$routes->setAutoRoute(false);

$routes->get('/', 'Dashboard::index');          // the home page is open to everyone
$routes->get('dashboard', 'Dashboard::index');
$routes->get('dashboard/live', 'Dashboard::live'); // free-now and top-bookings shelves, refreshed in the background

// Browsing courts is public; booking and everything personal needs a login.
$routes->get('facilities', 'Facilities::index');
$routes->get('facilities/availability', 'Facilities::availabilityJson');
$routes->get('facilities/court/(:num)', 'Facilities::court/$1');
$routes->get('facilities/(:segment)', 'Facilities::category/$1');
$routes->get('fitness', 'Facilities::fitness');

// Search: results page and the suggestions under the search box (open to everyone).
$routes->get('search', 'Search::index');
$routes->get('search/suggest', 'Search::suggest');

// Hotels: browsing is public, requesting a stay needs a login.
$routes->get('hotels', 'Hotels::index');
$routes->get('hotels/room/(:num)', 'Hotels::room/$1');
$routes->get('hotels/room/(:num)/taken', 'Hotels::taken/$1');
$routes->get('hotels/(:num)', 'Hotels::show/$1');

// ---------- Public ----------
$routes->get('login', 'Auth::login');
$routes->post('login', 'Auth::attemptLogin');
$routes->get('register', 'Auth::register');
$routes->post('register', 'Auth::attemptRegister');
$routes->get('logout', 'Auth::logout');
$routes->get('forgot-password', 'PasswordReset::forgot');
$routes->post('forgot-password', 'PasswordReset::sendLink');
$routes->get('reset-password/(:segment)', 'PasswordReset::reset/$1');
$routes->post('reset-password/(:segment)', 'PasswordReset::update/$1');

// ---------- Logged-in users (customer + staff + admin) ----------
$routes->group('', ['filter' => 'auth'], static function ($routes) {

    // Calendar view
    $routes->get('calendar', 'Calendar::index');
    $routes->get('calendar/events', 'Calendar::events');

    // Booking request (customers)
    $routes->get('bookings/new', 'Bookings::create');
    $routes->get('bookings/busy', 'Bookings::busy');
    $routes->get('bookings/span', 'Bookings::span');
    $routes->post('bookings', 'Bookings::store');
    $routes->post('hotels/book', 'Hotels::book');
    $routes->get('my-bookings', 'Bookings::mine');
    $routes->post('bookings/(:num)/cancel', 'Bookings::cancel/$1');

    // Notifications
    $routes->get('notifications', 'Notifications::index');
    $routes->get('notifications/feed', 'Notifications::feed');
    $routes->get('notifications/pulse', 'Notifications::pulse');   // tiny poll: unread count + 'did my bookings change?'
    $routes->get('notifications/(:num)/read', 'Notifications::read/$1');
    $routes->post('notifications/read-all', 'Notifications::readAll');
});

// ---------- Admin + Staff: run the bookings ----------
$routes->group('manage', ['filter' => 'staff', 'namespace' => 'App\Controllers\Admin'], static function ($routes) {
    $routes->get('bookings', 'Bookings::index');
    $routes->post('bookings/(:num)/approve', 'Bookings::approve/$1');
    $routes->post('bookings/(:num)/reject', 'Bookings::reject/$1');
    $routes->post('bookings/(:num)/arrive', 'Bookings::arrive/$1');
    $routes->post('bookings/(:num)/cancel', 'Bookings::cancel/$1');
});

// ---------- Admin only: facilities and user accounts ----------
$routes->group('admin', ['filter' => 'admin', 'namespace' => 'App\Controllers\Admin'], static function ($routes) {
    $routes->get('facilities', 'Facilities::index');
    $routes->get('facilities/new', 'Facilities::new');
    $routes->post('facilities', 'Facilities::create');
    $routes->get('facilities/(:num)/edit', 'Facilities::edit/$1');
    $routes->post('facilities/(:num)', 'Facilities::update/$1');
    $routes->post('facilities/(:num)/archive', 'Facilities::archive/$1');

    $routes->get('users', 'Users::index');
    $routes->get('users/new', 'Users::new');
    $routes->post('users', 'Users::create');
    $routes->get('users/(:num)/edit', 'Users::edit/$1');
    $routes->post('users/(:num)', 'Users::update/$1');
    $routes->post('users/(:num)/toggle', 'Users::toggle/$1');
    $routes->post('users/(:num)/archive', 'Users::archive/$1');

    $routes->post('bookings/(:num)/archive', 'Bookings::archive/$1');

    // Archive: archived facilities, users and bookings, with restore
    $routes->get('archive', 'Archive::index');
    $routes->post('archive/(:segment)/(:num)/restore', 'Archive::restore/$1/$2');
});
