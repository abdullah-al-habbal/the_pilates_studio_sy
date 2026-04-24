<?php

declare(strict_types=1);

namespace App\Http\Actions\Web\Admin\Scheduler;

use Illuminate\View\View;

final class IndexAction
{
    public function __invoke(): View
    {
        return view('admin.scheduler.index');
    }
}