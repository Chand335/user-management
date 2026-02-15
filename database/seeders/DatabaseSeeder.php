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
      Role::create(['name' => 'manager']);
      Role::create(['name' => 'user']);

      $manage_users = Permission::create(['name' => 'manage_users']);

      $admin->givePermissionTo($manage_users);
      User::factory(1000)->create();

        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);
    }
}
