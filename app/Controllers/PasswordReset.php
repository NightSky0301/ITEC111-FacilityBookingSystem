<?php

namespace App\Controllers;

use App\Models\UserModel;

/** Forgot password: email a single-use link that expires after 30 minutes. */
class PasswordReset extends BaseController
{
    private const LIFETIME_MIN = 30;
    private const COOLDOWN_SEC = 60;

    public function forgot()
    {
        if (session()->get('user_id')) {
            return redirect()->to('/dashboard');
        }
        return view('auth/forgot');
    }

    public function sendLink()
    {
        if (! $this->validate(['email' => 'required|valid_email|max_length[150]'])) {
            return redirect()->back()->withInput()->with('error', 'Please enter a valid email address.');
        }

        $email = strtolower(trim((string) $this->request->getPost('email')));
        $user  = (new UserModel())->where('email', $email)->first();
        $db    = \Config\Database::connect();

        if ($user && ($user['status'] ?? 'active') === 'active') {
            $recent = $db->table('password_resets')->where('user_id', $user['id'])
                ->where('created_at >', date('Y-m-d H:i:s', time() - self::COOLDOWN_SEC))->countAllResults();

            if ($recent === 0) {
                $token = bin2hex(random_bytes(32));
                $db->table('password_resets')->where('user_id', $user['id'])->where('used_at', null)->update(['used_at' => date('Y-m-d H:i:s')]);
                $db->table('password_resets')->insert([
                    'user_id'    => (int) $user['id'],
                    'token_hash' => hash('sha256', $token),
                    'expires_at' => date('Y-m-d H:i:s', time() + self::LIFETIME_MIN * 60),
                    'created_at' => date('Y-m-d H:i:s'),
                ]);
                $this->mail($user, site_url('reset-password/' . $token));
            }
        }

        // Same answer whether or not the email exists, so nobody can probe which emails are registered.
        return redirect()->to('/forgot-password')->with('sent', $email);
    }

    public function reset(string $token)
    {
        if (! $this->validRow($token)) {
            return view('auth/reset', ['token' => null]);
        }
        return view('auth/reset', ['token' => $token]);
    }

    public function update(string $token)
    {
        $row = $this->validRow($token);
        if (! $row) {
            return view('auth/reset', ['token' => null]);
        }

        $rules = [
            'password'         => ['rules' => 'required|min_length[8]|max_length[72]|regex_match[/^(?=.*[A-Z])(?=.*[a-z])(?=.*[0-9])(?=.*[^A-Za-z0-9]).+$/]', 'errors' => ['regex_match' => 'Password needs an uppercase letter, a letter, a number and a special character.']],
            'password_confirm' => 'required|matches[password]',
        ];
        if (! $this->validate($rules)) {
            return redirect()->back()->with('errors', $this->validator->getErrors());
        }

        (new UserModel())->update((int) $row['user_id'], ['password' => password_hash((string) $this->request->getPost('password'), PASSWORD_DEFAULT)]);
        \Config\Database::connect()->table('password_resets')->where('user_id', $row['user_id'])->where('used_at', null)->update(['used_at' => date('Y-m-d H:i:s')]);

        return redirect()->to('/login')->with('success', 'Your password was changed. You can log in now.');
    }

    /** The unused, unexpired reset row for this token, or null. */
    private function validRow(string $token): ?array
    {
        if (! preg_match('/^[a-f0-9]{64}$/', $token)) {
            return null;
        }
        $row = \Config\Database::connect()->table('password_resets')
            ->where('token_hash', hash('sha256', $token))->where('used_at', null)
            ->where('expires_at >', date('Y-m-d H:i:s'))->get()->getRowArray();
        if (! $row) {
            return null;
        }
        $user = (new UserModel())->find((int) $row['user_id']);

        return ($user && ($user['status'] ?? 'active') === 'active') ? $row : null;
    }

    private function mail(array $user, string $link): void
    {
        try {
            $email = service('email');
            $email->setTo($user['email']);
            $email->setSubject('[Athletix] Reset your password');
            $email->setMailType('html');
            $email->setMessage(
                '<div style="font-family:Arial,sans-serif;max-width:480px;margin:auto;color:#18302f">'
                . '<h2 style="color:#0e3b3f">Reset your password</h2>'
                . '<p>Hi ' . esc($user['name']) . ',</p>'
                . '<p>We got a request to reset the password for your account. Click the button below to choose a new one. This link works once and expires in ' . self::LIFETIME_MIN . ' minutes.</p>'
                . '<p style="margin:28px 0"><a href="' . esc($link, 'attr') . '" style="background:#a8782b;color:#fff;padding:12px 22px;border-radius:8px;text-decoration:none;font-weight:bold">Choose a new password</a></p>'
                . '<p style="font-size:13px;color:#5b6d6c">If the button does not work, copy this link into your browser:<br>' . esc($link) . '</p>'
                . '<p style="font-size:13px;color:#5b6d6c">If you did not ask for this, you can ignore this email. Your password stays the same.</p>'
                . '<hr><small>Athletix</small></div>'
            );
            if (! $email->send(false)) {
                log_message('error', 'Password reset email to ' . $user['email'] . ' failed: ' . $email->printDebugger(['headers']));
            }
        } catch (\Throwable $e) {
            log_message('error', 'Password reset email exception: ' . $e->getMessage());
        }
    }
}
