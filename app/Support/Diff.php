<?php

namespace App\Support;

/**
 * Text diff for the revision comparison screen. Pure PHP (no framework), HTML is converted to readable text lines
 * first, then compared with a longest-common-subsequence on lines; changed lines get a word-level diff.
 */
class Diff
{
    private const MAX_LINES = 1500;

    /** HTML => plain text, one block per line (headings, paragraphs, list items, table rows, breaks). */
    public static function toLines(string $html): array
    {
        $t = preg_replace('~<\s*br\s*/?>|</\s*(p|div|h[1-6]|li|tr|blockquote|pre|figure|ul|ol|table)\s*>~i', "\n", $html) ?? $html;
        $t = html_entity_decode(strip_tags($t), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $lines = [];
        foreach (preg_split('/\R/u', $t) ?: [] as $l) {
            $l = trim(preg_replace('/\s+/u', ' ', $l));
            if ($l !== '') {
                $lines[] = $l;
            }
        }

        return $lines;
    }

    /** @return array<int,array{t:string,a:string,b:string}> t = eq|add|del|chg; a = old line, b = new line */
    public static function lines(array $a, array $b): array
    {
        $a = array_slice($a, 0, self::MAX_LINES);
        $b = array_slice($b, 0, self::MAX_LINES);
        $n = count($a);
        $m = count($b);
        // LCS table (rows as arrays of ints)
        $L = array_fill(0, $n + 1, array_fill(0, $m + 1, 0));
        for ($i = $n - 1; $i >= 0; $i--) {
            for ($j = $m - 1; $j >= 0; $j--) {
                $L[$i][$j] = $a[$i] === $b[$j] ? $L[$i + 1][$j + 1] + 1 : max($L[$i + 1][$j], $L[$i][$j + 1]);
            }
        }
        $ops = [];
        $i = $j = 0;
        while ($i < $n && $j < $m) {
            if ($a[$i] === $b[$j]) {
                $ops[] = ['t' => 'eq', 'a' => $a[$i], 'b' => $b[$j]];
                $i++;
                $j++;
            } elseif ($L[$i + 1][$j] >= $L[$i][$j + 1]) {
                $ops[] = ['t' => 'del', 'a' => $a[$i++], 'b' => ''];
            } else {
                $ops[] = ['t' => 'add', 'a' => '', 'b' => $b[$j++]];
            }
        }
        while ($i < $n) {
            $ops[] = ['t' => 'del', 'a' => $a[$i++], 'b' => ''];
        }
        while ($j < $m) {
            $ops[] = ['t' => 'add', 'a' => '', 'b' => $b[$j++]];
        }

        return self::pairChanges($ops);
    }

    /** A run of deletions directly followed by additions becomes "changed" lines (shown side by side with word marks). */
    private static function pairChanges(array $ops): array
    {
        $out = [];
        $k = 0;
        $c = count($ops);
        while ($k < $c) {
            if ($ops[$k]['t'] !== 'del') {
                $out[] = $ops[$k++];
                continue;
            }
            $dels = [];
            while ($k < $c && $ops[$k]['t'] === 'del') {
                $dels[] = $ops[$k++];
            }
            $adds = [];
            while ($k < $c && $ops[$k]['t'] === 'add') {
                $adds[] = $ops[$k++];
            }
            $pairs = min(count($dels), count($adds));
            for ($x = 0; $x < $pairs; $x++) {
                $out[] = ['t' => 'chg', 'a' => $dels[$x]['a'], 'b' => $adds[$x]['b']];
            }
            foreach (array_slice($dels, $pairs) as $d) {
                $out[] = $d;
            }
            foreach (array_slice($adds, $pairs) as $d) {
                $out[] = $d;
            }
        }

        return $out;
    }

    /** Word-level diff of two lines as escaped HTML: [old html, new html] with <del>/<ins> around changed words. */
    public static function words(string $old, string $new): array
    {
        $a = preg_split('/(\s+)/u', $old, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY) ?: [];
        $b = preg_split('/(\s+)/u', $new, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY) ?: [];
        if (count($a) > 400 || count($b) > 400) {
            return [self::e($old), self::e($new)];
        }
        $n = count($a);
        $m = count($b);
        $L = array_fill(0, $n + 1, array_fill(0, $m + 1, 0));
        for ($i = $n - 1; $i >= 0; $i--) {
            for ($j = $m - 1; $j >= 0; $j--) {
                $L[$i][$j] = $a[$i] === $b[$j] ? $L[$i + 1][$j + 1] + 1 : max($L[$i + 1][$j], $L[$i][$j + 1]);
            }
        }
        $ho = $hn = '';
        $i = $j = 0;
        while ($i < $n && $j < $m) {
            if ($a[$i] === $b[$j]) {
                $ho .= self::e($a[$i]);
                $hn .= self::e($b[$j]);
                $i++;
                $j++;
            } elseif ($L[$i + 1][$j] >= $L[$i][$j + 1]) {
                $ho .= self::mark('del', $a[$i++]);
            } else {
                $hn .= self::mark('ins', $b[$j++]);
            }
        }
        while ($i < $n) {
            $ho .= self::mark('del', $a[$i++]);
        }
        while ($j < $m) {
            $hn .= self::mark('ins', $b[$j++]);
        }

        return [$ho, $hn];
    }

    private static function mark(string $tag, string $w): string
    {
        return trim($w) === '' ? self::e($w) : "<$tag>".self::e($w)."</$tag>";
    }

    private static function e(string $s): string
    {
        return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    /** Counts for the summary line. */
    public static function stats(array $ops): array
    {
        $s = ['add' => 0, 'del' => 0, 'chg' => 0];
        foreach ($ops as $o) {
            if (isset($s[$o['t']])) {
                $s[$o['t']]++;
            }
        }

        return $s;
    }
}
