<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Media;
use App\Models\Setting;
use App\Support\Design;
use App\Support\Discussion;
use App\Support\Widgets;
use App\Support\Options;
use App\Support\Sanitizer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Mail;

class SettingController extends Controller
{
    public function edit()
    {
        return view('admin.settings', [
            'languages' => mcms_languages(),
            'logo' => ($id = setting('logo_media_id')) ? Media::find($id) : null,
            'favicon' => ($id = setting('favicon_media_id')) ? Media::find($id) : null,
            'design' => Design::all(),
            'discussion' => Discussion::all(),
            'footerCols' => Widgets::footerCols(),
            'footerAll' => collect(range(1, Widgets::MAX_COLS))->mapWithKeys(fn ($c) => [$c => Widgets::decode(setting('widgets.footer.c'.$c))])->all(),
            'sidebar' => Widgets::sidebar(),
            'contactFields' => \App\Support\ContactFields::all(),
            'opt' => Options::all(),
            'optCategories' => \App\Models\Category::with('translations')->orderBy('sort')->get(),
            'menus' => \App\Models\Menu::orderBy('id')->get(),
            'ogImage' => ($id = setting('og_media_id')) ? Media::find($id) : null,
        ]);
    }

    public function update(Request $request)
    {
        $request->validate([
            'site_name' => 'required|string|max:100',
            'contact_email' => 'nullable|email|max:190',
            'posts_per_page' => 'required|integer|min:1|max:50',
            'related_count' => 'required|integer|min:0|max:12',
            'webp_quality' => 'nullable|integer|min:40|max:100',
            'logo_media_id' => 'nullable|integer|exists:media,id',
            'favicon_media_id' => 'nullable|integer|exists:media,id',
            'og_media_id' => 'nullable|integer|exists:media,id',
            'social_facebook' => 'nullable|url|max:200',
            'social_instagram' => 'nullable|url|max:200',
            'social_linkedin' => 'nullable|url|max:200',
            'social_youtube' => 'nullable|url|max:200',
            'robots_extra' => 'nullable|string|max:1000',
            'smtp_host' => 'nullable|string|max:190',
            'smtp_port' => 'nullable|integer|min:1|max:65535',
            'smtp_username' => 'nullable|string|max:190',
            'smtp_password' => 'nullable|string|max:300',
            'smtp_encryption' => 'nullable|in:tls,ssl',
            'mail_from_address' => 'nullable|email|max:190',
            'mail_from_name' => 'nullable|string|max:100',
            'tagline' => 'nullable|array',
            'tagline.*' => 'nullable|string|max:200',
            'footer_text' => 'nullable|array',
            'footer_text.*' => 'nullable|string|max:500',
            'meta_description' => 'nullable|array',
            'meta_description.*' => 'nullable|string|max:300',
        ]);

        $values = [];
        foreach (['site_name', 'contact_email', 'posts_per_page', 'related_count', 'logo_media_id', 'favicon_media_id', 'og_media_id',
            'social_facebook', 'social_instagram', 'social_linkedin', 'social_youtube', 'robots_extra',
            'smtp_host', 'smtp_port', 'smtp_username', 'smtp_encryption', 'mail_from_address', 'mail_from_name'] as $k) {
            $values[$k] = $this->clean($request->input($k));
        }

        $values['webp_convert'] = $request->boolean('webp_convert') ? '1' : '0';
        $values['webp_quality'] = $this->clean($request->input('webp_quality')) ?? '82';
        $values['maintenance_mode'] = $request->boolean('maintenance_mode') ? '1' : '0';

        // Password is stored encrypted and only replaced when a new one is typed
        if ($request->filled('smtp_password')) {
            $values['smtp_password'] = Crypt::encryptString($request->input('smtp_password'));
        } elseif ($request->boolean('smtp_password_clear')) {
            $values['smtp_password'] = null;
        }

        foreach (['tagline', 'footer_text', 'meta_description'] as $group) {
            foreach (mcms_locales() as $loc) {
                $values["$group.$loc"] = $this->clean($request->input("$group.$loc"));
            }
        }

        Setting::putMany($values);
        audit('settings.update', null, ['maintenance' => $values['maintenance_mode']]);

        return back()->with('status', __('Настройките са записани.'));
    }

