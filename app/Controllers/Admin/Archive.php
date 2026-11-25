<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\FacilityModel;
use App\Models\UserModel;

/** Admin only: archived facilities and accounts, with a Restore button. */
class Archive extends BaseController
{
    private const TABLES = ['facilities' => 'facilities', 'users' => 'users'];

    public function index()
    {
        $tab = (string) $this->request->getGet('tab');
        if (! isset(self::TABLES[$tab])) {
            $tab = 'facilities';
        }

        $facilities = (new FacilityModel())->onlyDeleted()->orderBy('deleted_at', 'DESC')->findAll();
        $users      = (new UserModel())->onlyDeleted()->orderBy('deleted_at', 'DESC')->findAll();

        return view('admin/archive/index', [
            'tab'        => $tab,
            'facilities' => $facilities,
            'users'      => $users,
            'roles'      => UserModel::ROLES,
            'types'      => FacilityModel::TYPES,
        ]);
    }

    public function restore(string $what, int $id)
    {
        if (! isset(self::TABLES[$what])) {
            return redirect()->to('/admin/archive')->with('error', 'Unknown item.');
        }
        $table = self::TABLES[$what];
        $db    = \Config\Database::connect();

        $row = $db->table($table)->where('id', $id)->where('deleted_at IS NOT NULL')->get()->getRowArray();
        if (! $row) {
            return redirect()->to('/admin/archive?tab=' . $what)->with('error', 'That item is not in the Archive.');
        }

        $restoreIds = [$id];
        if ($what === 'users') {
            $taken = $db->table('users')->where('email', $row['email'])->where('deleted_at', null)->where('id !=', $id)->countAllResults();
            if ($taken > 0) {
                return redirect()->to('/admin/archive?tab=users')->with('error', 'Cannot restore: that email address is now used by another account.');
            }
        }

        $db->table($table)->whereIn('id', $restoreIds)->update(['deleted_at' => null]);

        $label = ['facilities' => 'Facility', 'users' => 'Account'][$what];
        return redirect()->to('/admin/archive?tab=' . $what)->with('success', $label . (count($restoreIds) > 1 ? ' restored (' . count($restoreIds) . ' days).' : ' restored.'));
    }
}
