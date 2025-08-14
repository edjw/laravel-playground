<?php

use App\Models\PlaygroundTool;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Remove the default tools that were previously seeded
        PlaygroundTool::whereIn('slug', ['todo-list', 'calculator', 'text-transformer'])->delete();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Recreate the tools if needed
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
                'user_id' => null,
            ]
        );

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
                'user_id' => null,
            ]
        );

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
                'user_id' => null,
            ]
        );
    }
};
