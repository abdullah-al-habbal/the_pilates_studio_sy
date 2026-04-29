<?php

declare(strict_types=1);

namespace App\Http\Actions\Web\Admin\Operations;

use Illuminate\View\View;

final class OperationsIndexAction
{
    /**
     * Display the Operations Hub SPA.
     */
    public function __invoke(): View
    {
        return view('admin.operations.index');
    }
}
