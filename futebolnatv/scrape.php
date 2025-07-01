<?php

// Versão Final com Logs de Erro

/**
 * Registra uma mensagem de erro no arquivo de log.
 * @param string $message A mensagem a ser registrada.
 */
function log_error($message) {
    $logFile = 'api_error.log';
    $timestamp = date('Y-m-d H:i:s');
    // Formato da linha de log: [Data Hora] Mensagem de Erro
    $logEntry = "[" . $timestamp . "] " . $message . PHP_EOL;
    // Adiciona a nova linha ao final do arquivo de log
    file_put_contents($logFile, $logEntry, FILE_APPEND);
}

/**
 * Converte uma URL relativa em absoluta.
 * @param string $url A URL relativa.
 * @return string A URL absoluta.
 */
function to_absolute_url($url) {
    $baseUrl = 'https://www.futebolnatv.com.br';
    if (substr($url, 0, 4) === 'http') {
        return $url;
    }
    return $baseUrl . $url;
}

function obterJogosComDetalhes() {
    
    $apiKey = '5ac94dc910bc05ab0df3ba96f7f9ac33e9121d54'; 
    $urlParaExtrair = 'https://www.futebolnatv.com.br/jogos-hoje/';
    
    $apiParams = [
        'apikey' => $apiKey,
        'url' => $urlParaExtrair,
        'js_render' => 'true',
        'wait' => 10000,
        'json_response' => 'true'
    ];
    $apiUrl = 'https://api.zenrows.com/v1/?' . http_build_query($apiParams);

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $apiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 90); 

    $responseJson = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($http_code != 200 || $responseJson === false) {
        $errorMessage = "A API da ZenRows retornou um erro. Código HTTP: " . $http_code;
        log_error($errorMessage);
        die(json_encode(["erro" => $errorMessage]));
    }
    
    $responseData = json_decode($responseJson, true);
    if (json_last_error() !== JSON_ERROR_NONE || !isset($responseData['html'])) {
        $errorMessage = "Não foi possível decodificar a resposta JSON da ZenRows.";
        log_error($errorMessage);
        die(json_encode(["erro" => $errorMessage]));
    }
    $html_final = $responseData['html'];
    
    $doc = new DOMDocument();
    libxml_use_internal_errors(true);
    $doc->loadHTML('<?xml encoding="UTF-8">' . $html_final);
    libxml_clear_errors();
    $xpath = new DOMXPath($doc);

    $jogos = [];
    $gameCards = $xpath->query('//div[contains(@class, "gamecard")]');
    
    foreach ($gameCards as $card) {
        if ($xpath->query('.//ins[contains(@class, "adsbygoogle")]', $card)->length > 0) {
            continue;
        }

        $campeonatoNode = $xpath->query('.//div[contains(@class, "all-scores-widget-competition-header-container-hora")]/div/b', $card);
        $imgCompeticaoNode = $xpath->query('.//div[contains(@class, "all-scores-widget-competition-header-container-hora")]//img', $card);
        $timeCasaNode = $xpath->query('.//div[contains(@class, "endgame")]/div[1]/span[1]', $card);
        $timeVisitanteNode = $xpath->query('.//div[contains(@class, "endgame")]/div[2]/span[1]', $card);
        $imgTime1Node = $xpath->query('.//div[contains(@class, "endgame")]/div[1]//img', $card);
        $imgTime2Node = $xpath->query('.//div[contains(@class, "endgame")]/div[2]//img', $card);
        $placarTime1Node = $xpath->query('.//div[contains(@class, "endgame")]/div[1]/span[2]', $card);
        $placarTime2Node = $xpath->query('.//div[contains(@class, "endgame")]/div[2]/span[2]', $card);
        $canaisNodes = $xpath->query('.//div[contains(@class, "bcmact")]', $card);
        $horarioNode = $xpath->query('.//div[contains(@class, "box_time_live")]', $card);
        $destaqueNode = $xpath->query('.//div[contains(@class, "boxchannel")]', $card);

        if ($timeCasaNode->length > 0 && $timeVisitanteNode->length > 0) {
            preg_match('/(\d{2}:\d{2})/', $horarioNode[0]->nodeValue, $matches);
            $horario = !empty($matches[1]) ? $matches[1] : 'Não informado';
            $destaque = $destaqueNode->length > 0 && strpos($destaqueNode[0]->nodeValue, 'Destaque') !== false;
            $canais = [];
            foreach ($canaisNodes as $canalNode) {
                $nomeCanal = trim(preg_replace('/\s+/', ' ', $canalNode->nodeValue));
                $imgCanalNode = $xpath->query('.//img', $canalNode);
                $imgUrl = '';
                if ($imgCanalNode->length > 0) {
                    $imgUrl = to_absolute_url($imgCanalNode[0]->getAttribute('data-src') ?: $imgCanalNode[0]->getAttribute('src'));
                }
                if (!empty($nomeCanal)) {
                    $canais[] = ["img_url" => $imgUrl, "nome" => $nomeCanal];
                }
            }

            $jogos[] = [
                "canais" => $canais, "competicao" => $campeonatoNode->length > 0 ? trim($campeonatoNode[0]->nodeValue) : 'Não informado',
                "data_jogo" => "hoje", "destaque" => $destaque, "horario" => $horario,
                "img_competicao_url" => $imgCompeticaoNode->length > 0 ? to_absolute_url($imgCompeticaoNode[0]->getAttribute('data-src') ?: $imgCompeticaoNode[0]->getAttribute('src')) : '',
                "img_time1_url" => $imgTime1Node->length > 0 ? to_absolute_url($imgTime1Node[0]->getAttribute('data-src') ?: $imgTime1Node[0]->getAttribute('src')) : '',
                "img_time2_url" => $imgTime2Node->length > 0 ? to_absolute_url($imgTime2Node[0]->getAttribute('data-src') ?: $imgTime2Node[0]->getAttribute('src')) : '',
                "placar_time1" => $placarTime1Node->length > 0 ? trim($placarTime1Node[0]->nodeValue) : '',
                "placar_time2" => $placarTime2Node->length > 0 ? trim($placarTime2Node[0]->nodeValue) : '',
                "status" => $destaque ? "Destaque" : "",
                "time1" => trim($timeCasaNode[0]->nodeValue), "time2" => trim($timeVisitanteNode[0]->nodeValue)
            ];
        }
    }

    if (empty($jogos)) {
        log_error("Nenhum jogo foi encontrado na página. A estrutura do site pode ter mudado ou não havia jogos no momento da execução.");
    }
    
    return json_encode($jogos, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
}

// --- LÓGICA FINAL: SALVAR EM ARQUIVO E LOGAR ERROS ---

$jsonData = obterJogosComDetalhes();
$nomeDoArquivo = 'jogos_hoje.json';

if (file_put_contents($nomeDoArquivo, $jsonData) !== false) {
    // Se o script for executado via navegador, exibe uma mensagem.
    // Se for via CRON, esta mensagem não será vista, mas o arquivo será salvo.
    echo "Arquivo '{$nomeDoArquivo}' foi salvo com sucesso!";
} else {
    $errorMessage = "ERRO FATAL: Não foi possível salvar o arquivo '{$nomeDoArquivo}'. Verifique as permissões de escrita da pasta no servidor.";
    log_error($errorMessage);
    // Exibe o erro na tela também, caso seja um acesso via navegador.
    echo $errorMessage;
}

?>