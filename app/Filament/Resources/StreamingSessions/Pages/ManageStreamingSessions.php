<?php

namespace App\Filament\Resources\StreamingSessions\Pages;

use App\Filament\Resources\StreamingSessions\StreamingSessionResource;
use Filament\Resources\Pages\ManageRecords;

class ManageStreamingSessions extends ManageRecords
{
    protected static string $resource = StreamingSessionResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
