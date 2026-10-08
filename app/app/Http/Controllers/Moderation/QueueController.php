<?php

namespace App\Http\Controllers\Moderation;

use App\Http\Controllers\Controller;
use App\Services\ModerationQueue;
use Illuminate\Contracts\View\View;

class QueueController extends Controller
{
    public function index(ModerationQueue $queue): View
    {
        return view('moderation.queue', [
            'cases' => $queue->cases((int) config('votalis.moderation.queue_per_page', 50)),
            'openCount' => $queue->openCount(),
        ]);
    }
}
