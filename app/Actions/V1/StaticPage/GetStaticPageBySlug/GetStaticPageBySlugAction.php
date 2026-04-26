<?php

declare(strict_types=1);

namespace App\Actions\V1\StaticPage\GetStaticPageBySlug;

use App\Http\Requests\Web\StaticPage\GetStaticPageBySlugRequest;
use App\Http\Presenters\Web\StaticPage\StaticPagePresenter;
use Illuminate\View\View;

final readonly class GetStaticPageBySlugAction
{
    public function __construct(
        private GetStaticPageBySlugHandler $handler,
    ) {
    }

    public function __invoke(GetStaticPageBySlugRequest $request): View
    {
        $page = $this->handler->handle($request->validated('slug'));

        return view('static-pages.show', [
            'page' => new StaticPagePresenter($page),
        ]);
    }
}
