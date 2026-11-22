<?php

namespace App\Controllers;

use App\Models\NotificationModel;

class Notifications extends BaseController
{
    public function index()
    {
        $userId = (int) session()->get('user_id');

        return view('notifications/index', [
            'notifications' => (new NotificationModel())
                ->where('user_id', $userId)
                ->orderBy('created_at', 'DESC')
                ->orderBy('id', 'DESC')
                ->findAll(100),
        ]);
    }

    /** HTML for the notifications pop-up (loaded by the bell button). */
    public function feed()
    {
        $userId = (int) session()->get('user_id');
        $model  = new NotificationModel();

        return $this->response
            ->setHeader('Cache-Control', 'no-store')
            ->setBody(view('notifications/_modal', [
                'notifications' => $model->where('user_id', $userId)->orderBy('created_at', 'DESC')->orderBy('id', 'DESC')->findAll(30),
                'unread'        => $model->unreadCount($userId),
            ]));
    }

    /**
     * Polled every few seconds by every open page. Says how many notifications are unread, gives the newest one
     * (so the page can pop it up) and a fingerprint of the person's bookings, so the page knows when to refresh.
     */
    public function pulse()
    {
        $userId = (int) session()->get('user_id');
        $model  = new NotificationModel();
        $latest = $model->where('user_id', $userId)->where('is_read', 0)->orderBy('id', 'DESC')->first();

        return $this->response->setHeader('Cache-Control', 'no-store')->setJSON([
            'unread' => $model->unreadCount($userId),
            'stamp'  => live_stamp(),
            'latest' => $latest ? ['id' => (int) $latest['id'], 'title' => $latest['title'], 'message' => $latest['message']] : null,
        ]);
    }

    public function read(int $id)
    {
        $model = new NotificationModel();
        $note  = $model->where('user_id', (int) session()->get('user_id'))->find($id);

        if ($note) {
            $model->update($id, ['is_read' => 1]);
            if (! empty($note['booking_id'])) {
                return redirect()->to(is_manager() ? '/manage/bookings' : '/my-bookings');
            }
        }
        // Nothing to open for this one: stay on the page the person was on.
        return redirect()->back();
    }

    public function readAll()
    {
        (new NotificationModel())
            ->where('user_id', (int) session()->get('user_id'))
            ->where('is_read', 0)
            ->set(['is_read' => 1])
            ->update();

        if ($this->request->isAJAX()) {
            return $this->response->setJSON(['ok' => true]);
        }

        return redirect()->to('/notifications')->with('success', 'All notifications marked as read.');
    }
}
