<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PlaygroundTool>
 */
class PlaygroundToolFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = $this->faker->unique()->words(3, true);

        return [
            'name' => ucwords($name),
            'slug' => str($name)->slug(),
            'description' => $this->faker->sentence(),
            'icon' => $this->faker->randomElement(['Beaker', 'Wrench', 'Cog', 'Code', 'Terminal', 'Zap']),
            'component_name' => str($name)->studly(),
            'configuration' => [],
            'saved_data' => [],
            'is_active' => $this->faker->boolean(90), // 90% chance of being active
            'user_id' => User::factory(),
        ];
    }

    /**
     * Create a Word Counter tool.
     */
    public function wordCounter(): static
    {
        return $this->state(fn (): array => [
            'name' => 'Word Counter',
            'slug' => 'word-counter',
            'description' => 'Count words, characters, lines, paragraphs and estimate reading time for any text.',
            'icon' => 'Type',
            'component_name' => 'WordCounter',
            'configuration' => [
                'max_text_length' => 50000,
                'reading_speed_wpm' => 200,
            ],
            'is_active' => true,
        ]);
    }

    /**
     * Create a JSON Formatter tool.
     */
    public function jsonFormatter(): static
    {
        return $this->state(fn (): array => [
            'name' => 'JSON Formatter',
            'slug' => 'json-formatter',
            'description' => 'Format, minify, and validate JSON with syntax highlighting and error detection.',
            'icon' => 'Braces',
            'component_name' => 'JsonFormatter',
            'configuration' => [
                'max_json_size' => 100000,
                'indent_size' => 2,
            ],
            'is_active' => true,
        ]);
    }

    /**
     * Create a Color Palette tool.
     */
    public function colorPalette(): static
    {
        return $this->state(fn (): array => [
            'name' => 'Color Palette',
            'slug' => 'color-palette',
            'description' => 'Generate harmonious color schemes from a base color using color theory.',
            'icon' => 'Palette',
            'component_name' => 'ColorPalette',
            'configuration' => [
                'default_harmony' => 'complementary',
                'max_colors' => 10,
            ],
            'is_active' => true,
        ]);
    }

    /**
     * Create an active tool.
     */
    public function active(): static
    {
        return $this->state(fn (): array => [
            'is_active' => true,
        ]);
    }

    /**
     * Create an inactive tool.
     */
    public function inactive(): static
    {
        return $this->state(fn (): array => [
            'is_active' => false,
        ]);
    }
}
