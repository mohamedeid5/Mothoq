<?php

namespace Database\Seeders;

use App\Models\ServiceCenterScheduleException;
use Illuminate\Database\Seeder;

class ServiceCenterScheduleExceptionSeeder extends Seeder
{
    /** Create an opt-in demo fixture with its own center; never change an existing center's schedule. */
    public function run(): void
    {
        ServiceCenterScheduleException::factory()->closed()->create([
            'date' => now()->addWeek()->toDateString(),
        ]);
    }
}
