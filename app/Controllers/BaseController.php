<?php

namespace App\Controllers;

use CodeIgniter\Controller;
use CodeIgniter\HTTP\CLIRequest;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

/**
 * BaseController provides a convenient place for loading components
 * and performing functions that are needed by all your controllers.
 *
 * Extend this class in any new controllers:
 * ```
 *     class Home extends BaseController
 * ```
 *
 * For security, be sure to declare any new methods as protected or private.
 */
abstract class BaseController extends Controller
{
    /**
     * Be sure to declare properties for any property fetch you initialized.
     * The creation of dynamic property is deprecated in PHP 8.2.
     */

    protected $helpers = ['form', 'url', 'booking'];

    // protected $session;

    /**
     * @param CLIRequest|IncomingRequest $request
     *
     * @return void
     */
    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        // Load here all helpers you want to be available in your controllers that extend BaseController.
        // Caution: Do not put the this below the parent::initController() call below.
        // $this->helpers = ['form', 'url'];

        // Caution: Do not edit this line.
        parent::initController($request, $response, $logger);

        // Preload any models, libraries, etc, here.
        // $this->session = service('session');

        // Requests that were never approved or rejected before their start time are rejected automatically.
        // Checked at most once every 30 seconds (and on every page, so the lists are always up to date).
        if (! is_cli() && ! cache('pending_expiry_checked')) {
            (new \App\Models\BookingModel())->expirePending();
            cache()->save('pending_expiry_checked', 1, 30);
        }
        // Reminders for bookings starting within 24 hours (also sent by `php spark bookings:remind` if scheduled).
        if (! is_cli() && ! cache('booking_reminders_checked')) {
            (new \App\Models\BookingModel())->remindUpcoming();
            cache()->save('booking_reminders_checked', 1, 600);
        }
    }
}
