<?php

namespace App\Commands;

use App\Models\BookingModel;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

/** php spark bookings:remind  - run it every 15 to 60 minutes (Task Scheduler / cron). */
class BookingReminders extends BaseCommand
{
    protected $group       = 'Bookings';
    protected $name        = 'bookings:remind';
    protected $description = 'Sends a reminder (in-app and email) for approved bookings that start within 24 hours.';

    public function run(array $params)
    {
        $n = (new BookingModel())->remindUpcoming();
        CLI::write($n . ' reminder' . ($n === 1 ? '' : 's') . ' sent.', 'green');
    }
}
