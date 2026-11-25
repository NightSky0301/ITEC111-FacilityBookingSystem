<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Libraries\NotificationService;
use App\Models\BookingModel;
use App\Models\FacilityModel;

class Bookings extends BaseController
{
    public const CATEGORIES = ['activities' => 'Sport Activities', 'hotels' => 'Hotel & Suites', 'fitness' => 'Fitness & Wellbeing', 'entertainment' => 'Entertainment'];

    /** Which of the four groups a facility type belongs to. */
    public static function categoryOf(string $type): string
    {
        return FacilityModel::sectionOf($type);
    }

    public function index()
    {
        $status     = $this->request->getGet('status');
        $facilityId = (int) $this->request->getGet('facility_id');
        $category   = (string) $this->request->getGet('category');
        if (! isset(self::CATEGORIES[$category])) {
            $category = '';
        }

        $bookings   = (new BookingModel())->withDetails([
            'status'      => $status ?: null,
            'facility_id' => $facilityId ?: null,
        ]);
        $facilities = (new FacilityModel())->orderBy('name')->findAll();
        if ($category !== '') {
            $bookings   = array_values(array_filter($bookings, static fn ($b) => self::categoryOf((string) ($b['facility_type'] ?? '')) === $category));
            $facilities = array_values(array_filter($facilities, static fn ($f) => self::categoryOf((string) $f['type']) === $category));
        }

        return view('admin/bookings/index', [
            'bookings'   => $bookings,
            'facilities' => $facilities,
            'status'     => $status,
            'facilityId' => $facilityId,
            'category'   => $category,
            'categories' => self::CATEGORIES,
        ]);
    }

    /** The days an action covers: the whole stay (only days in $statuses, not yet ended), or just this one day (single=1). */
    private function days(BookingModel $bookings, array $booking, array $statuses, bool $futureOnly): array
    {
        if ($this->request->getPost('single') === '1') {
            return in_array($booking['status'], $statuses, true) ? [$booking] : [];
        }

        return $bookings->groupRows($booking, $statuses, $futureOnly);
    }

    private function dayText(array $days): string
    {
        return count($days) > 1 ? count($days) . ' days from ' . fmt_dt($days[0]['start_at']) : fmt_dt($days[0]['start_at']);
    }

    /** Ends the open transaction (nothing was written) and goes back with an error. */
    private function deny(\CodeIgniter\Database\BaseConnection $db, string $message)
    {
        $db->transComplete();

        return redirect()->back()->with('error', $message);
    }

    public function approve(int $id)
    {
        $bookings = new BookingModel();
        $booking  = $bookings->findWithDetails($id);

        if (! $booking || $booking['status'] !== 'pending') {
            return redirect()->back()->with('error', 'Only pending bookings can be approved.');
        }
        $days = $this->days($bookings, $booking, ['pending'], true);
        if (! $days) {
            return redirect()->back()->with('error', 'Only pending bookings can be approved.');
        }

        // Every day is checked first; nothing is approved unless all of them are free.
        $perHead = FacilityModel::isPerHead((string) $booking['facility_type']);
        $hasRef  = str_contains((string) $booking['addons'], '"key":"' . \App\Libraries\Addons::REFEREE_KEY . '"');
        $db = \Config\Database::connect();
        $db->transStart();
        $bookings->lockFacility((int) $booking['facility_id']);   // two admins approving at once take turns
        foreach ($days as $d) {
            $label = count($days) > 1 ? ' on ' . date('D, M j', strtotime($d['start_at'])) : ' during this time';
            if ($perHead) {
                if (! $bookings->hasRoom(['id' => $booking['facility_id'], 'capacity' => $booking['facility_capacity']], $d['start_at'], $d['end_at'], max(1, (int) $d['guests']), (int) $d['id'])) {
                    return $this->deny($db,  'Cannot approve: the gym would go over its ' . (int) $booking['facility_capacity'] . '-person limit' . $label . '.');
                }
            } elseif ($bookings->hasConflict((int) $booking['facility_id'], $d['start_at'], $d['end_at'], (int) $d['id'])) {
                return $this->deny($db,  'Cannot approve: it overlaps with an already approved booking' . $label . '.');
            }
            if ($hasRef && $bookings->refereePeak($d['start_at'], $d['end_at'], (int) $d['id']) >= \App\Libraries\Addons::REFEREE_POOL) {
                return $this->deny($db,  'Cannot approve: all ' . \App\Libraries\Addons::REFEREE_POOL . ' referees are booked' . $label . '.');
            }
        }

        $remarks = trim((string) $this->request->getPost('admin_remarks'));
        foreach ($days as $d) {
            $bookings->update((int) $d['id'], ['status' => 'approved', 'admin_remarks' => $remarks, 'approved_by' => (int) session()->get('user_id')]);
        }
        $db->transComplete();

        (new NotificationService())->notifyUser(
            (int) $booking['user_id'],
            'Booking approved',
            'Your booking for ' . $booking['facility_name'] . ' (' . $this->dayText($days) . ') was approved.' . ($remarks ? ' Note: ' . $remarks : ''),
            $id
        );

        return redirect()->back()->with('success', count($days) > 1 ? 'Booking approved (' . count($days) . ' days).' : 'Booking approved.');
    }

