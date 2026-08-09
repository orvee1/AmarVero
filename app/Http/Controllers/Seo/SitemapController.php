<?php

namespace App\Http\Controllers\Seo;

use App\Http\Controllers\Controller;
use App\Support\Seo\SeoManager;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    public function __invoke(SeoManager $seo): Response
    {
        $xml = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
        $xml .= "<urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\">\n";

        foreach ($seo->sitemapUrls() as $url) {
            $xml .= "    <url>\n";
            $xml .= '        <loc>'.$this->escape($url['loc'])."</loc>\n";

            if ($url['lastmod'] !== null) {
                $xml .= '        <lastmod>'.$this->escape($url['lastmod'])."</lastmod>\n";
            }

            $xml .= '        <changefreq>'.$this->escape($url['changefreq'])."</changefreq>\n";
            $xml .= '        <priority>'.$this->escape($url['priority'])."</priority>\n";
            $xml .= "    </url>\n";
        }

        $xml .= "</urlset>\n";

        return response($xml, 200, [
            'Content-Type' => 'application/xml; charset=UTF-8',
        ]);
    }

    protected function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_XML1 | ENT_COMPAT, 'UTF-8');
    }
}
