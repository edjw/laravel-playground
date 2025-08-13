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
        // Get or create an admin user for the playground tools
        $adminUser = User::firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Playground Admin',
                'email' => 'admin@example.com',
                'email_verified_at' => now(),
                'password' => bcrypt('password'),
            ]
        );

        // Create the three demo playground tools
        PlaygroundTool::factory()
            ->wordCounter()
            ->for($adminUser)
            ->create();

        PlaygroundTool::factory()
            ->jsonFormatter()
            ->for($adminUser)
            ->create();

        PlaygroundTool::factory()
            ->colorPalette()
            ->for($adminUser)
            ->create();
    }
}
