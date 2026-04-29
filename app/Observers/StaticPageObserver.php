<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\StaticPage;
use Illuminate\Support\Facades\Cache;

class StaticPageObserver
{
    /**
     * Handle the StaticPage "saved" event.
     */
    public function saved(StaticPage $page): void
    {
        Cache::forget("static_page_{$page->slug}");
    }

    /**
     * Handle the StaticPage "deleted" event.
     */
    public function deleted(StaticPage $page): void
    {
        Cache::forget("static_page_{$page->slug}");
    }
}
