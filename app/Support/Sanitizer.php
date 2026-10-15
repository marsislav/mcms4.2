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
    ];

    private const DROP = [
        'script', 'style', 'object', 'embed', 'applet', 'form', 'input', 'button', 'textarea',
        'select', 'option', 'link', 'meta', 'base', 'noscript', 'noembed', 'noframes', 'template',
        'svg', 'math', 'frame', 'frameset', 'title', 'head', 'xmp', 'plaintext', 'listing',
        'audio', 'video', 'source', 'track', 'canvas', 'dialog', 'portal',
    ];

    private const ATTRS = [
        '*' => ['class'],
        'a' => ['href', 'title', 'target'],
        'img' => ['src', 'alt', 'title', 'width', 'height', 'loading'],
        'td' => ['colspan', 'rowspan'],
        'th' => ['colspan', 'rowspan', 'scope'],
        'ol' => ['start'],
        'iframe' => ['src', 'width', 'height', 'title', 'allowfullscreen', 'loading'],
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

            $ok = match ($name) {
                'href' => $this->safeUrl($value, ['http', 'https', 'mailto', 'tel']),
                'src' => $tag === 'iframe' ? $this->safeEmbed($value) : $this->safeUrl($value, ['http', 'https'], false),
                'class' => (bool) preg_match('/^[A-Za-z0-9_\- ]{1,100}$/', $value),
                'width', 'height', 'colspan', 'rowspan', 'start' => (bool) preg_match('/^\d{1,4}$/', $value),
                'target' => $value === '_blank',
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
