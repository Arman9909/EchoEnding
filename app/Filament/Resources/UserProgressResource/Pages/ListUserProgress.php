<?php

namespace App\Filament\Resources\UserProgressResource\Pages;

use App\Filament\Resources\UserProgressResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListUserProgress extends ListRecords
{
    protected static string $resource = UserProgressResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
