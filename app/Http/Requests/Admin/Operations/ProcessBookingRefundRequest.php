<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Operations;

use Illuminate\Foundation\Http\FormRequest;

class ProcessBookingRefundRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $bookingId = (int) $this->route('bookingId');
        $paidAmount = \App\Models\Booking::find($bookingId)?->paid_amount;

        $amountRules = ['nullable', 'numeric', 'min:1'];
        if ($paidAmount !== null) {
            $amountRules[] = "max:{$paidAmount}";
        }

        return [
            'amount' => $amountRules,
        ];
    }

    public function messages(): array
    {
        return [
            'amount.max' => 'Refund amount cannot exceed the original paid amount.',
            'amount.min' => 'Refund amount must be at least 1.',
        ];
    }
}
