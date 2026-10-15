<?php
/**
 * Framework-free self-test for the security-critical helpers.
 * Run:  php tests/standalone.php
 */
require __DIR__.'/../app/Support/Sanitizer.php';
require __DIR__.'/../app/Support/Totp.php';
require __DIR__.'/../app/Support/EnvWriter.php';

use App\Support\{Sanitizer, Totp, EnvWriter};

$fail = 0;
$check = function (string $name, bool $ok) use (&$fail) {
    echo ($ok ? '  ok   ' : '  FAIL ').$name."\n";
    $fail += $ok ? 0 : 1;
};
$hosts = ['www.youtube-nocookie.com'];
$clean = fn (string $h) => Sanitizer::clean($h, $hosts);

echo "Sanitizer\n";
$check('keeps basic formatting + cyrillic', $clean('<p>Здравей <b>свят</b></p>') === '<p>Здравей <b>свят</b></p>');
$check('strips <script>', ! str_contains($clean('<p>a</p><script>alert(1)</script>'), 'script'));
$check('strips on* handlers', ! str_contains($clean('<p onclick="x()">a</p>'), 'onclick'));
$check('blocks javascript: href', ! str_contains($clean('<a href="javascript:alert(1)">x</a>'), 'javascript'));
$check('blocks obfuscated javascript: href', ! str_contains($clean("<a href=\"java\tscript:alert(1)\">x</a>"), 'script:'));
$check('blocks data: href', ! str_contains($clean('<a href="data:text/html;base64,AAA">x</a>'), 'data:'));
$check('blocks img onerror', ! str_contains($clean('<img src="x" onerror="alert(1)">'), 'onerror'));
$check('drops relative img src without slash', $clean('<img src="x.png">') === '');
$check('keeps local img', str_contains($clean('<img src="/uploads/a.jpg" alt="a">'), '/uploads/a.jpg'));
$check('target=_blank gets rel=noopener', str_contains($clean('<a href="https://x.bg" target="_blank">x</a>'), 'noopener'));
$check('blocks foreign iframe', ! str_contains($clean('<iframe src="https://evil.com/embed/1"></iframe>'), 'iframe'));
$check('allows youtube-nocookie embed', str_contains($clean('<iframe src="https://www.youtube-nocookie.com/embed/abc"></iframe>'), 'iframe'));
$check('drops svg/script payload', ! preg_match('/svg|alert/i', $clean('<svg><script>alert(1)</script></svg>')));
$check('drops noscript mXSS vector', ! str_contains($clean('<noscript><p title="</noscript><img src=x onerror=alert(1)>">'), 'onerror'));
$check('drops unknown style property', ! str_contains($clean('<p style="position:fixed;top:0">a</p>'), 'style'));
$check('keeps allowed style', $clean('<span style="color:#ff0000;font-size:18px">a</span>') === '<span style="color:#ff0000;font-size:18px">a</span>');
$check('keeps hsl colour + text-align', str_contains($clean('<p style="text-align:center;color:hsl(0,75%,60%)">a</p>'), 'text-align:center;color:hsl(0,75%,60%)'));
$check('blocks url() in style', ! str_contains($clean('<p style="background-color:url(http://x/y)">a</p>'), 'style'));
$check('blocks expression() in style', ! str_contains($clean('<p style="width:expression(alert(1))">a</p>'), 'style'));
$check('blocks style comment/escape tricks', ! str_contains($clean('<p style="color:r\\65d">a</p>'), 'style') && ! str_contains($clean('<p style="color:red/**/;position:fixed">a</p>'), 'style'));
$check('blocks javascript in style value', ! str_contains($clean('<p style="color:javascript:x">a</p>'), 'javascript'));
$check('keeps table cell style', str_contains($clean('<table><tr><td style="border:1px solid #ccc;padding:4px;width:30%">a</td></tr></table>'), 'border:1px solid #ccc;padding:4px;width:30%'));
$check('oembed youtube kept', str_contains($clean('<figure class="media"><oembed url="https://www.youtube.com/watch?v=dQw4w9WgXcQ"></oembed></figure>'), '<oembed url='));
$check('oembed foreign host dropped', ! str_contains($clean('<oembed url="https://evil.com/watch?v=abcdefgh"></oembed>'), 'oembed'));
$check('oembed renders nocookie iframe', str_contains(Sanitizer::renderEmbeds('<oembed url="https://youtu.be/dQw4w9WgXcQ"></oembed>'), 'youtube-nocookie.com/embed/dQw4w9WgXcQ'));
$check('oembed vimeo renders player', str_contains(Sanitizer::renderEmbeds('<oembed url="https://vimeo.com/123456789"></oembed>'), 'player.vimeo.com/video/123456789'));
$check('grid classes kept', str_contains($clean('<div class="mc-row mc-cols-3"><div class="mc-col mc-span-2"><p>a</p></div></div>'), '<div class="mc-row mc-cols-3"><div class="mc-col mc-span-2"><p>a</p></div></div>'));
$check('mark/colgroup allowed', str_contains($clean('<p><mark class="marker-yellow">a</mark></p>'), '<mark class="marker-yellow">'));
$check('drops <style> element', ! str_contains($clean('<style>*{}</style><p>a</p>'), 'style'));
$check('drops <form>', ! str_contains($clean('<form action="/x"><input></form>'), 'form'));
$check('unwraps unknown tags', $clean('<custom>inner</custom>') === 'inner');
$check('removes comments', ! str_contains($clean('<p>a</p><!-- secret -->'), 'secret'));

echo "TOTP (RFC 6238 vectors)\n";
$secret = Totp::base32Encode('12345678901234567890');
$check('t=59 -> 94287082', Totp::code($secret, intdiv(59, 30), 8) === '94287082');
$check('t=1111111109 -> 07081804', Totp::code($secret, intdiv(1111111109, 30), 8) === '07081804');
$check('verify current code', Totp::verify($secret, Totp::code($secret)) !== null);
$check('reject wrong code', Totp::verify($secret, '000000', 0, 1700000000) === null || Totp::code($secret, intdiv(1700000000, 30)) === '000000');
$check('reject non-numeric', Totp::verify($secret, 'abcdef') === null);
$r = random_bytes(20);
$check('base32 round trip', Totp::base32Decode(Totp::base32Encode($r)) === $r);

echo "EnvWriter\n";
$f = sys_get_temp_dir().'/mcms-env-'.bin2hex(random_bytes(4));
file_put_contents($f, "A=1\nDB_PASSWORD=\nKEEP=yes\n");
EnvWriter::set($f, ['DB_PASSWORD' => "p\$a'ss\\w\"rd", 'NEW' => 'x y', 'A' => null]);
$env = file_get_contents($f);
$check('escapes special chars in single quotes', str_contains($env, "DB_PASSWORD='p\$a\\'ss\\\\w\"rd'"));
$check('keeps untouched keys', str_contains($env, 'KEEP=yes'));
$check('removes null keys', ! str_contains($env, 'A=1'));
try { EnvWriter::set($f, ['X' => "a\nb"]); $check('rejects newline injection', false); } catch (InvalidArgumentException) { $check('rejects newline injection', true); }
try { EnvWriter::set($f, ['bad key' => 'x']); $check('rejects invalid key', false); } catch (InvalidArgumentException) { $check('rejects invalid key', true); }
unlink($f);

echo $fail ? "\n$fail FAILED\n" : "\nAll tests passed\n";
exit($fail ? 1 : 0);
