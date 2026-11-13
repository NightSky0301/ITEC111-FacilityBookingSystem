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

    public function read(int $id)
    {
        $model = new NotificationModel();
        $note  = $model->where('user_id', (int) session()->get('user_id'))->find($id);

        if ($note) {
            $model->update($id, ['is_read' => 1]);
            if (! empty($note['booking_id'])) {
                return redirect()->to(is_admin() ? '/admin/bookings' : '/my-bookings');
            }
        }
        return redirect()->to('/notifications');
    }

    public function readAll()
    {
        (new NotificationModel())
            ->where('user_id', (int) session()->get('user_id'))
            ->where('is_read', 0)
            ->set(['is_read' => 1])
            ->update();

        return redirect()->to('/notifications')->with('success', 'All notifications marked as read.');
    }
}
