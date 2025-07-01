<?php

// Versão Final Futebol - Compatível com o Servidor Trabalhador Genérico

/**
 * Registra uma mensagem de erro no arquivo de log.
 * @param string $message A mensagem a ser registrada.
 */
function log_error($message) {
    $logFile = 'api_error_futebol.log'; // Log separado para facilitar a depuração
    $timestamp = date('Y-m-d H:i:s');
    $logEntry = "[" . $timestamp . "] " . $message . PHP_EOL;
    file_put_contents($logFile, $logEntry, FILE_APPEND);
}

/**
 * Converte uma URL relativa em absoluta.
 * @param string $url A URL relativa.
 * @return string A URL absoluta.
 */
function to_absolute_url($url) {
    $baseUrl = 'https://www.futebolnatv.com.br';
    if (empty($url) || substr($url, 0, 4) === 'http') {
        return $url;
    }
    return $baseUrl . $url;
}

/**
 * Função principal que orquestra a chamada ao serviço de scraping e a análise do HTML.
 * @return string O JSON final com os dados dos jogos.
 */
function obterJogosDeFutebol() {
    
    // ===================================================================
    // CONFIGURAÇÃO - Altere as duas linhas abaixo com seus dados
    // ===================================================================
    $urlDoSeuServicoDeScraping = 'http://136.248.89.161:3000/scrape';
    $chaveSecreta = 'chave-muito-secreta-que-so-voce-sabe';
    // ===================================================================
    
    $urlParaExtrair = 'https://www.futebolnatv.com.br/jogos-hoje/';
    
    // Parâmetros para enviar ao nosso serviço de scraping
    $apiParams = [
        'url' => $urlParaExtrair,
        'waitFor' => '.gamecard' // O seletor específico para o site de FUTEBOL
    ];
    
    $apiUrl = $urlDoSeuServicoDeScraping . '?' . http_build_query($apiParams);

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $apiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 90); 
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['X-API-Key: ' . $chaveSecreta]);

    $html_final = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($http_code != 200 || $html_final === false) {
        $errorMessage = "O serviço de scraping (Trabalhador) falhou ao buscar dados de FUTEBOL. Código HTTP: " . $http_code;
        log_error($errorMessage);
        die(json_encode(["erro" => $errorMessage]));
    }
    
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
        
        $status = '';
        $destaque = false;
        $statusContainerNode = $xpath->query('.//div[contains(@class, "cardtime")]', $card);
        if ($statusContainerNode->length > 0) {
            $statusText = trim($statusContainerNode[0]->nodeValue);
            if (stripos($statusText, 'FIM') !== false) {
                $status = 'FIM DE JOGO';
            } elseif (stripos($statusText, 'INTERVALO') !== false) {
                $status = 'INTERVALO';
            } elseif (preg_match("/(\d{1,2}'(\s*\+\s*\d{1,2})?)/", $statusText, $matches)) {
                $status = str_replace(' ', '', $matches[0]);
            }
            if (stripos($statusText, 'Destaque') !== false) {
                $destaque = true;
                if (empty($status)) {
                    $status = 'Destaque';
                }
            }
        }
        
        $horarioRawNode = $xpath->query('.//div[contains(@class, "box_time_live")]', $card);
        $horario = 'Não informado';
        if ($horarioRawNode->length > 0) {
            preg_match('/(\d{2}:\d{2})/', $horarioRawNode[0]->nodeValue, $horarioMatches);
            $horario = !empty($horarioMatches[1]) ? $horarioMatches[1] : 'Não informado';
        }

        if ($timeCasaNode->length > 0 && $timeVisitanteNode->length > 0) {
            $canais = [];
            foreach ($canaisNodes as $canalNode) {
                $nomeCanal = trim(preg_replace('/\s+/', ' ', $canalNode->nodeValue));
                $imgCanalNode = $xpath->query('.//img', $canalNode);
                $imgUrl = $imgCanalNode->length > 0 ? to_absolute_url($imgCanalNode[0]->getAttribute('data-src') ?: $imgCanalNode[0]->getAttribute('src')) : '';
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
                "status" => $status,
                "time1" => trim($timeCasaNode[0]->nodeValue), "time2" => trim($timeVisitanteNode[0]->nodeValue)
            ];
        }
    }

    if (empty($jogos)) {
        log_error("Nenhum jogo de FUTEBOL foi encontrado. A estrutura do site pode ter mudado.");
    }
    
    return json_encode($jogos, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
}

// --- LÓGICA FINAL: MEDIR TEMPO, SALVAR EM ARQUIVO E LOGAR ERROS ---

$startTime = microtime(true);
$jsonData = obterJogosDeFutebol();
$endTime = microtime(true);
$duration = $endTime - $startTime;
$nomeDoArquivo = 'jogos_hoje.json';

if (file_put_contents($nomeDoArquivo, $jsonData) !== false) {
    $formattedDuration = number_format($duration, 2, ',', '.');
    echo "Arquivo '{$nomeDoArquivo}' foi salvo com sucesso! Tempo total da operação: {$formattedDuration} segundos.";
} else {
    $errorMessage = "ERRO FATAL FUTEBOL: Não foi possível salvar o arquivo '{$nomeDoArquivo}'. Verifique as permissões de escrita da pasta.";
    log_error($errorMessage);
    echo $errorMessage;
}

?>