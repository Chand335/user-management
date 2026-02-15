<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
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
    $admin = Role::firstOrCreate(['name' => 'admin']);
    $manager = Role::firstOrCreate(['name' => 'manager']);
    $userRole = Role::firstOrCreate(['name' => 'user']);

    $manage_users = Permission::firstOrCreate(['name' => 'manage_users']);
    $view_audit_logs = Permission::firstOrCreate(['name' => 'view_audit_logs']);

    $admin->syncPermissions([$manage_users, $view_audit_logs]);
    $manager->syncPermissions([$manage_users]);

    $adminUser = User::factory()->firstOrCreate(
      [
        'email' => 'admin@example.com',
      ],
      [
        'name' => 'Admin',
        'password' => Hash::make('Admin@123'),
      ]
    );
    $adminUser->assignRole($admin);

    $managerUser = User::factory()->firstOrCreate([
      'email' => 'manager@example.com',
    ], [
      'name' => 'Manager',
      'password' => Hash::make('Manager@123'),
    ]);
    $managerUser->assignRole($manager);

    $users = User::factory(100)->create();
    $userRoleId = $userRole->id;
    $users->each(function ($user) use ($userRoleId) {
      $user->roles()->sync([$userRoleId]);
    });
  }
}