    /** WordPress-like groups: reading, writing, datetime, permalinks, media, scripts, maintenance. */
    public function updateOptions(Request $request, string $group)
    {
        abort_unless(isset(Options::GROUPS[$group]), 404);

        $ctx = ['locales' => mcms_locales()];
        if ($group === 'writing') {
            $ctx['categories'] = \App\Models\Category::pluck('id')->map(fn ($i) => (int) $i)->all();
        }
        if ($group === 'permalinks') {
            $ctx['reserved'] = config('mcms.reserved_slugs', []);
            $ctx['current'] = array_intersect_key(Options::all(), array_flip(Options::BASE_KEYS));
            $cand = array_map(fn ($k) => strtolower(trim((string) $request->input($k), "/ \t")), Options::BASE_KEYS);
            $ctx['taken'] = \App\Models\ContentTranslation::whereIn('slug', array_filter($cand))
                ->whereHas('content', fn ($q) => $q->where('type', 'page'))->pluck('slug')->all();
        }

        $r = Options::clean($group, $request->all(), $ctx);
        $values = [];
        foreach ($r['values'] as $k => $v) {
            $values['opt.'.$k] = $v === '' ? null : $v;
        }
        Setting::putMany($values);
        if ($group === 'permalinks') {
            \Illuminate\Support\Facades\Cache::forget('mcms.sitemap');
        }
        audit('settings.'.$group);

        $back = redirect(route('admin.settings').'#'.$group);

        return $r['errors']
            ? $back->withErrors($r['errors'])->with('status', __('Записано частично — вижте забележките.'))
            : $back->with('status', __(':group: настройките са записани.', ['group' => __(Options::GROUPS[$group])]));
    }

