<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Subscriber;
use App\Support\Tables;
use Illuminate\Http\Request;

/** Newsletter subscribers: list, filter, CSV export, delete (admin only). */
class SubscriberController extends Controller
{
    public function index(Request $request)
    {
        $ready = Tables::has('subscribers');
        $filter = in_array($request->query('f'), ['active', 'pending', 'out'], true) ? $request->query('f') : 'all';
        $rows = null;
        $counts = [];
        if ($ready) {
            $counts = [
                'active' => Subscriber::whereNotNull('confirmed_at')->whereNull('unsubscribed_at')->count(),
                'pending' => Subscriber::whereNull('confirmed_at')->whereNull('unsubscribed_at')->count(),
                'out' => Subscriber::whereNotNull('unsubscribed_at')->count(),
            ];
            $q = Subscriber::orderByDesc('id');
            match ($filter) {
                'active' => $q->whereNotNull('confirmed_at')->whereNull('unsubscribed_at'),
                'pending' => $q->whereNull('confirmed_at')->whereNull('unsubscribed_at'),
                'out' => $q->whereNotNull('unsubscribed_at'),
                default => null,
            };
            $rows = $q->paginate(40)->withQueryString();
        }

        return view('admin.subscribers', compact('ready', 'rows', 'counts', 'filter'));
    }

    public function export()
    {
        abort_unless(Tables::has('subscribers'), 404);
        $q = Subscriber::whereNotNull('confirmed_at')->whereNull('unsubscribed_at')->orderBy('id');
        audit('subscribers.export');

        return response()->streamDownload(function () use ($q) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['email', 'name', 'locale', 'confirmed_at']);
            $q->chunk(500, function ($chunk) use ($out) {
                foreach ($chunk as $s) {
                    // a leading = + - @ would be run as a formula by spreadsheets
                    $cell = fn ($v) => preg_match('/^[=+\-@\t\r]/', (string) $v) ? "'".$v : (string) $v;
                    fputcsv($out, [$cell($s->email), $cell($s->name), $s->locale, $s->confirmed_at?->toDateTimeString()]);
                }
            });
            fclose($out);
        }, 'subscribers-'.now()->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function destroy(Subscriber $subscriber)
    {
        audit('subscriber.delete', $subscriber->email);
        $subscriber->delete();

        return back()->with('status', __('Абонатът е изтрит.'));
    }
}
