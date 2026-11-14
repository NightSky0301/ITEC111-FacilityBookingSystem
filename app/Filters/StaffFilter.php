<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/** Admin and staff may pass (the people who run the bookings). */
class StaffFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        if ($redirect = AuthFilter::guard()) {
            return $redirect;
        }
        if (! in_array(session()->get('role'), ['admin', 'staff'], true)) {
            return redirect()->to('/dashboard')->with('error', 'You do not have access to that page.');
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
    }
}