    /** Desk confirms the customer has arrived. Only possible inside the customer's arrival window. */
    public function arrive(int $id)
    {
        $bookings = new BookingModel();
        $booking  = $bookings->findWithDetails($id);

        if (! $booking || $booking['status'] !== 'approved') {
            return redirect()->back()->with('error', 'Only approved bookings can be checked in.');
        }
        if (! empty($booking['arrived_at'])) {
            return redirect()->back()->with('error', 'This arrival is already confirmed.');
        }
        // Arrival can only be confirmed inside the arrival window the customer chose (e.g. 12:00 PM – 12:30 PM).
        [$ws, $we] = arrival_window($booking);
        $label     = date('g:i A', $ws) . ' – ' . date('g:i A', $we);
        $now       = strtotime(date('Y-m-d H:i:00'));
        if ($now < $ws) {
            return redirect()->back()->with('error', 'Too early: arrival can be confirmed from ' . $label . '.');
        }
        if (time() >= $we) {
            return redirect()->back()->with('error', 'The arrival window (' . $label . ') has passed, so this booking counts as Did not arrive.');
        }

        $bookings->update($id, ['arrived_at' => date('Y-m-d H:i:s'), 'arrived_by' => (int) session()->get('user_id')]);

        return redirect()->back()->with('success', 'Arrival confirmed for ' . $booking['customer_name'] . ' at ' . $booking['facility_name'] . '.');
    }

    public function reject(int $id)
    {
        $bookings = new BookingModel();
        $booking  = $bookings->findWithDetails($id);

        if (! $booking || $booking['status'] !== 'pending') {
            return redirect()->back()->with('error', 'Only pending bookings can be rejected.');
        }

        $days    = $this->days($bookings, $booking, ['pending'], false);
        $remarks = trim((string) $this->request->getPost('admin_remarks'));
        foreach ($days as $d) {
            $bookings->update((int) $d['id'], ['status' => 'rejected', 'admin_remarks' => $remarks]);
        }

        (new NotificationService())->notifyUser(
            (int) $booking['user_id'],
            'Booking rejected',
            'Your booking for ' . $booking['facility_name'] . ' (' . $this->dayText($days) . ') was rejected.' . ($remarks ? ' Reason: ' . $remarks : ''),
            $id
        );

        return redirect()->back()->with('success', count($days) > 1 ? 'Booking rejected (' . count($days) . ' days).' : 'Booking rejected.');
    }

    public function cancel(int $id)
    {
        $bookings = new BookingModel();
        $booking  = $bookings->findWithDetails($id);

        if (! $booking || $booking['status'] !== 'approved') {
            return redirect()->back()->with('error', 'Only approved bookings can be cancelled by admin.');
        }

        // Days that already happened stay as they are; every day still to come is cancelled.
        $days    = $this->days($bookings, $booking, ['approved'], true);
        $remarks = trim((string) $this->request->getPost('admin_remarks'));
        foreach ($days as $d) {
            $bookings->update((int) $d['id'], ['status' => 'cancelled', 'admin_remarks' => $remarks]);
        }
        if (! $days) {
            return redirect()->back()->with('error', 'Nothing left to cancel: every day has already passed.');
        }

        (new NotificationService())->notifyUser(
            (int) $booking['user_id'],
            'Booking cancelled',
            'Your approved booking for ' . $booking['facility_name'] . ' (' . $this->dayText($days) . ') was cancelled by the admin.' . ($remarks ? ' Reason: ' . $remarks : ''),
            $id
        );

        return redirect()->back()->with('success', count($days) > 1 ? 'Booking cancelled (' . count($days) . ' days).' : 'Booking cancelled.');
    }
}
