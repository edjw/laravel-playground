<?php

use App\Models\User;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

test('guests are redirected to the login page', function () {
    $response = $this->get('/playground');
    $response->assertRedirect('/login');
});

test('authenticated users can visit the playground', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->get('/playground');
    $response->assertStatus(200);
});