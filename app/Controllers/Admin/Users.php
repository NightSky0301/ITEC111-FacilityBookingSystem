<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\UserModel;

/** Admin only: create staff accounts, change roles, deactivate accounts. */
class Users extends BaseController
{
    public function index()
    {
        $role  = $this->request->getGet('role');
        $model = new UserModel();

        if ($role && isset(UserModel::ROLES[$role])) {
            $model->where('role', $role);
        }

        // Admins first, then staff, then customers; alphabetical inside each group.
        $users = $model->orderBy("CASE role WHEN 'admin' THEN 1 WHEN 'staff' THEN 2 ELSE 3 END", '', false)
            ->orderBy('name')
            ->findAll();

        return view('admin/users/index', [
            'users' => $users,
            'roles' => UserModel::ROLES,
            'role'  => $role,
            'meId'  => (int) session()->get('user_id'),
        ]);
    }

    public function new()
    {
        return view('admin/users/form', ['user' => null, 'roles' => UserModel::ROLES]);
    }

    public function create()
    {
        $rules = [
            'name'     => 'required|min_length[2]|max_length[120]',
            'email'    => 'required|valid_email|max_length[150]|is_unique[users.email]',
            'phone'    => 'permit_empty|max_length[30]',
            'role'     => 'required|in_list[admin,staff,customer]',
            'password' => 'required|min_length[6]',
        ];
        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        (new UserModel())->insert([
            'name'     => trim($this->request->getPost('name')),
            'email'    => strtolower(trim($this->request->getPost('email'))),
            'phone'    => trim((string) $this->request->getPost('phone')),
            'role'     => $this->request->getPost('role'),
            'status'   => 'active',
            'password' => password_hash((string) $this->request->getPost('password'), PASSWORD_DEFAULT),
        ]);

        return redirect()->to('/admin/users')->with('success', 'Account created. Share the email and password with them privately.');
    }

    public function edit(int $id)
    {
        $user = (new UserModel())->find($id);
        if (! $user) {
            return redirect()->to('/admin/users')->with('error', 'User not found.');
        }
        return view('admin/users/form', ['user' => $user, 'roles' => UserModel::ROLES]);
    }

    public function update(int $id)
    {
        $model = new UserModel();
        $user  = $model->find($id);
        if (! $user) {
            return redirect()->to('/admin/users')->with('error', 'User not found.');
        }

        $rules = [
            'name'     => 'required|min_length[2]|max_length[120]',
            'email'    => 'required|valid_email|max_length[150]|is_unique[users.email,id,' . $id . ']',
            'phone'    => 'permit_empty|max_length[30]',
            'role'     => 'required|in_list[admin,staff,customer]',
            'password' => 'permit_empty|min_length[6]',
        ];
        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $data = [
            'name'  => trim($this->request->getPost('name')),
            'email' => strtolower(trim($this->request->getPost('email'))),
            'phone' => trim((string) $this->request->getPost('phone')),
            'role'  => $this->request->getPost('role'),
        ];

        // Admins cannot lock themselves out by changing their own role.
        if ($id === (int) session()->get('user_id')) {
            $data['role'] = $user['role'];
        }

        $password = (string) $this->request->getPost('password');
        if ($password !== '') {
            $data['password'] = password_hash($password, PASSWORD_DEFAULT);
        }

        $model->update($id, $data);

        return redirect()->to('/admin/users')->with('success', 'Account updated.');
    }

    public function toggle(int $id)
    {
        if ($id === (int) session()->get('user_id')) {
            return redirect()->to('/admin/users')->with('error', 'You cannot deactivate your own account.');
        }

        $model = new UserModel();
        $user  = $model->find($id);
        if (! $user) {
            return redirect()->to('/admin/users')->with('error', 'User not found.');
        }

        $new = ($user['status'] ?? 'active') === 'active' ? 'inactive' : 'active';
        $model->update($id, ['status' => $new]);

        return redirect()->to('/admin/users')->with('success', $user['name'] . ($new === 'active' ? ' can log in again.' : ' has been deactivated and logged out.'));
    }
}
