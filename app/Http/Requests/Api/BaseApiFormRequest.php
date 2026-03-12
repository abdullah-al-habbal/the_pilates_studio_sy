<?php

declare(strict_types=1);

namespace App\Http\Requests\Api;

use App\Traits\ApiResponseTrait;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

abstract class BaseApiFormRequest extends FormRequest
{
    use ApiResponseTrait;

    public function authorize(): bool
    {
        return true;
    }

    protected function failedValidation(Validator $validator): never
    {
        $response = $this->unprocessable(
            'Validation failed.',
            $validator->errors()
        );

        throw new HttpResponseException($response);
    }

    protected function failedAuthorization(): never
    {
        $response = $this->forbidden(
            'This action is unauthorized.'
        );

        throw new HttpResponseException($response);
    }
}
