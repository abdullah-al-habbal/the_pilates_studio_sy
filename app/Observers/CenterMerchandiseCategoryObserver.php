<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\CenterMerchandiseCategory;
use InvalidArgumentException;

final class CenterMerchandiseCategoryObserver
{
    public function deleting(CenterMerchandiseCategory $category): void
    {
        if ($category->merchandises()->exists()) {
            throw new InvalidArgumentException(
                'Cannot delete this category because it contains merchandise items. Please remove or reassign all merchandise first.'
            );
        }
    }
}
