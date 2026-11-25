<?php

use App\Models\BookingModel;
use App\Models\FacilityModel;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;

/**
 * Booking rules: overlaps, gym capacity, referees, multi-day stays, opening hours, cancel rule.
 * Run with phpunit (composer require --dev phpunit/phpunit) once a test database is set up.
 *
 * @internal
 */
final class BookingRulesTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $migrate = true;
    protected $namespace = null;

    private function add(int $facility, string $start, string $end, string $status = 'approved', int $guests = 1, ?string $addons = null): int
    {
        return (int) (new BookingModel())->insert([
            'facility_id' => $facility, 'user_id' => 1, 'start_at' => $start, 'end_at' => $end,
            'guests' => $guests, 'status' => $status, 'addons' => $addons,
        ], true);
    }

    public function testDaysBetweenIsInclusiveAndRejectsBadRanges(): void
    {
        $this->assertSame(1, FacilityModel::daysBetween('2026-12-01', '2026-12-01'));
        $this->assertSame(7, FacilityModel::daysBetween('2026-12-01', '2026-12-07'));
        $this->assertSame(62, FacilityModel::daysBetween('2027-12-31', '2028-03-01'));
        $this->assertSame(0, FacilityModel::daysBetween('2026-12-05', '2026-12-01'));
        $this->assertSame(0, FacilityModel::daysBetween('2026-12-01', '2028-01-01'));   // above the one-year safety ceiling
    }

    public function testOpeningHoursUseOwnValuesThenTypeThenDefault(): void
    {
        $this->assertSame([8, 20], FacilityModel::hoursFor(['type' => 'futsal', 'open_hour' => 8, 'close_hour' => 20]));
        $this->assertSame([9, 21], FacilityModel::hoursFor(['type' => 'massage']));
        $this->assertSame([6, 22], FacilityModel::hoursFor(['type' => 'futsal']));
    }

    public function testOverlapRules(): void
    {
        $this->add(2, '2030-01-01 10:00:00', '2030-01-01 12:00:00');
        $m = new BookingModel();
        $this->assertTrue($m->hasConflict(2, '2030-01-01 11:00:00', '2030-01-01 13:00:00'));
        $this->assertFalse($m->hasConflict(2, '2030-01-01 12:00:00', '2030-01-01 13:00:00'));   // back to back is fine
        $this->assertFalse($m->hasConflict(3, '2030-01-01 10:00:00', '2030-01-01 12:00:00'));   // other facility
    }

    public function testPendingRequestsDoNotBlockButAreCounted(): void
    {
        $p = $this->add(2, '2030-01-01 11:00:00', '2030-01-01 12:00:00', 'pending');
        $m = new BookingModel();
        $this->assertFalse($m->hasConflict(2, '2030-01-01 11:00:00', '2030-01-01 12:00:00'));
        $this->assertSame(1, $m->pendingOverlap(2, '2030-01-01 10:30:00', '2030-01-01 11:30:00'));
        $this->assertSame(0, $m->pendingOverlap(2, '2030-01-01 10:30:00', '2030-01-01 11:30:00', $p));
    }

    public function testRefereePoolIsShared(): void
    {
        $ref = json_encode([['key' => \App\Libraries\Addons::REFEREE_KEY]]);
        for ($i = 0; $i < \App\Libraries\Addons::REFEREE_POOL; $i++) {
            $this->add(5, '2030-01-01 15:00:00', '2030-01-01 16:00:00', 'approved', 1, $ref);
        }
        $m = new BookingModel();
        $this->assertFalse($m->refereeAvailable('2030-01-01 15:30:00', '2030-01-01 16:30:00'));
        $this->assertTrue($m->refereeAvailable('2030-01-01 16:00:00', '2030-01-01 17:00:00'));
    }

    public function testCancelRule(): void
    {
        $at = static fn (string $status, float $hours): array => [
            'status' => $status, 'start_at' => date('Y-m-d H:i:s', time() + (int) ($hours * 3600)), 'end_at' => date('Y-m-d H:i:s', time() + (int) (($hours + 1) * 3600)),
        ];
        $this->assertTrue(BookingModel::customerCanCancel($at('pending', 1)));
        $this->assertTrue(BookingModel::customerCanCancel($at('approved', 30)));
        $this->assertFalse(BookingModel::customerCanCancel($at('approved', 23)));
        $this->assertFalse(BookingModel::customerCanCancel($at('cancelled', 50)));
    }

    public function testStayDaysCanBeCancelledOneByOne(): void
    {
        $ids = [];
        foreach ([10, 11, 12] as $d) {
            $ids[] = $this->add(4, date('Y-m-d 09:00:00', strtotime("+$d days")), date('Y-m-d 10:00:00', strtotime("+$d days")), 'pending');
        }
        $m = new BookingModel();
        foreach ($ids as $id) {
            $m->update($id, ['group_id' => $ids[0]]);
        }
        $m->update($ids[1], ['status' => 'cancelled']);
        $this->assertCount(2, $m->groupRows($m->find($ids[0]), ['pending'], true));
    }
}
