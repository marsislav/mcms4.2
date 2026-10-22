<?php

namespace App\Http\Controllers;

use App\Models\Subscriber;
use App\Support\Options;
use App\Support\Tables;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

/** Public newsletter endpoints: sign up (optionally double opt-in), confirm, unsubscribe. Settings: Settings > Newsletter. */
class NewsletterController extends Controller
{
    private function ready(): void
    {
        abort_unless(Options::on('nl_on') && Tables::has('subscribers'), 404);
    }

    public function subscribe(Request $request)
    {
        $this->ready();
        // honeypot: bots fill the hidden field; pretend success
        if ($request->filled('website_url')) {
            return back()->with('newsletter', __('Благодарим!'));
        }
        $rules = ['nl_email' => 'required|email:rfc|max:190', 'nl_name' => 'nullable|string|max:100'];
        if (Options::on('nl_require_consent')) {
            $rules['nl_consent'] = 'accepted';
        }
        $data = $request->validate($rules, [
            'nl_email.required' => __('Въведете имейл.'),
            'nl_email.email' => __('Невалиден имейл адрес.'),
            'nl_consent.accepted' => __('Потвърдете съгласието.'),
        ]);
        $email = mb_strtolower(trim($data['nl_email']));
        $double = Options::on('nl_double_optin');

        $sub = Subscriber::where('email', $email)->first();
        if ($sub && $sub->isActive()) {
            return back()->with('newsletter', __('Вече сте абонирани.'));
        }
        $sub ??= new Subscriber(['email' => $email, 'token' => bin2hex(random_bytes(20))]);
        $sub->fill([
            'name' => isset($data['nl_name']) ? mb_substr(trim(strip_tags($data['nl_name'])), 0, 100) : $sub->name,
            'locale' => app()->getLocale(),
            'ip' => $request->ip(),
            'unsubscribed_at' => null,
            'confirmed_at' => $double ? null : now(),
        ])->save();

        if ($double) {
            $this->mail($sub->email, __('Потвърдете абонамента си'), __('Потвърдете абонамента за :site с тази връзка:', ['site' => setting('site_name')])."\n\n".lroute('newsletter.confirm', ['token' => $sub->token]));

            return back()->with('newsletter', __('Изпратихме ви имейл — потвърдете абонамента с връзката в него.'));
        }
        $this->notifyAdmin($sub);

        return back()->with('newsletter', __('Абонирани сте. Благодарим!'));
    }

    public function confirm(string $token)
    {
        $this->ready();
        $sub = Subscriber::where('token', $token)->firstOrFail();
        if (! $sub->confirmed_at) {
            $sub->update(['confirmed_at' => now(), 'unsubscribed_at' => null]);
            $this->notifyAdmin($sub);
        }

        return redirect(lroute('home'))->with('newsletter_flash', __('Абонаментът е потвърден. Благодарим!'));
    }

    public function unsubscribe(string $token)
    {
        abort_unless(Tables::has('subscribers'), 404);
        $sub = Subscriber::where('token', $token)->firstOrFail();
        $sub->update(['unsubscribed_at' => now()]);

        return redirect(lroute('home'))->with('newsletter_flash', __('Отписахте се успешно.'));
    }

    private function notifyAdmin(Subscriber $sub): void
    {
        $to = setting('contact_email');
        if (Options::on('nl_notify_admin') && $to) {
            $this->mail($to, __('Нов абонат за бюлетина'), $sub->email.($sub->name ? ' ('.$sub->name.')' : ''));
        }
    }

    private function mail(string $to, string $subject, string $body): void
    {
        try {
            Mail::raw($body, fn ($m) => $m->to($to)->subject(Str::limit($subject, 150, '')));
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
