<?php

namespace App\Support;

use App\Models\Content;
use App\Models\Setting;
use App\Models\Subscriber;
use Illuminate\Support\Facades\Mail;

/**
 * Emails active subscribers when a post becomes publicly visible (Settings > Newsletter > "Email subscribers about new posts").
 * Each post is sent once (flag setting `nl.sent.<id>`); at most BATCH recipients per save to stay within shared-hosting limits.
 */
class Newsletter
{
    public const BATCH = 200;

    public static function notifyPost(Content $content): int
    {
        try {
            if ($content->type !== 'post' || ! Options::on('nl_on') || ! Options::on('nl_notify_posts') || ! Tables::has('subscribers')) {
                return 0;
            }
            $content->refresh()->load('translations');
            if (! $content->isVisible() || setting('nl.sent.'.$content->id)) {
                return 0;
            }
            Setting::putMany(['nl.sent.'.$content->id => '1']); // mark first: never send twice

            $sent = 0;
            $orig = app()->getLocale();
            Subscriber::whereNotNull('confirmed_at')->whereNull('unsubscribed_at')->orderBy('id')->limit(self::BATCH)->get()->each(function ($s) use ($content, &$sent) {
                $tr = $content->tr($s->locale ?: mcms_default_locale()) ?? $content->tr(mcms_default_locale()) ?? $content->translations->first();
                if (! $tr) {
                    return;
                }
                $tr->setRelation('content', $content);
                $body = $tr->title."\n\n".excerpt($tr->excerpt ?: $tr->body, 300)."\n\n".$tr->url()
                    ."\n\n— — —\n".__('Отписване:').' '.lroute('newsletter.unsubscribe', ['token' => $s->token], $s->locale ?: mcms_default_locale());
                try {
                    Mail::raw($body, fn ($m) => $m->to($s->email)->subject('['.setting('site_name').'] '.$tr->title));
                    $sent++;
                } catch (\Throwable $e) {
                    report($e);
                }
            });
            app()->setLocale($orig);

            return $sent;
        } catch (\Throwable $e) {
            report($e);

            return 0;
        }
    }
}
