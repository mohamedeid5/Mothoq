<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\ServiceCenter;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class BookingCapacityMigrationTest extends TestCase
{
    use DatabaseMigrations;

    public function test_upgrade_leaves_existing_centers_unconfigured_and_preserves_bookings(): void
    {
        $center = ServiceCenter::factory()->create();
        $booking = Booking::factory()->for($center)->create(['duration_minutes' => null]);
        $before = DB::table('bookings')->where('id', $booking->id)->first();
        $migration = require database_path('migrations/2026_10_12_223741_add_booking_capacity_to_service_centers_table.php');

        $migration->down();
        $migration->up();

        $this->assertNull($center->fresh()->booking_capacity);
        $this->assertSame((array) $before, (array) DB::table('bookings')->where('id', $booking->id)->first());
    }
}
