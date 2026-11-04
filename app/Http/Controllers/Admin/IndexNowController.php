<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\IndexNow;

/** "Submit all pages now" button in Settings > SEO > IndexNow. */
class IndexNowController extends Controller
{
    public function submitAll()
    {
        if (! IndexNow::enabled()) {
            return back()->with('error', __('IndexNow is off, the site is set to "noindex", or the address is not a public domain.'));
        }
        $r = IndexNow::submitAll();
        $ok = in_array($r['status'], [200, 202], true);

        return back()->with($ok ? 'status' : 'error', $ok
            ? __(':n addresses sent to search engines.', ['n' => $r['urls']])
            : __('Sending failed: :why', ['why' => IndexNow::statusText($r['status'])]));
    }
}
