<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Scheduler;

use App\Services\ClassSession\ClassSessionService;
use Illuminate\Foundation\Http\FormRequest;

final class ProcessExistingWalkInRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $sessionId = (int) $this->route('sessionId');
        $service = app(ClassSessionService::class);
        $available = $service->getAvailableSpots($sessionId);

        return [
            'user_ids' => [
                'required',
                'array',
                'min:1',
                function (string $attr, mixed $value, \Closure $fail) use ($available): void {
                    if ($available !== PHP_INT_MAX && count($value) > $available) {
                        $fail("Only {$available} spot(s) remaining — cannot add " . count($value) . ' walk-in(s).');
                    }
                },
            ],
            'user_ids.*' => ['integer', 'exists:users,id'],
        ];
    }
}
