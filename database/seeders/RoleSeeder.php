<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roleNames = ['super_admin', 'admin', 'support', 'finance', 'reseller', 'customer'];
        $roles = [];

        foreach ($roleNames as $roleName) {
            $roles[$roleName] = Role::findOrCreate($roleName, 'web');
        }

        $allPermissions = [
            'ViewAny:Role', 'View:Role', 'Create:Role', 'Update:Role', 'Delete:Role', 'ForceDelete:Role',
            'ViewAny:User', 'View:User', 'Create:User', 'Update:User', 'Delete:User', 'Manage:UserRoles',
            'ViewAny:CustomerProfile', 'View:CustomerProfile', 'Create:CustomerProfile', 'Update:CustomerProfile',
            'ViewAny:Order', 'View:Order', 'Create:Order', 'Update:Order',
            'ViewAny:Payment', 'View:Payment', 'Create:Payment',
            'ViewAny:PaymentRefund', 'View:PaymentRefund', 'Create:PaymentRefund',
            'ViewAny:Invoice', 'View:Invoice', 'Create:Invoice', 'Update:Invoice',
            'ViewAny:CreditNote', 'View:CreditNote', 'Create:CreditNote',
            'ViewAny:Subscription', 'View:Subscription', 'Create:Subscription', 'Update:Subscription',
            'ViewAny:Device', 'View:Device', 'Create:Device', 'Update:Device',
            'ViewAny:DeviceAssignment', 'View:DeviceAssignment', 'Create:DeviceAssignment', 'Update:DeviceAssignment',
            'ViewAny:StreamingSession', 'View:StreamingSession',
            'ViewAny:ResellerProfile', 'View:ResellerProfile', 'Create:ResellerProfile', 'Update:ResellerProfile',
            'ViewAny:ResellerCustomer', 'View:ResellerCustomer', 'Create:ResellerCustomer', 'Update:ResellerCustomer',
            'ViewAny:ResellerPackage', 'View:ResellerPackage', 'Create:ResellerPackage', 'Update:ResellerPackage',
            'ViewAny:ResellerWallet', 'View:ResellerWallet',
            'ViewAny:WalletTransaction', 'View:WalletTransaction',
            'View:Dashboard', 'View:AccountWidget', 'View:FilamentInfoWidget',
        ];

        $permissionModels = collect($allPermissions)
            ->mapWithKeys(fn (string $permission): array => [
                $permission => Permission::findOrCreate($permission, 'web'),
            ]);

        $permissionsByRole = [
            'admin' => $allPermissions,
            'support' => [
                'ViewAny:Order', 'View:Order',
                'ViewAny:Subscription', 'View:Subscription',
                'ViewAny:Device', 'View:Device',
                'ViewAny:DeviceAssignment', 'View:DeviceAssignment',
                'ViewAny:StreamingSession', 'View:StreamingSession',
                'ViewAny:CustomerProfile', 'View:CustomerProfile',
                'ViewAny:ResellerCustomer', 'View:ResellerCustomer',
                'View:Dashboard', 'View:AccountWidget', 'View:FilamentInfoWidget',
            ],
            'finance' => [
                'ViewAny:Order', 'View:Order',
                'ViewAny:Payment', 'View:Payment',
                'ViewAny:PaymentRefund', 'View:PaymentRefund',
                'ViewAny:Invoice', 'View:Invoice',
                'ViewAny:CreditNote', 'View:CreditNote',
                'ViewAny:ResellerProfile', 'View:ResellerProfile',
                'ViewAny:ResellerWallet', 'View:ResellerWallet',
                'ViewAny:WalletTransaction', 'View:WalletTransaction',
            ],
            'reseller' => [
                'ViewAny:Order', 'View:Order', 'Create:Order',
                'ViewAny:Payment', 'View:Payment',
                'ViewAny:PaymentRefund', 'View:PaymentRefund',
                'ViewAny:Invoice', 'View:Invoice',
                'ViewAny:Subscription', 'View:Subscription',
                'ViewAny:Device', 'View:Device',
                'ViewAny:DeviceAssignment', 'View:DeviceAssignment',
                'ViewAny:StreamingSession', 'View:StreamingSession',
                'ViewAny:ResellerProfile', 'View:ResellerProfile',
                'ViewAny:ResellerCustomer', 'View:ResellerCustomer', 'Create:ResellerCustomer',
                'ViewAny:ResellerPackage', 'View:ResellerPackage',
                'ViewAny:ResellerWallet', 'View:ResellerWallet',
                'ViewAny:WalletTransaction', 'View:WalletTransaction',
            ],
            'customer' => [
                'ViewAny:Order', 'View:Order', 'Create:Order',
                'ViewAny:Payment', 'View:Payment',
                'ViewAny:PaymentRefund', 'View:PaymentRefund',
                'ViewAny:Invoice', 'View:Invoice',
                'ViewAny:CreditNote', 'View:CreditNote',
                'ViewAny:Subscription', 'View:Subscription',
                'ViewAny:Device', 'View:Device',
                'ViewAny:DeviceAssignment', 'View:DeviceAssignment',
                'ViewAny:StreamingSession', 'View:StreamingSession',
                'View:CustomerProfile', 'Create:CustomerProfile', 'Update:CustomerProfile',
            ],
        ];

        foreach ($permissionsByRole as $roleName => $permissionNames) {
            $roles[$roleName]->syncPermissions($permissionModels->only($permissionNames)->values());
        }

        $roles['super_admin']->syncPermissions(Permission::query()->where('guard_name', 'web')->get());
    }
}
