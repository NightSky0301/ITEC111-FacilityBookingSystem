<?php

use App\Models\NotificationModel;

if (! function_exists('is_admin')) {
    function is_admin(): bool
    {
        return session()->get('role') === 'admin';
    }
}

if (! function_exists('is_staff')) {
    function is_staff(): bool
    {
        return session()->get('role') === 'staff';
    }
}

if (! function_exists('is_manager')) {
    /** Admin or staff: the people who run the bookings (customers are everyone else). */
    function is_manager(): bool
    {
        return in_array(session()->get('role'), ['admin', 'staff'], true);
    }
}

if (! function_exists('unread_notifications')) {
    /** Number shown on the bell icon in the header. */
    function unread_notifications(): int
    {
        $userId = (int) session()->get('user_id');
        if (! $userId) {
            return 0;
        }
        return (new NotificationModel())->unreadCount($userId);
    }
}

if (! function_exists('status_badge')) {
    /** Small colored status label (pending / approved / rejected / cancelled / active / inactive). */
    function status_badge(string $status): string
    {
        $known = ['pending', 'approved', 'rejected', 'cancelled', 'active', 'inactive'];
        $class = in_array($status, $known, true) ? $status : 'cancelled';
        return '<span class="pill pill-' . $class . '">' . esc(ucfirst($status)) . '</span>';
    }
}

if (! function_exists('facility_icon')) {
    /** Bootstrap-icon name for each facility type. Add new types here. */
    function facility_icon(string $type): string
    {
        $icons = [
            'basketball'    => 'bi-dribbble',
            'volleyball'    => 'bi-record-circle',
            'pickleball'    => 'bi-bullseye',
            'badminton'     => 'bi-feather',
            'tennis'        => 'bi-lightning-charge',
            'futsal'        => 'bi-trophy',
            'football'      => 'bi-flag',
            'tabletennis'   => 'bi-bullseye',
            'bowling'       => 'bi-circle-fill',
            'billiards'     => 'bi-record-circle',
            'boxing'        => 'bi-lightning-charge',
            'multipurpose'  => 'bi-grid-3x3-gap',
            'gym'           => 'bi-heart-pulse',
            'swimming'      => 'bi-water',
            'dance'         => 'bi-music-note-beamed',
            'other'         => 'bi-geo-alt',
        ];
        return $icons[$type] ?? 'bi-geo-alt';
    }
}

if (! function_exists('facility_is_outdoor')) {
    /**
     * Indoor or outdoor. Gym, futsal, multipurpose, table tennis, bowling, billiards and boxing are always indoor, football is always outdoor.
     * Every other court says it in its name: "Basketball Court (Outdoor)". Older "Court B" / "2" names still count as outdoor.
     */
    function facility_is_outdoor(array $facility): bool
    {
        $type = (string) ($facility['type'] ?? '');
        $name = trim((string) ($facility['name'] ?? ''));
        if (in_array($type, ['gym', 'dance', 'futsal', 'multipurpose', 'tabletennis', 'bowling', 'billiards', 'boxing'], true)) {
            return false;
        }
        if ($type === 'football') {
            return true;
        }
        if (stripos($name, 'outdoor') !== false) {
            return true;
        }
        if (stripos($name, 'indoor') !== false) {
            return false;
        }
        return (bool) preg_match('/\s(B|2)$/i', $name);
    }
}

if (! function_exists('facility_image')) {
    /**
     * Photo for a facility: the uploaded one if there is one,
     * otherwise the generated court illustration for its type (A/B variant from the name).
     */
    function facility_image(array $facility): string
    {
        if (! empty($facility['image']) && is_file(FCPATH . $facility['image'])) {
            return base_url($facility['image']) . '?v=' . filemtime(FCPATH . $facility['image']);
        }

        $type    = (string) ($facility['type'] ?? 'other');
        $variant = facility_is_outdoor($facility) ? 'b' : 'a';

        // Photos (jpg or png) come first; the drawn illustration (svg) is the fallback.
        foreach ([$type . '-' . $variant, $type . '-a', 'other-a'] as $name) {
            foreach (['jpg', 'png', 'svg'] as $ext) {
                if (is_file(FCPATH . 'assets/img/courts/' . $name . '.' . $ext)) {
                    return base_url('assets/img/courts/' . $name . '.' . $ext) . '?v=' . filemtime(FCPATH . 'assets/img/courts/' . $name . '.' . $ext);
                }
            }
        }
        return base_url('assets/img/courts/other-a.svg');
    }
}

