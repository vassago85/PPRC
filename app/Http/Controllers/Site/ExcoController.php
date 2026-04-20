<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\ExcoMember;

class ExcoController extends Controller
{
    public function __invoke()
    {
        $members = ExcoMember::current()->get();

        return view('site.exco', compact('members'));
    }
}
