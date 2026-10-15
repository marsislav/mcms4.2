<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Media;
use App\Models\Setting;
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
            'ogImage' => ($id = setting('og_media_id')) ? Media::find($id) : null,
        ]);
    }

    public function update(Request $request)
    {
        $request->validate([
            'site_name' => 'required|string|max:100',
            'contact_email' => 'nullable|email|max:190',
            'posts_per_page' => 'required|integer|min:1|max:50',
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
        foreach (['site_name', 'contact_email', 'posts_per_page', 'logo_media_id', 'favicon_media_id', 'og_media_id',
            'social_facebook', 'social_instagram', 'social_linkedin', 'social_youtube', 'robots_extra',
            'smtp_host', 'smtp_port', 'smtp_username', 'smtp_encryption', 'mail_from_address', 'mail_from_name'] as $k) {
            $values[$k] = $this->clean($request->input($k));
        }

        $values['maintenance_mode'] = $request->boolean('maintenance_mode') ? '1' : '0';
        $values['require_2fa_admin'] = $request->boolean('require_2fa_admin') ? '1' : '0';

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
        audit('settings.update', null, ['maintenance' => $values['maintenance_mode'], '2fa_required' => $values['require_2fa_admin']]);

        return back()->with('status', 'Settings saved.');
    }

    public function testMail(Request $request)
    {
        $to = $request->user()->email;
        try {
            Mail::raw('Test message from '.setting('site_name', 'mCMS').'. Mail is working.', function ($m) use ($to) {
                $m->to($to)->subject('mCMS — email test');
            });
        } catch (\Throwable $e) {
            report($e);

            return back()->with('error', 'Sending failed: '.mb_substr($e->getMessage(), 0, 200));
        }

        return back()->with('status', "Test email sent to $to.");
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
