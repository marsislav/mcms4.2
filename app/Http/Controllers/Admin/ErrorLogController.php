<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\ErrorLog;
use Illuminate\Http\Request;

/** Reads storage/logs/*.log in the panel (admin only). */
class ErrorLogController extends Controller
{
    public function index(Request $request)
    {
        $files = ErrorLog::files();
        $file = (string) $request->query('file', $files[0]['name'] ?? '');
        $entries = [];
        $truncated = false;
        if ($file !== '' && ErrorLog::path($file)) {
            [$entries, $truncated] = ErrorLog::read($file, max(50, (int) \App\Support\Options::get('log_tail_kb')) * 1024);
        }
        $level = in_array($request->query('level'), ErrorLog::LEVELS, true) ? $request->query('level') : '';
        if ($level !== '') {
            $entries = array_values(array_filter($entries, fn ($e) => $e['level'] === $level));
        }

        return view('admin.errors', compact('files', 'file', 'entries', 'truncated', 'level'));
    }

    public function download(Request $request)
    {
        $path = ErrorLog::path((string) $request->query('file'));
        abort_unless($path, 404);
        audit('errorlog.download', basename($path));

        return response()->download($path);
    }

    public function clear(Request $request)
    {
        $path = ErrorLog::path((string) $request->input('file'));
        abort_unless($path, 404);
        file_put_contents($path, '');
        audit('errorlog.clear', basename($path));

        return back()->with('status', __('Журналът е изчистен.'));
    }
}
