<?php

namespace App\Filament\Resources\ResellerProfiles\Pages;

use App\Filament\Resources\ResellerProfiles\ResellerProfileResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageResellerProfiles extends ManageRecords
{
    protected static string $resource = ResellerProfileResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
