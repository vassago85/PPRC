<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;

class FaqController extends Controller
{
    /**
     * Public FAQ page.
     *
     * The content is hand-authored and rendered directly from the Blade view
     * (see resources/views/site/faqs.blade.php) — we deliberately do not hit
     * the database here. Copy lives in version control alongside the design
     * so answers, lists, and emphasis stay consistent with the rest of the
     * site without a CMS layer in the middle.
     */
    public function __invoke()
    {
        return view('site.faqs');
    }
}
