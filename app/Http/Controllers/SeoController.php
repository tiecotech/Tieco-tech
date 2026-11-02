<?php

namespace App\Http\Controllers;

use App\Models\Page;
use App\Models\Service;
use App\Support\Launch;
use Illuminate\Http\Response;

class SeoController extends Controller
{
    public function robots(): Response
    {
        $lines = ['User-agent: *', 'Disallow: /admin', 'Disallow: /dashboard', 'Disallow: /login', 'Disallow: /settings', 'Disallow: /preview'];
        if (Launch::isLive()) {
            array_push($lines, '', 'Sitemap: '.url('/sitemap.xml'));
        }

        return response(implode("\n", $lines)."\n", 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }

    public function sitemap(): Response
    {
        abort_unless(Launch::isLive(), 404); // pages are hidden behind the coming-soon gate until launch

        $paths = ['', '/services', '/about', '/contact', '/start'];
        foreach (Service::visible()->pluck('slug') as $slug) {
            $paths[] = "/services/{$slug}";
        }
        foreach (Page::whereIn('slug', PageController::LEGAL)->where('is_published', true)->pluck('slug') as $slug) {
            $paths[] = "/legal/{$slug}";
        }

        $xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n".'<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xhtml="http://www.w3.org/1999/xhtml">'."\n";
        foreach ($paths as $p) {
            foreach (['en', 'ar'] as $loc) {
                $xml .= '<url><loc>'.e(url("/{$loc}{$p}")).'</loc>';
                foreach (['en', 'ar'] as $alt) {
                    $xml .= '<xhtml:link rel="alternate" hreflang="'.$alt.'" href="'.e(url("/{$alt}{$p}")).'"/>';
                }
                $xml .= "</url>\n";
            }
        }

        return response($xml.'</urlset>', 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }
}
