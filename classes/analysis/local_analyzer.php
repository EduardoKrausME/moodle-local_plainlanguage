<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Deterministic plain-language heuristics.
 *
 * @package   local_plainlanguage
 * @copyright 2026 Eduardo Kraus
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_plainlanguage\analysis;

use DOMDocument;
use DOMXPath;

/**
 * Analyze what can be measured locally without AI.
 */
class local_analyzer {
    /** Long sentence threshold in words. */
    private const LONG_SENTENCE_WORDS = 30;

    /** Large paragraph threshold in words. */
    private const LARGE_PARAGRAPH_WORDS = 120;

    /**
     * Analyze HTML/text.
     *
     * The metrics are intentionally presented as heuristics. They are not claimed to be
     * language-independent readability truth.
     *
     * @param string $html Teacher-authored content.
     * @return array{metrics:array<string,int|float>,findings:array<int,array<string,mixed>>,plaintext:string}
     */
    public function analyze(string $html): array {
        $plaintext = self::to_plain_text($html);
        $words = $this->words($plaintext);
        $sentences = $this->sentences($plaintext);
        $paragraphs = $this->paragraphs($html, $plaintext);

        $sentencewordcounts = [];
        $findings = [];
        foreach ($sentences as $sentence) {
            $count = count($this->words($sentence));
            $sentencewordcounts[] = $count;
            if ($count > self::LONG_SENTENCE_WORDS && count($findings) < 10) {
                $findings[] = [
                    'code' => 'long_sentence',
                    'excerpt' => $this->limit($sentence, 500),
                    'value' => $count,
                ];
            }
        }

        $longparagraphs = 0;
        foreach ($paragraphs as $paragraph) {
            $count = count($this->words($paragraph));
            if ($count > self::LARGE_PARAGRAPH_WORDS) {
                $longparagraphs++;
                if (count($findings) < 20) {
                    $findings[] = [
                        'code' => 'large_paragraph',
                        'excerpt' => $this->limit($paragraph, 500),
                        'value' => $count,
                    ];
                }
            }
        }

        $uppercasewords = 0;
        foreach ($words as $word) {
            if (mb_strlen($word) < 3) {
                continue;
            }
            $upper = mb_strtoupper($word);
            $lower = mb_strtolower($word);
            if ($word === $upper && $upper !== $lower) {
                $uppercasewords++;
            }
        }
        $uppercaseratio = count($words) > 0 ? $uppercasewords / count($words) : 0.0;
        if ($uppercasewords >= 5 && $uppercaseratio >= 0.20) {
            $findings[] = [
                'code' => 'uppercase',
                'excerpt' => '',
                'value' => round($uppercaseratio * 100, 1),
            ];
        }

        $structure = $this->analyze_structure($html);
        foreach ($structure['findings'] as $finding) {
            $findings[] = $finding;
        }

        if (count($words) > 350 && $structure['headingcount'] === 0) {
            $findings[] = [
                'code' => 'no_headings',
                'excerpt' => '',
                'value' => count($words),
            ];
        }

        $instructioncount = 0;
        $instructionpattern = '/\b(' .
            'deve|deverá|deverao|deverão|faça|faca|clique|acesse|envie|responda|selecione|preencha|leia|escreva|' .
            'complete|must|should|click|access|submit|answer|select|fill|read|write' .
            ')\b/iu';
        foreach ($sentences as $sentence) {
            if (preg_match($instructionpattern, $sentence)) {
                $instructioncount++;
            }
        }

        $maxsentence = $sentencewordcounts ? max($sentencewordcounts) : 0;
        $avgsentence = $sentencewordcounts ? array_sum($sentencewordcounts) / count($sentencewordcounts) : 0.0;

        return [
            'metrics' => [
                'wordcount' => count($words),
                'sentencecount' => count($sentences),
                'avgsentencewords' => round($avgsentence, 1),
                'maxsentencewords' => $maxsentence,
                'paragraphcount' => count($paragraphs),
                'longparagraphs' => $longparagraphs,
                'uppercaseratio' => round($uppercaseratio * 100, 1),
                'instructioncount' => $instructioncount,
                'linkcount' => $structure['linkcount'],
                'headingcount' => $structure['headingcount'],
            ],
            'findings' => $findings,
            'plaintext' => $plaintext,
        ];
    }

