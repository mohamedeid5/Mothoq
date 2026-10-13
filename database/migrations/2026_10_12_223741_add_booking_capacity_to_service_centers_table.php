<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            DB::statement('ALTER TABLE service_centers ADD COLUMN booking_capacity INTEGER NULL CHECK (booking_capacity IS NULL OR (typeof(booking_capacity) = \'integer\' AND booking_capacity BETWEEN 1 AND 2147483647))');

            return;
        }

        Schema::table('service_centers', function (Blueprint $table): void {
            $table->unsignedInteger('booking_capacity')->nullable();
        });
        DB::statement('ALTER TABLE service_centers ADD CONSTRAINT service_centers_booking_capacity_positive CHECK (booking_capacity IS NULL OR booking_capacity BETWEEN 1 AND 2147483647)');
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE service_centers DROP CHECK service_centers_booking_capacity_positive');
        }
        Schema::table('service_centers', function (Blueprint $table): void {
            $table->dropColumn('booking_capacity');
        });
    }
};
