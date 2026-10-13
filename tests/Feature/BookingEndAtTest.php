<?php

namespace Tests\Feature;

use App\Models\Booking;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class BookingEndAtTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_end_time_tracks_start_and_duration_including_bulk_updates(): void
    {
        $booking = Booking::factory()->create([
            'scheduled_at' => '2026-10-13 10:30:00',
            'duration_minutes' => 60,
        ]);

        $this->assertSame('2026-10-13 11:30:00', $booking->fresh()->end_at->format('Y-m-d H:i:s'));

        DB::table('bookings')->where('id', $booking->id)->update([
            'scheduled_at' => '2026-10-13 23:30:00',
            'duration_minutes' => 90,
        ]);

        $this->assertSame('2026-10-14 01:00:00', $booking->fresh()->end_at->format('Y-m-d H:i:s'));

        $booking->update(['duration_minutes' => null]);

        $this->assertNull($booking->fresh()->end_at);
    }

    public function test_migration_populates_existing_rows_without_inventing_unknown_durations(): void
    {
        $known = Booking::factory()->create(['scheduled_at' => '2026-10-13 10:30:00', 'duration_minutes' => 60]);
        $unknown = Booking::factory()->create(['duration_minutes' => null]);
        $migration = require database_path('migrations/2026_10_12_192423_add_end_at_to_bookings_table.php');

        $migration->down();
        $this->assertFalse(Schema::hasColumn('bookings', 'end_at'));
        $migration->up();

        $this->assertSame('2026-10-13 11:30:00', $known->fresh()->end_at->format('Y-m-d H:i:s'));
        $this->assertNull($unknown->fresh()->end_at);
        $this->assertNull($unknown->fresh()->duration_minutes);
    }

    public function test_overlap_query_excludes_touching_boundaries_and_unknown_end_times(): void
    {
        $first = Booking::factory()->create(['scheduled_at' => '2026-10-13 10:00:00', 'duration_minutes' => 60]);
        $overlap = Booking::factory()->create(['service_center_id' => $first->service_center_id, 'scheduled_at' => '2026-10-13 10:30:00', 'duration_minutes' => 60]);
        Booking::factory()->create(['service_center_id' => $first->service_center_id, 'scheduled_at' => '2026-10-13 11:30:00', 'duration_minutes' => 60]);
        Booking::factory()->create(['service_center_id' => $first->service_center_id, 'scheduled_at' => '2026-10-13 10:30:00', 'duration_minutes' => null]);

        $ids = Booking::query()
            ->where('service_center_id', $first->service_center_id)
            ->whereIn('status', ['pending', 'accepted'])
            ->where('scheduled_at', '<', '2026-10-13 11:30:00')
            ->where('end_at', '>', '2026-10-13 11:00:00')
            ->pluck('id')->all();

        $this->assertSame([$overlap->id], $ids);
    }
}
