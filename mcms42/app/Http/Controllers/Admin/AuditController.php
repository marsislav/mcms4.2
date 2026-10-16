<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;

class AuditController extends Controller
{
    public function index(Request $request)
    {
        $logs = AuditLog::with('user')->latest('id')
            ->when($request->filled('action'), fn ($q) => $q->where('action', 'like', addcslashes($request->string('action')->limit(40), '%_\\').'%'))
            ->when($request->filled('user'), fn ($q) => $q->where('user_id', (int) $request->input('user')))
            ->paginate(40)->withQueryString();

        return view('admin.audit', [
            'logs' => $logs,
            'users' => \App\Models\User::orderBy('name')->get(['id', 'name']),
        ]);
    }
}
