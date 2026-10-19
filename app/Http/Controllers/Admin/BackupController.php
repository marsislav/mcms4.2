<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\Backup;
use Illuminate\Http\Request;

class BackupController extends Controller
{
    public function index()
    {
        return view('admin.backups', ['backups' => Backup::list(), 'zip' => class_exists(\ZipArchive::class)]);
    }

    public function create(Request $request)
    {
        $request->validate(['kind' => 'required|in:db,uploads']);

        try {
            $name = $request->kind === 'db' ? Backup::database() : Backup::uploads();
        } catch (\Throwable $e) {
            report($e);

            return back()->with('error', __('Архивирането неуспешно: :err', ['err' => mb_substr($e->getMessage(), 0, 200)]));
        }
        audit('backup.create', $name);

        return back()->with('status', __('Архивът :name е създаден.', ['name' => $name]));
    }

    public function download(string $name)
    {
        $path = Backup::path($name);
        abort_unless($path, 404);
        audit('backup.download', $name);

        return response()->download($path, $name, ['X-Content-Type-Options' => 'nosniff']);
    }

    public function destroy(string $name)
    {
        abort_unless(Backup::delete($name), 404);
        audit('backup.delete', $name);

        return back()->with('status', __('Архивът е изтрит.'));
    }
}
