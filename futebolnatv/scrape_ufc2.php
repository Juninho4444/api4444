<?php

// Versão UFC v5 - Correção definitiva na extração do Título e Luta Principal

function log_error($message) {
    $logFile = 'api_error_ufc.log';
    $timestamp = date('Y-m-d H:i:s');
    $logEntry = "[" . $timestamp . "] " . $message . PHP_EOL;
    file_put_contents($logFile, $logEntry, FILE_APPEND);
}

function to_absolute_url($url) {
    if (empty($url) || substr($url, 0, 4) === 'http') {
        return $url;
    }
    return 'https://www.ufc.com.br' . $url;
}

function obterLutasUFC() {
    
    $urlDoSeuServicoDeScraping = 'http://136.248.89.161:3000/scrape';
    $chaveSecreta = 'chave-muito-secreta-que-so-voce-sabe';
    
    $urlParaExtrair = 'https://www.ufc.com.br/events';
    
    $apiParams = [
        'url' => $urlParaExtrair,
        'waitFor' => 'article.c-card-event--result'
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
        $errorMessage = "O serviço de scraping (Trabalhador) falhou ao buscar dados do UFC. Código HTTP: " . $http_code;
        log_error($errorMessage);
        die(json_encode(["erro" => $errorMessage]));
    }
    
    $doc = new DOMDocument();
    libxml_use_internal_errors(true);
    $doc->loadHTML('<?xml encoding="UTF-8">' . $html_final);
    libxml_clear_errors();
    $xpath = new DOMXPath($doc);

    $eventos = [];
    $eventCards = $xpath->query('//details[@id="events-list-upcoming"]//article[contains(@class, "c-card-event--result")]');
    
    foreach ($eventCards as $card) {
        $headlineNode = $xpath->query('.//h3[contains(@class, "c-card-event--result__headline")]/a', $card)->item(0);
        if (!$headlineNode) continue;

        // ===================================================================
        // LÓGICA DE TÍTULO E LUTA PRINCIPAL CORRIGIDA
        // ===================================================================
        $link_evento = to_absolute_url($headlineNode->getAttribute('href'));
        $subtitulo_evento = trim($headlineNode->nodeValue);
        
        $titulo_evento = 'UFC'; // Padrão
        if (strpos($link_evento, 'ufc-fight-night') !== false) {
            $titulo_evento = 'UFC Fight Night';
        } elseif (preg_match('/ufc-(\d+)/', $link_evento, $matches)) {
            $titulo_evento = 'UFC ' . $matches[1];
        }

        // Cria uma string de comparação para encontrar a luta principal no card
        $luta_principal_label_comparacao = str_ireplace(' x ', ' vs ', $subtitulo_evento);
        // ===================================================================

        $dateNodeParent = $xpath->query('.//div[contains(@class, "c-card-event--result__date")]', $card)->item(0);
        $data_completa_str = $dateNodeParent ? $dateNodeParent->getAttribute('data-main-card') : ' / ';
        list($data_str, $hora_str) = array_pad(explode(' / ', $data_completa_str), 2, '');
        
        $data_formatada = '';
        if(trim($data_str) != '') {
            $data_formatada = substr(trim($data_str), 0, 5);
        }

        $luta_principal_obj = null;
        $card_principal = [];
        $card_preliminar = [];
        
        $fightItems = $xpath->query('.//div[contains(@class, "c-carousel__item")]', $card);
        foreach ($fightItems as $item) {
            $fightCardNode = $xpath->query('.//div[contains(@class, "fight-card-tickets")]', $item)->item(0);
            if (!$fightCardNode) continue;

            $luta_label = $fightCardNode->getAttribute('data-fight-label');
            $card_tipo = $fightCardNode->getAttribute('data-fight-card-name');
            
            list($lutador1_nome, $lutador2_nome) = array_pad(explode(' vs ', $luta_label), 2, 'A definir');

            $img_lutador1_node = $xpath->query('.//div[contains(@class, "field--name-red-corner")]//img', $item)->item(0);
            $img_lutador2_node = $xpath->query('.//div[contains(@class, "field--name-blue-corner")]//img', $item)->item(0);
            
            $luta_obj = [
                'luta' => $luta_label,
                'lutadores' => [
                    'canto_vermelho' => ['nome' => trim($lutador1_nome), 'imagem' => $img_lutador1_node ? to_absolute_url($img_lutador1_node->getAttribute('src')) : ''],
                    'canto_azul' => ['nome' => trim($lutador2_nome), 'imagem' => $img_lutador2_node ? to_absolute_url($img_lutador2_node->getAttribute('src')) : '']
                ]
            ];

            // Compara o label da luta com o subtítulo do evento para definir a luta principal
            if (strcasecmp($luta_label, $luta_principal_label_comparacao) == 0) {
                $luta_principal_obj = $luta_obj;
            }

            // Adiciona ao card apropriado
            if ($card_tipo === 'Card Principal' || $card_tipo === 'Main Card') {
                $card_principal[] = $luta_obj;
            } elseif ($card_tipo === 'Preliminares' || $card_tipo === 'Prelims' || $card_tipo === 'Early Prelims') {
                $card_preliminar[] = $luta_obj;
            }
        }
        
        $localNode = $xpath->query('.//div[contains(@class, "c-card-event--result__location")]', $card)->item(0);

        $eventos[] = [
            "titulo_evento" => $titulo_evento,
            "subtitulo_evento" => $subtitulo_evento,
            "link_evento" => $link_evento,
            "data" => $data_formatada,
            "hora" => trim(explode(' ', $hora_str)[0]),
            "timestamp_card_principal" => $dateNodeParent ? $dateNodeParent->getAttribute('data-main-card-timestamp') : '',
            "local" => [
                'arena' => $localNode ? trim($xpath->query('.//h5', $localNode)->item(0)->nodeValue ?? '') : '',
                'localidade' => $localNode ? trim($xpath->query('.//span[contains(@class, "locality")]', $localNode)->item(0)->nodeValue ?? '') : '',
                'pais' => $localNode ? trim($xpath->query('.//span[contains(@class, "country")]', $localNode)->item(0)->nodeValue ?? '') : ''
            ],
            "luta_principal" => $luta_principal_obj,
            "card_principal" => $card_principal,
            "card_preliminar" => $card_preliminar
        ];
    }

    if (empty($eventos)) {
        log_error("Nenhum evento do UFC foi encontrado. A estrutura do site pode ter mudado.");
    }
    
    return json_encode(["eventos" => $eventos], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}

// --- LÓGICA FINAL ---
$startTime = microtime(true);
$jsonData = obterLutasUFC();
$endTime = microtime(true);
$duration = $endTime - $startTime;
$nomeDoArquivo = 'ufc_eventos.json';

if (file_put_contents($nomeDoArquivo, $jsonData) !== false) {
    header('Content-Type: application/json; charset=utf-8');
    $dadosArray = json_decode($jsonData, true);
    echo json_encode([
        "status" => "sucesso",
        "mensagem" => "Arquivo '{$nomeDoArquivo}' foi salvo com sucesso!",
        "tempo_execucao_segundos" => number_format($duration, 2),
        "eventos_encontrados" => count($dadosArray['eventos'] ?? [])
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
} else {
    $errorMessage = "ERRO FATAL UFC: Não foi possível salvar o arquivo '{$nomeDoArquivo}'. Verifique as permissões de escrita da pasta.";
    log_error($errorMessage);
    header('Content-Type: application/json; charset=utf-8', true, 500);
    echo json_encode(["erro" => $errorMessage]);
}

?>