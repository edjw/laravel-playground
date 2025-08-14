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

        // Playground tools will be added here automatically when created

        // Create the Todo List tool
        PlaygroundTool::firstOrCreate(
            ['slug' => 'todo-list'],
            [
                'name' => 'Todo List',
                'slug' => 'todo-list',
                'description' => 'Track tasks efficiently',
                'icon' => 'CheckSquare',
                'component_name' => 'TodoList',
                'configuration' => [],
                'saved_data' => [],
                'is_active' => true,
                'user_id' => $adminUser->id,
            ]
        );

        // Create the Calculator tool
        PlaygroundTool::firstOrCreate(
            ['slug' => 'calculator'],
            [
                'name' => 'Calculator',
                'slug' => 'calculator',
                'description' => 'A calculator with the ability to save and label results',
                'icon' => 'Calculator',
                'component_name' => 'Calculator',
                'configuration' => [],
                'saved_data' => [],
                'is_active' => true,
                'user_id' => $adminUser->id,
            ]
        );
    }
}
