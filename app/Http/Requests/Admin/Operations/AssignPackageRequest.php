<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Operations;

use App\Commands\Admin\Operations\AssignPackageCommand;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class AssignPackageRequest extends FormRequest
{
    private const LANG = 'dashboard.operations_ui.historical_backfill.';

    private const HISTORICAL_ONLY = [
        'attended_session_ids',
        'missed_session_ids',
        'idempotency_key',
        'exchange_rate_snapshot',
    ];

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'user_id' => ['required', 'exists:users,id'],
            'currency_id' => ['nullable', 'integer', 'exists:currencies,id'],
            'paid_amount' => ['nullable', 'integer', 'min:1'],

            'purchased_at' => ['nullable', 'date', 'before_or_equal:today'],

            'attended_session_ids' => ['nullable', 'array'],
            'attended_session_ids.*' => [
                'integer',
                'distinct',
                'exists:class_sessions,id',
            ],
            'missed_session_ids' => ['nullable', 'array'],
            'missed_session_ids.*' => [
                'integer',
                'distinct',
                'exists:class_sessions,id',
            ],

            'exchange_rate_snapshot' => [
                'nullable',
                'numeric',
                'min:0.000001',
                'max:999999',
            ],

            'idempotency_key' => [
                'required_with:purchased_at',
                'string',
                'uuid',
            ],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($this->filled('purchased_at')) {
                return;
            }

            foreach (self::HISTORICAL_ONLY as $field) {
                if (
                    $this->has($field) &&
                    ! $this->isEmptyValue($this->input($field))
                ) {
                    $validator
                        ->errors()
                        ->add(
                            'purchased_at',
                            __(
                                self::LANG .
                                    'error_purchase_date_required_for_historical',
                            ),
                        );

                    return;
                }
            }
        });
    }

    public function isHistorical(): bool
    {
        return $this->filled('purchased_at');
    }

    public function sessionIds(string $field): array
    {
        return array_map(intval(...), $this->input($field) ?? []);
    }

    public function exchangeRateOverride(): ?float
    {
        return $this->filled('exchange_rate_snapshot')
            ? (float) $this->input('exchange_rate_snapshot')
            : null;
    }

    private function isEmptyValue(mixed $value): bool
    {
        return $value === null || $value === '' || $value === [];
    }

    public function toCommand(
        int $packageId,
    ): AssignPackageCommand {
        return AssignPackageCommand::fromRequest(
            $this,
            $packageId,
        );
    }
}
