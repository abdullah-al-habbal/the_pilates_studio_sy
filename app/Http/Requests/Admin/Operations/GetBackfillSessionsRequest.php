<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Operations;

use App\Commands\Admin\Operations\GetBackfillSessionsCommand;
use App\Models\Package;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;

final class GetBackfillSessionsRequest extends FormRequest
{
    private const LANG = 'dashboard.operations_ui.historical_backfill.';

    private const MAX_PER_PAGE = 50;

    public function authorize(): bool
    {
        // The route group already enforces web + auth + freeze.user + role.admin.
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'package_id' => ['required', 'integer', 'exists:packages,id'],
            'purchased_at' => ['required', 'date', 'before_or_equal:today'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:' . self::MAX_PER_PAGE],
            'cursor' => ['nullable', 'string'],
            'month' => ['nullable', 'integer', 'min:1', 'max:12'],
            'year' => ['nullable', 'integer', 'min:2000', 'max:2100'],
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
        ];
    }

    /**
     * The window is derived server-side from the package, never taken from the client.
     *
     * If the picker and the submit-time validator computed `expires_at` differently, the picker
     * would happily offer sessions that the write path then rejects as out of range.
     */
    public function toCommand(): GetBackfillSessionsCommand
    {
        $package = Package::findOrFail($this->integer('package_id'));

        $purchasedAt = Carbon::parse($this->string('purchased_at')->toString())->startOfDay();

        return new GetBackfillSessionsCommand(
            purchasedAt: $purchasedAt,
            // No validity means no expiry: the window runs to today (same rule as the validator).
            expiresAt: $package->validity_days > 0 ? $purchasedAt->copy()->addDays($package->validity_days) : null,
            perPage: min((int) $this->input('per_page', 15), self::MAX_PER_PAGE),
            cursor: $this->input('cursor'),
            month: $this->input('month') !== null ? $this->integer('month') : null,
            year: $this->input('year') !== null ? $this->integer('year') : null,
            excludeUserId: $this->input('user_id') !== null ? $this->integer('user_id') : null,
        );
    }
}
