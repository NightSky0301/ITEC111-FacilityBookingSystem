<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\BookingModel;
use App\Models\FacilityModel;
use App\Models\UserModel;

/** Admin only: everything that was deleted, with a Restore button. */
class Archive extends BaseController
{
    private const TABLES = ['facilities' => 'facilities', 'users' => 'users', 'bookings' => 'bookings'];

    public function index()
    {
        $tab = (string) $this->request->getGet('tab');
        if (! isset(self::TABLES[$tab])) {
            $tab = 'facilities';
        }

        $facilities = (new FacilityModel())->onlyDeleted()->orderBy('deleted_at', 'DESC')->findAll();
        $users      = (new UserModel())->onlyDeleted()->orderBy('deleted_at', 'DESC')->findAll();
        $bookings   = (new BookingModel())->onlyDeleted()
            ->select('bookings.*, facilities.name AS facility_name, facilities.type AS facility_type, facilities.image AS facility_image, users.name AS customer_name, users.email AS customer_email')
            ->join('facilities', 'facilities.id = bookings.facility_id', 'left')
            ->join('users', 'users.id = bookings.user_id', 'left')
            ->orderBy('bookings.deleted_at', 'DESC')
            ->findAll();

        return view('admin/archive/index', [
            'tab'        => $tab,
            'facilities' => $facilities,
            'users'      => $users,
            'bookings'   => $bookings,
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

        if ($what === 'bookings' && in_array($row['status'], ['approved', 'pending'], true)) {
            // Restoring an approved booking must not double-book the court.
            if ($row['status'] === 'approved' && (new BookingModel())->hasConflict((int) $row['facility_id'], $row['start_at'], $row['end_at'], $id)) {
                return redirect()->to('/admin/archive?tab=bookings')->with('error', 'Cannot restore: another approved booking now uses that time slot.');
            }
        }
        if ($what === 'users') {
            $taken = $db->table('users')->where('email', $row['email'])->where('deleted_at', null)->where('id !=', $id)->countAllResults();
            if ($taken > 0) {
                return redirect()->to('/admin/archive?tab=users')->with('error', 'Cannot restore: that email address is now used by another account.');
            }
        }

        $db->table($table)->where('id', $id)->update(['deleted_at' => null]);

        $label = ['facilities' => 'Facility', 'users' => 'Account', 'bookings' => 'Booking'][$what];
        return redirect()->to('/admin/archive?tab=' . $what)->with('success', $label . ' restored.');
    }
}
