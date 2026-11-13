<?php

namespace App\Controllers;

use App\Models\UserModel;

class Auth extends BaseController
{
    public function login()
    {
        if (session()->get('user_id')) {
            return redirect()->to('/dashboard');
        }
        return view('auth/login');
    }

    public function attemptLogin()
    {
        $rules = [
            'email'    => 'required|valid_email',
            'password' => 'required',
        ];
        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('error', 'Please enter a valid email and password.');
        }

        $user = (new UserModel())->where('email', strtolower(trim($this->request->getPost('email'))))->first();

        if (! $user || ! password_verify((string) $this->request->getPost('password'), $user['password'])) {
            return redirect()->back()->withInput()->with('error', 'Wrong email or password.');
        }

        session()->regenerate();
        session()->set([
            'user_id' => (int) $user['id'],
            'name'    => $user['name'],
            'email'   => $user['email'],
            'role'    => $user['role'],
        ]);

        return redirect()->to('/dashboard')->with('success', 'Welcome back, ' . $user['name'] . '!');
    }

    public function register()
    {
        if (session()->get('user_id')) {
            return redirect()->to('/dashboard');
        }
        return view('auth/register');
    }

    public function attemptRegister()
    {
        $rules = [
            'name'             => 'required|min_length[2]|max_length[120]',
            'email'            => 'required|valid_email|max_length[150]|is_unique[users.email]',
            'phone'            => 'permit_empty|max_length[30]',
            'password'         => 'required|min_length[6]',
            'password_confirm' => 'required|matches[password]',
        ];
        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        // Public sign-ups are always customers. Admins are created by seeding / database.
        (new UserModel())->insert([
            'name'     => trim($this->request->getPost('name')),
            'email'    => strtolower(trim($this->request->getPost('email'))),
            'phone'    => trim((string) $this->request->getPost('phone')),
            'password' => password_hash((string) $this->request->getPost('password'), PASSWORD_DEFAULT),
            'role'     => 'customer',
        ]);

        return redirect()->to('/login')->with('success', 'Account created. You can log in now.');
    }

    public function logout()
    {
        session()->destroy();
        return redirect()->to('/login')->with('success', 'You have been logged out.');
    }
}
