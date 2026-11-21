<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\SuperAdminSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class SuperAdminSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_an_idempotent_super_admin_with_every_web_permission(): void
    {
        $password = Str::random(32);
        config([
            'auth.bootstrap_super_admin' => [
                'name' => 'admin',
                'email' => 'admin@admin.com',
                'password' => $password,
            ],
        ]);

        $this->seed(SuperAdminSeeder::class);

        $user = User::query()->where('email', 'admin@admin.com')->firstOrFail();
        $this->assertSame('admin', $user->name);
        $this->assertTrue(Hash::check($password, $user->password));
        $this->assertSame(['super_admin'], $user->getRoleNames()->all());
        $this->assertEqualsCanonicalizing(
            Permission::query()->where('guard_name', 'web')->pluck('name')->all(),
            $user->getAllPermissions()->pluck('name')->all(),
        );

        $this->seed(SuperAdminSeeder::class);

        $this->assertSame(1, User::query()->where('email', 'admin@admin.com')->count());
    }

    public function test_it_requires_a_bootstrap_password(): void
    {
        config([
            'auth.bootstrap_super_admin' => [
                'name' => 'admin',
                'email' => 'admin@admin.com',
                'password' => null,
            ],
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Set BOOTSTRAP_SUPER_ADMIN_PASSWORD');

        $this->seed(SuperAdminSeeder::class);
    }
}
