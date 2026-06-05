<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Operations;

use App\Commands\Admin\Operations\GetClientsCommand;
use Illuminate\Foundation\Http\FormRequest;

class GetClientsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'search'   => ['nullable', 'string', 'max:255'],
            'page'     => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'filter'   => ['nullable', 'string', 'in:best_user,most_active_booking,best_seller,most_attended'],
            'only_clients' => ['nullable', 'boolean'],
            'with_valid_fcm' => ['nullable', 'boolean'],
        ];
    }

    public function toCommand(): GetClientsCommand
    {
        return new GetClientsCommand(
            search: $this->query('search'),
            page: (int) $this->query('page', 1),
            filter: $this->query('filter'),
            perPage: (int) $this->query('per_page', 15),
            onlyClients: (bool) $this->query('only_clients', false),
            withValidFcm: (bool) $this->query('with_valid_fcm', false),
        );
    }
}