<?php

declare(strict_types=1);

use App\Models\PlaygroundTool;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
});

describe('Playground Index', function () {
    it('redirects unauthenticated users to login', function () {
        $response = $this->get('/playground');

        $response->assertRedirect('/login');
    });

    it('shows playground index for authenticated users', function () {
        $this->actingAs($this->user);

        $response = $this->get('/playground');

        $response->assertSuccessful();
        $response->assertInertia(fn ($page) => $page->component('Playground/Index'));
    });

    it('shows only active tools on index', function () {
        $this->actingAs($this->user);

        PlaygroundTool::factory()->wordCounter()->create();
        PlaygroundTool::factory()->jsonFormatter()->inactive()->create();

        $response = $this->get('/playground');

        $response->assertInertia(fn ($page) => $page->component('Playground/Index')
            ->has('tools', 1)
            ->where('tools.0.name', 'Word Counter')
        );
    });
});

describe('Playground Show', function () {
    it('shows tool page for authenticated users', function () {
        $this->actingAs($this->user);

        $tool = PlaygroundTool::factory()->wordCounter()->create();

        $response = $this->get("/playground/tools/{$tool->slug}");

        $response->assertSuccessful();
        $response->assertInertia(fn ($page) => $page->component('Playground/Tools/WordCounter')
            ->has('tool')
            ->where('tool.name', 'Word Counter')
        );
    });

    it('returns 404 for non-existent tools', function () {
        $this->actingAs($this->user);

        $response = $this->get('/playground/tools/non-existent');

        $response->assertNotFound();
    });
});

describe('Playground Update', function () {
    it('updates tool saved data', function () {
        $this->actingAs($this->user);

        $tool = PlaygroundTool::factory()->create();

        $updateData = [
            'saved_data' => ['last_input' => 'test data'],
            'configuration' => ['theme' => 'dark'],
        ];

        $response = $this->putJson("/playground/tools/{$tool->id}", $updateData);

        $response->assertSuccessful();
        $response->assertJson([
            'tool' => [
                'saved_data' => ['last_input' => 'test data'],
                'configuration' => ['theme' => 'dark'],
            ],
        ]);

        $tool->refresh();
        expect($tool->saved_data)->toBe(['last_input' => 'test data']);
        expect($tool->configuration)->toBe(['theme' => 'dark']);
    });
});

describe('Tool Execution', function () {
    it('executes word counter tool correctly', function () {
        $this->actingAs($this->user);

        $tool = PlaygroundTool::factory()->wordCounter()->create();

        $response = $this->postJson("/playground/tools/{$tool->id}/execute", [
            'text' => 'Hello world! This is a test.',
        ]);

        $response->assertSuccessful();
        $response->assertJson([
            'words' => 6,
            'characters' => 27,
            'lines' => 1,
            'paragraphs' => 1,
        ]);
    });

    it('executes json formatter tool correctly', function () {
        $this->actingAs($this->user);

        $tool = PlaygroundTool::factory()->jsonFormatter()->create();

        $jsonString = '{"name":"test","value":123}';

        $response = $this->postJson("/playground/tools/{$tool->id}/execute", [
            'json' => $jsonString,
        ]);

        $response->assertSuccessful();
        $response->assertJsonStructure([
            'formatted',
            'minified',
            'valid',
            'size_original',
            'size_formatted',
            'size_minified',
        ]);

        expect($response->json('valid'))->toBeTrue();
    });

    it('handles invalid json in formatter', function () {
        $this->actingAs($this->user);

        $tool = PlaygroundTool::factory()->jsonFormatter()->create();

        $response = $this->postJson("/playground/tools/{$tool->id}/execute", [
            'json' => '{"invalid": json}',
        ]);

        $response->assertSuccessful();
        $response->assertJson([
            'valid' => false,
        ]);
        $response->assertJsonStructure(['error']);
    });

    it('executes color palette tool correctly', function () {
        $this->actingAs($this->user);

        $tool = PlaygroundTool::factory()->colorPalette()->create();

        $response = $this->postJson("/playground/tools/{$tool->id}/execute", [
            'base_color' => '#FF0000',
        ]);

        $response->assertSuccessful();
        $response->assertJsonStructure([
            'palette' => [
                'primary',
                'lighter',
                'darker',
                'complement',
                'triad1',
                'triad2',
            ],
        ]);

        expect($response->json('palette.primary'))->toBe('#FF0000');
    });

    it('returns error for unimplemented tools', function () {
        $this->actingAs($this->user);

        $tool = PlaygroundTool::factory()->create(['slug' => 'unknown-tool']);

        $response = $this->postJson("/playground/tools/{$tool->id}/execute", []);

        $response->assertSuccessful();
        $response->assertJson([
            'error' => 'Tool not implemented',
        ]);
    });
});

describe('Authentication', function () {
    it('requires authentication for all playground routes', function () {
        $tool = PlaygroundTool::factory()->create();

        // Test all routes require auth
        $routes = [
            ['GET', '/playground'],
            ['GET', "/playground/tools/{$tool->slug}"],
            ['PUT', "/playground/tools/{$tool->id}"],
            ['POST', "/playground/tools/{$tool->id}/execute"],
        ];

        foreach ($routes as [$method, $uri]) {
            $response = $this->call($method, $uri);
            expect($response->status())->toBe(302); // Redirect to login
        }
    });
});
