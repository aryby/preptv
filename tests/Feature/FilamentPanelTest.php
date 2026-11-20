<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class FilamentPanelTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_panel_login_page_is_available(): void
    {
        $this->get('/admin/login')->assertOk();
    }

    public function test_only_authorized_roles_can_access_the_admin_panel(): void
    {
        foreach (['super_admin', 'admin', 'support'] as $roleName) {
            $user = User::factory()->create();
            $user->assignRole(Role::findOrCreate($roleName, 'web'));

            $this->actingAs($user)->get('/admin')->assertOk();
        }

        foreach (['finance', 'reseller', 'customer'] as $roleName) {
            $user = User::factory()->create();
            $user->assignRole(Role::findOrCreate($roleName, 'web'));

            $this->actingAs($user)->get('/admin')->assertForbidden();
        }
    }

    public function test_super_admin_role_cannot_be_renamed_or_deleted(): void
    {
        $role = Role::findOrCreate('super_admin', 'web');
        $user = User::factory()->create();

        foreach (['Update:Role', 'Delete:Role', 'ForceDelete:Role', 'DeleteAny:Role', 'ForceDeleteAny:Role'] as $permissionName) {
            $user->givePermissionTo(Permission::findOrCreate($permissionName, 'web'));
        }

        $this->assertFalse($user->can('update', $role));
        $this->assertFalse($user->can('delete', $role));
        $this->assertFalse($user->can('deleteAny', Role::class));
        $this->assertFalse($user->can('forceDelete', $role));
        $this->assertFalse($user->can('forceDeleteAny', Role::class));
    }
}
