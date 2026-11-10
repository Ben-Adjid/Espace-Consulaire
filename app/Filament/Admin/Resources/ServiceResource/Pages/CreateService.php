<?php

namespace App\Filament\Admin\Resources\ServiceResource\Pages;

use App\Filament\Admin\Resources\ServiceResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateService extends CreateRecord
{
    use CreateRecord\Concerns\Translatable;

    protected static string $resource = ServiceResource::class;
}