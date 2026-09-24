<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Classes\Pages;

use App\Filament\Admin\Resources\Classes\ClassesResource;
use App\Models\Classes;
use App\Services\Classes\ClassLifecycleService;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use LaraZeus\SpatieTranslatable\Actions\LocaleSwitcher;
use LaraZeus\SpatieTranslatable\Resources\Pages\EditRecord\Concerns\Translatable;

class EditClasses extends EditRecord
{
    use Translatable;

    protected static string $resource = ClassesResource::class;

    /**
     * Session generation happens in ClassesObserver, i.e. after the class row is
     * written. Without a transaction around the whole page action, a schedule
     * that fails validation or hits a conflict leaves a committed class with
     * zero sessions. Scoped to this resource rather than enabled panel-wide.
     */
    protected ?bool $hasDatabaseTransactions = true;

    protected function getHeaderActions(): array
    {
        return [
            LocaleSwitcher::make(),
            ViewAction::make(),
            DeleteAction::make()
                ->using(function (Classes $record): bool {
                    app(ClassLifecycleService::class)->softDelete((int) $record->getKey());

                    return true;
                }),
            ForceDeleteAction::make()
                ->using(function (Classes $record): bool {
                    app(ClassLifecycleService::class)->forceDelete((int) $record->getKey());

                    return true;
                }),
            RestoreAction::make()
                ->using(function (Classes $record): bool {
                    app(ClassLifecycleService::class)->restore((int) $record->getKey());

                    return true;
                }),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     *
     * @return array<string, mixed>
     */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        return app(ClassLifecycleService::class)->update((int) $record->getKey(), $data);
    }
}
