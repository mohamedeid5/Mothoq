<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $driver = DB::getDriverName();
        if (! in_array($driver, ['mysql', 'sqlite'], true)) {
            throw new RuntimeException('Booking end times support MySQL and SQLite.');
        }

        Schema::table('bookings', function (Blueprint $table) use ($driver): void {
            $column = $table->dateTime('end_at')->nullable();
            if ($driver === 'sqlite') {
                $column->virtualAs("datetime(scheduled_at, '+' || duration_minutes || ' minutes')");
            } else {
                $column->storedAs('DATE_ADD(scheduled_at, INTERVAL duration_minutes MINUTE)');
            }

            $table->index(['service_center_id', 'status', 'end_at'], 'bookings_center_status_end_at_index');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table): void {
            $table->dropIndex('bookings_center_status_end_at_index');
            $table->dropColumn('end_at');
        });
    }
};
