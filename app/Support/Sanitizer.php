<?php

namespace App\Support;

use DOMDocument;
use DOMElement;
use DOMNode;

/**
 * Allow-list HTML sanitizer (no external packages).
 * Unknown tags are unwrapped, dangerous tags are removed with their content,
 * every attribute is validated individually, URLs are scheme-checked.
 */
class Sanitizer
{
    private const TAGS = [
        'p', 'br', 'strong', 'b', 'em', 'i', 'u', 's', 'sub', 'sup', 'ul', 'ol', 'li', 'a',
        'h2', 'h3', 'h4', 'h5', 'blockquote', 'pre', 'code', 'img', 'figure', 'figcaption',
        'table', 'thead', 'tbody', 'tfoot', 'tr', 'th', 'td', 'hr', 'div', 'span', 'iframe',
        'mark', 'caption', 'colgroup', 'col', 'oembed',
    ];

    private const DROP = [
        'script', 'style', 'object', 'embed', 'applet', 'form', 'input', 'button', 'textarea',
        'select', 'option', 'link', 'meta', 'base', 'noscript', 'noembed', 'noframes', 'template',
        'svg', 'math', 'frame', 'frameset', 'title', 'head', 'xmp', 'plaintext', 'listing',
        'audio', 'video', 'source', 'track', 'canvas', 'dialog', 'portal',
    ];

    private const ATTRS = [
        '*' => ['class', 'style'],
        'a' => ['href', 'title', 'target'],
        'img' => ['src', 'alt', 'title', 'width', 'height', 'loading'],
        'td' => ['colspan', 'rowspan'],
        'th' => ['colspan', 'rowspan', 'scope'],
        'ol' => ['start'],
        'iframe' => ['src', 'width', 'height', 'title', 'allowfullscreen', 'loading'],
        'oembed' => ['url'],
    ];

    /** CSS properties the rich-text editor may emit (value rules in cleanStyle()). */
    private const STYLE_PROPS = [
        'color', 'background-color', 'font-size', 'font-family', 'text-align', 'vertical-align',
        'width', 'height', 'min-width', 'float', 'list-style-type', 'page-break-after',
        'margin-left', 'margin-right', 'padding', 'padding-top', 'padding-right', 'padding-bottom', 'padding-left',
        'border', 'border-top', 'border-right', 'border-bottom', 'border-left',
        'border-color', 'border-style', 'border-width',
    ];

    private array $embedHosts;

    public function __construct(?array $embedHosts = null)
    {
        $this->embedHosts = $embedHosts ?? (function_exists('config') ? (array) config('mcms.embed_hosts', []) : []);
    }

    public static function clean(?string $html, ?array $embedHosts = null): string
    {
        return (new self($embedHosts))->run((string) $html);
    }

    public function run(string $html): string
    {
        $html = trim($html);
        if ($html === '') {
            return '';
        }

        $prev = libxml_use_internal_errors(true);
        $doc = new DOMDocument('1.0', 'UTF-8');
        $doc->loadHTML(
            '<?xml encoding="UTF-8"><div>'.$html.'</div>',
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_NONET
        );
        libxml_clear_errors();
        libxml_use_internal_errors($prev);

        $root = $doc->getElementsByTagName('div')->item(0);
        if (! $root) {
            return '';
        }

        $this->walk($root);

        $out = '';
        foreach ($root->childNodes as $child) {
            $out .= $doc->saveHTML($child);
        }

        return trim($out);
    }

    private function walk(DOMNode $node): void
    {
        foreach (iterator_to_array($node->childNodes) as $child) {
            if ($child instanceof DOMElement) {
                $tag = strtolower($child->tagName);

                if (in_array($tag, self::DROP, true)) {
                    $node->removeChild($child);
                    continue;
                }

                if (! in_array($tag, self::TAGS, true)) {
                    $this->walk($child);
                    while ($child->firstChild) {
                        $node->insertBefore($child->firstChild, $child);
                    }
                    $node->removeChild($child);
                    continue;
                }

                $this->cleanAttributes($child, $tag);

                if ($tag === 'oembed' && ! $child->hasAttribute('url')) {
                    $node->removeChild($child);
                    continue;
                }

                if ($tag === 'img' && ! $child->hasAttribute('src')) {
                    $node->removeChild($child);
                    continue;
                }

                if ($tag === 'iframe') {
                    if (! $child->hasAttribute('src')) {
                        $node->removeChild($child);
                        continue;
                    }
                    while ($child->firstChild) {
                        $child->removeChild($child->firstChild);
                    }
                    continue;
                }

                $this->walk($child);
            } elseif ($child->nodeType !== XML_TEXT_NODE) {
                // comments, processing instructions, CDATA ...
                $node->removeChild($child);
            }
        }
    }

