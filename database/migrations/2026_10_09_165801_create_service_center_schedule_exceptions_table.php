<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_center_schedule_exceptions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('service_center_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->time('opens_at')->nullable();
            $table->time('closes_at')->nullable();
            $table->boolean('is_closed');
            $table->timestamps();
            $table->unique(['service_center_id', 'date'], 'center_exception_date_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_center_schedule_exceptions');
    }
};
