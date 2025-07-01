<?php
// helper_scraper.php - v2.1 Final

/**
 * Converte uma URL relativa em absoluta.
 * @param string $relative_url A URL que pode ser relativa (ex: /img/logo.png).
 * @param string $base_url A URL base do site (ex: https://www.futebolnatv.com.br).
 * @return string A URL absoluta e completa.
 */
function to_absolute_url($relative_url, $base_url) {
    if (empty($relative_url) || substr($relative_url, 0, 4) === 'http') {
        return $relative_url;
    }
    // Garante que a base_url não tenha uma / no final e a url relativa não tenha no começo
    return rtrim($base_url, '/') . '/' . ltrim($relative_url, '/');
}

/**
 * Função central para executar o scrape via servidor trabalhador.
 * @param string $url_alvo - A URL do site que queremos extrair.
 * @param string $seletor_de_espera - O seletor CSS que o Puppeteer deve esperar.
 * @return array|false - Retorna um array com ['html' => ..., 'duration' => ...] em sucesso, ou false em erro.
 */
function executar_scrape($url_alvo, $seletor_de_espera) {
    // ===================================================================
    // CONFIGURAÇÃO CENTRALIZADA - Altere as duas linhas abaixo
    // ===================================================================
    $urlDoSeuServicoDeScraping = 'https://api-craper-tra.lenwap.easypanel.host/scrape';
    $chaveSecreta = 'chave-muito-secreta-que-so-voce-sabe';
    // ===================================================================

    $apiParams = [
        'url' => $url_alvo,
        'waitFor' => $seletor_de_espera
    ];
    $apiUrl = $urlDoSeuServicoDeScraping . '?' . http_build_query($apiParams);

    $startTime = microtime(true);

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $apiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 90);  
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['X-API-Key: ' . $chaveSecreta]);
    $html_final = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    $endTime = microtime(true);
    $duration = $endTime - $startTime;

    if ($http_code != 200 || $html_final === false) {
        return false;
    }

    return [
        'html' => $html_final,
        'duration' => $duration
    ];
}
?>