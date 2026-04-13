<?php

// filePath: app/Filament/Admin/Resources/Merchandise/CenterMerchandises/RelationManagers/ImagesRelationManager.php

namespace App\Filament\Admin\Resources\Merchandise\CenterMerchandises\RelationManagers;

use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;

class ImagesRelationManager extends RelationManager
{
    protected static string $relationship = 'images';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                FileUpload::make('url')
                    ->label('Image')
                    ->image()
                    ->directory('merchandise-images')
                    ->visibility('public')
                    ->required(),
                Toggle::make('is_primary')
                    ->label('Is Primary Image')
                    ->default(false),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('url')
            ->columns([
                ImageColumn::make('url')
                    ->label('Thumbnail'),
                ToggleColumn::make('is_primary')
                    ->label('Primary'),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->headerActions([
                CreateAction::make(),
            ]);
    }
}
