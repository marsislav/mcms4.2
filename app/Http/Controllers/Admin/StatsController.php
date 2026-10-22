<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\Stats;
use App\Support\Tables;

/** Visit statistics overview (data comes from the public-site counter, see App\Support\Stats). */
class StatsController extends Controller
{
    public function index()
    {
        return view('admin.stats', ['ready' => Tables::has('page_views'), 's' => Stats::summary()]);
    }
}
