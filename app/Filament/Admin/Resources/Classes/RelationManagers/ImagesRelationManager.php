<?php
// filePath: app/Filament/Admin/Resources/Classes/RelationManagers/ImagesRelationManager.php

namespace App\Filament\Admin\Resources\Classes\RelationManagers;

use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ImagesRelationManager extends RelationManager
{
    protected static string $relationship = 'images';

    protected static ?string $recordTitleAttribute = 'url';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                FileUpload::make('url')
                    ->label(__('dashboard.resources.class_images.fields.url'))
                    ->image()
                    ->imageEditor()
                    ->imageEditorAspectRatios([
                        '16:9',
                        '4:3',
                        '1:1',
                    ])
                    ->maxSize(5120)
                    ->required()
                    ->directory('classes/gallery')
                    ->visibility('public')
                    ->columnSpanFull(),

                Toggle::make('is_primary')
                    ->label(__('dashboard.resources.class_images.fields.is_primary'))
                    ->default(false)
                    ->inline(false)
                    ->helperText(__('dashboard.resources.class_images.helpers.is_primary')),
            ]);
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(2)->schema([
                    ImageEntry::make('url')
                        ->label(__('dashboard.resources.class_images.fields.image'))
                        ->height(200)
                        ->width(300)
                        ->visible(fn ($record) => !empty($record?->url))
                        ->columnSpanFull(),

                    TextEntry::make('url')
                        ->label(__('dashboard.resources.class_images.fields.image'))
                        ->state(__('dashboard.messages.no_image'))
                        ->visible(fn ($record) => empty($record?->url))
                        ->badge()
                        ->color('gray')
                        ->icon('heroicon-o-photo')
                        ->columnSpanFull(),

                    IconEntry::make('is_primary')
                        ->label(__('dashboard.resources.class_images.fields.is_primary'))
                        ->boolean()
                        ->trueIcon('heroicon-o-star')
                        ->falseIcon('heroicon-o-star')
                        ->trueColor('warning')
                        ->falseColor('gray'),

                    TextEntry::make('created_at')
                        ->label(__('dashboard.resources.class_images.fields.created_at'))
                        ->dateTime('M d, Y H:i')
                        ->icon('heroicon-o-calendar'),

                    TextEntry::make('updated_at')
                        ->label(__('dashboard.resources.class_images.fields.updated_at'))
                        ->dateTime('M d, Y H:i')
                        ->icon('heroicon-o-arrow-path'),
                ]),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                $this->getImageColumnForTable(),

                IconColumn::make('is_primary')
                    ->label(__('dashboard.resources.class_images.fields.is_primary'))
                    ->boolean()
                    ->trueIcon('heroicon-o-star')
                    ->falseIcon('heroicon-o-star')
                    ->trueColor('warning')
                    ->falseColor('gray'),

                TextColumn::make('created_at')
                    ->label(__('dashboard.resources.class_images.fields.created_at'))
                    ->dateTime('M d, Y H:i')
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([])
            ->headerActions([
                CreateAction::make()
                    ->label(__('dashboard.actions.add_image'))
                    ->modalHeading(__('dashboard.actions.add_image'))
                    ->modalWidth('lg'),
            ])
            ->recordActions([
                ViewAction::make()
                    ->label(__('dashboard.actions.view'))
                    ->modalHeading(__('dashboard.actions.view_image')),
                EditAction::make()
                    ->label(__('dashboard.actions.edit'))
                    ->modalHeading(__('dashboard.actions.edit_image')),
                DeleteAction::make()
                    ->label(__('dashboard.actions.delete'))
                    ->modalHeading(__('dashboard.actions.delete_image'))
                    ->modalDescription(__('dashboard.actions.delete_image_confirmation')),
            ])
            ->bulkActions([])
            ->emptyStateHeading(__('dashboard.resources.class_images.empty_state.heading'))
            ->emptyStateDescription(__('dashboard.resources.class_images.empty_state.description'))
            ->emptyStateIcon('heroicon-o-photo');
    }

    protected function getImageColumnForTable(): TextColumn
    {
        return TextColumn::make('url')
            ->label(__('dashboard.resources.class_images.fields.image'))
            ->formatStateUsing(function ($state) {
                if (empty($state)) {
                    return view('filament.components.no-image-placeholder', [
                        'text' => __('dashboard.messages.no_image'),
                        'size' => 50,
                    ])->render();
                }
                return '<img src="' . e($state) . '" alt="Image" style="width:50px;height:50px;object-fit:cover;border-radius:9999px;" />';
            })
            ->html();
    }

}
