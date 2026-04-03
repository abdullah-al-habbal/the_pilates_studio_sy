<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\BookingSession;

use App\Models\Booking;
use Illuminate\Foundation\Http\FormRequest;

class ReserveSessionRequest extends FormRequest
{
    public function authorize(): bool
    {
        $booking = Booking::findOrFail($this->booking_id);

        return $booking->user_id === $this->user()->id
            && $booking->isActive();
    }

    public function rules(): array
    {
        return [
            'booking_id' => [
                'required',
                'exists:bookings,id',
                'bail',
            ],
            'class_session_id' => [
                'required',
                'exists:class_sessions,id',
                'bail',
            ],
        ];
    }
}
