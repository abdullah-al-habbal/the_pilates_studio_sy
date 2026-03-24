<?php

// filePath: app/Http/Controllers/Api/V1/Language/LanguageController.php
declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Language;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Requests\Api\V1\Language\SetLocaleRequest;
use App\Http\Resources\Api\V1\LanguageResource;
use App\Services\Language\LanguageService;
use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;

#[Group('Languages')]
class LanguageController extends BaseApiController
{
    public function __construct(
        private readonly LanguageService $languageService
    ) {}

    #[Endpoint('List languages', description: 'Returns a list of active languages.')]
    public function index(): JsonResponse
    {
        $languages = $this->languageService->getActiveLanguages();

        return $this->success(LanguageResource::collection($languages));
    }

    #[Endpoint('Set user locale', description: 'Sets the user\'s language/locale.')]
    public function setLocale(SetLocaleRequest $request): JsonResponse
    {
        $data = $this->languageService->setUserLocale(
            $request->user(),
            $request->code
        );

        // reload user settings to reflect change immediately
        $request->user()->load('settings.preferredLanguage');

        return $this->success($data, 'Language updated successfully.');
    }
}
