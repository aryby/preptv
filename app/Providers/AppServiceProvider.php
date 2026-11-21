<?php

namespace App\Providers;

use App\Models\CreditNote;
use App\Models\Currency;
use App\Models\CustomerProfile;
use App\Models\Device;
use App\Models\DeviceAssignment;
use App\Models\EmailTemplate;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\Payment;
use App\Models\PaymentRefund;
use App\Models\Plan;
use App\Models\PlanPrice;
use App\Models\ResellerCustomer;
use App\Models\ResellerPackage;
use App\Models\ResellerProfile;
use App\Models\ResellerWallet;
use App\Models\StreamingSession;
use App\Models\Subscription;
use App\Models\Theme;
use App\Models\User;
use App\Models\WalletTransaction;
use App\Policies\CreditNotePolicy;
use App\Policies\CurrencyPolicy;
use App\Policies\CustomerProfilePolicy;
use App\Policies\DeviceAssignmentPolicy;
use App\Policies\DevicePolicy;
use App\Policies\EmailTemplatePolicy;
use App\Policies\InvoicePolicy;
use App\Policies\OrderPolicy;
use App\Policies\PaymentPolicy;
use App\Policies\PaymentRefundPolicy;
use App\Policies\PlanPolicy;
use App\Policies\PlanPricePolicy;
use App\Policies\ResellerCustomerPolicy;
use App\Policies\ResellerPackagePolicy;
use App\Policies\ResellerProfilePolicy;
use App\Policies\ResellerWalletPolicy;
use App\Policies\RolePolicy;
use App\Policies\StreamingSessionPolicy;
use App\Policies\SubscriptionPolicy;
use App\Policies\ThemePolicy;
use App\Policies\UserPolicy;
use App\Policies\WalletTransactionPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Spatie\Permission\Models\Role;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(User::class, UserPolicy::class);
        Gate::policy(CustomerProfile::class, CustomerProfilePolicy::class);
        Gate::policy(Order::class, OrderPolicy::class);
        Gate::policy(Payment::class, PaymentPolicy::class);
        Gate::policy(PaymentRefund::class, PaymentRefundPolicy::class);
        Gate::policy(Invoice::class, InvoicePolicy::class);
        Gate::policy(Subscription::class, SubscriptionPolicy::class);
        Gate::policy(Device::class, DevicePolicy::class);
        Gate::policy(DeviceAssignment::class, DeviceAssignmentPolicy::class);
        Gate::policy(StreamingSession::class, StreamingSessionPolicy::class);
        Gate::policy(ResellerProfile::class, ResellerProfilePolicy::class);
        Gate::policy(ResellerCustomer::class, ResellerCustomerPolicy::class);
        Gate::policy(ResellerPackage::class, ResellerPackagePolicy::class);
        Gate::policy(ResellerWallet::class, ResellerWalletPolicy::class);
        Gate::policy(WalletTransaction::class, WalletTransactionPolicy::class);
        Gate::policy(Role::class, RolePolicy::class);
        Gate::policy(CreditNote::class, CreditNotePolicy::class);
        Gate::policy(Currency::class, CurrencyPolicy::class);
        Gate::policy(Plan::class, PlanPolicy::class);
        Gate::policy(PlanPrice::class, PlanPricePolicy::class);
        Gate::policy(EmailTemplate::class, EmailTemplatePolicy::class);
        Gate::policy(Theme::class, ThemePolicy::class);
    }
}
