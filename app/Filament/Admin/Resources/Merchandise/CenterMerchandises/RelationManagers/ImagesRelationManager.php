<?php

declare(strict_types=1);

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
use Illuminate\Database\Eloquent\Model;

class ImagesRelationManager extends RelationManager
{
    protected static string $relationship = 'images';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('dashboard.resources.center_merchandises.sections.gallery');
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            FileUpload::make('url')
                ->label(__('dashboard.resources.center_merchandises.fields.image'))
                ->image()
                ->directory('merchandise-images')
                ->visibility('public')
                ->imagePreviewHeight('150')
                ->required(),

            Toggle::make('is_primary')
                ->label(__('dashboard.resources.center_merchandises.fields.is_primary'))
                ->default(false),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('url')
            ->columns([
                ImageColumn::make('url')
                    ->label(__('dashboard.resources.center_merchandises.fields.image'))
                    ->height(60)->width(60),

                ToggleColumn::make('is_primary')
                    ->label(__('dashboard.resources.center_merchandises.fields.is_primary')),
            ])
            ->headerActions([CreateAction::make()])
            ->actions([EditAction::make(), DeleteAction::make()]);
    }
}
