<?php

namespace App\Http\Controllers;

use App\Services\PlatformPulse;
use Illuminate\Contracts\View\View;

class HomeController extends Controller
{
    public function __invoke(PlatformPulse $pulse): View
    {
        return view('home', [
            'pulse' => $pulse->counts(),
            'tradeoff' => $pulse->featuredTradeoff(),
        ]);
    }
}
