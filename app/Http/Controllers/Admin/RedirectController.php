<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Redirect;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class RedirectController extends Controller
{
    public function index()
    {
        return view('admin.redirects', ['redirects' => Redirect::orderByDesc('id')->paginate(30)]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'from_path' => 'required|string|max:190',
            'to_url' => 'required|string|max:500',
            'code' => 'required|in:301,302',
        ]);

        $from = '/'.ltrim(trim(parse_url($data['from_path'], PHP_URL_PATH) ?: $data['from_path']), '/');
        if ($from === '/' || str_starts_with($from, '/'.config('mcms.admin_path'))) {
            throw ValidationException::withMessages(['from_path' => 'This URL cannot be redirected.']);
        }

        $to = trim($data['to_url']);
        if (! preg_match('~^(/(?!/)|https?://)[^\s\x00-\x1f]*$~i', $to)) {
            throw ValidationException::withMessages(['to_url' => 'The target must be a /path or an http(s):// URL.']);
        }
        if ($to === $from) {
            throw ValidationException::withMessages(['to_url' => 'The source and target are the same.']);
        }

        Redirect::updateOrCreate(['from_path' => $from], ['to_url' => $to, 'code' => (int) $data['code']]);
        audit('redirect.save', $from, $to);

        return back()->with('status', 'Redirect saved.');
    }

    public function destroy(Redirect $redirect)
    {
        audit('redirect.delete', $redirect->from_path);
        $redirect->delete();

        return back()->with('status', 'Redirect deleted.');
    }
}
