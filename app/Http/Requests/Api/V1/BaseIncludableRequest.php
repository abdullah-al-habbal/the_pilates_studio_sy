<?php

// filePath: app/Http/Requests/Api/V1/BaseIncludableRequest.php
declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

abstract class BaseIncludableRequest extends FormRequest
{
    abstract protected function allowedIncludes(): array;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'include' => ['nullable', 'string'],
        ];
    }

    public function after(): array
    {
        return [
            function () {
                $requested = $this->parsedIncludes();
                $allowed = $this->allowedIncludes();
                $invalid = array_diff($requested, $allowed);

                if (! empty($invalid)) {
                    $this->validator->errors()->add(
                        'include',
                        sprintf(
                            'Invalid include(s): [%s]. Allowed: [%s].',
                            implode(', ', $invalid),
                            implode(', ', $allowed)
                        )
                    );
                }
            },
        ];
    }

    public function includes(): array
    {
        return $this->parsedIncludes();
    }

    private function parsedIncludes(): array
    {
        $raw = (string) $this->query('include', '');

        if ($raw === '') {
            return [];
        }

        return array_values(array_filter(
            array_map('trim', explode(',', $raw))
        ));
    }
}
