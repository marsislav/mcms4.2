<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\Stats;
use App\Support\StatsDigest;
use App\Support\Tables;
use Illuminate\Http\Request;

/** Visit statistics (data comes from the public-site counter, see App\Support\Stats). */
class StatsController extends Controller
{
    private function range(Request $request): array
    {
        return Stats::resolveRange($request->query('range'), $request->query('from'), $request->query('to'), new \DateTimeImmutable('today'));
    }

    public function index(Request $request)
    {
        $r = $this->range($request);
        $ready = Tables::has('page_views');

        return view('admin.stats', [
            'ready' => $ready,
            'r' => $r,
            's' => $ready ? Stats::summary($r) : [],
            'd' => $ready ? Stats::details($r, substr(trim((string) $request->query('ip')), 0, 45)) : [],
            'x' => $ready ? Stats::extras($r) : [],
        ]);
    }

    /** CSV download of one report. The raw visit log contains IPs, so it is limited to admins. */
    public function export(Request $request)
    {
        $r = $this->range($request);
        $type = (string) $request->query('type', 'pages');
        abort_unless(in_array($type, ['pages', 'sources', 'search', 'clicks', 'visits'], true), 404);
        abort_if($type === 'visits' && ! auth()->user()?->isAdmin(), 403);

        $s = Stats::summary($r);
        $d = Stats::details($r, null, 0);
        $x = Stats::extras($r);
        $head = [];
        $rows = [];
        switch ($type) {
            case 'pages':
                $head = ['Page', 'Path / key', 'Views'];
                foreach ($s['top'] ?? [] as $t) {
                    $rows[] = [$t->label, $t->view_key, $t->total];
                }
                break;
            case 'sources':
                $head = ['Type', 'Name', 'Visits'];
                foreach ($d['source_names'] ?? [] as $t) {
                    $rows[] = [$t->source, $t->name, $t->total];
                }
                break;
            case 'search':
                $head = ['Term', 'Searches', 'Without results'];
                foreach ($x['terms'] as $t) {
                    $rows[] = [$t->term, $t->searches, $t->zero];
                }
                break;
            case 'clicks':
                $head = ['Label', 'Kind', 'Clicks'];
                foreach ($x['clicks'] as $t) {
                    $rows[] = [$t->label, $t->kind, $t->total];
                }
                break;
            case 'visits':
                $head = ['Time', 'Path', 'IP', 'Country', 'City', 'Browser', 'System', 'Device', 'Source', 'Referrer'];
                foreach (\Illuminate\Support\Facades\DB::table('visits')->whereBetween('created_at', [$r['from'].' 00:00:00', $r['to'].' 23:59:59'])->orderBy('id')->limit(50000)->get() as $v) {
                    $rows[] = [$v->created_at, $v->path, $v->ip, $v->country, $v->city ?? '', $v->browser, $v->os, $v->device, $v->source ?? '', $v->ref_host];
                }
                break;
        }

        return response()->streamDownload(function () use ($head, $rows) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // UTF-8 BOM so Excel opens it correctly
            fputcsv($out, $head);
            foreach ($rows as $row) {
                fputcsv($out, array_map([Stats::class, 'csvCell'], $row));
            }
            fclose($out);
        }, 'stats-'.$type.'-'.$r['from'].'_'.$r['to'].'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /** "Send now" button next to the weekly summary settings. */
    public function digest()
    {
        $ok = StatsDigest::send(true);

        return back()->with($ok ? 'status' : 'error', $ok ? __('Summary sent.') : __('Could not send the summary. Check the recipient address and the mail settings.'));
    }
}
