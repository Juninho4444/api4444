<?php
// Versão Refatorada e Completa - FUTEBOL
require_once 'helper_scraper.php';

function log_error_futebol($message) {
    $logFile = 'api_error_futebol.log';
    $timestamp = date('Y-m-d H:i:s');
    $logEntry = "[{$timestamp}] {$message}" . PHP_EOL;
    file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
}

// Define a URL base para este scraper
$baseUrlFutebol = 'https://www.futebolnatv.com.br';

$resultado = executar_scrape(
    $baseUrlFutebol . '/jogos-amanha/',
    '.gamecard'
);

if ($resultado === false) {
    log_error_futebol("O serviço de scraping (Trabalhador) falhou.");
    echo "Falha ao executar o scrape para Futebol.";
    exit;
}

$html = $resultado['html'];
$doc = new DOMDocument();
@$doc->loadHTML('<?xml encoding="UTF-8">' . $html);
$xpath = new DOMXPath($doc);

$jogos = [];
$gameCards = $xpath->query('//div[contains(@class, "gamecard")]');

foreach ($gameCards as $card) {
    if ($xpath->query('.//ins[contains(@class, "adsbygoogle")]', $card)->length > 0) continue;

    $timeCasaNode = $xpath->query('.//div[contains(@class, "endgame")]/div[1]/span[1]', $card)->item(0);
    $timeVisitanteNode = $xpath->query('.//div[contains(@class, "endgame")]/div[2]/span[1]', $card)->item(0);

    if ($timeCasaNode && $timeVisitanteNode) {
        $campeonatoNode = $xpath->query('.//div[contains(@class, "all-scores-widget-competition-header-container-hora")]/div/b', $card)->item(0);
        $imgCompeticaoNode = $xpath->query('.//div[contains(@class, "all-scores-widget-competition-header-container-hora")]//img', $card)->item(0);
        $imgTime1Node = $xpath->query('.//div[contains(@class, "endgame")]/div[1]//img', $card)->item(0);
        $imgTime2Node = $xpath->query('.//div[contains(@class, "endgame")]/div[2]//img', $card)->item(0);
        $placarTime1Node = $xpath->query('.//div[contains(@class, "endgame")]/div[1]/span[2]', $card)->item(0);
        $placarTime2Node = $xpath->query('.//div[contains(@class, "endgame")]/div[2]/span[2]', $card)->item(0);
        $canaisNodes = $xpath->query('.//div[contains(@class, "bcmact")]', $card);
        
        $status = '';
        $destaque = false;
        $statusContainerNode = $xpath->query('.//div[contains(@class, "cardtime")]', $card)->item(0);
        if ($statusContainerNode) {
            $statusText = trim($statusContainerNode->nodeValue);
            if (stripos($statusText, 'FIM') !== false) $status = 'FIM DE JOGO';
            elseif (stripos($statusText, 'INT') !== false) $status = 'INTERVALO';
            elseif (preg_match("/(\d{1,2}'(\s*\+\s*\d{1,2})?)/", $statusText, $matches)) $status = str_replace(' ', '', $matches[0]);
            
            if (stripos($statusText, 'Destaque') !== false) {
                $destaque = true;
                if (empty($status)) $status = 'Destaque';
            }
        }
        
        $horarioRawNode = $xpath->query('.//div[contains(@class, "box_time_live")]', $card)->item(0);
        $horario = 'Não informado';
        if ($horarioRawNode) {
            preg_match('/(\d{2}:\d{2})/', $horarioRawNode->nodeValue, $horarioMatches);
            $horario = !empty($horarioMatches[1]) ? $horarioMatches[1] : 'Não informado';
        }

        $canais = [];
        foreach ($canaisNodes as $canalNode) {
            $nomeCanal = trim(preg_replace('/\s+/', ' ', $canalNode->nodeValue));
            $imgCanalNode = $xpath->query('.//img', $canalNode)->item(0);
            $imgUrl = $imgCanalNode ? to_absolute_url($imgCanalNode->getAttribute('data-src') ?: $imgCanalNode->getAttribute('src'), $baseUrlFutebol) : '';
            if (!empty($nomeCanal)) $canais[] = ["img_url" => $imgUrl, "nome" => $nomeCanal];
        }

        $jogos[] = [
            "canais" => $canais, "competicao" => $campeonatoNode ? trim($campeonatoNode->nodeValue) : 'Não informado',
            "data_jogo" => "hoje", "destaque" => $destaque, "horario" => $horario,
            "img_competicao_url" => $imgCompeticaoNode ? to_absolute_url($imgCompeticaoNode->getAttribute('data-src') ?: $imgCompeticaoNode->getAttribute('src'), $baseUrlFutebol) : '',
            "img_time1_url" => $imgTime1Node ? to_absolute_url($imgTime1Node->getAttribute('data-src') ?: $imgTime1Node->getAttribute('src'), $baseUrlFutebol) : '',
            "img_time2_url" => $imgTime2Node ? to_absolute_url($imgTime2Node->getAttribute('data-src') ?: $imgTime2Node->getAttribute('src'), $baseUrlFutebol) : '',
            "placar_time1" => $placarTime1Node ? trim($placarTime1Node->nodeValue) : '',
            "placar_time2" => $placarTime2Node ? trim($placarTime2Node->nodeValue) : '',
            "status" => $status,
            "time1" => trim($timeCasaNode->nodeValue), "time2" => trim($timeVisitanteNode->nodeValue)
        ];
    }
}

if (empty($jogos)) {
    log_error_futebol("Nenhum jogo de FUTEBOL foi encontrado.");
}

$nomeDoArquivo = 'jogos_amanha.json';
$jsonData = json_encode($jogos, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

if (file_put_contents($nomeDoArquivo, $jsonData) !== false) {
    $formattedDuration = number_format($resultado['duration'], 2, ',', '.');
    echo "Arquivo '{$nomeDoArquivo}' foi salvo com sucesso! Tempo total da operação: {$formattedDuration} segundos.";
} else {
    log_error_futebol("ERRO FATAL: Não foi possível salvar o arquivo JSON.");
    echo "ERRO FATAL FUTEBOL: Não foi possível salvar o arquivo.";
}
?>