<?php
// get_admin_logs.php - Endpoint para o painel de admin buscar todos os logs com filtros

require_once 'painel_config.php';
session_start();

// Segurança: Apenas admins podem usar este endpoint
if (!isset($_SESSION['autenticado']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['erro' => 'Acesso não autorizado.']);
    exit;
}

header('Content-Type: application/json');
$db = conectar_db();

// Pegando os filtros da requisição
$filter_token = $_GET['token'] ?? '';
$filter_ip = $_GET['ip'] ?? '';

// --- Query para Logs de Sucesso ---
$params_sucesso = [];
$sql_sucesso = "
    SELECT l.*, c.owner_name 
    FROM api_logs l 
    LEFT JOIN chaves c ON l.key_id = c.id 
    WHERE l.status = 'Sucesso'
";

if (!empty($filter_ip)) {
    $sql_sucesso .= " AND l.ip_address LIKE ?";
    $params_sucesso[] = "%$filter_ip%";
}
if (!empty($filter_token)) {
    $sql_sucesso .= " AND c.token_key LIKE ?";
    $params_sucesso[] = "%$filter_token%";
}
$sql_sucesso .= " ORDER BY l.access_time DESC LIMIT 30";

$stmt_sucesso = $db->prepare($sql_sucesso);
$stmt_sucesso->execute($params_sucesso);
$logs_sucesso = $stmt_sucesso->fetchAll();


// --- Query para Logs de Falha ---
$params_falha = [];
$sql_falha = "
    SELECT l.*, c.owner_name 
    FROM api_logs l 
    LEFT JOIN chaves c ON l.key_id = c.id 
    WHERE l.status != 'Sucesso'
";

if (!empty($filter_ip)) {
    $sql_falha .= " AND l.ip_address LIKE ?";
    $params_falha[] = "%$filter_ip%";
}
if (!empty($filter_token)) {
    // Para falhas, o key_id pode ser 0 se o token for inválido, então a busca é diferente
    $sub_stmt = $db->prepare("SELECT id FROM chaves WHERE token_key LIKE ?");
    $sub_stmt->execute(["%$filter_token%"]);
    $key_ids = $sub_stmt->fetchAll(PDO::FETCH_COLUMN);
    if (!empty($key_ids)) {
        $placeholders = implode(',', array_fill(0, count($key_ids), '?'));
        $sql_falha .= " AND l.key_id IN ($placeholders)";
        $params_falha = array_merge($params_falha, $key_ids);
    } else {
        // Se o token filtrado não existe, não trará nenhum log de falha associado
         $sql_falha .= " AND 1=0";
    }
}
$sql_falha .= " ORDER BY l.access_time DESC LIMIT 30";

$stmt_falha = $db->prepare($sql_falha);
$stmt_falha->execute($params_falha);
$logs_falha = $stmt_falha->fetchAll();


// Retorna um JSON com as duas listas
echo json_encode([
    'sucesso' => $logs_sucesso,
    'falha'   => $logs_falha
]);

?>