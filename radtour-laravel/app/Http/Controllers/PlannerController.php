<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class PlannerController extends Controller
{
    public function __invoke(): View
    {
        return view('planner');
    }
}
