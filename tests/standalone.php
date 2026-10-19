<?php
/**
 * Framework-free self-test for the security-critical helpers.
 * Run:  php tests/standalone.php
 */
require __DIR__.'/../app/Support/Sanitizer.php';
require __DIR__.'/../app/Support/EnvWriter.php';
require __DIR__.'/../app/Support/Taxonomy.php';
require __DIR__.'/../app/Support/Design.php';
require __DIR__.'/../app/Support/Discussion.php';
require __DIR__.'/../app/Support/Widgets.php';
require __DIR__.'/../app/Support/Options.php';
require __DIR__.'/../app/Support/Members.php';

use App\Support\{Sanitizer, EnvWriter, Taxonomy, Design, Discussion, Widgets, Options, Members};

// tiny stand-ins for the Laravel helpers Design uses
$GLOBALS['S'] = [];
function setting(string $k, $d = null) { return $GLOBALS['S'][$k] ?? $d; }
function setting_l(string $k, $d = null) { return $GLOBALS['S'][$k.'.bg'] ?? $d; }
if (!function_exists('__')) { function __($k, array $r = []) { foreach ($r as $a => $b) { $k = str_replace(':'.$a, (string) $b, $k); } return $k; } }

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

echo "Discussion (comments)\n";
$cfg = Discussion::DEFAULTS;
$cm = fn (string $body = 'Здравей!', array $o = []) => $o + ['author' => 'Иван', 'email' => 'i@example.com', 'url' => '', 'body' => $body, 'ip' => '203.0.113.5'];
$check('first-time commenter is held (previously_approved)', Discussion::decide($cm(), $cfg, false) === 'pending');
$check('returning commenter is approved', Discussion::decide($cm(), $cfg, true) === 'approved');
$check('logged-in team member always approved', Discussion::decide($cm('http://a.bg http://b.bg http://c.bg'), $cfg, false, true) === 'approved');
$check('2 links -> held', Discussion::decide($cm('виж http://a.bg и https://b.bg'), $cfg, true) === 'pending');
$check('1 link -> approved', Discussion::decide($cm('виж http://a.bg'), $cfg, true) === 'approved');
$check('max_links=0 disables the link rule', Discussion::decide($cm('http://a http://b http://c'), ['max_links' => '0'] + $cfg, true) === 'approved');
$check('moderation_all holds everything', Discussion::decide($cm(), ['moderation_all' => '1'] + $cfg, true) === 'pending');
$check('moderation keyword (case-insens., any field)', Discussion::decide($cm('купи ВИАГРА'), ['moderation_keys' => "casino\nвиагра"] + $cfg, true) === 'pending');
$check('disallowed keyword -> spam', Discussion::decide($cm('best casino'), ['disallowed_keys' => 'casino'] + $cfg, true) === 'spam');
$check('disallowed IP -> spam', Discussion::decide($cm(), ['disallowed_keys' => '203.0.113.'] + $cfg, true) === 'spam');
$check('blank lines in keyword list ignored', Discussion::decide($cm(), ['moderation_keys' => "\n\n  \n"] + $cfg, true) === 'approved');
$check('spam beats trusted? no - team is trusted first', Discussion::decide($cm('casino'), ['disallowed_keys' => 'casino'] + $cfg, true, true) === 'approved');
$check('countLinks', Discussion::countLinks('a http://x.bg b www.y.bg <a href="z">') === 3);
$f = Discussion::format("<script>alert(1)</script>\nвиж https://example.com/a?b=1&c=2.");
$check('format escapes HTML', ! str_contains($f, '<script') && str_contains($f, '&lt;script&gt;'));
$check('format keeps line breaks', str_contains($f, '<br>'));
$check('format links URL with nofollow ugc', str_contains($f, '<a href="https://example.com/a?b=1&amp;c=2" rel="nofollow ugc noopener noreferrer" target="_blank">'));
$check('format keeps trailing dot outside the link', str_contains($f, '</a>.'));
$check('format blocks javascript: (no link made)', ! str_contains(Discussion::format('javascript:alert(1)'), '<a '));
$check('format escapes quote in URL', ! str_contains(Discussion::format('http://a.bg/"onmouseover="x'), 'onmouseover="x"'));
$rows = [
  ['id' => 1, 'parent_id' => null], ['id' => 2, 'parent_id' => 1], ['id' => 3, 'parent_id' => 2],
  ['id' => 4, 'parent_id' => 3], ['id' => 5, 'parent_id' => null], ['id' => 6, 'parent_id' => 99],
];
$tr = Discussion::tree($rows, true, 3);
$flat = function (array $nodes) use (&$flat) { $o = []; foreach ($nodes as $n) { $o[] = $n['c']['id'].'@'.$n['depth']; $o = array_merge($o, $flat($n['children'])); } return $o; };
$check('tree: nesting + depth limit flattens #4 to level 3', $flat($tr) === ['1@1', '2@2', '3@3', '4@3', '5@1', '6@1']);
$check('tree: orphan (missing parent) shown at top level', in_array('6@1', $flat($tr), true));
$check('tree: threading off -> everything top level', $flat(Discussion::tree($rows, false, 3)) === ['1@1', '2@1', '3@1', '4@1', '5@1', '6@1']);
$check('depthOf', Discussion::depthOf(4, [2 => 1, 3 => 2, 4 => 3]) === 4);
$GLOBALS['S'] = ['discussion.show_avatars' => '0'];
$check('avatar off -> null (no external request)', Discussion::avatar('a@b.c') === null);
$GLOBALS['S'] = ['discussion.show_avatars' => '1'];
$check('avatar on -> gravatar md5', str_contains((string) Discussion::avatar(' A@B.c '), md5('a@b.c')));

