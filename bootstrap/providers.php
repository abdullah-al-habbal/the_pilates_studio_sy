<?php

// filePath: bootstrap/providers.php

use App\Providers\ApplicationServiceProvider;
use App\Providers\BootstrapEnvironmentServiceProvider;
use App\Providers\EventServiceProvider;
use App\Providers\InitialDataServiceProvider;
use App\Providers\FcmServiceProvider;
use App\Providers\Filament\AdminPanelProvider;
use App\Providers\ScrambleServiceProvider;

return [
    BootstrapEnvironmentServiceProvider::class,
    ApplicationServiceProvider::class,
    InitialDataServiceProvider::class,
    AdminPanelProvider::class,
    ScrambleServiceProvider::class,
    EventServiceProvider::class,
    FcmServiceProvider::class,
];
