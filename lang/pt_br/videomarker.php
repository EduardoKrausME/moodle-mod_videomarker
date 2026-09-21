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
 * Brazilian Portuguese strings for Video Marker.
 *
 * @package   mod_videomarker
 * @copyright 2026 Eduardo Kraus
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['activitynotfound'] = 'A atividade Video Marker não foi encontrada.';
$string['addquestion'] = 'Adicionar pergunta';
$string['allowretry'] = 'Permitir novas tentativas';
$string['allowseek'] = 'Permitir avançar para trechos ainda não assistidos';
$string['answercorrect'] = 'Correto';
$string['answerincorrect'] = 'Incorreto';
$string['answerlocked'] = 'Esta pergunta não permite nova tentativa.';
$string['answerpartial'] = 'Parcialmente correto';
$string['attempt'] = 'Tentativa {$a}';
$string['averagedistance'] = 'Distância média';
$string['backtoactivity'] = 'Voltar para a atividade';
$string['backtoreport'] = 'Voltar para o relatório';
$string['completiondetail:markers'] = 'Enviar todas as perguntas de marcação obrigatórias';
$string['completiondetail:percent'] = 'Assistir pelo menos {$a}% do vídeo';
$string['completionmarkers'] = 'Exigir marcações obrigatórias';
$string['completionmarkers_help'] = 'Todas as perguntas marcadas como obrigatórias precisam ter pelo menos uma tentativa enviada.';
$string['completionpercent'] = 'Exigir percentual assistido';
$string['completionpercent_help'] = 'O aluno precisa realmente assistir pelo menos este percentual do vídeo. Grandes saltos não são contabilizados como tempo assistido.';
$string['correct'] = 'Acertos';
$string['deletequestion'] = 'Excluir pergunta';
$string['deletequestionconfirm'] = 'Excluir esta pergunta e todas as tentativas dos alunos?';
$string['disabledownload'] = 'Desencorajar download do vídeo';
$string['distance'] = 'Distância';
$string['downloadblockednotice'] = 'Os controles de download são ocultados quando o navegador permite. Isso não é proteção DRM.';
$string['editquestion'] = 'Editar pergunta';
$string['endinterval'] = 'Finalizar intervalo';
$string['errorpercent'] = 'Informe um percentual entre 1 e 100.';
$string['eventanswersubmitted'] = 'Resposta do Video Marker enviada';
$string['eventcoursemoduleviewed'] = 'Atividade Video Marker visualizada';
$string['expected'] = 'Esperado';
$string['feedback'] = 'Feedback';
$string['feedbackcorrect'] = 'Feedback quando totalmente correto';
$string['feedbackincorrect'] = 'Feedback quando parcial ou incorreto';
$string['grade'] = 'Nota';
$string['incorrect'] = 'Erros';
$string['interval'] = 'Intervalo';
$string['invalidintervaltargets'] = 'Perguntas de intervalo exigem início e fim em cada linha.';
$string['invalidmarkcount'] = 'Envie exatamente a quantidade de marcações exigida pela pergunta.';
$string['invalidmarkdata'] = 'Uma ou mais marcações enviadas são inválidas.';
$string['invalidpoints'] = 'A pontuação deve ser maior que zero.';
$string['invalidquestion'] = 'A pergunta solicitada não pertence a esta atividade.';
$string['invalidtargets'] = 'Informe pelo menos um alvo válido. Use MM:SS-MM:SS ou HH:MM:SS-HH:MM:SS.';
$string['invalidtolerance'] = 'A tolerância deve ficar entre 0 e 60 segundos.';
$string['invalidvideourl'] = 'Informe uma URL de vídeo válida e suportada.';
$string['lastaccess'] = 'Última atividade';
$string['latestattempt'] = 'Última tentativa';
$string['managequestions'] = 'Gerenciar perguntas';
$string['mark'] = 'Marcação';
$string['markers'] = 'Marcações';
$string['markersneeded'] = '{$a->current} de {$a->required} marcações selecionadas';
$string['markings'] = 'Marcações';
$string['markmoment'] = 'Marcar momento';
$string['maxplaybackrate'] = 'Velocidade máxima de reprodução';
$string['modulename'] = 'Video Marker';
$string['modulenameplural'] = 'Video Markers';
$string['movedown'] = 'Mover para baixo';
$string['moveup'] = 'Mover para cima';
$string['newattempt'] = 'Nova tentativa';
$string['no'] = 'Não';
$string['noattempts'] = 'Ainda não há tentativas';
$string['noquestions'] = 'Ainda não foram criadas perguntas de marcação.';
$string['notgraded'] = 'Sem nota';
$string['overallgrade'] = 'Nota geral';
$string['playbackheader'] = 'Reprodução e acompanhamento';
$string['pluginadministration'] = 'Administração do Video Marker';
$string['pluginname'] = 'Video Marker';
$string['points'] = 'Pontuação';
$string['poster'] = 'Imagem de capa';
$string['privacy:metadata:videomarker_attempts'] = 'Armazena tentativas enviadas nas perguntas do Video Marker.';
$string['privacy:metadata:videomarker_attempts:questionid'] = 'Pergunta respondida pelo usuário.';
$string['privacy:metadata:videomarker_attempts:score'] = 'Pontuação atribuída à tentativa.';
$string['privacy:metadata:videomarker_attempts:timecreated'] = 'Momento em que a tentativa foi enviada.';
$string['privacy:metadata:videomarker_attempts:userid'] = 'Usuário que enviou a tentativa.';
$string['privacy:metadata:videomarker_marks'] = 'Armazena os momentos e intervalos individuais enviados dentro de uma tentativa.';
$string['privacy:metadata:videomarker_marks:distance'] = 'A distância temporal até o alvo esperado.';
$string['privacy:metadata:videomarker_marks:endtime'] = 'O tempo final opcional marcado no vídeo.';
$string['privacy:metadata:videomarker_marks:iscorrect'] = 'Indica se a marcação ficou dentro do alvo aceito.';
$string['privacy:metadata:videomarker_marks:starttime'] = 'O tempo inicial marcado no vídeo.';
$string['privacy:metadata:videomarker_progress'] = 'Armazena o progresso de visualização de vídeo nas atividades Video Marker.';
$string['privacy:metadata:videomarker_progress:lastposition'] = 'Última posição de reprodução usada para retomada.';
$string['privacy:metadata:videomarker_progress:percent'] = 'Percentual do vídeo realmente assistido.';
$string['privacy:metadata:videomarker_progress:timemodified'] = 'Momento da última atualização do progresso.';
$string['privacy:metadata:videomarker_progress:userid'] = 'Usuário cujo progresso de visualização é armazenado.';
$string['privacy:metadata:videomarker_progress:watchedranges'] = 'Intervalos assistidos usados no cálculo do progresso.';
$string['progress'] = 'Progresso';
$string['questioncount'] = '{$a} pergunta(s)';
$string['questionnotfound'] = 'A pergunta do Video Marker não foi encontrada.';
$string['questionoptional'] = 'Opcional';
$string['questionrequired'] = 'Obrigatória';
$string['questions'] = 'Perguntas de marcação';
$string['questiontext'] = 'Pergunta';
$string['questiontype'] = 'Tipo de resposta';
$string['questiontypeinterval'] = 'Marcação de intervalo';
$string['questiontypepoint'] = 'Marcação de momento';
$string['removemarker'] = 'Remover';
$string['reportattempts'] = 'Histórico de tentativas';
$string['reports'] = 'Relatórios';
$string['reportstudent'] = 'Detalhes do aluno';
$string['requiredanswered'] = 'Perguntas obrigatórias';
$string['requiredmarkers'] = '{$a} marcação(ões) necessária(s)';
$string['requiredquestion'] = 'Obrigatória para conclusão';
$string['resetuserdata'] = 'Excluir tentativas e progresso de visualização do Video Marker';
$string['resumeautomatic'] = 'Retomar automaticamente da última posição';
$string['resumefromstart'] = 'Sempre iniciar do começo';
$string['resumeplayback'] = 'Retomar reprodução';
$string['retrynotallowed'] = 'Uma nova tentativa não é permitida nesta pergunta.';
$string['scorelabel'] = 'Pontuação: {$a->score} / {$a->max}';
$string['seconds'] = '{$a} s';
$string['showfeedback'] = 'Mostrar feedback após o envio';
$string['sourceheader'] = 'Fonte do vídeo';
$string['sourceunsupported'] = 'Fonte de vídeo não suportada.';
$string['sourceupload'] = 'Enviar para o Moodle';
$string['sourceurl'] = 'URL direta do vídeo';
$string['sourceurlhint'] = 'Use uma URL HTTPS sempre que possível.';
$string['sourcevimeo'] = 'Vimeo';
$string['sourceyoutube'] = 'YouTube';
$string['startinterval'] = 'Iniciar intervalo';
$string['student'] = 'Aluno';
$string['studentmark'] = 'Marcação do aluno';
$string['submitmarkers'] = 'Enviar marcações';
$string['targets'] = 'Alvo(s) esperado(s)';
$string['targets_help'] = 'Informe um alvo esperado por linha. Para momento, use uma faixa de acerto como 03:38-03:47. Para intervalo, informe o trecho esperado como 08:10-08:45.';
$string['targetsexampleinterval'] = 'Exemplo:
08:10-08:45';
$string['targetsexamplepoint'] = 'Exemplo:
03:38-03:47
08:12-08:20
14:00-14:08';
$string['timeformathelp'] = 'Tempos aceitos: MM:SS ou HH:MM:SS.';
$string['timeline'] = 'Linha do tempo';
$string['tolerance'] = 'Tolerância nas bordas do intervalo (segundos)';
$string['tolerance_help'] = 'Nas perguntas de intervalo, início e fim devem ficar dentro desta tolerância em relação às bordas esperadas.';
$string['trackingnotice'] = 'O progresso considera os trechos realmente reproduzidos. Grandes saltos não são contabilizados como tempo assistido.';
$string['videofile'] = 'Arquivo de vídeo';
$string['videofilemissing'] = 'Envie um arquivo de vídeo para esta fonte.';
$string['videomarker:addinstance'] = 'Adicionar uma nova atividade Video Marker';
$string['videomarker:attempt'] = 'Enviar respostas do Video Marker';
$string['videomarker:managequestions'] = 'Gerenciar perguntas do Video Marker';
$string['videomarker:view'] = 'Visualizar atividades Video Marker';
$string['videomarker:viewreports'] = 'Visualizar relatórios do Video Marker';
$string['videomarkername'] = 'Nome do Video Marker';
$string['videosource'] = 'Fonte do vídeo';
$string['videourl'] = 'URL do vídeo';
$string['videourl_help'] = 'Para URL direta, informe um endereço reproduzível. Para YouTube ou Vimeo, informe a URL normal do vídeo público ou não listado.';
$string['viewreport'] = 'Ver relatório';
$string['watched'] = 'Assistido';
$string['yes'] = 'Sim';
