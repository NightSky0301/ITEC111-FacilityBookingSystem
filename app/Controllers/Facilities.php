<?php

namespace App\Controllers;

use App\Models\FacilityModel;

/** Customer-facing list of bookable facilities. */
class Facilities extends BaseController
{
    public function index()
    {
        return view('facilities/index', [
            'facilities' => (new FacilityModel())->active(),
            'types'      => FacilityModel::TYPES,
        ]);
    }
}
