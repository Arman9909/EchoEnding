<?php

namespace App\Filament\Resources\SceneResource\Pages;

use App\Filament\Resources\SceneResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditScene extends EditRecord
{
    protected static string $resource = SceneResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
