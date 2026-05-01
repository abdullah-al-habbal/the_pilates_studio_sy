<?php

declare(strict_types=1);

namespace App\Http\Actions\Web\Admin\Operations;

use Illuminate\View\View;

final class OperationsIndexAction
{
    public function __invoke(): View
    {
        return view('admin.operations.index');
    }
}
