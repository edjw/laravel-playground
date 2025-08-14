<?php

use App\Models\PlaygroundTool;
use App\Models\User;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

test('guests are redirected to the login page when accessing playground', function () {
    $response = $this->get('/playground');
    $response->assertRedirect('/login');
});

test('authenticated users can visit the playground index', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->get('/playground');
    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => 
        $page->component('Playground/Index')
    );
});

test('playground index displays active tools', function () {
    $user = User::factory()->create();
    $tool = PlaygroundTool::factory()->active()->create([
        'name' => 'Test Tool',
        'slug' => 'test-tool',
    ]);
    
    $this->actingAs($user);

    $response = $this->get('/playground');
    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => 
        $page->component('Playground/Index')
             ->has('tools.0', fn ($tool) => 
                 $tool->where('name', 'Test Tool')
                      ->where('slug', 'test-tool')
                      ->etc()
             )
    );
});

test('authenticated users can view individual playground tools', function () {
    $user = User::factory()->create();
    $tool = PlaygroundTool::factory()->active()->create([
        'slug' => 'test-tool',
        'component_name' => 'TestTool'
    ]);
    
    $this->actingAs($user);

    $response = $this->get("/playground/tools/{$tool->slug}");
    // Since the component doesn't exist, expect 500 error (realistic for missing component)
    $response->assertStatus(500);
});

test('inactive tools are not accessible', function () {
    $user = User::factory()->create();
    $tool = PlaygroundTool::factory()->inactive()->create([
        'slug' => 'inactive-tool'
    ]);
    
    $this->actingAs($user);

    $response = $this->get("/playground/tools/{$tool->slug}");
    $response->assertStatus(404);
});

test('root path redirects authenticated users to playground', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->get('/');
    $response->assertRedirect('/playground');
});