<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use InvalidArgumentException;
use Spatie\Permission\Models\Role;

class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        $bootstrapAdmin = config('auth.bootstrap_super_admin');
        $name = $bootstrapAdmin['name'] ?? null;
        $email = $bootstrapAdmin['email'] ?? null;
        $password = $bootstrapAdmin['password'] ?? null;

        if (! is_string($name) || trim($name) === '') {
            throw new InvalidArgumentException('Set BOOTSTRAP_SUPER_ADMIN_NAME to a non-empty name.');
        }

        if (! is_string($email) || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            throw new InvalidArgumentException('Set BOOTSTRAP_SUPER_ADMIN_EMAIL to a valid email address.');
        }

        if (! is_string($password) || $password === '') {
            throw new InvalidArgumentException('Set BOOTSTRAP_SUPER_ADMIN_PASSWORD before running this seeder.');
        }

        $this->call(RoleSeeder::class);

        $user = User::query()->updateOrCreate(
            ['email' => $email],
            [
                'name' => trim($name),
                'password' => Hash::make($password),
            ],
        );

        $user->syncRoles(Role::findByName('super_admin', 'web'));
    }
}
