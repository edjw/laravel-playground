<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\PlaygroundTool;
use App\Models\User;
use Illuminate\Database\Seeder;

class PlaygroundToolSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create system tools (no user ownership needed)
        // Tools have been removed as requested

        // Create the Workout Tracker tool
        PlaygroundTool::firstOrCreate(
            ['slug' => 'workout-tracker'],
            [
                'name' => 'Workout Tracker',
                'slug' => 'workout-tracker',
                'description' => 'Mobile-first personal fitness tracking with custom exercises and session recording',
                'icon' => 'Dumbbell',
                'component_name' => 'WorkoutTracker',
                'configuration' => [],
                'is_active' => true,
                'user_id' => null, // System tool
            ]
        );
    }
}
