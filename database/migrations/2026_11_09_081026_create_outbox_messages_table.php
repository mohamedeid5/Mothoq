<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('outbox_messages', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('booking_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type');
            $table->string('recipient');
            $table->json('payload');
            $table->string('status')->default('pending');
            $table->unsignedInteger('attempts')->default(0);
            $table->dateTime('available_at');
            $table->dateTime('locked_until')->nullable();
            $table->uuid('lock_token')->nullable();
            $table->dateTime('sent_at')->nullable();
            $table->string('last_error')->nullable();
            $table->timestamps();

            $table->unique(['booking_id', 'type']);
            $table->index(['status', 'available_at']);
            $table->index(['status', 'locked_until']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('outbox_messages');
    }
};
