<?php

// filePath: bootstrap/providers.php

use App\Providers\ApplicationServiceProvider;
use App\Providers\EventServiceProvider;
use App\Providers\Filament\AdminPanelProvider;
use App\Providers\ScrambleServiceProvider;

return [
    ApplicationServiceProvider::class,
    AdminPanelProvider::class,
    ScrambleServiceProvider::class,
    EventServiceProvider::class,
];
