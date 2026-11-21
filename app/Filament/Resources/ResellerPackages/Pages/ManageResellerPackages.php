<?php

namespace App\Filament\Resources\ResellerPackages\Pages;

use App\Filament\Resources\ResellerPackages\ResellerPackageResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageResellerPackages extends ManageRecords
{
    protected static string $resource = ResellerPackageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
