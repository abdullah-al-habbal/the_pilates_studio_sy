<?php
declare(strict_types=1);

namespace App\Http\Actions\Web\Admin\Scheduler;

use App\Http\Resources\Admin\Scheduler\InstructorResource;
use App\Models\Instructor;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;

final class GetInstructorsAction
{
    use ApiResponseTrait;

    public function __invoke(): JsonResponse
    {
        $instructors = Instructor::select('id', 'name')->orderBy('name')->get();
        return $this->success(InstructorResource::collection($instructors));
    }
}
