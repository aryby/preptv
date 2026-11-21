<?php

namespace App\Filament\Resources\PlanPrices\Pages;

use App\Filament\Resources\PlanPrices\PlanPriceResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManagePlanPrices extends ManageRecords
{
    protected static string $resource = PlanPriceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
