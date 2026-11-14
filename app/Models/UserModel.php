<?php

namespace App\Models;

use CodeIgniter\Model;

class UserModel extends Model
{
    protected $table         = 'users';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $allowedFields = ['name', 'email', 'phone', 'password', 'role', 'status'];
    protected $useTimestamps = true;

    /** Roles and what they mean. Shown on the Users page. */
    public const ROLES = [
        'admin'    => 'Admin',
        'staff'    => 'Staff',
        'customer' => 'Customer',
    ];
}