echo "Widgets\n";
$L = ['bg', 'en'];
$w = Widgets::clean(['type' => 'html', 'title' => ['bg' => ' <b>Hi</b> '], 'icon' => 'fa-solid fa-phone', 'class' => 'a b"><x', 'body' => ['bg' => '<p onclick="x()">T <span class="fa-solid fa-x" aria-hidden="true"></span></p><script>alert(1)</script>']], $L);
$check('widget: html cleaned', $w && !str_contains($w['body']['bg'], 'script') && !str_contains($w['body']['bg'], 'onclick') && str_contains($w['body']['bg'], 'fa-solid fa-x'));
$check('widget: title stripped', $w['title']['bg'] === 'Hi');
$check('widget: icon ok', $w['icon'] === 'fa-solid fa-phone');
$check('widget: bad class dropped', empty($w['class']));
$check('widget: id 8 hex', (bool) preg_match('/^[a-f0-9]{8}$/', $w['id']));
$check('widget: unknown type -> null', Widgets::clean(['type' => 'evil'], $L) === null);
$check('widget: bad icon -> null', Widgets::clean(['type' => 'search', 'icon' => 'x" onload="y'], $L)['icon'] === null);
$check('widget: count clamped', Widgets::clean(['type' => 'recent_posts', 'count' => 999], $L)['count'] === 20);
$check('widget: tags style default', Widgets::clean(['type' => 'tags', 'style' => 'zzz'], $L)['style'] === 'cloud');
$check('widget: menu slug sanitized', Widgets::clean(['type' => 'menu', 'menu' => '../x'], $L)['menu'] === 'footer');
$check('widget: list limited', count(Widgets::cleanList(array_fill(0, 40, ['type' => 'search']), $L)) === Widgets::MAX_WIDGETS);
$check('widget: inactive locale ignored', !isset(Widgets::clean(['type' => 'html', 'body' => ['de' => 'x']], $L)['body']['de']));

