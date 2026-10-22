<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Redirect;
use App\Support\Tables;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/** 404 journal: missing paths with hit counts; one click turns an entry into a redirect. */
class NotFoundController extends Controller
{
    public function index(Request $request)
    {
        $ready = Tables::has('not_found_log');
        $rows = $ready ? DB::table('not_found_log')->orderByDesc('hits')->orderByDesc('last_seen')->paginate(40) : null;

        return view('admin.notfound', ['ready' => $ready, 'rows' => $rows]);
    }

    /** Create a redirect for a logged path and drop it from the journal. */
    public function redirect(Request $request, int $id)
    {
        abort_unless(Tables::has('not_found_log'), 404);
        $row = DB::table('not_found_log')->where('id', $id)->first();
        abort_unless($row, 404);

        $to = trim((string) $request->validate(['to_url' => 'required|string|max:500', 'code' => 'required|in:301,302'])['to_url']);
        if (! preg_match('~^(/(?!/)|https?://)[^\s\x00-\x1f]*$~i', $to)) {
            throw ValidationException::withMessages(['to_url' => __('Целта трябва да е /път или http(s):// адрес.')]);
        }
        if ($to === $row->path) {
            throw ValidationException::withMessages(['to_url' => __('Източникът и целта са еднакви.')]);
        }
        Redirect::updateOrCreate(['from_path' => $row->path], ['to_url' => $to, 'code' => (int) $request->input('code')]);
        DB::table('not_found_log')->where('id', $id)->delete();
        audit('redirect.save', $row->path, $to);

        return back()->with('status', __('Пренасочването е създадено.'));
    }

    public function destroy(int $id)
    {
        abort_unless(Tables::has('not_found_log'), 404);
        DB::table('not_found_log')->where('id', $id)->delete();

        return back()->with('status', __('Записът е изтрит.'));
    }

    public function clear()
    {
        abort_unless(Tables::has('not_found_log'), 404);
        DB::table('not_found_log')->delete();
        audit('notfound.clear');

        return back()->with('status', __('Журналът е изчистен.'));
    }
}
