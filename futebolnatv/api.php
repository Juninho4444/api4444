<?php
// api.php - v11 - Final e Completo com Lógica de IP e Logs Corrigida

header("Content-Type: application/json; charset=utf-8");
require_once 'painel_config.php';

// A conexão com o banco de dados é a primeira coisa a ser feita
try {
    $db = conectar_db();
} catch (PDOException $e) {
    // Erro crítico de DB, não podemos nem logar.
    http_response_code(500);
    echo json_encode(["erro" => "Erro interno do servidor."]);
    exit;
}

// Função para encerrar o script com um erro JSON e registrar o log
function enviarErro($http_code, $mensagem, $log_status, $log_details = []) {
    log_acesso($log_status, $log_details);
    http_response_code($http_code);
    echo json_encode(["erro" => $mensagem]);
    exit;
}

// Função para registrar o acesso no banco de dados
function log_acesso($status, $details) {
    global $db;
    $key_id = $details['key_id'] ?? 0;
    $api_requisitada = $details['api'] ?? 'N/A';
    $client_ip = get_client_ip();
    $token_tentado = $details['token_tentativa'] ?? null;

    // Regra: Não logar sucessos repetidos em menos de 2 horas para o mesmo token
    if ($status === 'Sucesso' && $key_id) {
        $stmt_check = $db->prepare("SELECT access_time FROM api_logs WHERE key_id = ? AND status = 'Sucesso' ORDER BY access_time DESC LIMIT 1");
        $stmt_check->execute([$key_id]);
        if ($ultimo_log = $stmt_check->fetch()) {
            $tempo_desde_ultimo_log = time() - strtotime($ultimo_log['access_time']);
            if ($tempo_desde_ultimo_log < 7200) { // 7200 segundos = 2 horas
                return; // Aborta a função, não registra o log de sucesso repetido.
            }
        }
    }
    
    $stmt_insert = $db->prepare("INSERT INTO api_logs (key_id, api_called, ip_address, access_time, status, token_attempted) VALUES (?, ?, ?, NOW(), ?, ?)");
    $stmt_insert->execute([$key_id, $api_requisitada, $client_ip, $status, $token_tentado]);
}


// --- INÍCIO DA LÓGICA PRINCIPAL ---

$client_token = $_SERVER['HTTP_X_API_KEY'] ?? $_GET['token'] ?? null;
$client_ip = get_client_ip();
$api_requisitada = $_GET['api'] ?? '';

// --- VALIDAÇÃO DO TOKEN (SEMPRE PRIMEIRO) ---
if (empty($client_token)) {
    enviarErro(401, 'Acesso não autorizado. Token de API ausente.', 'Token Ausente', ['api' => $api_requisitada]);
}

$stmt = $db->prepare("SELECT * FROM chaves WHERE token_key = ?");
$stmt->execute([$client_token]);
$chave_data = $stmt->fetch();

// Prepara os detalhes para o log, mesmo que o token seja inválido
$log_details = ['key_id' => $chave_data['id'] ?? 0, 'api' => $api_requisitada, 'token_tentativa' => $client_token];

if (!$chave_data) {
    enviarErro(401, 'Acesso não autorizado. Token inválido.', 'Token Invalido', $log_details);
}

// Se o token é válido, garantimos que o key_id correto será usado em logs futuros
$log_details['key_id'] = $chave_data['id'];

// --- VALIDAÇÃO DO PARÂMETRO 'api' ---
$apis_disponiveis = ['jogos_hoje' => 'jogos_hoje.json', 'jogos_amanha' => 'jogos_amanha.json', 'ufc_eventos' => 'ufc_eventos.json'];
if (empty($api_requisitada) || !array_key_exists($api_requisitada, $apis_disponiveis)) {
    enviarErro(400, 'Requisição inválida. O parâmetro "api" é obrigatório e deve ser um dos seguintes: ' . implode(', ', array_keys($apis_disponiveis)), 'Requisicao Invalida', $log_details);
}

// --- OUTRAS VALIDAÇÕES (IP, Status, Validade, Permissão) ---
if (!empty($chave_data['expiration_date']) && new DateTime() > new DateTime($chave_data['expiration_date'])) {
    if($chave_data['status'] == 1) { // Só atualiza se estava ativo
        $db->prepare("UPDATE chaves SET status = 2 WHERE id = ?")->execute([$chave_data['id']]);
        $log_details['message'] = "Token movido para status 'Expirado'";
    }
    enviarErro(403, 'Acesso negado. Token expirou.', 'Expirado Automaticamente', $log_details);
}

if (!empty($chave_data['associated_ip']) && $chave_data['associated_ip'] !== $client_ip) {
    enviarErro(403, 'Acesso negado. IP não permitido.', 'IP Nao Permitido', $log_details);
}

if ($chave_data['status'] == 0) {
    enviarErro(403, 'Acesso negado. Este token está desativado.', 'Token Desativado', $log_details);
}

if ($chave_data['status'] == 2) {
    enviarErro(403, 'Acesso negado. Este token expirou.', 'Token Expirado', $log_details);
}


$permissoes = json_decode($chave_data['permissions'] ?? '[]', true);
if (!in_array($api_requisitada, $permissoes)) {
    enviarErro(403, 'Acesso negado. Sem permissão para este conteúdo.', 'Permissao Negada', $log_details);
}

// --- SUCESSO ---
log_acesso('Sucesso', $log_details);
$json_file = $apis_disponiveis[$api_requisitada];

if (!file_exists($json_file)) {
    enviarErro(500, "Arquivo de dados '{$json_file}' não encontrado.", 'Erro Interno', $log_details);
}

echo file_get_contents($json_file);
?>