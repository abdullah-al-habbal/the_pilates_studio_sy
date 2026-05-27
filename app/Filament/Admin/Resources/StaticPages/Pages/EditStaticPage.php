<?php

namespace App\Filament\Admin\Resources\StaticPages\Pages;

use App\Filament\Admin\Resources\StaticPages\StaticPageResource;
use Filament\Resources\Pages\EditRecord;

class EditStaticPage extends EditRecord
{
    protected static string $resource = StaticPageResource::class;
}
