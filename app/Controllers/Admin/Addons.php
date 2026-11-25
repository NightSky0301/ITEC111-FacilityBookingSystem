<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Libraries\Addons as AddonList;

/** Admin: the list of add-ons players can pick (which facility offers which is set on each facility's form). */
class Addons extends BaseController
{
    public function index()
    {
        $db   = db_connect();
        $uses = [];
        foreach ($db->table('facility_addons fa')->select('fa.addon_id, COUNT(*) AS n')->join('facilities f', 'f.id = fa.facility_id AND f.deleted_at IS NULL')->groupBy('fa.addon_id')->get()->getResultArray() as $r) {
            $uses[(int) $r['addon_id']] = (int) $r['n'];
        }

        return view('admin/addons/index', [
            'addons'    => AddonList::all(true),
            'uses'      => $uses,
            'perLabels' => AddonList::PER_LABELS,
        ]);
    }

    private function fields(): array
    {
        return [
            'label' => trim((string) $this->request->getPost('label')),
            'price' => (string) $this->request->getPost('price'),
            'per'   => (string) $this->request->getPost('per'),
            'group' => (string) $this->request->getPost('group'),
            'note'  => trim((string) $this->request->getPost('note')),
        ];
    }

    private function valid(array $f): ?string
    {
        if ($f['label'] === '' || mb_strlen($f['label']) > 80) {
            return 'Give the add-on a name (up to 80 characters).';
        }
        if (! is_numeric($f['price']) || (float) $f['price'] < 0) {
            return 'The price must be a number, 0 or more.';
        }
        if (! isset(AddonList::PER_LABELS[$f['per']])) {
            return 'Choose how the add-on is charged.';
        }

        return null;
    }

    public function create()
    {
        $f = $this->fields();
        if ($err = $this->valid($f)) {
            return redirect()->back()->withInput()->with('error', $err);
        }
        AddonList::create($f['label'], (float) $f['price'], $f['per'], $f['group'], $f['note'], (bool) $this->request->getPost('evening'), (bool) $this->request->getPost('indoor'));

        return redirect()->to('/admin/addons')->with('success', $f['label'] . ' was created. Tick it on a facility to offer it.');
    }

    public function update(int $id)
    {
        $row = db_connect()->table('addons')->where('id', $id)->get()->getRowArray();
        if (! $row) {
            return redirect()->to('/admin/addons')->with('error', 'Add-on not found.');
        }
        $f = $this->fields();
        if ($err = $this->valid($f)) {
            return redirect()->back()->with('error', $err);
        }
        db_connect()->table('addons')->where('id', $id)->update([
            'label' => $f['label'], 'note' => mb_substr($f['note'], 0, 160), 'price' => (float) $f['price'], 'per' => $f['per'],
            'grp' => $f['group'] === 'cooling' ? 'cooling' : 'extra',
            'evening' => $this->request->getPost('evening') ? 1 : 0, 'indoor' => $this->request->getPost('indoor') ? 1 : 0,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        return redirect()->to('/admin/addons')->with('success', $f['label'] . ' was updated. New bookings use the new price; existing bookings keep theirs.');
    }

    /** Turn an add-on off (players stop seeing it) or back on. Nothing is erased. */
    public function toggle(int $id)
    {
        $row = db_connect()->table('addons')->where('id', $id)->get()->getRowArray();
        if (! $row) {
            return redirect()->to('/admin/addons')->with('error', 'Add-on not found.');
        }
        $new = $row['status'] === 'active' ? 'inactive' : 'active';
        db_connect()->table('addons')->where('id', $id)->update(['status' => $new, 'updated_at' => date('Y-m-d H:i:s')]);

        return redirect()->to('/admin/addons')->with('success', $row['label'] . ($new === 'active' ? ' is on again.' : ' is turned off. Players no longer see it.'));
    }
}
