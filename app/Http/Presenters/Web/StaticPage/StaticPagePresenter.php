<?php

declare(strict_types=1);

namespace App\Http\Presenters\Web\StaticPage;

use Illuminate\Http\Resources\Json\JsonResource;

class StaticPagePresenter extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'title' => $this->getTranslation('title', app()->getLocale()),
            'content' => $this->getTranslation('content', app()->getLocale()),
            'image_url' => $this->image_url,
            'slug' => $this->slug,
        ];
    }
}
