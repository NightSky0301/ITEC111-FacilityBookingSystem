<?php

namespace App\Controllers;

use App\Models\BookingModel;
use App\Models\FacilityModel;
use DateTime;

/**
 * Facility menu:  Activity (basketball, badminton...)  ->  booking form (court, dates, time, hours)
 */
class Facilities extends BaseController
{
    /** Live count of facilities that are free right now, per activity: [type => ['free' => n, 'total' => n]]. */
    private function availability(array $byType): array
    {
        $bookings = new BookingModel();
        $now      = date('Y-m-d H:i:s');
        $soon     = date('Y-m-d H:i:s', time() + 60);
        $out      = [];
        foreach ($byType as $type => $courts) {
            $free = 0;
            foreach ($courts as $c) {
                $busy = FacilityModel::isPerHead($c)
                    ? ! $bookings->hasRoom($c, $now, $soon, 1)
                    : $bookings->liveStatus((int) $c['id'])['in_use'];
                $free += $busy ? 0 : 1;
            }
            $out[$type] = ['free' => $free, 'total' => count($courts)];
        }

        return $out;
    }

    /** JSON for the live indicator on the activity cards (refreshed every 30 seconds). */
    public function availabilityJson()
    {
        $byType   = (new FacilityModel())->activeByType();
        $bookings = new BookingModel();
        $data     = ['types' => [], 'courts' => []];
        foreach ($this->availability($byType) as $type => $a) {
            $data['types'][$type] = $a + ['label' => $this->availLabel($type, $a['free'], $a['total'])];
        }
        foreach ($byType as $courts) {
            foreach ($courts as $c) {
                $data['courts'][(int) $c['id']] = $bookings->isFreeNow($c);
            }
        }

        return $this->response->setJSON($data);
    }

    private function availLabel(string $type, int $free, int $total): string
    {
        $unit = FacilityModel::unitName($type, $total);
        if ($total === 1) {
            return $free ? 'Available now' : 'In use now';
        }

        return $free ? $free . ' of ' . $total . ' ' . $unit . ' free' : 'All ' . $unit . ' in use';
    }

    /** Activities: every court in one place, with filters. Clicking a card opens its schedule. */
    public function index()
    {
        // Staff get the same facilities table the admin manages, but view only.
        if (is_manager() && ! is_admin()) {
            return view('admin/facilities/index', [
                'facilities' => (new FacilityModel())->orderBy('type')->orderBy('name')->findAll(),
                'types'      => FacilityModel::TYPES,
                'readOnly'   => true,
            ]);
        }

        return $this->browse('activities');
    }

    /** Fitness: the gym, gymnastics, yoga, massage and foot spa. */
    public function fitness()
    {
        return $this->browse('fitness');
    }

    /** Entertainment: bowling, billiards, swimming pools, dance studios, the music studio and karaoke. */
    public function entertainment()
    {
        return $this->browse('entertainment');
    }

    private function browse(string $section)
    {
        helper('booking');
        $bookings = new BookingModel();
        $byType   = (new FacilityModel())->activeByType();
        // Each facility type belongs to exactly one section: Activities, Fitness or Entertainment.
        $byType = array_filter($byType, static fn ($courts, $type) => FacilityModel::sectionOf((string) $type) === $section, ARRAY_FILTER_USE_BOTH);
        $items    = [];
        $chips    = [];
        foreach ($byType as $type => $courts) {
            $chips[$type] = ['name' => FacilityModel::activityName($type), 'count' => count($courts)];
            foreach ($courts as $c) {
                $items[] = facility_card_item($c, $bookings);
            }
        }

        $type = (string) $this->request->getGet('type');
        $date = (string) $this->request->getGet('date');
        $d    = DateTime::createFromFormat('!Y-m-d', $date);
        $date = ($d && $d->format('Y-m-d') === $date && $date >= date('Y-m-d')) ? $date : '';
        $end  = $date !== '' ? valid_end_date($date, (string) $this->request->getGet('end_date')) : '';

        $heading = ['fitness' => 'Fitness', 'entertainment' => 'Entertainment'][$section] ?? 'Activities';
        $lead    = [
            'fitness'       => 'Book the gym, a yoga studio, a massage room or a foot spa lounge.',
            'entertainment' => 'Bowling, billiards, swimming, dance, music and karaoke. Pick one and book it by the hour.',
        ][$section] ?? 'Courts for every sport. Pick one to see its schedule and book a time.';

        return view('facilities/index', [
            'section' => $section,
            'heading' => $heading,
            'lead'    => $lead,
            'action'  => site_url(FacilityModel::sectionPath($section)),
            'items'   => $items,
            'chips'   => $chips,
            'selType' => isset($chips[$type]) ? $type : '',
            'date'    => $date,
            'end'     => $end,
        ]);
    }

    /** Step 2: choose a court of that activity. */
    public function category(string $type)
    {
        $courts = (new FacilityModel())->activeByType()[$type] ?? null;
        if (! $courts) {
            return redirect()->to('/facilities')->with('error', 'We could not find that activity.');
        }

        $bookings = new BookingModel();
        foreach ($courts as &$court) {
            $court['live'] = $bookings->liveStatus((int) $court['id']);
        }
        unset($court);

        return view('facilities/category', [
            'type'     => $type,
            'activity' => FacilityModel::activityName($type),
            'courts'   => $courts,
        ]);
    }

    /**
     * There is no separate "pick a date" page any more. Customers choose their dates on the booking form, and
     * staff / admin do not book: they see a court's bookings on the calendar.
     */
    public function court(int $id)
    {
        $facility = $id > 0 ? (new FacilityModel())->find($id) : null;
        if ($facility && FacilityModel::isRoom($facility)) {
            return redirect()->to('/hotels/room/' . (int) $facility['id']);   // hotel rooms have their own page
        }
        if (! $facility || ($facility['status'] !== 'active' && ! is_manager())) {
            return redirect()->to('/facilities')->with('error', 'That court is not available.');
        }
        if (is_manager()) {
            return redirect()->to('/calendar?facility_id=' . (int) $facility['id']);
        }

        $q = ['facility_id' => (int) $facility['id']];
        $d = DateTime::createFromFormat('!Y-m-d', (string) $this->request->getGet('date'));
        if ($d && $d->format('Y-m-d') === (string) $this->request->getGet('date') && $d >= new DateTime('today')) {
            $q['start'] = $d->format('Y-m-d');
            if ($end = valid_end_date($q['start'], (string) $this->request->getGet('end_date'))) {
                $q['end'] = $end;
            }
        }

        return redirect()->to('/bookings/new?' . http_build_query($q));
    }
}
