<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Media;
use App\Models\Setting;
use App\Support\Design;
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

        return back()->with('status', 'Настройките са записани.');
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
                    $fail('Големината на логото е „auto“ или число между 20 и 300 (px).');
                }
            }],
            'anim_page_type' => $anim,
            'anim_speed' => 'required|integer|min:150|max:2000',
            'topbar_cols' => 'required|integer|min:1|max:'.Design::MAX_COLS,
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

        Setting::putMany($values);
        audit('settings.design');

        return redirect(route('admin.settings').'#design')->with('status', 'Дизайнът е записан.');
    }

    public function resetDesign()
    {
        Setting::where('key', 'like', 'design.%')->delete();
        \Illuminate\Support\Facades\Cache::forget('mcms.settings');
        audit('settings.design.reset');

        return redirect(route('admin.settings').'#design')->with('status', 'Дизайнът е върнат към подразбиращите се стойности.');
    }

    public function testMail(Request $request)
    {
        $to = $request->user()->email;
        try {
            Mail::raw('Тестово съобщение от '.setting('site_name', 'mCMS').'. Пощата работи.', function ($m) use ($to) {
                $m->to($to)->subject('mCMS — тест на имейл');
            });
        } catch (\Throwable $e) {
            report($e);

            return back()->with('error', 'Изпращането неуспешно: '.mb_substr($e->getMessage(), 0, 200));
        }

        return back()->with('status', "Тестово писмо е изпратено до $to.");
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
