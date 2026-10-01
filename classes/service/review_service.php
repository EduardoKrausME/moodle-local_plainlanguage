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
 * Plain-language review orchestration.
 *
 * @package   local_plainlanguage
 * @copyright 2026 Eduardo Kraus
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_plainlanguage\service;

use context;
use local_plainlanguage\ai\client;
use local_plainlanguage\analysis\local_analyzer;
use local_plainlanguage\content\content_item;
use local_plainlanguage\exception\ai_exception;

/**
 * Combine deterministic checks with semantic AI review.
 */
class review_service {
    /** @var local_analyzer */
    private local_analyzer $analyzer;

    /** @var client */
    private client $client;

    /** Constructor. */
    public function __construct() {
        $this->analyzer = new local_analyzer();
        $this->client = new client();
    }

    /**
     * Review one item.
     *
     * @param content_item $item Item.
     * @param bool $ignorecache Cache bypass.
     * @return array<string,mixed>
     */
    public function review(content_item $item, bool $ignorecache = false): array {
        $local = $this->analyzer->analyze($item->html);
        $ai = [
            'summary' => '',
            'findings' => [],
            'cached' => false,
            'error' => '',
        ];
        try {
            $airesult = $this->client->review($item, $local['plaintext'], $local['metrics'], $ignorecache);
            $ai = array_merge($ai, $airesult);
        } catch (ai_exception $e) {
            $ai['error'] = $e->getMessage();
        }

        return [
            'key' => $item->key,
            'title' => $item->title,
            'type' => get_string('source' . $item->type, 'local_plainlanguage'),
            'originalhtml' => $item->formatted_html(),
            'metrics' => $this->metrics_for_template($local['metrics']),
            'localfindings' => $this->local_findings_for_template($local['findings']),
            'haslocalfindings' => !empty($local['findings']),
            'aifindings' => $this->ai_findings_for_template($ai['findings']),
            'hasaifindings' => !empty($ai['findings']),
            'aisummary' => $ai['summary'],
            'hasaisummary' => $ai['summary'] !== '',
            'aierror' => $ai['error'],
            'hasaierror' => $ai['error'] !== '',
            'cached' => !empty($ai['cached']),
        ];
    }

    /**
     * Get a protected full rewrite suggestion.
     *
     * @param content_item $item Item.
     * @param bool $ignorecache Cache bypass.
     * @return array<string,mixed>
     */
    public function rewrite(content_item $item, bool $ignorecache = false): array {
        $rewrite = $this->client->rewrite($item, $ignorecache);
        $context = context::instance_by_id($item->contextid);
        $formatted = format_text($rewrite['html'], $item->format, [
            'context' => $context,
            'para' => false,
            'filter' => true,
            'noclean' => false,
        ]);

        return [
            'title' => $item->title,
            'type' => get_string('source' . $item->type, 'local_plainlanguage'),
            'originalhtml' => $item->formatted_html(),
            'rewrittenhtml' => $formatted,
            'rewrittensource' => $rewrite['html'],
            'notes' => array_map(static fn(string $note): array => ['text' => $note], $rewrite['notes']),
            'hasnotes' => !empty($rewrite['notes']),
            'cached' => !empty($rewrite['cached']),
        ];
    }

    /**
     * Prepare metrics for Mustache.
     *
     * @param array<string,int|float> $metrics Metrics.
     * @return array<int,array{label:string,value:string}>
     */
    private function metrics_for_template(array $metrics): array {
        $map = [
            'wordcount' => 'metricwordcount',
            'sentencecount' => 'metricsentencecount',
            'avgsentencewords' => 'metricavgsentence',
            'maxsentencewords' => 'metricmaxsentence',
            'paragraphcount' => 'metricparagraphcount',
            'longparagraphs' => 'metriclongparagraphs',
            'uppercaseratio' => 'metricuppercaseratio',
            'instructioncount' => 'metricinstructioncount',
            'linkcount' => 'metriclinkcount',
            'headingcount' => 'metricheadingcount',
        ];
        $out = [];
        foreach ($map as $key => $stringkey) {
            $value = $metrics[$key] ?? 0;
            if ($key === 'uppercaseratio') {
                $value .= '%';
            }
            $out[] = [
                'label' => get_string($stringkey, 'local_plainlanguage'),
                'value' => (string)$value,
            ];
        }
        return $out;
    }

    /**
     * Translate deterministic finding codes.
     *
     * @param array<int,array<string,mixed>> $findings Findings.
     * @return array<int,array<string,string>>
     */
    private function local_findings_for_template(array $findings): array {
        $out = [];
        foreach ($findings as $finding) {
            $code = $finding['code'];
            $value = $finding['value'] ?? null;
            $out[] = [
                'category' => get_string('localcategory', 'local_plainlanguage'),
                'excerpt' => (string)($finding['excerpt'] ?? ''),
                'hasexcerpt' => (string)($finding['excerpt'] ?? '') !== '',
                'problem' => get_string('finding_' . $code . '_problem', 'local_plainlanguage', $value),
                'why' => get_string('finding_' . $code . '_why', 'local_plainlanguage'),
                'suggestion' => get_string('finding_' . $code . '_suggestion', 'local_plainlanguage'),
            ];
        }
        return $out;
    }

    /**
     * Prepare sanitized AI findings.
     *
     * @param array<int,array<string,string>> $findings Findings from json_parser.
     * @return array<int,array<string,string|bool>>
     */
    private function ai_findings_for_template(array $findings): array {
        $out = [];
        foreach ($findings as $finding) {
            $categorykey = 'category_' . $finding['category'];
            $out[] = [
                'category' => get_string($categorykey, 'local_plainlanguage'),
                'excerpt' => $finding['excerpt'],
                'hasexcerpt' => $finding['excerpt'] !== '',
                'problem' => $finding['problem'],
                'why' => $finding['why_confusing'],
                'suggestion' => $finding['suggestion'],
            ];
        }
        return $out;
    }
}
