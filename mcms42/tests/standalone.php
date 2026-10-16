<?php
/**
 * Framework-free self-test for the security-critical helpers.
 * Run:  php tests/standalone.php
 */
require __DIR__.'/../app/Support/Sanitizer.php';
require __DIR__.'/../app/Support/EnvWriter.php';
require __DIR__.'/../app/Support/Taxonomy.php';
require __DIR__.'/../app/Support/Design.php';

use App\Support\{Sanitizer, EnvWriter, Taxonomy, Design};

// tiny stand-ins for the Laravel helpers Design uses
$GLOBALS['S'] = [];
function setting(string $k, $d = null) { return $GLOBALS['S'][$k] ?? $d; }
function setting_l(string $k, $d = null) { return $GLOBALS['S'][$k.'.bg'] ?? $d; }

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
$check('font awesome span kept', $clean('<p><span class="fa-solid fa-house" aria-hidden="true"></span></p>') === '<p><span class="fa-solid fa-house" aria-hidden="true"></span></p>');
$check('aria-hidden only "true"', ! str_contains($clean('<span aria-hidden="x">a</span>'), 'aria-hidden'));
$check('mark/colgroup allowed', str_contains($clean('<p><mark class="marker-yellow">a</mark></p>'), '<mark class="marker-yellow">'));
$check('drops <style> element', ! str_contains($clean('<style>*{}</style><p>a</p>'), 'style'));
$check('drops <form>', ! str_contains($clean('<form action="/x"><input></form>'), 'form'));
$check('unwraps unknown tags', $clean('<custom>inner</custom>') === 'inner');
$check('removes comments', ! str_contains($clean('<p>a</p><!-- secret -->'), 'secret'));

echo "Taxonomy\n";
$check('tags: split, trim, dedupe (case-insens.)', Taxonomy::parse(' PHP,  Laravel ; php,,') === ['PHP', 'Laravel']);
$check('tags: strips HTML', Taxonomy::parse('<b>Нов</b> таг') === ['Нов таг'] && ! str_contains(Taxonomy::parse('<script>a</script>')[0] ?? '', '<'));
$check('tags: max 20', count(Taxonomy::parse(implode(',', range(1, 50)))) === 20);
$check('tags: name capped at 80', mb_strlen(Taxonomy::parse(str_repeat('я', 200))[0]) === 80);

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

echo "Design\n";
$css = fn (array $s) => (function () use ($s) { $GLOBALS['S'] = $s; return Design::css(); })();
$check('defaults add no colour rules', ! str_contains($css([]), '--bg:'));
$o = $css(['design.bg' => '#ffffff']);
$check('bg only -> dark text picked', str_contains($o, '--bg:#ffffff;--fg:#231f1a') && str_contains($o, 'color-scheme:light'));
$o = $css(['design.bg' => '#101010']);
$check('dark bg -> light text', str_contains($o, '--fg:#f0ebe3') && str_contains($o, 'color-scheme:dark'));
$o = $css(['design.fg' => '#f0f0f0']);
$check('light text only -> dark bg', str_contains($o, '--bg:#171513'));
$check('3-digit hex normalised', str_contains($css(['design.accent' => '#f00']), '--accent:#ff0000'));
$check('accent text contrast (red -> white)', str_contains($css(['design.accent' => '#b4472b']), '--accent-fg:#ffffff'));
$check('accent text contrast (yellow -> dark)', str_contains($css(['design.accent' => '#ffee00']), '--accent-fg:#111111'));
$bad = $css(['design.bg' => 'red;}body{display:none', 'design.heading' => 'url(x)', 'design.font_body' => 'x;}*{}']);
$check('invalid colours / fonts ignored (no CSS injection)', ! str_contains($bad, 'display:none') && ! str_contains($bad, 'url(') && ! str_contains($bad, '*{}'));
$check('font stack applied', str_contains($css(['design.font_body' => 'mono']), '--sans:ui-monospace'));
$check('logo size px', str_contains($css(['design.logo_size' => '80']), '.logo img{max-height:none;height:80px'));
$check('logo auto -> nothing', ! str_contains($css(['design.logo_size' => 'auto']), '.logo img'));
$check('logo out of range ignored', ! str_contains($css(['design.logo_size' => '9999']), '.logo img'));
$check('sticky off', str_contains($css(['design.sticky_header' => '0']), '.site-header{position:static}'));
$check('sticky on by default', ! str_contains($css([]), 'position:static'));
$check('header bg scoped text colour', str_contains($css(['design.header_bg' => '#111111']), '.site-header{background:#111111;color:#f0ebe3'));
$check('topbar hidden on mobile when off', str_contains($css(['design.topbar_mobile' => '0']), '.topbar{display:none}'));
$GLOBALS['S'] = ['design.anim_page_type' => 'zoom'];
$check('page anim on by default', Design::pageAnim() === 'zoom');
$GLOBALS['S'] = ['design.anim_page_type' => 'zoom', 'design.anim_page_on' => '0'];
$check('page anim can be stopped', Design::pageAnim() === 'none');
$GLOBALS['S'] = ['design.anim_page_type' => 'evil'];
$check('unknown anim type -> none', Design::pageAnim() === 'none');
$GLOBALS['S'] = ['design.el_cards' => 'fade', 'design.el_logo' => 'zoom', 'design.el_menu' => 'bad'];
$check('element anims: only valid active', Design::elementAnims() === ['logo' => 'zoom', 'cards' => 'fade'] || Design::elementAnims() === ['cards' => 'fade', 'logo' => 'zoom']);
$GLOBALS['S'] = ['design.el_cards' => 'fade', 'design.anim_el_on' => '0'];
$check('element anims master switch', Design::elementAnims() === []);
$GLOBALS['S'] = ['design.topbar_on' => '1', 'design.topbar_cols' => '3', 'design.topbar_c1.bg' => 'Здравей'];
$tb = Design::topbar();
$check('topbar: 3 columns, first has text', count($tb) === 3 && $tb[0]['html'] === 'Здравей' && $tb[0]['align'] === 'left');
$GLOBALS['S'] = ['design.topbar_on' => '1', 'design.topbar_cols' => '2'];
$check('topbar: all empty -> not rendered', Design::topbar() === []);
$GLOBALS['S'] = ['design.topbar_cols' => '2', 'design.topbar_c1.bg' => 'x'];
$check('topbar off -> not rendered', Design::topbar() === []);
$check('fa icon allowed in topbar html', str_contains($clean('<span class="fa-solid fa-phone" aria-hidden="true"></span> <a href="tel:+359888000000">0888</a>'), 'fa-phone'));

echo $fail ? "\n$fail FAILED\n" : "\nAll tests passed\n";
exit($fail ? 1 : 0);
