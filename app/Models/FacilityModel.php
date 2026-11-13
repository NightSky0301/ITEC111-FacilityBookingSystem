<?php

namespace App\Models;

use CodeIgniter\Model;

class FacilityModel extends Model
{
    protected $table         = 'facilities';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $allowedFields = ['name', 'type', 'description', 'capacity', 'price_per_hour', 'status'];
    protected $useTimestamps = true;

    /** Facility types shown in the dropdowns. Add more here anytime. */
    public const TYPES = [
        'basketball'    => 'Basketball Court',
        'volleyball'    => 'Volleyball Court',
        'pickleball'    => 'Pickleball Court',
        'badminton'     => 'Badminton Court',
        'tennis'        => 'Tennis Court',
        'futsal'        => 'Futsal / Football',
        'multipurpose'  => 'Multipurpose Court',
        'other'         => 'Other',
    ];

    public function active(): array
    {
        return $this->where('status', 'active')->orderBy('type')->orderBy('name')->findAll();
    }
}
