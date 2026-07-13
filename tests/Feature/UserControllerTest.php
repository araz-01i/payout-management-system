<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    Role::firstOrCreate(['name' => 'admin']);
    Role::firstOrCreate(['name' => 'staff']);
});

// Index
test('authenticated user can view users index', function () {
    $this->actingAs($this->user)
        ->get(route('users.index'))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page->component('users/Index'));
});

test('guest is redirected from users index', function () {
    $this->get(route('users.index'))->assertRedirect(route('login'));
});

// Create
test('authenticated user can view create user form', function () {
    $this->actingAs($this->user)
        ->get(route('users.create'))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page->component('users/Create'));
});

// Store - validation
it('requires all fields to create a user', function (string $field, mixed $value) {
    $data = [
        'name' => 'Jane Doe',
        'email' => 'jane@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'role' => 'staff',
        $field => $value,
    ];

    $this->actingAs($this->user)
        ->post(route('users.store'), $data)
        ->assertSessionHasErrors($field);
})->with([
    'name is required' => ['name', ''],
    'email is required' => ['email', ''],
    'email must be valid' => ['email', 'not-an-email'],
    'password is required' => ['password', ''],
    'role is required' => ['role', ''],
    'role must be valid' => ['role', 'superuser'],
]);

test('email must be unique when creating a user', function () {
    User::factory()->create(['email' => 'taken@example.com']);

    $this->actingAs($this->user)
        ->post(route('users.store'), [
            'name' => 'Another',
            'email' => 'taken@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'role' => 'staff',
        ])
        ->assertSessionHasErrors('email');
});

test('user can be created with a valid payload', function () {
    $this->actingAs($this->user)
        ->post(route('users.store'), [
            'name' => 'New Staff',
            'email' => 'newstaff@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'role' => 'staff',
        ])
        ->assertRedirect(route('users.index'));

    $this->assertDatabaseHas('users', ['email' => 'newstaff@example.com']);

    $user = User::where('email', 'newstaff@example.com')->first();
    expect($user->hasRole('staff'))->toBeTrue();
});

// Edit
test('authenticated user can view edit user form', function () {
    $staff = User::factory()->create();
    $staff->assignRole('staff');

    $this->actingAs($this->user)
        ->get(route('users.edit', $staff))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page->component('users/Edit'));
});

// Update
test('user can be updated with a valid payload', function () {
    $staff = User::factory()->create(['name' => 'Old Name', 'email' => 'old@example.com']);
    $staff->assignRole('staff');

    $this->actingAs($this->user)
        ->patch(route('users.update', $staff), [
            'name' => 'New Name',
            'email' => 'new@example.com',
            'role' => 'admin',
        ])
        ->assertRedirect(route('users.index'));

    $staff->refresh();
    expect($staff->name)->toBe('New Name');
    expect($staff->email)->toBe('new@example.com');
    expect($staff->hasRole('admin'))->toBeTrue();
});

test('email must be unique when updating, ignoring own email', function () {
    $staff = User::factory()->create(['email' => 'staff@example.com']);
    $staff->assignRole('staff');

    // Updating with own email should pass
    $this->actingAs($this->user)
        ->patch(route('users.update', $staff), [
            'name' => $staff->name,
            'email' => 'staff@example.com',
            'role' => 'staff',
        ])
        ->assertRedirect(route('users.index'));
});

// Destroy
test('user can be deleted', function () {
    $staff = User::factory()->create();
    $staff->assignRole('staff');

    $this->actingAs($this->user)
        ->delete(route('users.destroy', $staff))
        ->assertRedirect(route('users.index'));

    $this->assertModelMissing($staff);
});
