<?php

namespace App\Filament\Admin\Resources\ClassCategories\Pages;

use App\Filament\Admin\Resources\ClassCategories\ClassCategoryResource;
use Filament\Resources\Pages\CreateRecord;

class CreateClassCategory extends CreateRecord
{
    protected static string $resource = ClassCategoryResource::class;
}
