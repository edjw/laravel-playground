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
                'is_active' => true,
                'user_id' => null, // System tool
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
                'is_active' => true,
                'user_id' => null, // System tool
            ]
        );

        // Create the Text Transformer tool
        PlaygroundTool::firstOrCreate(
            ['slug' => 'text-transformer'],
            [
                'name' => 'Text Transformer',
                'slug' => 'text-transformer',
                'description' => 'Transform text with various operations',
                'icon' => 'Type',
                'component_name' => 'TextTransformer',
                'configuration' => [],
                'is_active' => true,
                'user_id' => null, // System tool
            ]
        );
    }
}
