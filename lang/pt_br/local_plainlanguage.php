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
 * Brazilian Portuguese language strings.
 *
 * @package   local_plainlanguage
 * @copyright 2026 Eduardo Kraus
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

$string['aicategory'] = 'Achado semântico';
$string['backtoreview'] = 'Voltar à revisão';
$string['bridgeerror'] = 'A revisão por IA não está disponível para esta solicitação. Verifique tenant, purpose, rota, permissões e créditos no AI Bridge.';
$string['cached'] = 'Resultado em cache';
$string['category_ambiguity'] = 'Ambiguidade';
$string['category_confusing_implicit_subject'] = 'Sujeito implícito confuso';
$string['category_confusing_sequence'] = 'Sequência confusa';
$string['category_incomplete_instruction'] = 'Instrução incompleta';
$string['category_multiple_interpretations'] = 'Duas ou mais interpretações plausíveis';
$string['category_overloaded_instruction'] = 'Excesso de informação em uma instrução';
$string['category_terminology_inconsistency'] = 'Inconsistência terminológica';
$string['category_undefined_term'] = 'Termo técnico não definido';
$string['category_unexplained_prerequisite'] = 'Pré-requisito não explicado';
$string['contentitem'] = 'Item de conteúdo';
$string['contentnotfound'] = 'O item de conteúdo selecionado não foi encontrado.';
$string['copysource'] = 'HTML da versão sugerida';
$string['excerpt'] = 'Trecho';
$string['finding_empty_heading_problem'] = 'Foi encontrado um título vazio.';
$string['finding_empty_heading_suggestion'] = 'Remova o título vazio ou atribua a ele um texto descritivo.';
$string['finding_empty_heading_why'] = 'Um título vazio cria estrutura sem descrever a seção.';
$string['finding_large_paragraph_problem'] = 'Parágrafo grande detectado ({$a} palavras).';
$string['finding_large_paragraph_suggestion'] = 'Considere separar ideias em parágrafos, títulos ou uma lista real quando a estrutura justificar.';
$string['finding_large_paragraph_why'] = 'Blocos extensos dificultam localizar ações, condições e exceções.';
$string['finding_long_sentence_problem'] = 'Frase longa detectada ({$a} palavras).';
$string['finding_long_sentence_suggestion'] = 'Verifique se a frase pode ser dividida sem alterar o sentido.';
$string['finding_long_sentence_why'] = 'Frases muito longas podem esconder mais de uma ação ou ideia e dificultar a leitura das instruções.';
$string['finding_no_headings_problem'] = 'Conteúdo extenso sem títulos.';
$string['finding_no_headings_suggestion'] = 'Considere títulos descritivos se o conteúdo possuir seções distintas de forma natural.';
$string['finding_no_headings_why'] = 'Conteúdo longo e contínuo pode ser difícil de percorrer e consultar novamente.';
$string['finding_uppercase_problem'] = 'Há uma proporção alta de palavras em caixa alta.';
$string['finding_uppercase_suggestion'] = 'Use caixa alta apenas em rótulos curtos ou siglas e prefira capitalização normal no texto corrido.';
$string['finding_uppercase_why'] = 'Texto contínuo em caixa alta é mais difícil de percorrer e pode dar ênfase visual excessiva a instruções comuns.';
$string['finding_vague_link_problem'] = 'O texto do link é vago: “{$a}”.';
$string['finding_vague_link_suggestion'] = 'Use um texto de link que identifique o destino ou a ação.';
$string['finding_vague_link_why'] = 'Um link vago não explica o destino quando é lido fora da frase ao redor.';
$string['findings'] = 'Problemas e sugestões';
$string['heading'] = 'Auditoria de linguagem clara';
$string['ignorecache'] = 'Ignorar revisão em cache';
$string['intro'] = 'Revise conteúdos criados pelo professor quanto à clareza. As verificações locais rodam primeiro; a IA é usada apenas para aspectos semânticos e nunca altera o conteúdo automaticamente.';
$string['invalidairesponse'] = 'A IA retornou JSON inválido ou incompatível com o schema esperado.';
$string['invalidcontentkey'] = 'Identificador de conteúdo inválido.';
$string['localcategory'] = 'Heurística local';
$string['localchecks'] = 'Verificações locais';
$string['metricavgsentence'] = 'Média de palavras por frase';
$string['metricheadingcount'] = 'Títulos';
$string['metricinstructioncount'] = 'Prováveis instruções';
$string['metriclinkcount'] = 'Links';
$string['metriclongparagraphs'] = 'Parágrafos grandes';
$string['metricmaxsentence'] = 'Maior frase (palavras)';
$string['metricparagraphcount'] = 'Parágrafos';
$string['metrics'] = 'Métricas locais';
$string['metricsentencecount'] = 'Frases (heurística)';
$string['metricsnote'] = 'Estes valores são heurísticas, não índices absolutos de legibilidade.';
$string['metricuppercaseratio'] = 'Proporção de palavras em caixa alta';
$string['metricwordcount'] = 'Palavras';
$string['nocontent'] = 'Nenhum conteúdo suportado criado pelo professor foi encontrado neste curso.';
$string['nofindings'] = 'Nenhum problema foi retornado para este item.';
$string['original'] = 'Original';
$string['plainlanguage:review'] = 'Revisar conteúdo do curso quanto à clareza da linguagem';
$string['pluginname'] = 'Linguagem clara';
$string['privacy:metadata'] = 'O plugin Linguagem clara não armazena dados de alunos. Os resultados ficam em cache somente na sessão atual do usuário.';
$string['problem'] = 'Problema';
$string['review'] = 'Revisar';
$string['reviewresults'] = 'Resultados da revisão';
$string['rewriteheading'] = 'Sugestão de reescrita';
$string['rewritenote'] = 'Isto é apenas uma sugestão. O plugin não salva nem substitui o conteúdo original.';
$string['rewritten'] = 'Versão sugerida';
$string['scope'] = 'Escopo';
$string['scopecourse'] = 'Curso inteiro';
$string['scopeitem'] = 'Um item de conteúdo';
$string['selectcontent'] = 'Selecione o conteúdo';
$string['semanticchecks'] = 'Revisão semântica';
$string['sourceassignment'] = 'Instruções da tarefa';
$string['sourcebook'] = 'Capítulo de livro';
$string['sourceforum'] = 'Descrição do fórum';
$string['sourcelabel'] = 'Área de texto e mídia';
$string['sourcepage'] = 'Página';
$string['sourcesection'] = 'Resumo da seção';
$string['suggestion'] = 'Sugestão';
$string['suggestrerewrite'] = 'Sugerir reescrita';
$string['summary'] = 'Resumo';
$string['validationselectitem'] = 'Selecione um item ao revisar apenas um conteúdo.';
$string['whyconfusing'] = 'Por que pode confundir';
