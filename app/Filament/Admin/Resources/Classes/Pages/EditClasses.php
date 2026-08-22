<?php

namespace App\Filament\Admin\Resources\Classes\Pages;

use App\Filament\Admin\Resources\Classes\ClassesResource;
use App\Filament\Admin\Resources\Classes\Schemas\ClassesForm;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
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
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        return ClassesForm::normaliseScheduleMode($data);
    }
}
