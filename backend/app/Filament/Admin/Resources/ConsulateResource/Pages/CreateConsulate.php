<?php

namespace App\Filament\Admin\Resources\ConsulateResource\Pages;

use App\Filament\Admin\Resources\ConsulateResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateConsulate extends CreateRecord
{
    use CreateRecord\Concerns\Translatable;

    protected static string $resource = ConsulateResource::class;
}