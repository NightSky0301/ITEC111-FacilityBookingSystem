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

    /**
     * Adds what a customer wants to see on a hotel card, to hotels from withRooms():
     *   free_tonight  rooms with no approved booking for tonight's stay (check-in today, check-out tomorrow)
     *   max_guests    the most guests the biggest room sleeps
     *   to            the price of the dearest room
     */
    public function withTonight(array $hotels): array
    {
        $bookings = new BookingModel();
        foreach ($hotels as &$h) {
            $ids = array_map(static fn ($r) => (int) $r['id'], $h['rooms'] ?? []);
            $taken = [];
            if ($ids) {
                $in  = date('Y-m-d') . ' ' . $h['check_in_time'] . ':00';
                $out = date('Y-m-d', strtotime('+1 day')) . ' ' . $h['check_out_time'] . ':00';
                $rows = $bookings->select('facility_id')->whereIn('facility_id', $ids)->where('status', 'approved')
                    ->where('start_at <', $out)->where('end_at >', $in)->findAll();
                $taken = array_unique(array_map(static fn ($r) => (int) $r['facility_id'], $rows));
            }
            $h['free_tonight'] = count($ids) - count($taken);
            $h['max_guests']   = $ids ? max(array_map(static fn ($r) => (int) $r['capacity'], $h['rooms'])) : 0;
            $h['to']           = $ids ? max(array_map(static fn ($r) => (float) $r['price_per_hour'], $h['rooms'])) : 0.0;
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
