<?php

namespace App\Controllers;

use App\Libraries\Addons;
use App\Libraries\NotificationService;
use App\Models\BookingModel;
use App\Models\FacilityModel;
use App\Models\HotelModel;
use DateTime;

/**
 * Hotels: browse hotels and rooms (open to everyone), then request a stay by choosing check-in and
 * check-out dates. A stay is one booking that runs from check-in time to check-out time; there is no payment,
 * staff approve the request like any other booking.
 */
class Hotels extends BaseController
{
    public function index()
    {
        helper('booking');

        $model = new HotelModel();

        return view('hotels/index', ['hotels' => $model->withTonight($model->withRooms())]);
    }

    public function show(int $id)
    {
        helper('booking');
        $hotels = new HotelModel();
        $hotel  = $id > 0 ? $hotels->find($id) : null;
        if (! $hotel || $hotel['status'] !== 'active') {
            return redirect()->to('/hotels')->with('error', 'We could not find that hotel.');
        }

        $bookings = new BookingModel();
        $tonightIn  = date('Y-m-d') . ' ' . $hotel['check_in_time'] . ':00';
        $tonightOut = date('Y-m-d', strtotime('+1 day')) . ' ' . $hotel['check_out_time'] . ':00';
        $rooms      = $hotels->rooms((int) $hotel['id']);
        foreach ($rooms as &$r) {
            $r['free_tonight'] = ! $bookings->hasConflict((int) $r['id'], $tonightIn, $tonightOut);
        }
        unset($r);

        return view('hotels/show', ['hotel' => $hotel, 'rooms' => $rooms]);
    }

    public function room(int $id)
    {
        helper('booking');
        $room = $id > 0 ? (new FacilityModel())->find($id) : null;
        if (! $room || ! FacilityModel::isRoom($room) || ($room['status'] !== 'active' && ! is_manager())) {
            return redirect()->to('/hotels')->with('error', 'That room is not available.');
        }
        $hotel = ! empty($room['hotel_id']) ? (new HotelModel())->find((int) $room['hotel_id']) : null;
        if (! $hotel) {
            return redirect()->to('/hotels')->with('error', 'That room is not available.');
        }

        return view('hotels/room', [
            'room'  => $room,
            'hotel' => $hotel,
            'taken' => (new HotelModel())->takenRanges($id),
            'addons' => Addons::forFacility($room),
        ]);
    }

    /** JSON: nights already taken for a room (the room page asks again when something changes). */
    public function taken(int $id)
    {
        return $this->response->setHeader('Cache-Control', 'no-store')->setJSON((new HotelModel())->takenRanges($id));
    }

    /** POST: request a stay. */
    public function book()
    {
        $room = (new FacilityModel())->find((int) $this->request->getPost('room_id'));
        if (! $room || ! FacilityModel::isRoom($room) || $room['status'] !== 'active') {
            return redirect()->to('/hotels')->with('error', 'That room is not available.');
        }
        $back  = '/hotels/room/' . (int) $room['id'];
        $hotel = ! empty($room['hotel_id']) ? (new HotelModel())->find((int) $room['hotel_id']) : null;
        if (! $hotel) {
            return redirect()->to('/hotels')->with('error', 'That room is not available.');
        }

        $in  = DateTime::createFromFormat('!Y-m-d', (string) $this->request->getPost('check_in'));
        $out = DateTime::createFromFormat('!Y-m-d', (string) $this->request->getPost('check_out'));
        if (! $in || ! $out || $in->format('Y-m-d') !== (string) $this->request->getPost('check_in') || $out->format('Y-m-d') !== (string) $this->request->getPost('check_out')) {
            return redirect()->to($back)->withInput()->with('error', 'Choose your check-in and check-out dates.');
        }
        $today = new DateTime('today');
        if ($in < $today) {
            return redirect()->to($back)->withInput()->with('error', 'Check-in cannot be in the past.');
        }
        if ($in > (clone $today)->modify('+' . HotelModel::MAX_AHEAD_DAYS . ' days')) {
            return redirect()->to($back)->withInput()->with('error', 'Rooms can be booked up to a year ahead.');
        }
        $nights = (int) $in->diff($out)->format('%r%a');
        if ($nights < 1) {
            return redirect()->to($back)->withInput()->with('error', 'Check-out must be after check-in.');
        }
        if ($nights > HotelModel::MAX_NIGHTS) {
            return redirect()->to($back)->withInput()->with('error', 'You can book up to ' . HotelModel::MAX_NIGHTS . ' nights at a time.');
        }
        $startAt  = $in->format('Y-m-d') . ' ' . $hotel['check_in_time'] . ':00';
        $endAt    = $out->format('Y-m-d') . ' ' . $hotel['check_out_time'] . ':00';
        $bookings = new BookingModel();
        if ($bookings->hasConflict((int) $room['id'], $startAt, $endAt)) {
            return redirect()->to($back)->withInput()->with('error', 'Sorry, ' . $room['name'] . ' is already booked for some of those nights. Pick other dates.');
        }

        // Add-ons (extra bed, early check-in...): only the ones this room offers, priced here, never taken from the form.
        $extras = Addons::resolve((array) $this->request->getPost('addons'), 'room', 0, 0, false, 1, false, Addons::forFacility($room), $nights);
        if (is_string($extras)) {
            return redirect()->to($back)->withInput()->with('error', $extras);
        }

        $uid   = (int) session()->get('user_id');
        $auto  = is_manager();
        $total = round($nights * (float) $room['price_per_hour'] + $extras['total'], 2);  // one price per room, per night: the number of guests does not change it
        $id    = (int) $bookings->insert([
            'facility_id'  => $room['id'],
            'user_id'      => $uid,
            'start_at'     => $startAt,
            'end_at'       => $endAt,
            'guests'       => 1,   // rooms are not booked by head count
            'addons'       => $extras['items'] ? json_encode($extras['items']) : null,
            'addons_total' => $extras['total'],
            'purpose'      => mb_substr(trim((string) $this->request->getPost('requests')), 0, 255),
            'status'       => $auto ? 'approved' : 'pending',
            'approved_by'  => $auto ? $uid : null,
            'total_price'  => $total,
        ], true);
        if (! $id) {
            return redirect()->to($back)->withInput()->with('error', 'Sorry, the booking could not be saved. Please try again.');
        }

        $what   = $nights . ($nights === 1 ? ' night' : ' nights') . ', ' . $in->format('M j') . ' – ' . $out->format('M j, Y');
        $notify = new NotificationService();
        if ($auto) {
            $notify->notifyUser($uid, 'Booking confirmed', 'Your stay at ' . $hotel['name'] . ', ' . $room['name'] . ' (' . $what . ') is confirmed.', $id);

            return redirect()->to('/manage/bookings?status=approved')->with('success', 'Booked ' . $room['name'] . ' for ' . $what . '. It is confirmed, no approval needed.');
        }
        $notify->notifyManagers('New booking request', session()->get('name') . ' requested ' . $room['name'] . ' at ' . $hotel['name'] . ' for ' . $what . '.', $id);
        $notify->notifyUser($uid, 'Booking request sent', 'Your request for ' . $room['name'] . ' (' . $what . ') is waiting for approval.', $id);

        return redirect()->to('/my-bookings')->with('success', 'Booking request sent for ' . $room['name'] . ', ' . $what . ' (₱' . number_format($total, 2) . '). No payment is needed now. You will be notified once it is reviewed.');
    }
}
