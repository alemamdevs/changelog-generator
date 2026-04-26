<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('login page is accessible for guests', function (): void {
    $this->get(route('login'))
        ->assertOk()
        ->assertSee('Sign in to your account');
});

test('register page is accessible for guests', function (): void {
    $this->get(route('register'))
        ->assertOk()
        ->assertSee('Create your account');
});

test('guest can register and is signed in', function (): void {
    $response = $this->post(route('register.store'), [
        'name' => 'Jane Doe',
        'email' => 'jane@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ]);

    $response->assertRedirect(route('admin.projects.index'));

    $this->assertAuthenticated();
    $this->get(route('admin.projects.index'))->assertOk();

    expect(User::query()->where('email', 'jane@example.com')->exists())->toBeTrue();
});

test('existing user can log in and out', function (): void {
    $user = User::factory()->create([
        'password' => 'password123',
    ]);

    $response = $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password123',
    ]);

    $response->assertRedirect(route('admin.projects.index'));

    $this->assertAuthenticatedAs($user);
    $this->get(route('admin.projects.index'))->assertOk();

    $this->post(route('logout'))
        ->assertRedirect(route('login'));

    $this->get(route('admin.projects.index'))
        ->assertRedirect(route('login'));
});
