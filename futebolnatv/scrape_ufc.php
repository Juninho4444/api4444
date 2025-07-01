<?php
// Versão Refatorada e Completa - UFC
require_once 'helper_scraper.php';

function log_error_ufc($message) {
    $logFile = 'api_error_ufc.log';
    $timestamp = date('Y-m-d H:i:s');
    $logEntry = "[{$timestamp}] {$message}" . PHP_EOL;
    file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
}

// Define a URL base para este scraper
$baseUrlUFC = 'https://www.ufc.com.br';

$resultado = executar_scrape(
    $baseUrlUFC . '/events',
    'article.c-card-event--result'
);

if ($resultado === false) {
    log_error_ufc("O serviço de scraping (Trabalhador) falhou.");
    echo "Falha ao executar o scrape para UFC.";
    exit;
}

$html = $resultado['html'];
$doc = new DOMDocument();
@$doc->loadHTML('<?xml encoding="UTF-8">' . $html);
$xpath = new DOMXPath($doc);

$eventos = [];
$eventCards = $xpath->query('//details[@id="events-list-upcoming"]//article[contains(@class, "c-card-event--result")]');

foreach ($eventCards as $card) {
    $headlineNode = $xpath->query('.//h3[contains(@class, "c-card-event--result__headline")]/a', $card)->item(0);
    if (!$headlineNode) continue;
    
    $link_evento = to_absolute_url($headlineNode->getAttribute('href'), $baseUrlUFC);
    $subtitulo_evento = trim($headlineNode->nodeValue);
    
    $titulo_evento = 'UFC';
    if (strpos($link_evento, 'ufc-fight-night') !== false) {
        $titulo_evento = 'UFC Fight Night';
    } elseif (preg_match('/ufc-(\d+)/', $link_evento, $matches)) {
        $titulo_evento = 'UFC ' . $matches[1];
    }
    $luta_principal_label_comparacao = str_ireplace(' x ', ' vs ', $subtitulo_evento);

    $dateNodeParent = $xpath->query('.//div[contains(@class, "c-card-event--result__date")]', $card)->item(0);
    $data_completa_str = $dateNodeParent ? $dateNodeParent->getAttribute('data-main-card') : ' / ';
    list($data_str, $hora_str) = array_pad(explode(' / ', $data_completa_str), 2, '');
    $data_formatada = substr(trim($data_str), 0, 5);

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
                'canto_vermelho' => ['nome' => trim($lutador1_nome), 'imagem' => $img_lutador1_node ? to_absolute_url($img_lutador1_node->getAttribute('src'), $baseUrlUFC) : ''],
                'canto_azul' => ['nome' => trim($lutador2_nome), 'imagem' => $img_lutador2_node ? to_absolute_url($img_lutador2_node->getAttribute('src'), $baseUrlUFC) : '']
            ]
        ];

        if (strcasecmp($luta_label, $luta_principal_label_comparacao) == 0) {
            $luta_principal_obj = $luta_obj;
        }

        if ($card_tipo === 'Card Principal' || $card_tipo === 'Main Card') {
             $card_principal[] = $luta_obj;
        } elseif ($card_tipo === 'Preliminares' || $card_tipo === 'Prelims' || $card_tipo === 'Early Prelims') {
             $card_preliminar[] = $luta_obj;
        }
    }
    
    $localNode = $xpath->query('.//div[contains(@class, "c-card-event--result__location")]', $card)->item(0);
    
    $eventos[] = [
        "titulo_evento" => $titulo_evento, "subtitulo_evento" => $subtitulo_evento,
        "link_evento" => $link_evento, "data" => $data_formatada, "hora" => trim(explode(' ', $hora_str)[0]),
        "timestamp_card_principal" => $dateNodeParent ? $dateNodeParent->getAttribute('data-main-card-timestamp') : '',
        "local" => [
            'arena' => $localNode ? trim($xpath->query('.//h5', $localNode)->item(0)->nodeValue ?? '') : '',
            'localidade' => $localNode ? trim($xpath->query('.//span[contains(@class, "locality")]', $localNode)->item(0)->nodeValue ?? '') : '',
            'pais' => $localNode ? trim($xpath->query('.//span[contains(@class, "country")]', $localNode)->item(0)->nodeValue ?? '') : ''
        ],
        "luta_principal" => $luta_principal_obj, "card_principal" => $card_principal, "card_preliminar" => $card_preliminar
    ];
}

if (empty($eventos)) {
    log_error_ufc("Nenhum evento do UFC foi encontrado.");
}

$nomeDoArquivo = 'ufc_eventos.json';
$jsonData = json_encode(['eventos' => $eventos], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

if (file_put_contents($nomeDoArquivo, $jsonData) !== false) {
    $formattedDuration = number_format($resultado['duration'], 2, ',', '.');
    echo "Arquivo '{$nomeDoArquivo}' foi salvo com sucesso! Tempo total da operação: {$formattedDuration} segundos.";
} else {
    log_error_ufc("ERRO FATAL: Não foi possível salvar o arquivo JSON.");
    echo "ERRO FATAL UFC: Não foi possível salvar o arquivo.";
}
?>