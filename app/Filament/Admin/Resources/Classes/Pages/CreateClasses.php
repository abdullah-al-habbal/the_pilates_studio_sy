<?php

// filePath: app/Filament/Admin/Resources/Classes/Pages/CreateClasses.php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Classes\Pages;

use App\Filament\Admin\Resources\Classes\ClassesResource;
use App\Services\Classes\ClassLifecycleService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use LaraZeus\SpatieTranslatable\Actions\LocaleSwitcher;
use LaraZeus\SpatieTranslatable\Resources\Pages\CreateRecord\Concerns\Translatable;

class CreateClasses extends CreateRecord
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
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     *
     * @return array<string, mixed>
     */
    protected function handleRecordCreation(array $data): Model
    {
        return app(ClassLifecycleService::class)->create($data);
    }
}
