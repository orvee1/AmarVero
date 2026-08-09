<?php

namespace App\Http\Controllers;

use App\Support\Seo\SeoManager;
use App\Support\Storefront\StorefrontContent;
use Illuminate\Contracts\View\View;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class StorefrontController extends Controller
{
    public function __invoke(StorefrontContent $content, SeoManager $seo): View
    {
        return view('welcome', array_merge($content->home(), [
            'seo' => $seo->home(),
            'storefrontContent' => $content,
        ]));
    }

    public function page(string $page, StorefrontContent $content, SeoManager $seo): View
    {
        $staticPage = $content->publishedPage($page);

        if ($staticPage === null) {
            throw new NotFoundHttpException;
        }

        return view('pages.show', [
            'page' => $staticPage,
            'seo' => $seo->page($staticPage),
        ]);
    }
}
