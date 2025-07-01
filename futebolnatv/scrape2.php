<?php

// Versão Final - Arquitetura de Microserviços
// Este é o script "Maestro" que chama o serviço "Trabalhador"

/**
 * Registra uma mensagem de erro no arquivo de log.
 * @param string $message A mensagem a ser registrada.
 */
function log_error($message) {
    $logFile = 'api_error.log';
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
function obterJogosComServicoProprio() {
    
    // ===================================================================
    // CONFIGURAÇÃO - Altere as duas linhas abaixo
    // ===================================================================
    $urlDoSeuServicoDeScraping = 'http://136.248.89.161:3000/scrape';
    $chaveSecreta = 'chave-muito-secreta-que-so-voce-sabe';
    // ===================================================================
    
    $urlParaExtrair = 'https://www.futebolnatv.com.br/jogos-hoje/';
    
    // Monta a URL completa para o seu serviço
    $apiUrl = $urlDoSeuServicoDeScraping . '?url=' . urlencode($urlParaExtrair);

    // --- Fazendo a chamada cURL para o SEU PRÓPRIO SERVIÇO ---
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $apiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 90); 
    // Envia a chave secreta no cabeçalho para autenticação
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'X-API-Key: ' . $chaveSecreta
    ]);

    $html_final = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    // Verifica se o nosso próprio serviço de scraping respondeu corretamente
    if ($http_code != 200 || $html_final === false) {
        $errorMessage = "O serviço de scraping (Trabalhador) falhou ou não foi encontrado. Código HTTP: " . $http_code;
        log_error($errorMessage);
        die(json_encode(["erro" => $errorMessage]));
    }
    
    // --- Análise do HTML recebido do nosso serviço ---
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
$dataA = date('Y-m-d H:i:s');
$jsonData = obterJogosComServicoProprio();
$nomeDoArquivo = 'jogos_hoje.json';

if (file_put_contents($nomeDoArquivo, $jsonData) !== false) {
    echo "Arquivo '{$nomeDoArquivo}' foi salvo com sucesso! '{$dataA}";
} else {
    $errorMessage = "ERRO FATAL: Não foi possível salvar o arquivo '{$nomeDoArquivo}'. Verifique as permissões de escrita da pasta.";
    log_error($errorMessage);
    echo $errorMessage;
}
?>