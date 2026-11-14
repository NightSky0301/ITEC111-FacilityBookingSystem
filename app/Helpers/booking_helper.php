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
            'multipurpose'  => 'bi-grid-3x3-gap',
            'other'         => 'bi-geo-alt',
        ];
        return $icons[$type] ?? 'bi-geo-alt';
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
            return base_url($facility['image']);
        }

        $type    = (string) ($facility['type'] ?? 'other');
        $variant = preg_match('/\s(B|2)$/i', trim((string) ($facility['name'] ?? ''))) ? 'b' : 'a';

        foreach ([$type . '-' . $variant, $type . '-a', 'other-a'] as $name) {
            if (is_file(FCPATH . 'assets/img/courts/' . $name . '.svg')) {
                return base_url('assets/img/courts/' . $name . '.svg');
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
            'futsal'       => 'fa-futbol',
            'multipurpose' => 'fa-border-all',
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
