<?php
// --- ARQUIVO DE TESTE: debug_html.php ---
// Objetivo: Ver exatamente o que seu servidor está recebendo do site.

// Define o cabeçalho como texto plano para evitar que o navegador renderize o HTML
header('Content-Type: text/plain; charset=utf-8');

$url = "https://www.futebolnatv.com.br/jogos-hoje/";

echo "Iniciando teste de depuração para a URL: " . $url . "\n";
echo "Horário do teste: " . date('Y-m-d H:i:s') . "\n\n";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
// Usando um User-Agent de um navegador moderno e comum
curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0.0.0 Safari/537.36');
// Define tempos de espera para evitar que o script fique preso
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
curl_setopt($ch, CURLOPT_TIMEOUT, 15);
// Algumas hospedagens precisam disso para verificar certificados SSL
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);


$html = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

echo "--- INFORMAÇÕES DE DEPURAÇÃO ---\n\n";
echo "Código de Status HTTP: " . $http_code . "\n";
echo "Erro do cURL (se houver): " . ($error ?: 'Nenhum') . "\n\n";
echo "--- CONTEÚDO HTML RECEBIDO (Abaixo desta linha) ---\n\n";

// Imprime o HTML recebido
if ($html === false) {
    echo "Falha ao obter o HTML.";
} else if (empty($html)) {
    echo "O HTML recebido está VAZIO.";
}
else {
    echo $html;
}

?>