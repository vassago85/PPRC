<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Faq;

class FaqController extends Controller
{
    public function __invoke()
    {
        $faqs = Faq::published()->get()->groupBy('category');

        return view('site.faqs', compact('faqs'));
    }
}