    /** Footer: number of columns (0 = classic footer, 1-12) + widgets of each column. */
    public function updateFooter(Request $request)
    {
        $data = $request->validate(['footer_cols' => 'required|integer|min:0|max:'.Widgets::MAX_COLS, 'cols' => 'nullable|array', 'seen' => 'nullable|array']);
        $locales = mcms_locales();
        $values = ['footer.cols' => (string) (int) $data['footer_cols']];

        foreach ((array) $request->input('seen', []) as $c) {
            $c = (int) $c;
            if ($c < 1 || $c > Widgets::MAX_COLS) {
                continue;
            }
            $list = Widgets::cleanList((array) $request->input("cols.$c", []), $locales);
            $json = $list ? json_encode($list, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null;
            if ($json !== null && strlen($json) > Widgets::MAX_JSON) {
                throw \Illuminate\Validation\ValidationException::withMessages(["cols.$c" => __('Колона :c е твърде голяма — съкратете текстовете.', ['c' => $c])]);
            }
            $values["widgets.footer.c$c"] = $json;
        }
        Setting::putMany($values);
        audit('settings.footer', null, ['cols' => $values['footer.cols']]);

        return redirect(route('admin.settings').'#footer')->with('status', __('Футърът е записан.'));
    }

    /** Blog sidebar: on/off, side and widgets. */
    public function updateContact(Request $request)
    {
        $request->validate(['fields' => 'nullable|array|max:60']);
        $list = \App\Support\ContactFields::clean((array) $request->input('fields', []), mcms_locales());
        $json = $list ? json_encode($list, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null;
        if ($json !== null && strlen($json) > \App\Support\ContactFields::MAX_JSON) {
            throw \Illuminate\Validation\ValidationException::withMessages(['fields' => __('Формулярът е твърде голям — съкратете текстовете.')]);
        }
        Setting::putMany(['contact.fields' => $json]);
        audit('settings.contact');

        return redirect(route('admin.settings').'#contact')->with('status', __('Полетата на формуляра са записани.'));
    }

    public function updateSidebar(Request $request)
    {
        $request->validate(['sidebar_side' => 'required|in:left,right', 'cols' => 'nullable|array']);
        $list = Widgets::cleanList((array) $request->input('cols.0', []), mcms_locales());
        $json = $list ? json_encode($list, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null;
        if ($json !== null && strlen($json) > Widgets::MAX_JSON) {
            throw \Illuminate\Validation\ValidationException::withMessages(['cols' => __('Сайдбарът е твърде голям — съкратете текстовете.')]);
        }
        Setting::putMany([
            'sidebar.on' => $request->boolean('sidebar_on') ? '1' : '0',
            'sidebar.side' => $request->input('sidebar_side'),
            'widgets.sidebar' => $json,
        ]);
        audit('settings.sidebar');

        return redirect(route('admin.settings').'#sidebar')->with('status', __('Сайдбарът е записан.'));
    }

    public function updateDiscussion(Request $request)
    {
        $request->validate([
            'close_after_days' => 'required|integer|min:0|max:3650',
            'thread_depth' => 'required|integer|min:2|max:5',
            'per_page' => 'required|integer|min:0|max:200',
            'order' => 'required|in:asc,desc',
            'max_links' => 'required|integer|min:0|max:20',
            'flood_seconds' => 'required|integer|min:0|max:3600',
            'moderation_keys' => 'nullable|string|max:5000',
            'disallowed_keys' => 'nullable|string|max:5000',
        ]);

        $values = [];
        foreach (Discussion::DEFAULTS as $k => $_) {
            if (in_array($k, Discussion::BOOL_KEYS, true)) {
                $values["discussion.$k"] = $request->boolean($k) ? '1' : '0';
            } elseif (in_array($k, ['moderation_keys', 'disallowed_keys'], true)) {
                $lines = array_filter(array_map('trim', preg_split('/\R/u', strip_tags((string) $request->input($k, ''))) ?: []));
                $values["discussion.$k"] = $lines ? implode("\n", array_unique($lines)) : null;
            } else {
                $values["discussion.$k"] = (string) $request->input($k, Discussion::DEFAULTS[$k]);
            }
        }
        Setting::putMany($values);
        audit('settings.discussion', null, ['enabled' => $values['discussion.enabled']]);

        return redirect(route('admin.settings').'#discussion')->with('status', __('Настройките за дискусии са записани.'));
    }

    public function updateDesign(Request $request)
    {
        $color = ['nullable', 'regex:/^#([0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/'];
        $font = ['nullable', 'in:'.implode(',', array_keys(Design::FONTS))];
        $anim = ['required', 'in:'.implode(',', array_keys(Design::ANIMS))];
        $align = ['required', 'in:left,center,right'];
        $rules = [
            'font_size' => 'nullable|integer|min:14|max:22',
            'logo_size' => ['required', function ($attr, $v, $fail) {
                if ($v !== 'auto' && (! ctype_digit((string) $v) || (int) $v < 20 || (int) $v > 300)) {
                    $fail(__('Големината на логото е „auto“ или число между 20 и 300 (px).'));
                }
            }],
            'anim_page_type' => $anim,
            'anim_speed' => 'required|integer|min:150|max:2000',
            'topbar_cols' => 'required|integer|min:1|max:'.Design::MAX_COLS,
            'progress_height' => 'required|integer|min:1|max:12',
            'blur_height' => 'required|integer|min:5|max:50',
            'grain_opacity' => 'required|integer|min:1|max:40',
            'grain_density' => 'required|integer|min:5|max:100',
            'smooth_duration' => 'required|numeric|min:0.4|max:3',
            'progress_pos' => ['required', 'in:'.implode(',', array_keys(Design::PROGRESS_POS))],
            'progress_scope' => ['required', 'in:'.implode(',', array_keys(Design::PROGRESS_SCOPE))],
            'topbar' => 'nullable|array',
            'topbar.*' => 'nullable|array',
            'topbar.*.*' => 'nullable|string|max:2000',
        ];
        foreach (Design::COLOR_KEYS as $k) {
            $rules[$k] = $color;
        }
        foreach (['font_body', 'font_heading', 'font_menu', 'font_logo'] as $k) {
            $rules[$k] = $font;
        }
        foreach (array_keys(Design::ELEMENTS) as $k) {
            $rules['el_'.$k] = $anim;
        }
        for ($i = 1; $i <= Design::MAX_COLS; $i++) {
            $rules["topbar_c{$i}_align"] = $align;
        }
        $request->validate($rules);

        $values = [];
        foreach (Design::DEFAULTS as $k => $default) {
            if (in_array($k, Design::BOOL_KEYS, true)) {
                $values["design.$k"] = $request->boolean($k) ? '1' : '0';
            } elseif (in_array($k, Design::COLOR_KEYS, true)) {
                $values["design.$k"] = Design::normalizeColor($request->input($k));
            } else {
                $v = trim((string) $request->input($k, ''));
                $values["design.$k"] = $v === '' ? null : $v;
            }
        }

        // Top-bar texts per language: plain text stays text (line breaks kept), HTML goes through the Sanitizer
        for ($i = 1; $i <= Design::MAX_COLS; $i++) {
            foreach (mcms_locales() as $loc) {
                $raw = trim((string) $request->input("topbar.c$i.$loc", ''));
                if ($raw === '') {
                    $values["design.topbar_c$i.$loc"] = null;
                } elseif (strip_tags($raw) === $raw) {
                    $values["design.topbar_c$i.$loc"] = nl2br(e($raw), false);
                } else {
                    $values["design.topbar_c$i.$loc"] = Sanitizer::clean($raw);
                }
            }
        }

        // Per-element backgrounds: resolve picked media ids to stored paths (image/poster must be images, video must be a video)
        $bgIn = array_filter((array) $request->input('bg', []), 'is_array');
        $ids = [];
        foreach ($bgIn as $row) {
            foreach (['image_id', 'video_id', 'poster_id'] as $f) {
                if (isset($row[$f]) && is_scalar($row[$f]) && ctype_digit((string) $row[$f])) {
                    $ids[] = (int) $row[$f];
                }
            }
        }
        $media = $ids ? Media::whereIn('id', array_unique($ids))->get()->keyBy('id') : collect();
        foreach ($bgIn as $key => $row) {
            foreach (['image' => 'image/', 'video' => 'video/', 'poster' => 'image/'] as $f => $mimePrefix) {
                $m = $media[(int) ($row[$f.'_id'] ?? 0)] ?? null;
                $bgIn[$key][$f.'_path'] = ($m && str_starts_with((string) $m->mime, $mimePrefix)) ? $m->path : '';
            }
        }
        $bg = array_filter(Design::normalizeBg($bgIn), fn ($c) => Design::bgUsed($c));
        $values['design.backgrounds'] = $bg ? json_encode($bg, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null;

        Setting::putMany($values);
        audit('settings.design');

        return redirect(route('admin.settings').'#design')->with('status', __('Дизайнът е записан.'));
    }

    public function resetDesign()
    {
        Setting::where('key', 'like', 'design.%')->delete();
        \Illuminate\Support\Facades\Cache::forget('mcms.settings');
        audit('settings.design.reset');

        return redirect(route('admin.settings').'#design')->with('status', __('Дизайнът е върнат към подразбиращите се стойности.'));
    }

    public function testMail(Request $request)
    {
        $to = $request->user()->email;
        try {
            Mail::raw(__('Тестово съобщение от :site. Пощата работи.', ['site' => setting('site_name', 'mCMS')]), function ($m) use ($to) {
                $m->to($to)->subject(__('mCMS — тест на имейл'));
            });
        } catch (\Throwable $e) {
            report($e);

            return back()->with('error', __('Изпращането неуспешно: :err', ['err' => mb_substr($e->getMessage(), 0, 200)]));
        }

        return back()->with('status', __('Тестово писмо е изпратено до :to.', ['to' => $to]));
    }

    private function clean(mixed $v): ?string
    {
        if ($v === null) {
            return null;
        }
        $v = trim(strip_tags((string) $v));

        return $v === '' ? null : $v;
    }
}