    /**
     * Convert HTML to readable text while retaining rough block boundaries.
     *
     * @param string $html HTML/text.
     * @return string
     */
    public static function to_plain_text(string $html): string {
        $withbreaks = preg_replace(
            '/<\s*(br\s*\/?>|\/p\s*>|\/div\s*>|\/li\s*>|\/h[1-6]\s*>)/iu',
            "$0\n",
            $html
        ) ?? $html;
        $text = html_entity_decode(strip_tags($withbreaks), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/[\t ]+/u', ' ', $text) ?? $text;
        $text = preg_replace('/\h*\R\h*/u', "\n", $text) ?? $text;
        $text = preg_replace('/\n{3,}/u', "\n\n", $text) ?? $text;
        return trim($text);
    }

    /**
     * Find links/headings with DOM parsing.
     *
     * @param string $html HTML.
     * @return array{linkcount:int,headingcount:int,findings:array<int,array<string,mixed>>}
     */
    private function analyze_structure(string $html): array {
        $dom = new DOMDocument('1.0', 'UTF-8');
        $previous = libxml_use_internal_errors(true);
        $loaded = $dom->loadHTML(
            '<?xml encoding="utf-8" ?><div id="plainlanguage-root">' . $html . '</div>',
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
        );
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        if (!$loaded) {
            return ['linkcount' => 0, 'headingcount' => 0, 'findings' => []];
        }

        $xpath = new DOMXPath($dom);
        $links = $xpath->query('//a');
        $headings = $xpath->query('//h1|//h2|//h3|//h4|//h5|//h6');
        $findings = [];
        $vague = [
            'aqui', 'clique aqui', 'saiba mais', 'mais', 'link', 'ver mais',
            'here', 'click here', 'read more', 'more', 'learn more',
        ];

        if ($links !== false) {
            foreach ($links as $link) {
                $text = trim(preg_replace('/\s+/u', ' ', $link->textContent) ?? '');
                if (in_array(mb_strtolower($text), $vague, true)) {
                    $findings[] = [
                        'code' => 'vague_link',
                        'excerpt' => $text,
                        'value' => $text,
                    ];
                }
            }
        }

        if ($headings !== false) {
            foreach ($headings as $heading) {
                if (trim($heading->textContent) === '') {
                    $findings[] = [
                        'code' => 'empty_heading',
                        'excerpt' => '',
                        'value' => 0,
                    ];
                }
            }
        }

        return [
            'linkcount' => $links === false ? 0 : $links->length,
            'headingcount' => $headings === false ? 0 : $headings->length,
            'findings' => $findings,
        ];
    }

    /**
     * Extract words.
     *
     * @param string $text Text.
     * @return string[]
     */
    private function words(string $text): array {
        preg_match_all('/[\p{L}\p{N}][\p{L}\p{M}\p{N}\'’\-]*/u', $text, $matches);
        return $matches[0] ?? [];
    }

    /**
     * Split sentences conservatively.
     *
     * @param string $text Text.
     * @return string[]
     */
    private function sentences(string $text): array {
        $parts = preg_split('/(?<=[.!?])\\s+|\\R{2,}/u', trim($text), -1, PREG_SPLIT_NO_EMPTY);
        if (!$parts && trim($text) !== '') {
            return [trim($text)];
        }
        return array_values(array_filter(array_map('trim', $parts ?: []), static fn(string $part): bool => $part !== ''));
    }

    /**
     * Extract paragraph-like blocks.
     *
     * @param string $html HTML.
     * @param string $plaintext Plain text fallback.
     * @return string[]
     */
    private function paragraphs(string $html, string $plaintext): array {
        preg_match_all('/<p\b[^>]*>(.*?)<\/p>/isu', $html, $matches);
        $paragraphs = [];
        foreach ($matches[1] ?? [] as $paragraphhtml) {
            $paragraph = trim(html_entity_decode(strip_tags($paragraphhtml), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
            if ($paragraph !== '') {
                $paragraphs[] = preg_replace('/\s+/u', ' ', $paragraph) ?? $paragraph;
            }
        }
        if ($paragraphs) {
            return $paragraphs;
        }
        return array_values(array_filter(
            array_map('trim', preg_split('/\R{2,}/u', $plaintext) ?: []),
            static fn(string $paragraph): bool => $paragraph !== ''
        ));
    }

    /**
     * Limit an excerpt without breaking multibyte characters.
     *
     * @param string $text Text.
     * @param int $length Maximum characters.
     * @return string
     */
    private function limit(string $text, int $length): string {
        $text = trim(preg_replace('/\s+/u', ' ', $text) ?? $text);
        if (mb_strlen($text) <= $length) {
            return $text;
        }
        return mb_substr($text, 0, $length - 1) . '…';
    }
}
