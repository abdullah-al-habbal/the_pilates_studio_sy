<?php

// filePath: app/Http/Controllers/Api/V1/Language/LanguageController.php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Language;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\LanguageResource;
use App\Models\Language;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LanguageController extends Controller
{
    use ApiResponse;
    public function index(): JsonResponse
    {
        $languages = Language::getActive();

        return $this->success(LanguageResource::collection($languages));
    }

    // todo: make a formRequest for this
    public function setLocale(Request $request): JsonResponse
    {
        $request->validate([
            'code' => ['required', 'string', 'exists:languages,code'],
        ]);

        $language = Language::where('code', $request->code)
            ->where('is_active', true)
            ->firstOrFail();

        $request->user()
            ->settings()
            ->firstOrCreate(['user_id' => $request->user()->id])
            ->update(['preferred_language_id' => $language->id]);

        return $this->success([
            'locale'    => $language->code,
            'direction' => $language->direction,
        ], 'Language updated successfully.');
    }
}
