<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\PluginManager;
use Illuminate\Http\Request;

class PluginController extends Controller
{
    public function index()
    {
        return view('admin.plugins', ['plugins' => PluginManager::all()]);
    }

    public function toggle(Request $request, string $slug)
    {
        abort_unless(PluginManager::validSlug($slug) && isset(PluginManager::all()[$slug]), 404);

        $enabled = PluginManager::enabled();
        if (in_array($slug, $enabled, true)) {
            $enabled = array_values(array_diff($enabled, [$slug]));
            $action = 'plugin.disable';
        } else {
            $enabled[] = $slug;
            $action = 'plugin.enable';
        }
        PluginManager::setEnabled($enabled);
        audit($action, $slug);

        return back()->with('status', 'Plugin status changed.');
    }
}
