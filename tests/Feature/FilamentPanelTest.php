<?php

namespace Tests\Feature;

use App\Domain\Identity\Actions\SyncUserRoles;
use App\Filament\Widgets\PendingPaymentsWidget;
use App\Filament\Widgets\RecentOrdersWidget;
use App\Filament\Widgets\RevenueOverviewWidget;
use App\Models\Currency;
use App\Models\CustomerProfile;
use App\Models\Device;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\Payment;
use App\Models\ResellerProfile;
use App\Models\ResellerWallet;
use App\Models\Subscription;
use App\Models\User;
use App\Models\WalletTransaction;
use Database\Seeders\RoleSeeder;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class FilamentPanelTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

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

    public function test_new_user_receives_no_privileged_role_or_panel_access(): void
    {
        $user = User::factory()->create();

        $this->assertFalse($user->hasAnyRole(['super_admin', 'admin', 'support', 'finance']));
        $this->assertFalse(Route::has('register'));
        $this->actingAs($user)->get('/admin')->assertForbidden();
    }

    public function test_finance_permissions_are_read_only_and_finance_cannot_open_the_panel(): void
    {
        $finance = User::factory()->create();
        $finance->assignRole(Role::findOrCreate('finance', 'web'));

        $this->assertTrue($finance->can('View:Invoice'));
        $this->assertFalse($finance->can('Update:Invoice'));
        $this->actingAs($finance)->get('/admin')->assertForbidden();
    }

    public function test_dashboard_page_requires_its_shield_permission(): void
    {
        $support = User::factory()->create();
        $supportRole = Role::findOrCreate('support', 'web');
        $support->assignRole($supportRole);
        $supportRole->revokePermissionTo('View:Dashboard');

        $this->actingAs($support)->get('/admin')->assertForbidden();
    }

    public function test_resource_routes_and_widgets_follow_role_permissions(): void
    {
        $support = User::factory()->create();
        $support->assignRole(Role::findOrCreate('support', 'web'));

        $this->actingAs($support)->get('/admin/orders')->assertOk();
        $this->actingAs($support)->get('/admin/subscriptions')->assertOk();
        $this->actingAs($support)->get('/admin/devices')->assertOk();
        $this->actingAs($support)->get('/admin/plans')->assertForbidden();
        $this->actingAs($support)->get('/admin/payments')->assertForbidden();
        $this->actingAs($support)->get('/admin/users')->assertForbidden();
        $this->actingAs($support)->get('/admin')->assertOk();

        $this->assertTrue(RecentOrdersWidget::canView());
        $this->assertFalse(RevenueOverviewWidget::canView());

        $admin = User::factory()->create();
        $admin->assignRole(Role::findOrCreate('admin', 'web'));

        foreach ([
            '/admin/currencies',
            '/admin/plans',
            '/admin/plan-prices',
            '/admin/orders',
            '/admin/payments',
            '/admin/invoices',
            '/admin/subscriptions',
            '/admin/reseller-profiles',
            '/admin/reseller-packages',
            '/admin/wallet-transactions',
            '/admin/devices',
            '/admin/streaming-sessions',
            '/admin/email-templates',
            '/admin/themes',
            '/admin/users',
        ] as $resourcePath) {
            $this->actingAs($admin)->get($resourcePath)->assertOk();
        }

        $this->actingAs($admin)->get('/admin')->assertOk();

        $this->assertTrue(RevenueOverviewWidget::canView());

        $superAdmin = User::factory()->create();
        $superAdmin->assignRole(Role::findOrCreate('super_admin', 'web'));

        $this->actingAs($superAdmin)->get('/admin/payments')->assertOk();
        $this->actingAs($superAdmin)->get('/admin/wallet-transactions')->assertOk();
        $this->actingAs($superAdmin)->get('/admin')->assertOk();

        $this->assertTrue(PendingPaymentsWidget::canView());
    }

    public function test_direct_order_urls_and_reseller_lists_are_scoped_to_the_owner(): void
    {
        Route::get('/_authorization/orders/{order}', function (Order $order) {
            Gate::authorize('view', $order);

            return response()->json(['id' => $order->getKey()]);
        })->middleware(['auth', SubstituteBindings::class]);

        $customerRole = Role::findOrCreate('customer', 'web');
        $customer = User::factory()->create();
        $otherCustomer = User::factory()->create();
        $customer->assignRole($customerRole);
        $otherCustomer->assignRole($customerRole);
        $currencyCode = Currency::factory()->create()->code;
        $customerOrder = Order::factory()->create([
            'currency_code' => $currencyCode,
            'user_id' => $customer->id,
        ]);
        $customerProfile = (new CustomerProfile)->forceFill(['user_id' => $customer->id]);
        $customerInvoice = (new Invoice)->forceFill(['user_id' => $customer->id]);
        $customerSubscription = (new Subscription)->forceFill(['user_id' => $customer->id]);
        $customerDevice = (new Device)->forceFill(['owner_user_id' => $customer->id]);
        $customerPayment = (new Payment)->setRelation('order', $customerOrder);

        $this->assertTrue(Gate::forUser($customer)->allows('view', $customerOrder));
        $this->assertTrue(Gate::forUser($customer)->allows('view', $customerProfile));
        $this->assertFalse(Gate::forUser($otherCustomer)->allows('view', $customerProfile));
        $this->assertTrue(Gate::forUser($customer)->allows('view', $customerInvoice));
        $this->assertFalse(Gate::forUser($otherCustomer)->allows('view', $customerInvoice));
        $this->assertTrue(Gate::forUser($customer)->allows('view', $customerSubscription));
        $this->assertFalse(Gate::forUser($otherCustomer)->allows('view', $customerSubscription));
        $this->assertTrue(Gate::forUser($customer)->allows('view', $customerDevice));
        $this->assertFalse(Gate::forUser($otherCustomer)->allows('view', $customerDevice));
        $this->assertTrue(Gate::forUser($customer)->allows('view', $customerPayment));
        $this->assertFalse(Gate::forUser($otherCustomer)->allows('view', $customerPayment));

        $this->actingAs($customer)
            ->get('/_authorization/orders/'.$customerOrder->id)
            ->assertOk();
        $this->actingAs($otherCustomer)
            ->get('/_authorization/orders/'.$customerOrder->id)
            ->assertForbidden();

        $resellerRole = Role::findOrCreate('reseller', 'web');
        $reseller = User::factory()->create();
        $otherReseller = User::factory()->create();
        $reseller->assignRole($resellerRole);
        $otherReseller->assignRole($resellerRole);
        $resellerProfile = ResellerProfile::unguarded(fn (): ResellerProfile => ResellerProfile::query()->create([
            'user_id' => $reseller->id,
            'business_name' => 'Reseller A',
            'status' => 'active',
            'commission_basis_points' => 500,
        ]));
        $otherResellerProfile = ResellerProfile::unguarded(fn (): ResellerProfile => ResellerProfile::query()->create([
            'user_id' => $otherReseller->id,
            'business_name' => 'Reseller B',
            'status' => 'active',
            'commission_basis_points' => 500,
        ]));
        $resellerInvoice = (new Invoice)->forceFill(['reseller_profile_id' => $resellerProfile->id]);
        $resellerSubscription = (new Subscription)->forceFill(['reseller_profile_id' => $resellerProfile->id]);
        $resellerWallet = (new ResellerWallet)->forceFill(['reseller_profile_id' => $resellerProfile->id]);
        $resellerTransaction = (new WalletTransaction)->setRelation('wallet', $resellerWallet);

        $this->assertTrue(Gate::forUser($reseller)->allows('view', $resellerInvoice));
        $this->assertFalse(Gate::forUser($otherReseller)->allows('view', $resellerInvoice));
        $this->assertTrue(Gate::forUser($reseller)->allows('view', $resellerSubscription));
        $this->assertFalse(Gate::forUser($otherReseller)->allows('view', $resellerSubscription));
        $this->assertTrue(Gate::forUser($reseller)->allows('view', $resellerProfile));
        $this->assertFalse(Gate::forUser($otherReseller)->allows('view', $resellerProfile));
        $this->assertTrue(Gate::forUser($reseller)->allows('view', $resellerWallet));
        $this->assertFalse(Gate::forUser($otherReseller)->allows('view', $resellerWallet));
        $this->assertTrue(Gate::forUser($reseller)->allows('view', $resellerTransaction));
        $this->assertFalse(Gate::forUser($otherReseller)->allows('view', $resellerTransaction));
        $resellerOrder = Order::factory()->create([
            'currency_code' => $currencyCode,
            'user_id' => $customer->id,
            'reseller_profile_id' => $resellerProfile->id,
        ]);
        $otherResellerOrder = Order::factory()->create([
            'currency_code' => $currencyCode,
            'user_id' => $otherCustomer->id,
            'reseller_profile_id' => $otherResellerProfile->id,
        ]);

        $this->assertSame(
            [$resellerOrder->id],
            Order::query()->visibleTo($reseller)->pluck('id')->all()
        );
        $this->actingAs($otherReseller)
            ->get('/_authorization/orders/'.$resellerOrder->id)
            ->assertForbidden();
        $this->actingAs($reseller)
            ->get('/_authorization/orders/'.$otherResellerOrder->id)
            ->assertForbidden();
    }

    public function test_only_super_admin_can_grant_super_admin_and_last_super_admin_cannot_be_demoted(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(Role::findOrCreate('admin', 'web'));
        $target = User::factory()->create();

        try {
            app(SyncUserRoles::class)->execute($admin, $target, ['super_admin']);
            $this->fail('An admin must not grant the super_admin role.');
        } catch (AuthorizationException) {
            $this->assertFalse($target->hasRole('super_admin'));
        }

        $superAdminRole = Role::findOrCreate('super_admin', 'web');
        $firstSuperAdmin = User::factory()->create();
        $firstSuperAdmin->assignRole($superAdminRole);

        $this->assertFalse(Gate::forUser($admin)->allows('delete', $firstSuperAdmin));
        $this->assertFalse(Gate::forUser($firstSuperAdmin)->allows('delete', $firstSuperAdmin));

        try {
            app(SyncUserRoles::class)->execute($admin, $firstSuperAdmin, ['admin']);
            $this->fail('An admin must not alter a super_admin assignment.');
        } catch (AuthorizationException) {
            $this->assertTrue($firstSuperAdmin->fresh()->hasRole('super_admin'));
        }

        try {
            app(SyncUserRoles::class)->execute($firstSuperAdmin, $firstSuperAdmin, ['admin']);
            $this->fail('The last super_admin must not be demoted.');
        } catch (ValidationException) {
            $this->assertTrue($firstSuperAdmin->fresh()->hasRole('super_admin'));
        }

        $secondSuperAdmin = User::factory()->create();
        $secondSuperAdmin->assignRole($superAdminRole);
        app(SyncUserRoles::class)->execute($firstSuperAdmin, $firstSuperAdmin, ['admin']);

        $this->assertFalse($firstSuperAdmin->fresh()->hasRole('super_admin'));
        $this->assertTrue($secondSuperAdmin->fresh()->hasRole('super_admin'));
    }
}
