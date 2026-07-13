<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Brazilian Portuguese strings for the local_assignnodraft plugin.
 *
 * @package    local_assignnodraft
 * @copyright  2026 onwards
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['applied'] = 'Pronto.';
$string['assignnodraft:manage'] = 'Gerenciar a imposição de tarefa sem rascunho';
$string['assignsaffected'] = 'Tarefas afetadas: {$a}';
$string['assignswithdraftmode'] = 'Usando modo rascunho agora: {$a}';
$string['back'] = 'Voltar';
$string['category'] = 'Categoria';
$string['colassign'] = 'Tarefa';
$string['colcourse'] = 'Curso';
$string['coldraftmode'] = 'Modo rascunho';
$string['coldrafts'] = 'Envios em rascunho';
$string['confirmapply'] = 'Aplicar';
$string['course'] = 'Curso';
$string['draftstopromote'] = 'Envios presos em rascunho: {$a}';
$string['errornoscope'] = 'Escolha uma categoria ou um curso.';
$string['hiddennoaction'] = 'Tarefas já definitivas e sem rascunhos (ocultas): {$a}';
$string['includesubcats'] = 'Incluir subcategorias';
$string['includesubcats_help'] = 'Também afeta as tarefas de todas as categorias descendentes, em qualquer nível.';
$string['intro'] = 'Desliga a opção "Exigir que os estudantes cliquem no botão enviar" em todas as tarefas de uma categoria ou de um curso, promove os envios presos em rascunho para definitivos e mantém isso travado para que não seja reativado.';
$string['lockcontinuous'] = 'Manter travado (bloquear)';
$string['lockcontinuous_help'] = 'Memoriza este escopo. Se um professor reativar o modo rascunho em uma tarefa dentro dele, o modo é desligado automaticamente de novo.';
$string['lockedscopes'] = 'Escopos impostos';
$string['manage'] = 'Tarefa sem rascunho';
$string['manageheading'] = 'Forçar tarefas a pular o modo rascunho';
$string['next'] = 'Ver o que vai mudar';
$string['nolockedscopes'] = 'Nenhum escopo está sendo imposto no momento.';
$string['nothingtodo'] = 'Nada a mudar neste escopo: nenhuma tarefa usa modo rascunho e não há envios em rascunho.';
$string['off'] = 'Desligado';
$string['on'] = 'Ligado';
$string['pluginname'] = 'Tarefa: sem modo rascunho';



$string['previewheading'] = 'Prévia';
$string['previewscope'] = 'Escopo: {$a}';
$string['privacy:metadata'] = 'O plugin Tarefa sem modo rascunho não armazena nenhum dado pessoal.';
$string['promote'] = 'Promover rascunhos para envio definitivo';
$string['promote_help'] = 'Envios presos em rascunho são marcados como enviados para que o professor os veja como definitivos. Dispara o evento padrão de envio e recalcula as notas. Não pode ser desfeito.';
$string['promoteclosed'] = 'Promover também rascunhos com prazo encerrado';
$string['promoteclosed_help'] = 'Por padrão, um rascunho cujo prazo de envio já fechou é deixado como está. Ative para enviar esses também, de modo que o aluno que perdeu o prazo ainda em rascunho não fique de fora.';
$string['removescope'] = 'Remover';
$string['removescopeconfirm'] = 'Parar de impor sem-rascunho neste escopo? As tarefas existentes mantêm a configuração atual; só a reimposição automática para.';
$string['reportmodenote'] = 'Somente relatório: nada foi alterado. Este é o inventário completo do escopo.';
$string['reportonly'] = 'Somente mapear (relatório, sem alterar nada)';
$string['reportonly_help'] = 'Gera um inventário somente leitura do escopo: cada tarefa, se usa modo rascunho e quantos envios estão presos em rascunho. Nenhuma configuração é alterada e nenhum envio é promovido.';
$string['resultassigns'] = 'Tarefas mudadas para sem rascunho: {$a}';
$string['resulterrors'] = 'Tarefas puladas por erro: {$a}';
$string['resultlocked'] = 'Escopo travado — continuará sendo imposto.';
$string['resultpromoted'] = 'Rascunhos promovidos para definitivo: {$a}';
$string['scope'] = 'Escopo';
$string['scopelabelcategory'] = 'Categoria "{$a->name}"{$a->sub}';
$string['scopelabelcategorysub'] = ' e subcategorias';
$string['scopelabelcourse'] = 'Curso "{$a}"';
$string['scoperemoved'] = 'Escopo removido da imposição.';
$string['scopetype'] = 'Aplicar em';
$string['scopetype_category'] = 'Categoria';
$string['scopetype_course'] = 'Curso';
