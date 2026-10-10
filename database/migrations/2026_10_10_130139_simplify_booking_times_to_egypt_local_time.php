<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::table('bookings')->whereNotNull('scheduled_at_timezone')->exists()) {
            throw new RuntimeException('Review and explicitly convert existing UTC bookings before removing timezone metadata. No booking times were changed.');
        }
        if (DB::table('service_centers')->where('timezone', '!=', 'Africa/Cairo')->exists()) {
            throw new RuntimeException('Review centers outside Egypt before switching to Egypt local booking times.');
        }

        if (DB::getDriverName() === 'sqlite') {
            DB::unprepared('DROP TRIGGER IF EXISTS booking_timezone_check_INSERT');
            DB::unprepared('DROP TRIGGER IF EXISTS booking_timezone_check_UPDATE');
        } else {
            DB::statement('ALTER TABLE bookings DROP CHECK booking_timezone_check');
        }

        Schema::table('bookings', fn (Blueprint $table) => $table->dropColumn('scheduled_at_timezone'));
        Schema::table('service_centers', fn (Blueprint $table) => $table->dropColumn('timezone'));
    }

    public function down(): void
    {
        Schema::table('bookings', fn (Blueprint $table) => $table->string('scheduled_at_timezone', 40)->nullable());
        Schema::table('service_centers', fn (Blueprint $table) => $table->string('timezone', 64)->default('Africa/Cairo'));

        if (DB::getDriverName() === 'sqlite') {
            foreach (['INSERT', 'UPDATE'] as $operation) {
                DB::unprepared("CREATE TRIGGER booking_timezone_check_{$operation} BEFORE {$operation} ON bookings WHEN NEW.scheduled_at_timezone IS NOT NULL AND NEW.scheduled_at_timezone != 'UTC' BEGIN SELECT RAISE(ABORT, 'booking_timezone_check'); END");
            }
        } else {
            DB::statement("ALTER TABLE bookings ADD CONSTRAINT booking_timezone_check CHECK (scheduled_at_timezone IS NULL OR scheduled_at_timezone = 'UTC')");
        }
    }
};
