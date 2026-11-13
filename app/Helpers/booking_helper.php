<?php

use App\Models\NotificationModel;

if (! function_exists('is_admin')) {
    function is_admin(): bool
    {
        return session()->get('role') === 'admin';
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
