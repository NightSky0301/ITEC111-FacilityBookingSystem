<?php

namespace App\Filters;

use App\Models\UserModel;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Blocks guests: must be logged in.
 * On every request it also re-reads the account, so a deactivated account is
 * logged out right away and a changed role takes effect immediately.
 */
class AuthFilter implements FilterInterface
{
    /** Returns a redirect when the visitor may not continue, or null when everything is fine. */
    public static function guard()
    {
        $id = (int) session()->get('user_id');
        if (! $id) {
            // Remember the page so the person lands back on it after logging in.
            $req = service('request');
            if (strtolower($req->getMethod()) === 'get' && ! $req->isAJAX()) {
                session()->set('after_login', $req->getUri()->getRoutePath() . ($req->getUri()->getQuery() !== '' ? '?' . $req->getUri()->getQuery() : ''));
            }
            return redirect()->to('/login')->with('error', 'Please log in first.');
        }

        $user = (new UserModel())->find($id);
        if (! $user || ($user['status'] ?? 'active') !== 'active') {
            session()->remove(['user_id', 'name', 'email', 'role']);
            return redirect()->to('/login')->with('error', 'This account is not active. Please contact the administrator.');
        }

        session()->set(['role' => $user['role'], 'name' => $user['name'], 'email' => $user['email']]);
        return null;
    }

    public function before(RequestInterface $request, $arguments = null)
    {
        return self::guard();
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
    }
}