    private function cleanAttributes(DOMElement $el, string $tag): void
    {
        $allowed = array_merge(self::ATTRS['*'], self::ATTRS[$tag] ?? []);

        foreach (iterator_to_array($el->attributes) as $attr) {
            $name = strtolower($attr->name);
            $value = $attr->value;

            if (! in_array($name, $allowed, true)) {
                $el->removeAttribute($attr->name);
                continue;
            }

            if ($name === 'style') {
                $css = $this->cleanStyle($value);
                $css === '' ? $el->removeAttribute('style') : $el->setAttribute('style', $css);
                continue;
            }

            $ok = match ($name) {
                'href' => $this->safeUrl($value, ['http', 'https', 'mailto', 'tel']),
                'src' => $tag === 'iframe' ? $this->safeEmbed($value) : $this->safeUrl($value, ['http', 'https'], false),
                'class' => (bool) preg_match('/^[A-Za-z0-9_\- ]{1,100}$/', $value),
                'width', 'height', 'colspan', 'rowspan', 'start' => (bool) preg_match('/^\d{1,4}$/', $value),
                'target' => $value === '_blank',
                'url' => $this->parseEmbed($value) !== null,
                'loading' => in_array($value, ['lazy', 'eager'], true),
                'scope' => in_array($value, ['row', 'col', 'rowgroup', 'colgroup'], true),
                'allowfullscreen' => true,
                default => true, // title, alt (escaped on output)
            };

            if (! $ok) {
                $el->removeAttribute($attr->name);
            }
        }

        if ($tag === 'a' && $el->getAttribute('target') === '_blank') {
            $el->setAttribute('rel', 'noopener noreferrer');
        }
        if ($tag === 'img' && ! $el->hasAttribute('loading')) {
            $el->setAttribute('loading', 'lazy');
        }
    }

    /** Keep only allow-listed properties with strictly validated values. */
    private function cleanStyle(string $css): string
    {
        if (strlen($css) > 600 || preg_match('/[\\\\\x00-\x1f<>@{}\/]/', $css)) {
            return '';
        }
        $out = [];
        foreach (explode(';', $css) as $decl) {
            if (! str_contains($decl, ':')) {
                continue;
            }
            [$prop, $val] = array_map('trim', explode(':', $decl, 2));
            $prop = strtolower($prop);
            $val = preg_replace('/\s+/', ' ', strtolower($val)) ?? '';
            if (! in_array($prop, self::STYLE_PROPS, true) || $val === '' || ! $this->styleValueOk($prop, $val)) {
                continue;
            }
            $out[] = $prop.':'.$val;
        }

        return implode(';', $out);
    }

    private function styleValueOk(string $prop, string $v): bool
    {
        if (preg_match('/url|expression|image|var\(|attr\(|env\(|calc|import|behavior|script|\\\\/i', $v)) {
            return false;
        }
        $len = '(?:auto|0|-?\d{1,4}(?:\.\d{1,3})?(?:px|%|em|rem|pt)?)';
        $color = '(?:#[0-9a-f]{3,8}|(?:rgb|rgba|hsl|hsla)\([0-9., %\/-]{1,40}\)|[a-z]{3,25})';

        return match (true) {
            $prop === 'text-align' => in_array($v, ['left', 'right', 'center', 'justify'], true),
            $prop === 'vertical-align' => in_array($v, ['top', 'middle', 'bottom', 'baseline'], true),
            $prop === 'float' => in_array($v, ['left', 'right', 'none'], true),
            $prop === 'page-break-after' => in_array($v, ['always', 'auto', 'avoid'], true),
            $prop === 'list-style-type' => (bool) preg_match('/^[a-z\-]{3,30}$/', $v),
            $prop === 'font-family' => (bool) preg_match('/^[a-z0-9 ,\'"\-]{1,120}$/', $v),
            $prop === 'font-size' => (bool) preg_match('/^(?:\d{1,3}(?:\.\d{1,2})?(?:px|em|rem|%|pt)|(?:xx-small|x-small|small|medium|large|x-large|xx-large))$/', $v),
            $prop === 'color', $prop === 'background-color' => (bool) preg_match('/^'.$color.'$/', $v),
            $prop === 'border-color' => (bool) preg_match('/^'.$color.'(?: '.$color.'){0,3}$/', $v),
            $prop === 'border-style' => (bool) preg_match('/^[a-z]{3,8}(?: [a-z]{3,8}){0,3}$/', $v),
            str_starts_with($prop, 'border') && $prop !== 'border-width' => (bool) preg_match('/^(?:'.$len.'|[a-z]{3,8}|'.$color.')(?: (?:'.$len.'|[a-z]{3,8}|'.$color.')){0,3}$/', $v),
            default => (bool) preg_match('/^'.$len.'(?: '.$len.'){0,3}$/', $v), // widths, heights, padding, margins
        };
    }

