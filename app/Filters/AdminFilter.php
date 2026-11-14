<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/** Only admins may pass. */
class AdminFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        if ($redirect = AuthFilter::guard()) {
            return $redirect;
        }
        if (session()->get('role') !== 'admin') {
            return redirect()->to('/dashboard')->with('error', 'Only the admin can open that page.');
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
    }
}
