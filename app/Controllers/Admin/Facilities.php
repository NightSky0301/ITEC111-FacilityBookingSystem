<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\BookingModel;
use App\Models\FacilityModel;

class Facilities extends BaseController
{
    private function rules(): array
    {
        return [
            'name'           => 'required|min_length[2]|max_length[120]',
            'type'           => 'required|max_length[40]',
            'description'    => 'permit_empty|max_length[2000]',
            'capacity'       => 'required|is_natural_no_zero',
            'price_per_hour' => 'required|decimal',
            'status'         => 'required|in_list[active,inactive]',
        ];
    }

    private function payload(): array
    {
        return [
            'name'           => trim($this->request->getPost('name')),
            'type'           => $this->request->getPost('type'),
            'description'    => trim((string) $this->request->getPost('description')),
            'capacity'       => (int) $this->request->getPost('capacity'),
            'price_per_hour' => (float) $this->request->getPost('price_per_hour'),
            'status'         => $this->request->getPost('status'),
        ];
    }

    public function index()
    {
        return view('admin/facilities/index', [
            'facilities' => (new FacilityModel())->orderBy('type')->orderBy('name')->findAll(),
            'types'      => FacilityModel::TYPES,
        ]);
    }

    public function new()
    {
        return view('admin/facilities/form', ['facility' => null, 'types' => FacilityModel::TYPES]);
    }

    public function create()
    {
        if (! $this->validate($this->rules())) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }
        (new FacilityModel())->insert($this->payload());

        return redirect()->to('/admin/facilities')->with('success', 'Facility added.');
    }

    public function edit(int $id)
    {
        $facility = (new FacilityModel())->find($id);
        if (! $facility) {
            return redirect()->to('/admin/facilities')->with('error', 'Facility not found.');
        }
        return view('admin/facilities/form', ['facility' => $facility, 'types' => FacilityModel::TYPES]);
    }

    public function update(int $id)
    {
        $model = new FacilityModel();
        if (! $model->find($id)) {
            return redirect()->to('/admin/facilities')->with('error', 'Facility not found.');
        }
        if (! $this->validate($this->rules())) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }
        $model->update($id, $this->payload());

        return redirect()->to('/admin/facilities')->with('success', 'Facility updated.');
    }

    public function delete(int $id)
    {
        $model = new FacilityModel();
        if (! $model->find($id)) {
            return redirect()->to('/admin/facilities')->with('error', 'Facility not found.');
        }

        // Keep booking history intact: facilities that have bookings are deactivated instead of deleted.
        if ((new BookingModel())->where('facility_id', $id)->countAllResults() > 0) {
            $model->update($id, ['status' => 'inactive']);
            return redirect()->to('/admin/facilities')->with('success', 'Facility has booking history, so it was set to inactive instead of deleted.');
        }

        $model->delete($id);
        return redirect()->to('/admin/facilities')->with('success', 'Facility deleted.');
    }
}