if (! function_exists('fmt_time')) {
    function fmt_time(?string $datetime): string
    {
        return $datetime ? date('g:i A', strtotime($datetime)) : '';
    }
}

if (! function_exists('fmt_dt')) {
    function fmt_dt(?string $datetime): string
    {
        return $datetime ? date('M d, Y g:i A', strtotime($datetime)) : '';
    }
}

if (! function_exists('fmt_range')) {
    /** "Nov 14, 2026 · 10:00 AM – 12:00 PM" (or both dates when it spans days). */
    function fmt_range(string $start, string $end): string
    {
        $s = strtotime($start);
        $e = strtotime($end);
        if (date('Y-m-d', $s) === date('Y-m-d', $e)) {
            return date('M d, Y', $s) . ', ' . date('g:i A', $s) . ' – ' . date('g:i A', $e);
        }
        return date('M d, g:i A', $s) . ' – ' . date('M d, Y g:i A', $e);
    }
}

if (! function_exists('facility_icon_html')) {
    /**
     * Icon for an activity type, as ready-to-print HTML.
     * Font Awesome (public/assets/vendor/fontawesome) for most; badminton and tennis are small inline SVGs
     * because Font Awesome's free set has no icon for them.
     */
    function facility_icon_html(string $type): string
    {
        $fa = [
            'basketball'   => 'fa-basketball',
            'volleyball'   => 'fa-volleyball',
            'pickleball'   => 'fa-table-tennis-paddle-ball',
            'futsal'       => 'fa-person-running',
            'football'     => 'fa-futbol',
            'multipurpose' => 'fa-border-all',
            'gym'          => 'fa-dumbbell',
            'swimming'     => 'fa-person-swimming',
            'dance'        => 'fa-music',
            'tabletennis'  => 'fa-table-tennis-paddle-ball',
            'bowling'      => 'fa-bowling-ball',
            'billiards'    => 'fa-circle-dot',
            'boxing'       => 'fa-hand-fist',
        ];
        if (isset($fa[$type])) {
            return '<i class="fa-solid ' . $fa[$type] . '" aria-hidden="true"></i>';
        }

        $svg = static fn (string $inner): string =>
            '<svg class="icon-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $inner . '</svg>';

        if ($type === 'badminton') {      // shuttlecock
            return $svg('<path d="M9.4 17.2 5.2 4.2c4.6-1.6 9-1.6 13.6 0l-4.2 13"/><path d="M9 11.2h6M7.2 7.6h9.6M12 4.2v13"/><path d="M9.4 17.2h5.2v1.6a2.6 2.6 0 0 1-5.2 0z" fill="currentColor"/>');
        }
        if ($type === 'tennis') {         // tennis ball
            return $svg('<circle cx="12" cy="12" r="9"/><path d="M4.2 7.5c3.8 2 3.8 7 0 9M19.8 7.5c-3.8 2-3.8 7 0 9"/>');
        }
        return '<i class="fa-solid fa-location-dot" aria-hidden="true"></i>';
    }
}

if (! function_exists('approval_label')) {
    /** "Approved by Admin" / "Approved by Staff: Christian" (first name only); plain "Approved" for older bookings. */
    function approval_label(array $b): string
    {
        $role = $b['approver_role'] ?? null;
        if (! $role) {
            return 'Approved';
        }
        if ($role === 'admin') {
            return 'Approved by Admin';
        }
        $first = explode(' ', trim((string) ($b['approver_name'] ?? '')))[0];

        return 'Approved by Staff' . ($first !== '' ? ': ' . $first : '');
    }
}

if (! function_exists('arrival_window')) {
    /** [start, end) timestamps of the arrival window the customer chose in the booking form. */
    function arrival_window(array $b): array
    {
        $start = strtotime($b['start_at']);

        return [$start, $start + \App\Models\BookingModel::ARRIVAL_WINDOW_MIN * 60];
    }
}

if (! function_exists('arrival_state')) {
    /**
     * Where an approved booking stands on the desk's Session(s) today list.
     * Returns [key, label, css class]: upcoming, open (confirm now), missed (did not arrive), arrived, finished.
     */
    function arrival_state(array $b, ?int $now = null): array
    {
        $now = $now ?? time();
        [$ws, $we] = arrival_window($b);
        $end = strtotime($b['end_at']);

        if (! empty($b['arrived_at'])) {
            return $now >= $end ? ['finished', 'Arrived · Finished', 'is-arrived is-done'] : ['arrived', 'Arrived', 'is-arrived'];
        }
        if ($now < $ws) { return ['upcoming', 'Upcoming', 'is-soon']; }
        if ($now < $we) { return ['open', 'Waiting for arrival', 'is-due']; }

        return ['missed', 'Did not arrive', 'is-missed'];
    }
}

