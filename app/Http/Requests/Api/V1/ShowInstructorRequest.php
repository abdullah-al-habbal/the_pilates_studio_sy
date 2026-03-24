<?php
declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

class ShowInstructorRequest extends BaseIncludableRequest
{
    protected function allowedIncludes(): array
    {
        return [
            'classes',
            'classes.category',
            'classes.primaryImage',
        ];
    }
}
