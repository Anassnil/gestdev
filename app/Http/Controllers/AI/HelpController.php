<?php

namespace App\Http\Controllers\AI;

use App\Http\Controllers\Controller;

class HelpController extends Controller
{
    public function guide()
    {
        return view('ai_help.guide');
    }
}