    /**
     * Validates a YouTube / Vimeo page URL and returns its embeddable URL (or null).
     * Used for <oembed url="..."> produced by the rich-text editor.
     */
    public function parseEmbed(string $url): ?string
    {
        $url = html_entity_decode($url, ENT_QUOTES);
        $p = parse_url($url);
        if (! $p || ($p['scheme'] ?? '') !== 'https' || empty($p['host'])) {
            return null;
        }
        $host = preg_replace('/^(www|m)\./', '', strtolower($p['host']));
        $path = $p['path'] ?? '';
        parse_str($p['query'] ?? '', $q);

        if ($host === 'youtu.be' && preg_match('~^/([A-Za-z0-9_-]{6,20})$~', $path, $m)) {
            $id = $m[1];
        } elseif (in_array($host, ['youtube.com', 'youtube-nocookie.com'], true)) {
            if ($path === '/watch' && isset($q['v']) && is_string($q['v']) && preg_match('~^[A-Za-z0-9_-]{6,20}$~', $q['v'])) {
                $id = $q['v'];
            } elseif (preg_match('~^/(?:embed|shorts)/([A-Za-z0-9_-]{6,20})$~', $path, $m)) {
                $id = $m[1];
            } else {
                return null;
            }
        } elseif ($host === 'vimeo.com' && preg_match('~^/(\d{4,12})$~', $path, $m)) {
            return 'https://player.vimeo.com/video/'.$m[1];
        } else {
            return null;
        }

        return 'https://www.youtube-nocookie.com/embed/'.$id;
    }

    /** Turns stored <oembed> placeholders into responsive iframes (call when rendering). */
    public static function renderEmbeds(string $html): string
    {
        if (! str_contains($html, '<oembed')) {
            return $html;
        }
        $self = new self([]);

        return preg_replace_callback('~<oembed url="([^"]*)"></oembed>~', function ($m) use ($self) {
            $src = $self->parseEmbed($m[1]);

            return $src === null ? '' : '<div class="embed-responsive"><iframe src="'.htmlspecialchars($src, ENT_QUOTES).'" allowfullscreen loading="lazy" title="Video"></iframe></div>';
        }, $html) ?? $html;
    }

    private function safeUrl(string $url, array $schemes, bool $allowFragmentsAndQuery = true): bool
    {
        $clean = preg_replace('/[\x00-\x20\x7f]+/', '', $url) ?? '';
        if ($clean === '' || str_starts_with($clean, '//')) {
            return false;
        }
        if (preg_match('/^([a-z][a-z0-9+.\-]*):/i', $clean, $m)) {
            return in_array(strtolower($m[1]), $schemes, true);
        }
        if (! $allowFragmentsAndQuery && ! str_starts_with($clean, '/')) {
            return false; // img src must be absolute-path or http(s)
        }

        return true;
    }

    private function safeEmbed(string $url): bool
    {
        $p = parse_url($url);
        if (! $p || ($p['scheme'] ?? '') !== 'https' || empty($p['host'])) {
            return false;
        }
        $path = $p['path'] ?? '';

        return in_array(strtolower($p['host']), $this->embedHosts, true)
            && (str_starts_with($path, '/embed/') || str_starts_with($path, '/video/'));
    }
}
