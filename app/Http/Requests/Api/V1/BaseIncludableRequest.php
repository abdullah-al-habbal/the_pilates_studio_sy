<?php
// filePath: app/Http/Requests/Api/V1/BaseIncludableRequest.php
declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;

/**
 * Generic base request for any endpoint that supports ?include= query param.
 *
 * Subclasses must implement allowedIncludes() to declare the whitelist.
 * Validation is enforced automatically — no developer guesswork needed.
 *
 * Usage in subclass:
 *   protected function allowedIncludes(): array
 *   {
 *       return ['classes', 'classes.category', 'classes.primaryImage'];
 *   }
 */
/**
 * Generic base request for any endpoint that supports ?include= query param.
 *
 * Subclasses must implement allowedIncludes() to declare the whitelist.
 * Validation is enforced automatically — no developer guesswork needed.
 *
 * Usage in subclass:
 *   protected function allowedIncludes(): array
 *   {
 *       return ['classes', 'classes.category', 'classes.primaryImage'];
 *   }
 */
abstract class BaseIncludableRequest extends FormRequest
{
    /**
     * Declare all valid dot-notation include paths for this endpoint.
     *
     * @return string[]
     */
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

    /**
     * After base validation passes, validate each requested include
     * against the whitelist defined by allowedIncludes().
     *
     * This runs automatically via FormRequest lifecycle.
     *
     * @throws ValidationException
     */
    public function after(): array
    {
        return [
            function () {
                $requested = $this->parsedIncludes();
                $allowed   = $this->allowedIncludes();
                $invalid   = array_diff($requested, $allowed);

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

    /**
     * Returns the validated, trimmed include segments from ?include=
     *
     * @return string[]
     */
    public function includes(): array
    {
        return $this->parsedIncludes();
    }

    /**
     * @return string[]
     */
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
