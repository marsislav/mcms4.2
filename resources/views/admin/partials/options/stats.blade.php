<label class="check"><input type="checkbox" name="stats_on" value="1" @checked($opt['stats_on'] === '1')> {{ __('Брои посещенията (страници, публикации, категории, тагове, автори)') }}</label>
<label class="check"><input type="checkbox" name="stats_skip_logged" value="1" @checked($opt['stats_skip_logged'] === '1')> {{ __('Не брои влезлите в панела потребители') }}</label>
<label class="check"><input type="checkbox" name="stats_skip_bots" value="1" @checked($opt['stats_skip_bots'] === '1')> {{ __('Не брои ботове и роботи') }}</label>
<div class="grid-form">
    <label>{{ __('Пази дневните данни (дни)') }} <input type="number" name="stats_keep_days" min="7" max="3650" value="{{ $opt['stats_keep_days'] }}"></label>
    <label>{!! __('Период за „Най-четени“ (дни) <small class="muted">0 = за всички времена</small>') !!} <input type="number" name="popular_days" min="0" max="3650" value="{{ $opt['popular_days'] }}"></label>
</div>
<hr>
<label class="check"><input type="checkbox" name="stats_log" value="1" @checked($opt['stats_log'] === '1')> {{ __('Детайлен лог на посещенията (браузър, система, устройство, откъде идва, държава)') }}</label>
<label class="check"><input type="checkbox" name="stats_log_ip" value="1" @checked($opt['stats_log_ip'] === '1')> {{ __('Записвай IP адресите на посетителите') }}</label>
<label class="check"><input type="checkbox" name="stats_anon_ip" value="1" @checked($opt['stats_anon_ip'] === '1')> {{ __('Анонимизирай IP адресите (последният октет се нулира, напр. 203.0.113.0)') }}</label>
<div class="grid-form">
    <label>{{ __('Пази детайлния лог (дни)') }} <input type="number" name="stats_log_keep_days" min="1" max="3650" value="{{ $opt['stats_log_keep_days'] }}"></label>
</div>
<p class="muted">{{ __('Агрегираните дневни броячи не използват бисквитки. IP адресът е лични данни по GDPR — ако ги записвате, споменете го в политиката за поверителност или включете анонимизиране. „Най-четени“ се добавя като джаджа в Сайдбар/Футър.') }}</p>
<hr>
<h3>{{ __('Extended statistics') }}</h3>
<label class="check"><input type="checkbox" name="stats_log_bots" value="1" @checked($opt['stats_log_bots'] === '1')> {{ __('Log bots and crawlers separately (Googlebot, Bing, AI bots) — they are never counted as visitors') }}</label>
<label class="check"><input type="checkbox" name="stats_returning" value="1" @checked($opt['stats_returning'] === '1')> {{ __('Recognise returning visitors (stores a daily-stable pseudonymous hash; may need a mention in the privacy policy)') }}</label>
<label class="check"><input type="checkbox" name="stats_engage" value="1" @checked($opt['stats_engage'] === '1')> {{ __('Measure time on page and scroll depth') }}</label>
<label class="check"><input type="checkbox" name="stats_clicks" value="1" @checked($opt['stats_clicks'] === '1')> {{ __('Track clicks (data-track attribute, mailto:, tel:, downloads, outbound links)') }}</label>
<label class="check"><input type="checkbox" name="stats_search_terms" value="1" @checked($opt['stats_search_terms'] === '1')> {{ __('Record on-site search terms (including searches with no results)') }}</label>
<hr>
<h3>{{ __('Weekly email summary') }}</h3>
<label class="check"><input type="checkbox" name="stats_digest" value="1" @checked($opt['stats_digest'] === '1')> {{ __('Send a weekly statistics summary by email') }}</label>
<div class="grid-form">
    <label>{{ __('Day of the week') }}
        <select name="stats_digest_dow">
            @foreach([1 => __('Monday'), 2 => __('Tuesday'), 3 => __('Wednesday'), 4 => __('Thursday'), 5 => __('Friday'), 6 => __('Saturday'), 7 => __('Sunday')] as $n => $label)
                <option value="{{ $n }}" @selected((int) $opt['stats_digest_dow'] === $n)>{{ $label }}</option>
            @endforeach
        </select>
    </label>
    <label>{{ __('Recipient (empty = contact email)') }} <input type="email" name="stats_digest_to" value="{{ $opt['stats_digest_to'] }}" maxlength="190"></label>
</div>
<p class="muted">{{ __('Without a scheduler the summary is sent by the first visit on the chosen day. For exact timing add a cron job that opens this URL:') }}
    <code>{{ url('stats/digest/'.\App\Support\StatsDigest::token()) }}</code></p>
@if(\Illuminate\Support\Facades\Route::has('admin.stats.digest'))
<button type="submit" class="btn" formaction="{{ route('admin.stats.digest') }}" formmethod="post" formnovalidate>{{ __('Send a summary now') }}</button>
@endif
<p class="muted">{{ __('City needs a CDN header (Cloudflare IP Geolocation); without one the Cities table stays empty. Time on page and clicks use no cookies.') }}</p>
