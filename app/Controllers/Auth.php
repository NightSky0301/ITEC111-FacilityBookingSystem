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
        return view('auth/portal', ['mode' => 'login']);
    }

    public function attemptLogin()
    {
        $rules = [
            'email'    => 'required|max_length[150]',
            'password' => 'required',
        ];
        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('error', 'Please enter a valid email and password.');
        }

        $user = (new UserModel())->where('email', strtolower(trim($this->request->getPost('email'))))->first();

        if (! $user || ! password_verify((string) $this->request->getPost('password'), $user['password'])) {
            return redirect()->back()->withInput()->with('error', 'Wrong email or password.');
        }

        if (($user['status'] ?? 'active') !== 'active') {
            return redirect()->back()->withInput()->with('error', 'This account has been deactivated. Please contact the administrator.');
        }

        session()->regenerate();
        session()->set([
            'user_id' => (int) $user['id'],
            'name'    => $user['name'],
            'email'   => $user['email'],
            'role'    => $user['role'],
        ]);

        // Logging in always lands on the main page: the home page for customers, the Dashboard for staff and admin.
        return redirect()->to('/dashboard')->with('welcome', $user['name']);
    }

    public function register()
    {
        if (session()->get('user_id')) {
            return redirect()->to('/dashboard');
        }
        return view('auth/portal', ['mode' => 'register']);
    }

    public function attemptRegister()
    {
        $rules = [
            'first_name'       => ['label' => 'First name', 'rules' => 'required|min_length[2]|max_length[50]|regex_match[/^[^0-9]+$/]', 'errors' => ['regex_match' => 'First name cannot contain numbers.']],
            'middle_name'      => ['label' => 'Middle name', 'rules' => 'permit_empty|max_length[50]|regex_match[/^[^0-9]+$/]', 'errors' => ['regex_match' => 'Middle name cannot contain numbers.']],
            'last_name'        => ['label' => 'Last name', 'rules' => 'required|min_length[2]|max_length[50]|regex_match[/^[^0-9]+$/]', 'errors' => ['regex_match' => 'Last name cannot contain numbers.']],
            'email'            => 'required|valid_email|max_length[150]|is_unique[users.email]',
            'phone'            => ['rules' => 'permit_empty|regex_match[/^[0-9]{7,15}$/]', 'errors' => ['regex_match' => 'Phone must be 7 to 15 digits, numbers only.']],
            'password'         => ['rules' => 'required|min_length[8]|max_length[72]|regex_match[/^(?=.*[A-Z])(?=.*[a-z])(?=.*[0-9])(?=.*[^A-Za-z0-9]).+$/]', 'errors' => ['regex_match' => 'Password needs an uppercase letter, a letter, a number and a special character.']],
            'password_confirm' => 'required|matches[password]',
        ];
        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        // The users table keeps one "name" column: First Middle Last.
        $parts = array_filter(array_map(
            fn ($k) => trim((string) preg_replace('/\s+/', ' ', (string) $this->request->getPost($k))),
            ['first_name', 'middle_name', 'last_name']
        ), static fn ($v) => $v !== '');
        $fullName = implode(' ', $parts);

        // Public sign-ups are always customers. Admins are created by seeding / database.
        (new UserModel())->insert([
            'name'     => $fullName,
            'email'    => strtolower(trim($this->request->getPost('email'))),
            'phone'    => trim((string) $this->request->getPost('phone')),
            'password' => password_hash((string) $this->request->getPost('password'), PASSWORD_DEFAULT),
            'role'     => 'customer',
        ]);

        return redirect()->to('/login')->with('success', 'Account created. You can log in now.');
    }

    /** Logging out is a POST (after the "Do you really want to log out?" pop-up); opening the address in a browser does nothing. */
    public function logout()
    {
        if (strtolower($this->request->getMethod()) !== 'post') {
            return redirect()->to('/dashboard');
        }
        session()->remove(['user_id', 'name', 'email', 'role']);
        session()->regenerate(true);   // new session id; (destroy() would also throw away the flash message below)
        return redirect()->to('/')->with('success', 'You have been logged out.');
    }
}