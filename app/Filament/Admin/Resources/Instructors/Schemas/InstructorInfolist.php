<?php

namespace App\Filament\Admin\Resources\Instructors\Schemas;

use App\Models\Instructor;
use Filament\Infolists\Components\Actions;
use Filament\Infolists\Components\Actions\Action;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;

class InstructorInfolist
{
    public static function configure(Schema $schema): Schema
    {
        $locale = app()->getLocale();

        return $schema
            ->components([
                Grid::make(['default' => 1, 'lg' => 3])->schema([
                    Section::make('Photo')
                        ->icon('heroicon-o-camera')
                        ->columnSpan(1)
                        ->schema([
                            ImageEntry::make('image')
                                ->height(200)
                                ->width(200)
                                ->extraImgAttributes(['class' => 'rounded-xl object-cover'])
                                ->defaultImageUrl(url('/images/placeholder-person.jpg')),
                        ]),
                    Section::make('Personal Information')
                        ->icon('heroicon-o-user')
                        ->columnSpan(2)
                        ->schema([
                            TextEntry::make('name')
                                ->weight(FontWeight::Bold)
                                ->formatStateUsing(fn($state, $record) =>
                                    $record->getTranslation('name', $locale)),
                            TextEntry::make('title')
                                ->formatStateUsing(fn($state, $record) =>
                                    $record->getTranslation('title', $locale))
                                ->placeholder('No title')
                                ->icon('heroicon-o-briefcase'),
                            TextEntry::make('specialty')
                                ->badge()
                                ->color('info')
                                ->formatStateUsing(fn($state, $record) =>
                                    $record->getTranslation('specialty', $locale))
                                ->placeholder('No specialty')
                                ->icon('heroicon-o-academic-cap'),
                        ]),
                ]),
                Section::make('Biography')
                    ->icon('heroicon-o-document-text')
                    ->schema([
                        TextEntry::make('bio')
                            ->html()
                            ->prose()
                            ->formatStateUsing(fn($state, $record) =>
                                $record->getTranslation('bio', $locale))
                            ->placeholder('No biography provided')
                            ->columnSpanFull(),
                    ]),
                Section::make('Social Links')
                    ->icon('heroicon-o-globe-alt')
                    ->collapsible()
                    ->schema(function (Instructor $record) {
                        $links = $record->social_links ?? [];

                        if (empty($links)) {
                            return [
                                TextEntry::make('social_links_empty')
                                    ->label('Social Links')
                                    ->state('No social links configured'),
                            ];
                        }

                        return array_map(function (array $link, int $index) {
                            $platform = $link['platform'] ?? 'link';
                            $url = $link['url'] ?? '#';

                            return Actions::make([
                                Action::make("open_link_{$index}")
                                    ->label(ucfirst($platform))
                                    ->icon('heroicon-o-link')
                                    ->url($url)
                                    ->openUrlInNewTab()
                                    ->color('gray')
                                    ->size('sm'),
                            ]);
                        }, $links, array_keys($links));
                    }),
                Section::make('Statistics')
                    ->icon('heroicon-o-chart-bar')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('classes_count')
                            ->label('Classes')
                            ->badge()
                            ->color('info')
                            ->icon('heroicon-o-building-library'),
                    ]),
                Section::make('Timestamps')
                    ->icon('heroicon-o-clock')
                    ->collapsed()
                    ->columns(3)
                    ->schema([
                        TextEntry::make('created_at')
                            ->label('Created')
                            ->dateTime('M d, Y H:i')
                            ->icon('heroicon-o-calendar'),
                        TextEntry::make('updated_at')
                            ->label('Updated')
                            ->dateTime('M d, Y H:i')
                            ->icon('heroicon-o-arrow-path'),
                        TextEntry::make('deleted_at')
                            ->label('Deleted')
                            ->dateTime('M d, Y H:i')
                            ->placeholder('Not deleted')
                            ->color(fn($state) => $state ? 'danger' : 'success')
                            ->visible(fn(Instructor $record): bool => $record->trashed()),
                    ]),
            ]);
    }
}
