<?php

use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    $adminRole = Role::firstOrCreate(['name' => 'admin']);

    Permission::firstOrCreate(['name' => 'manage employees']);

    $adminRole->syncPermissions([
        'manage employees',
    ]);

    $this->adminUser = User::factory()->create();
    $this->adminUser->assignRole('admin');
});

// Index
test('authenticated user can view employees index', function () {
    $this->actingAs($this->adminUser)
        ->get(route('employees.index'))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page->component('employees/Index'));
});

test('guest is redirected from employees index', function () {
    $this->get(route('employees.index'))->assertRedirect(route('login'));
});

// Create
test('authenticated user is redirected from create employee form', function () {
    $this->actingAs($this->adminUser)
        ->get(route('employees.create'))
        ->assertRedirect(route('employees.index'));
});

// Store - validation
it('requires name to create an employee', function (string $field, mixed $value) {
    $data = [
        'name' => 'John Doe',
        $field => $value,
    ];

    $this->actingAs($this->adminUser)
        ->post(route('employees.store'), $data)
        ->assertSessionHasErrors($field);
})->with([
    'name is required' => ['name', ''],
]);

test('employee can be created with a valid payload', function () {
    $this->actingAs($this->adminUser)
        ->post(route('employees.store'), [
            'name' => 'New Employee',
        ])
        ->assertRedirect(route('employees.index'));

    $this->assertDatabaseHas('employees', ['name' => 'New Employee']);
});

// Edit
test('authenticated user is redirected from edit employee form', function () {
    $employee = Employee::factory()->create();

    $this->actingAs($this->adminUser)
        ->get(route('employees.edit', $employee))
        ->assertRedirect(route('employees.index'));
});

// Update
test('employee can be updated with a valid payload', function () {
    $employee = Employee::factory()->create(['name' => 'Old Name']);

    $this->actingAs($this->adminUser)
        ->patch(route('employees.update', $employee), [
            'name' => 'New Name',
        ])
        ->assertRedirect(route('employees.index'));

    $employee->refresh();
    expect($employee->name)->toBe('New Name');
});

// Destroy
test('employee can be deleted', function () {
    $employee = Employee::factory()->create();

    $this->actingAs($this->adminUser)
        ->delete(route('employees.destroy', $employee))
        ->assertRedirect(route('employees.index'));

    $this->assertModelMissing($employee);
});