if (! function_exists('group_bookings')) {
    /**
     * Collapses the days of a multi-day stay into one entry for the lists.
     * The entry is the next day that has not ended (or the last day), with extra keys:
     *   _days  every day of the stay (soonest first)   _n  number of days
     * Single bookings pass through with _days = [itself] and _n = 1. The order of the list is kept.
     *
     * @param  array[] $bookings
     * @return array[]
     */
    function group_bookings(array $bookings): array
    {
        $groups = [];
        $order  = [];
        foreach ($bookings as $b) {
            $key = ! empty($b['group_id']) ? 'g' . $b['group_id'] : 'b' . $b['id'];
            if (! isset($groups[$key])) {
                $groups[$key] = [];
                $order[]      = $key;
            }
            $groups[$key][] = $b;
        }

        $out = [];
        foreach ($order as $key) {
            $days = $groups[$key];
            usort($days, static fn (array $x, array $y): int => strcmp($x['start_at'], $y['start_at']));
            $rep = $days[count($days) - 1];
            foreach ($days as $d) {
                if (strtotime($d['end_at']) > time() && in_array($d['status'], ['pending', 'approved'], true)) {
                    $rep = $d;
                    break;
                }
            }
            $rep['_days'] = $days;
            $rep['_n']    = count($days);
            $out[]        = $rep;
        }

        return $out;
    }
}

if (! function_exists('live_stamp')) {
    /**
     * A short fingerprint of what this person can see (bookings, facilities, hotels, accounts for staff, notifications) (all of them for staff/admin, their own for a customer).
     * It changes when a booking is added, changed, approved, rejected, cancelled, checked in, deleted or restored.
     */
    function live_stamp(): string
    {
        $uid = (int) session()->get('user_id');
        if (! $uid) {
            return '';
        }
        $q = db_connect()->table('bookings')->select(
            "COUNT(*) AS c, MAX(id) AS i, MAX(updated_at) AS u, MAX(deleted_at) AS d, "
            . "SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) AS p, SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) AS a, "
            . "SUM(CASE WHEN arrived_at IS NOT NULL THEN 1 ELSE 0 END) AS r"
        );
        if (! is_manager()) {
            $q->where('user_id', $uid);
        }

        $parts = [$q->get()->getRowArray()];

        // Anything else a page can show: facilities, hotels, (for staff) accounts, and this person's notifications.
        $db = db_connect();
        foreach (['facilities', 'hotels'] as $t) {
            $parts[] = $db->table($t)->select("COUNT(*) AS c, MAX(updated_at) AS u, MAX(deleted_at) AS d, SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) AS s")->get()->getRowArray();
        }
        if (is_manager()) {
            $parts[] = $db->table('users')->select("COUNT(*) AS c, MAX(updated_at) AS u, MAX(deleted_at) AS d, SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) AS s")->get()->getRowArray();
        }
        $parts[] = $db->table('notifications')->select('COUNT(*) AS c, MAX(id) AS i, SUM(CASE WHEN is_read = 0 THEN 1 ELSE 0 END) AS u')->where('user_id', $uid)->get()->getRowArray();

        return md5(json_encode($parts));
    }
}

if (! function_exists('facility_card_item')) {
    /** Everything a court/gym card needs (Activities, Fitness and Search all print the same card). */
    function facility_card_item(array $c, \App\Models\BookingModel $bookings): array
    {
        $type = (string) $c['type'];

        return [
            'id'          => (int) $c['id'],
            'name'        => $c['name'],
            'type'        => $type,
            'activity'    => \App\Models\FacilityModel::activityName($type),
            'price'       => (float) $c['price_per_hour'],
            'per_head'    => \App\Models\FacilityModel::isPerHead($c),
            'people'      => \App\Models\FacilityModel::peopleLabel($c),
            'outdoor'     => facility_is_outdoor($c),
            'free'        => $bookings->isFreeNow($c),
            'img'         => facility_image($c),
            'description' => (string) ($c['description'] ?? ''),
        ];
    }
}
