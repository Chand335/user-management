<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
  use WithoutModelEvents;

  /**
   * Seed the application's database.
   */
  public function run(): void
  {
    $admin = Role::create(['name' => 'admin']);
    $manager = Role::create(['name' => 'manager']);
    Role::create(['name' => 'user']);

    $manage_users = Permission::create(['name' => 'manage_users']);
    $view_audit_logs = Permission::create(['name' => 'view_audit_logs']);

    $admin->givePermissionTo($manage_users);
    $manager->givePermissionTo($manage_users);
    $admin->givePermissionTo($view_audit_logs);

    $adminUser = User::factory()->create([
      'name' => 'Admin',
      'email' => 'admin@example.com',
      'password' => bcrypt('Admin@123'),
    ]);
    $adminUser->assignRole($admin);

    $managerUser = User::factory()->create([
      'name' => 'Manager',
      'email' => 'manager@example.com',
      'password' => bcrypt('Manager@123'),
    ]);
    $managerUser->assignRole($manager);

    User::factory(1000)->create();
  }
}
