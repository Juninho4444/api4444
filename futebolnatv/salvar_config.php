<?php
// salvar_config.php
session_start();

// Segurança: Garante que apenas usuários logados possam salvar
if (!isset($_SESSION['autenticado']) || $_SESSION['autenticado'] !== true) {
    http_response_code(403);
    die('Acesso não autorizado.');
}

$configFile = 'config.json';
$config = json_decode(file_get_contents($configFile), true);

// Lógica para ADICIONAR itens
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Adicionar Token
    if (!empty($_POST['add_token'])) {
        $novo_token = trim($_POST['add_token']);
        if (!in_array($novo_token, $config['allowed_tokens'])) {
            $config['allowed_tokens'][] = $novo_token;
        }
    }
    // Adicionar IP
    if (!empty($_POST['add_ip'])) {
        $novo_ip = trim($_POST['add_ip']);
        if (!in_array($novo_ip, $config['allowed_ips'])) {
            $config['allowed_ips'][] = $novo_ip;
        }
    }
    // Adicionar Domínio
    if (!empty($_POST['add_domain'])) {
        $novo_dominio = trim($_POST['add_domain']);
        if (!in_array($novo_dominio, $config['allowed_domains'])) {
            $config['allowed_domains'][] = $novo_dominio;
        }
    }

    // Lógica para REMOVER itens
    // Remover Token
    if (!empty($_POST['remover_token'])) {
        $token_a_remover = $_POST['remover_token'];
        $config['allowed_tokens'] = array_values(array_diff($config['allowed_tokens'], [$token_a_remover]));
    }
    // Remover IP
    if (!empty($_POST['remover_ip'])) {
        $ip_a_remover = $_POST['remover_ip'];
        $config['allowed_ips'] = array_values(array_diff($config['allowed_ips'], [$ip_a_remover]));
    }
    // Remover Domínio
    if (!empty($_POST['remover_domain'])) {
        $dominio_a_remover = $_POST['remover_domain'];
        $config['allowed_domains'] = array_values(array_diff($config['allowed_domains'], [$dominio_a_remover]));
    }

    // Salva o arquivo config.json atualizado
    file_put_contents($configFile, json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

// Redireciona de volta para o painel
header('Location: painel.php');
exit;
?>