echo "Options\n";
$GLOBALS['S'] = [];
$o = fn (string $g, array $in, array $ctx = []) => Options::clean($g, $in, $ctx);
$r = $o('reading', ['front_mode' => 'x', 'feed_count' => '999', 'noindex' => '1', 'excerpt_length' => '5']);
$check('reading: enum/clamp', $r['values']['front_mode'] === 'page' && $r['values']['feed_count'] === '50' && $r['values']['noindex'] === '1' && $r['values']['feed_full'] === '0' && $r['values']['excerpt_length'] === '50');
$r = $o('datetime', ['timezone' => 'Europe/Sofia', 'date_format' => 'evil', 'time_format' => 'g:i a']);
$check('datetime: ok tz, bad format -> default', $r['values']['timezone'] === 'Europe/Sofia' && $r['values']['date_format'] === 'j F Y' && $r['values']['time_format'] === 'g:i a' && !$r['errors']);
$check('datetime: bad tz rejected', $o('datetime', ['timezone' => 'Mars/Base'])['errors'] !== []);
$r = $o('permalinks', ['blog_base' => ' News ', 'category_base' => 'Topics', 'tag_base' => 'labels']);
$check('permalinks: normalised', $r['values'] === ['blog_base' => 'news', 'category_base' => 'topics', 'tag_base' => 'labels'] && !$r['errors']);
$check('permalinks: reserved/page/2-letter/bad chars rejected', count($o('permalinks', ['blog_base' => 'search'])['errors']) === 1 && count($o('permalinks', ['blog_base' => 'about'], ['taken' => ['about']])['errors']) === 1 && count($o('permalinks', ['blog_base' => 'en'])['errors']) === 1 && count($o('permalinks', ['blog_base' => 'a/b'])['errors']) === 1);
$r = $o('permalinks', ['blog_base' => 'search'], ['current' => ['blog_base' => 'news']]);
$check('permalinks: rejected keeps current', $r['values']['blog_base'] === 'news');
$check('permalinks: category = tag rejected', $o('permalinks', ['category_base' => 'x', 'tag_base' => 'x'])['errors'] !== []);
$check('legacy: /blog/foo -> /news/foo', Options::legacyPath('blog/foo', 'news', 'category', 'tag') === 'news/foo');
$check('legacy: locale + category', Options::legacyPath('en/blog/category/x', 'news', 'topics', 'tag') === 'en/news/topics/x');
$check('legacy: tag only', Options::legacyPath('blog/tag/x', 'blog', 'category', 'labels') === 'blog/labels/x');
$check('legacy: unchanged / unrelated -> null', Options::legacyPath('blog/foo', 'blog', 'category', 'tag') === null && Options::legacyPath('about', 'news', 'c', 't') === null);
$r = $o('media', ['upload_max_mb' => '500', 'image_max_px' => '', 'thumb_px' => '50']);
$check('media: clamp / empty = default', $r['values'] === ['upload_max_mb' => '64', 'image_max_px' => '', 'thumb_px' => '100']);
$r = $o('scripts', ['scripts_head' => '<meta name="x">', 'csp_hosts' => "https://www.googletagmanager.com/\nhttp://evil.com\nhttps://a.b';x\nHTTPS://Www.Google-Analytics.com"]);
$check('scripts: hosts validated, https only, normalised', $r['values']['csp_hosts'] === "https://www.googletagmanager.com\nhttps://www.google-analytics.com" && count($r['errors']) === 2);
$GLOBALS['S'] = ['opt.scripts_head' => '<script nonce="evil" src="a.js"></script><SCRIPT>x()</SCRIPT><p>hi</p>'];
$out = Options::scripts('head', 'N0NCE');
$check('scripts: nonce injected, foreign nonce replaced', substr_count($out, 'nonce="N0NCE"') === 2 && !str_contains($out, 'evil'));
$GLOBALS['S'] = ['opt.csp_hosts' => "https://ok.example.com\njavascript:alert(1)"];
$check('csp hosts at read time filtered', Options::cspHosts() === ['https://ok.example.com']);
$r = $o('maintenance', ['maintenance_message' => '<b>Скоро</b>', 'maintenance_ips' => "1.2.3.4\nnot-ip\n::1"]);
$check('maintenance: strip tags, validate IPs', $r['values']['maintenance_message'] === 'Скоро' && $r['values']['maintenance_ips'] === "1.2.3.4\n::1" && count($r['errors']) === 1);
$GLOBALS['S'] = ['opt.maintenance_ips' => "1.2.3.4"];
$check('maintenance: ip allowed', Options::ipAllowed('1.2.3.4') && !Options::ipAllowed('1.2.3.5'));
$r = $o('writing', ['default_category' => '7', 'default_status' => 'published'], ['categories' => [1, 2]]);
$check('writing: unknown category dropped', $r['values']['default_category'] === '' && $r['values']['default_status'] === 'published');
$GLOBALS['S'] = [];

echo "Members\n";
$none = fn ($u) => false;
$check('username: cyrillic transliterated', Members::usernameFrom('Иван Петров', $none) === 'ivan-petrov');
$check('username: unique suffix', Members::usernameFrom('Ivan', fn ($u) => in_array($u, ['ivan', 'ivan-2'], true)) === 'ivan-3');
$check('username: reserved/short/empty safe', Members::usernameFrom('panel', $none) === 'user-panel' && Members::usernameFrom('bg', $none) === 'user-bg' && Members::usernameFrom('!!!', $none) === 'user' && Members::usernameFrom(str_repeat('a', 80), $none) === str_repeat('a', 32));
$check('username: only a-z0-9-', (bool) preg_match('/^[a-z0-9\-]+$/', Members::usernameFrom("O'Brien <b>x</b> ñ", $none)));
$GLOBALS['S'] = ['opt.default_role' => 'admin'];
$check('default role can never be admin', Members::defaultRole() === 'subscriber');
$GLOBALS['S'] = ['opt.default_role' => 'author'];
$check('default role author', Members::defaultRole() === 'author');
$r = Options::clean('users', ['registration' => '1', 'default_role' => 'editor']);
$check('users options: invalid role -> subscriber', $r['values']['default_role'] === 'subscriber' && $r['values']['registration'] === '1' && $r['values']['reg_approval'] === '0');
$GLOBALS['S'] = [];

echo $fail ? "\n$fail FAILED\n" : "\nAll tests passed\n";
exit($fail ? 1 : 0);
