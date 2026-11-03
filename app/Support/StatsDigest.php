<?php

namespace App\Support;

use App\Models\Setting;
use Illuminate\Support\Facades\Mail;

/**
 * Weekly statistics email (Settings > Statistics > "Weekly email summary").
 * Shared hosting has no scheduler by default, so it is sent lazily by a visit on the chosen weekday (see Stats::hit),
 * or by a cron job calling the secret URL shown in the settings: /stats/digest/<token>.
 * Each calendar day sends at most once (setting `stats.digest_last`).
 */
class StatsDigest
{
    /** Secret token for the cron URL (created on first use). */
    public static function token(): string
    {
        $t = (string) setting('stats.digest_token', '');
        if (! preg_match('/^[a-f0-9]{32}$/', $t)) {
            $t = bin2hex(random_bytes(16));
            Setting::putMany(['stats.digest_token' => $t]);
        }

        return $t;
    }

    /** Sends the summary when it is due. Never throws. */
    public static function maybeSend(): bool
    {
        try {
            if (! Options::on('stats_digest') || (int) date('N') !== (int) Options::get('stats_digest_dow')) {
                return false;
            }

            return self::send(false);
        } catch (\Throwable $e) {
            report($e);

            return false;
        }
    }

    /** Sends now ($force ignores the once-a-day guard). Returns true when a mail was handed to the mailer. */
    public static function send(bool $force): bool
    {
        $to = trim((string) Options::get('stats_digest_to')) ?: (string) setting('contact_email', '');
        if (! filter_var($to, FILTER_VALIDATE_EMAIL) || ! Tables::has('page_views')) {
            return false;
        }
        $today = now()->toDateString();
        if (! $force && setting('stats.digest_last') === $today) {
            return false;
        }
        Setting::putMany(['stats.digest_last' => $today]); // mark first: never send twice
        try {
            Mail::raw(self::build(), fn ($m) => $m->to($to)->subject('['.setting('site_name').'] '.__('Weekly statistics')));
        } catch (\Throwable $e) {
            report($e);

            return false;
        }

        return true;
    }

    /** Plain-text body for the last 7 days compared with the 7 days before. */
    public static function build(): string
    {
        $r = Stats::resolveRange('7', null, null, new \DateTimeImmutable('today'));
        $s = Stats::summary($r);
        $d = Stats::details($r, null, 0);
        $arrow = fn (?array $x) => ! $x ? '' : match ($x['dir']) {
            'up' => ' (+'.$x['pct'].'%)', 'down' => ' ('.$x['pct'].'%)', 'new' => ' (new)', default => ' (=)',
        };
        $lines = [
            __('Statistics for :from – :to', ['from' => $r['from'], 'to' => $r['to']]),
            str_repeat('=', 40),
            __('Page views').': '.($s['hits'] ?? 0).$arrow($s['hits_delta'] ?? null),
        ];
        if (($s['uniq'] ?? null) !== null) {
            $lines[] = __('Unique visitors').': '.$s['uniq'].$arrow($s['uniq_delta'] ?? null);
        }
        $lines[] = '';
        $lines[] = __('Top pages');
        foreach (collect($s['top'] ?? [])->take(5) as $i => $row) {
            $lines[] = ($i + 1).'. '.($row->label ?: $row->view_key).' — '.$row->total;
        }
        foreach (['referrers' => 'Top referrers', 'source_names' => 'Top sources'] as $k => $title) {
            if (! empty($d[$k]) && count($d[$k])) {
                $lines[] = '';
                $lines[] = __($title);
                foreach (collect($d[$k])->take(5) as $row) {
                    $lines[] = '- '.$row->name.' — '.$row->total;
                }
                break;
            }
        }
        $lines[] = '';
        $lines[] = url(config('mcms.admin_path').'/stats');

        return implode("\n", $lines);
    }
}
