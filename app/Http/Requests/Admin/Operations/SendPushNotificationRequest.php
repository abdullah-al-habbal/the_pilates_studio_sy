<?php
declare(strict_types=1);
namespace App\Http\Requests\Admin\Operations;

use App\Commands\SendPushNotificationCommand;
use Illuminate\Foundation\Http\FormRequest;

final class SendPushNotificationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title'      => ['required', 'string', 'max:255'],
            'body'       => ['required', 'string', 'max:1000'],
            'target'     => ['required', 'in:all,specific'],
            'user_ids'   => ['required_if:target,specific', 'array', 'min:1'],
            'user_ids.*' => ['integer', 'exists:users,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'user_ids.required_if' => 'At least one user must be selected for targeted delivery.',
        ];
    }

    public function toCommand(): SendPushNotificationCommand
    {
        return new SendPushNotificationCommand(
            title:   $this->validated('title'),
            body:    $this->validated('body'),
            target:  $this->validated('target'),
            userIds: $this->validated('user_ids', []),
        );
    }
}