<?php

namespace Tests\Feature\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class UserApiTest extends TestCase
{
  use RefreshDatabase;
  protected User $admin;
  protected User $normalUser;

  protected function setUp(): void
  {
    parent::setUp();


    app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

    $permission = Permission::create(['name' => 'manage_users']);
    $role = Role::create(['name' => 'admin']);
    $user = Role::create(['name' => 'user']);
    $role->givePermissionTo($permission);

    $this->admin = User::factory()->create();
    $this->admin->assignRole($role);

    $this->normalUser = User::factory()->create();
  }

  public function test_users_endpoint_requires_authentication()
  {
    $response = $this->getJson('/api/v1/users');

    $response->assertStatus(401);
  }

  public function test_user_without_permission_cannot_access_users()
  {
    Sanctum::actingAs($this->normalUser);

    $response = $this->getJson('/api/v1/users');

    $response->assertStatus(403);
  }


  public function test_admin_can_fetch_users()
  {
    Sanctum::actingAs($this->admin);

    User::factory()->count(5)->create();

    $response = $this->getJson('/api/v1/users');

    $response->assertStatus(200)
      ->assertJsonStructure([
        'success',
        'message',
        'data',
        'meta' => [
          'current_page',
          'per_page',
          'total',
          'last_page',
        ],
      ]);
  }
  public function test_admin_can_create_user()
  {
    Sanctum::actingAs($this->admin);

    $payload = [
      'name' => 'Test User',
      'email' => 'test@example.com',
      'password' => 'Password@123',
      'password_confirmation' => 'Password@123',
      'roles' => ['user'],
    ];

    $response = $this->postJson('/api/v1/users', $payload);

    $response->assertStatus(201)
      ->assertJsonPath('data.email', 'test@example.com');

    $this->assertDatabaseHas('users', [
      'email' => 'test@example.com',
    ]);
  }
  public function test_create_user_validation_fails()
  {
    Sanctum::actingAs($this->admin);

    $response = $this->postJson('/api/v1/users', []);

    $response->assertStatus(422)
      ->assertJsonValidationErrors(['name', 'email', 'password']);
  }

  public function test_admin_can_update_user()
  {
    Sanctum::actingAs($this->admin);

    $user = User::factory()->create();

    $response = $this->putJson("/api/v1/users/{$user->id}", [
      'name' => 'Updated Name',
    ]);

    $response->assertStatus(200);

    $this->assertDatabaseHas('users', [
      'id' => $user->id,
      'name' => 'Updated Name',
    ]);
  }

  public function test_user_cannot_delete_self()
  {
    Sanctum::actingAs($this->admin);

    $response = $this->deleteJson("/api/v1/users/{$this->admin->id}");

    $response->assertStatus(403);
  }
}
