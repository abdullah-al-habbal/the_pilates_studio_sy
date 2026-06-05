<?php

namespace App\Filament\Admin\Resources\ClassCategories\RelationManagers;

use App\Filament\Admin\Resources\Classes\ClassesResource;
use Filament\Actions\CreateAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class ClassesRelationManager extends RelationManager
{
    protected static string $relationship = 'classes';

    protected static ?string $relatedResource = ClassesResource::class;

    public static function getRecordTitle(Model $record): string
    {
        return $record->getTranslation('title', app()->getLocale());
    }

    public function table(Table $table): Table
    {
        return $table
            ->headerActions([
                CreateAction::make(),
            ]);
    }
}
