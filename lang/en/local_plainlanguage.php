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
 * English language strings.
 *
 * @package   local_plainlanguage
 * @copyright 2026 Eduardo Kraus
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

$string['aicategory'] = 'Semantic finding';
$string['backtoreview'] = 'Back to review';
$string['bridgeerror'] = 'The AI review is unavailable for this request. Check the AI Bridge tenant, purpose, route, permissions and credits.';
$string['cached'] = 'Cached result';
$string['category_ambiguity'] = 'Ambiguity';
$string['category_confusing_implicit_subject'] = 'Confusing implicit subject';
$string['category_confusing_sequence'] = 'Confusing sequence';
$string['category_incomplete_instruction'] = 'Incomplete instruction';
$string['category_multiple_interpretations'] = 'Multiple plausible interpretations';
$string['category_overloaded_instruction'] = 'Overloaded instruction';
$string['category_terminology_inconsistency'] = 'Terminology inconsistency';
$string['category_undefined_term'] = 'Undefined technical term';
$string['category_unexplained_prerequisite'] = 'Unexplained prerequisite';
$string['contentitem'] = 'Content item';
$string['contentnotfound'] = 'The selected content item could not be found.';
$string['copysource'] = 'HTML source of suggested version';
$string['excerpt'] = 'Excerpt';
$string['finding_empty_heading_problem'] = 'An empty heading was found.';
$string['finding_empty_heading_suggestion'] = 'Remove the empty heading or give it a descriptive title.';
$string['finding_empty_heading_why'] = 'An empty heading creates structure without describing the section.';
$string['finding_large_paragraph_problem'] = 'Large paragraph detected ({$a} words).';
$string['finding_large_paragraph_suggestion'] = 'Consider separating ideas with paragraphs, headings or a real list when the structure supports it.';
$string['finding_large_paragraph_why'] = 'Large text blocks make it harder to locate actions, conditions and exceptions.';
$string['finding_long_sentence_problem'] = 'Long sentence detected ({$a} words).';
$string['finding_long_sentence_suggestion'] = 'Check whether the sentence can be split without changing its meaning.';
$string['finding_long_sentence_why'] = 'Very long sentences can hide more than one action or idea and make instructions harder to scan.';
$string['finding_no_headings_problem'] = 'Long content has no headings.';
$string['finding_no_headings_suggestion'] = 'Consider descriptive headings if the content naturally contains distinct sections.';
$string['finding_no_headings_why'] = 'Long uninterrupted content can be difficult to scan and revisit.';
$string['finding_uppercase_problem'] = 'A high proportion of words are written in uppercase.';
$string['finding_uppercase_suggestion'] = 'Keep uppercase for short labels or acronyms and use normal sentence casing for prose.';
$string['finding_uppercase_why'] = 'Sustained uppercase text is harder to scan and may visually overemphasize ordinary instructions.';
$string['finding_vague_link_problem'] = 'Link text is vague: “{$a}”.';
$string['finding_vague_link_suggestion'] = 'Use link text that identifies the destination or action.';
$string['finding_vague_link_why'] = 'A vague link does not explain its destination when read outside the surrounding sentence.';
$string['findings'] = 'Findings and suggestions';
$string['heading'] = 'Plain language review';
$string['ignorecache'] = 'Ignore cached review';
$string['intro'] = 'Review teacher-authored course content for clarity. Local checks run first; AI is used only for semantic issues and never changes course content automatically.';
$string['invalidairesponse'] = 'The AI returned malformed or schema-incompatible JSON.';
$string['invalidcontentkey'] = 'Invalid content identifier.';
$string['localcategory'] = 'Local heuristic';
$string['localchecks'] = 'Local checks';
$string['metricavgsentence'] = 'Average words per sentence';
$string['metricheadingcount'] = 'Headings';
$string['metricinstructioncount'] = 'Likely instructions';
$string['metriclinkcount'] = 'Links';
$string['metriclongparagraphs'] = 'Large paragraphs';
$string['metricmaxsentence'] = 'Longest sentence (words)';
$string['metricparagraphcount'] = 'Paragraphs';
$string['metrics'] = 'Local metrics';
$string['metricsentencecount'] = 'Sentences (heuristic)';
$string['metricsnote'] = 'These values are heuristics, not absolute readability scores.';
$string['metricuppercaseratio'] = 'Uppercase word ratio';
$string['metricwordcount'] = 'Words';
$string['nocontent'] = 'No supported teacher-authored content was found in this course.';
$string['nofindings'] = 'No findings were returned for this item.';
$string['original'] = 'Original';
$string['plainlanguage:review'] = 'Review course content for plain language and clarity';
$string['pluginname'] = 'Plain language review';
$string['privacy:metadata'] = 'The Plain language review plugin does not store student data. Review results are cached only in the current user session.';
$string['problem'] = 'Problem';
$string['review'] = 'Review';
$string['reviewresults'] = 'Review results';
$string['rewriteheading'] = 'Suggested rewrite';
$string['rewritenote'] = 'This is a suggestion only. The plugin does not save or replace the original content.';
$string['rewritten'] = 'Suggested version';
$string['scope'] = 'Scope';
$string['scopecourse'] = 'Entire course';
$string['scopeitem'] = 'One content item';
$string['selectcontent'] = 'Select content';
$string['semanticchecks'] = 'Semantic review';
$string['sourceassignment'] = 'Assignment instructions';
$string['sourcebook'] = 'Book chapter';
$string['sourceforum'] = 'Forum description';
$string['sourcelabel'] = 'Text and media area';
$string['sourcepage'] = 'Page';
$string['sourcesection'] = 'Section summary';
$string['suggestion'] = 'Suggestion';
$string['suggestrerewrite'] = 'Suggest rewrite';
$string['summary'] = 'Summary';
$string['validationselectitem'] = 'Select a content item when reviewing one item.';
$string['whyconfusing'] = 'Why it may confuse';
