<?php

namespace App\Filament\Admin\Resources\StaticPages\Pages;

use App\Filament\Admin\Resources\StaticPages\StaticPageResource;
use Filament\Resources\Pages\ListRecords;

class ListStaticPages extends ListRecords
{
    protected static string $resource = StaticPageResource::class;
}
