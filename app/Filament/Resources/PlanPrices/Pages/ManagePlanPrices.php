<?php

namespace App\Filament\Resources\PlanPrices\Pages;

use App\Domain\Catalog\Actions\CreatePlanPrice;
use App\Filament\Resources\PlanPrices\PlanPriceResource;
use App\Models\PlanPrice;
use App\Models\User;
use Filament\Actions\CreateAction;
use Filament\Facades\Filament;
use Filament\Resources\Pages\ManageRecords;

class ManagePlanPrices extends ManageRecords
{
    protected static string $resource = PlanPriceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->using(function (array $data): PlanPrice {
                    $actor = Filament::auth()->user();
                    abort_unless($actor instanceof User, 403);

                    return app(CreatePlanPrice::class)->execute($actor, $data);
                }),
        ];
    }
}
