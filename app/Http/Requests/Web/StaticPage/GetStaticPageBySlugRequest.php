<?php

declare(strict_types=1);

namespace App\Http\Requests\Web\StaticPage;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GetStaticPageBySlugRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'slug' => [
                'required',
                'string',
                Rule::exists('static_pages', 'slug'),
            ],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'slug' => $this->route('slug'),
        ]);
    }
}
