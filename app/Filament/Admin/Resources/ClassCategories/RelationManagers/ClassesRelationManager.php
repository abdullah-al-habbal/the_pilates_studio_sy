<?php

namespace App\Filament\Admin\Resources\ClassCategories\RelationManagers;

use App\Filament\Admin\Resources\ClassCategories\ClassCategoryResource;
use Filament\Actions\CreateAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Table;

class ClassesRelationManager extends RelationManager
{
    protected static string $relationship = 'classes';

    protected static ?string $relatedResource = ClassCategoryResource::class;

    public function table(Table $table): Table
    {
        return $table
            ->headerActions([
                CreateAction::make(),
            ]);
    }
}
