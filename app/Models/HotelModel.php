<?php

namespace App\Models;

use CodeIgniter\Model;

class HotelModel extends Model
{
    protected $table          = 'hotels';
    protected $primaryKey     = 'id';
    protected $returnType     = 'array';
    protected $allowedFields  = ['name', 'tagline', 'description', 'address', 'image', 'check_in_time', 'check_out_time', 'status'];
    protected $useTimestamps  = true;
    protected $useSoftDeletes = true;
    protected $deletedField   = 'deleted_at';

    /** Longest stay a customer can request, in nights. */
    public const MAX_NIGHTS = 30;

    /** How far ahead a room can be booked, in days. */
    public const MAX_AHEAD_DAYS = 365;

    /** Active hotels, each with its active rooms and the lowest nightly price. */
    public function withRooms(): array
    {
        $hotels = $this->where('status', 'active')->orderBy('name')->findAll();
        foreach ($hotels as &$h) {
            $h['rooms'] = $this->rooms((int) $h['id']);
            $h['from']  = $h['rooms'] ? min(array_map(static fn ($r) => (float) $r['price_per_hour'], $h['rooms'])) : 0.0;
        }
        unset($h);

        return $hotels;
    }

    /** Active rooms of a hotel, cheapest first. */
    public function rooms(int $hotelId): array
    {
        return (new FacilityModel())->where('type', FacilityModel::ROOM)->where('hotel_id', $hotelId)->where('status', 'active')
            ->orderBy('price_per_hour')->orderBy('name')->findAll();
    }

    /** Nights already taken by approved bookings: [[check-in date, check-out date], ...] from today on. */
    public function takenRanges(int $roomId): array
    {
        $rows = (new BookingModel())->where('facility_id', $roomId)->where('status', 'approved')
            ->where('end_at >=', date('Y-m-d 00:00:00'))->orderBy('start_at')->findAll();

        return array_map(static fn ($r) => [date('Y-m-d', strtotime($r['start_at'])), date('Y-m-d', strtotime($r['end_at']))], $rows);
    }
}
