<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('service_service_center', function (Blueprint $table): void {
            $table->unsignedSmallInteger('duration_minutes')->nullable();
        });
        Schema::table('bookings', function (Blueprint $table): void {
            $table->unsignedSmallInteger('duration_minutes')->nullable();
            $table->string('scheduled_at_timezone', 40)->nullable();
        });
        Schema::table('service_centers', function (Blueprint $table): void {
            $table->string('timezone', 64)->default('Africa/Cairo');
        });
    }

    public function down(): void
    {
        Schema::table('service_centers', fn (Blueprint $table) => $table->dropColumn('timezone'));
        Schema::table('bookings', fn (Blueprint $table) => $table->dropColumn(['duration_minutes', 'scheduled_at_timezone']));
        Schema::table('service_service_center', fn (Blueprint $table) => $table->dropColumn('duration_minutes'));
    }
};
