<?php

// filePath: app/Filament/Admin/Resources/Merchandise/MerchandiseCategories/Schemas/MerchandiseCategoryForm.php

namespace App\Filament\Admin\Resources\Merchandise\MerchandiseCategories\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class MerchandiseCategoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
            ]);
    }
}
