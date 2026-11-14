<?php

namespace App\Libraries;

use App\Models\NotificationModel;
use App\Models\UserModel;

/**
 * One place to send notifications.
 *  - always saves an in-app notification
 *  - also sends an email when email.enabled = true in .env
 */
class NotificationService
{
    private NotificationModel $notifications;
    private UserModel $users;

    public function __construct()
    {
        $this->notifications = new NotificationModel();
        $this->users         = new UserModel();
    }

    public function notifyUser(int $userId, string $title, string $message, ?int $bookingId = null): void
    {
        $this->notifications->insert([
            'user_id'    => $userId,
            'booking_id' => $bookingId,
            'title'      => $title,
            'message'    => $message,
            'is_read'    => 0,
        ]);

        $user = $this->users->find($userId);
        if ($user) {
            $this->sendEmail($user['email'], $title, $message);
        }
    }

    /** Tells every active admin and staff member (the people who handle bookings). */
    public function notifyManagers(string $title, string $message, ?int $bookingId = null): void
    {
        $managers = $this->users->whereIn('role', ['admin', 'staff'])->where('status', 'active')->findAll();
        foreach ($managers as $manager) {
            $this->notifyUser((int) $manager['id'], $title, $message, $bookingId);
        }
    }

    private function sendEmail(string $to, string $subject, string $message): void
    {
        $enabled = filter_var(env('email.enabled', false), FILTER_VALIDATE_BOOLEAN);
        if (! $enabled) {
            return;
        }

        try {
            $email = service('email');
            $email->setTo($to);
            $email->setSubject('[Facility Booking] ' . $subject);
            $email->setMessage('<p>' . nl2br(esc($message)) . '</p><hr><small>Facility Booking System</small>');

            if (! $email->send(false)) {
                log_message('error', 'Email to ' . $to . ' failed: ' . $email->printDebugger(['headers']));
            }
        } catch (\Throwable $e) {
            // Never break a booking because email failed.
            log_message('error', 'Email exception: ' . $e->getMessage());
        }
    }
}
