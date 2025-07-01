<?php
// get_client_logs.php - Endpoint seguro para buscar logs do cliente logado

require_once 'painel_config.php';
session_start();

// Segurança: Garante que apenas um cliente logado possa acessar seus próprios logs
if (!isset($_SESSION['autenticado']) || $_SESSION['role'] !== 'client' || !isset($_SESSION['key_id'])) {
    http_response_code(403);
    echo json_encode(['erro' => 'Acesso não autorizado.']);
    exit;
}

header('Content-Type: application/json');

$db = conectar_db();

// Pega os parâmetros do filtro da URL, com valores padrão seguros
$key_id = $_SESSION['key_id'];
$status_filter = $_GET['status'] ?? 'todos'; // 'todos', 'sucesso', 'falha'
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
$limit = max(10, min(500, $limit)); // Limita entre 10 e 500 para segurança

$params = [$key_id];

// Constrói a query SQL dinamicamente
$sql = "SELECT * FROM api_logs WHERE key_id = ?";

if ($status_filter === 'sucesso') {
    $sql .= " AND status = ?";
    $params[] = 'Sucesso';
} elseif ($status_filter === 'falha') {
    $sql .= " AND status != ?";
    $params[] = 'Sucesso';
}

$sql .= " ORDER BY access_time DESC LIMIT ?";
$params[] = $limit;

$stmt = $db->prepare($sql);
$stmt->execute($params);
$logs = $stmt->fetchAll();

echo json_encode($logs);
?>