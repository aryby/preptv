<?php

namespace App\Filament\Widgets;

use App\Domain\Subscriptions\Enums\SubscriptionStatus;
use App\Models\Subscription;
use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class SubscriptionHealthWidget extends StatsOverviewWidget
{
    use HasWidgetShield;

    protected ?string $heading = 'État des abonnements';

    protected function getStats(): array
    {
        $activeSubscriptions = Subscription::query()
            ->where('status', SubscriptionStatus::Active->value)
            ->count();

        $expiringSubscriptions = Subscription::query()
            ->where('status', SubscriptionStatus::Active->value)
            ->whereBetween('current_period_ends_at', [now(), now()->addDays(7)])
            ->count();

        return [
            Stat::make('Abonnements actifs', number_format($activeSubscriptions)),
            Stat::make('Fin de période sous 7 jours', number_format($expiringSubscriptions))
                ->color($expiringSubscriptions > 0 ? 'warning' : 'success'),
        ];
    }
}
