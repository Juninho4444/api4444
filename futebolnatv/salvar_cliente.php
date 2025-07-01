<?php
// salvar_cliente.php

require_once 'painel_config.php';
session_start();

// Segurança: Apenas clientes logados podem executar esta ação
if (!isset($_SESSION['autenticado']) || $_SESSION['role'] !== 'client' || !isset($_SESSION['key_id'])) {
    http_response_code(403);
    die('Acesso não autorizado.');
}

// Verifica se o formulário foi enviado e se o campo de IP não está vazio
if (isset($_POST['update_ip']) && !empty(trim($_POST['associated_ip']))) {
    
    $db = conectar_db();
    
    $novo_ip = trim($_POST['associated_ip']);
    $id_da_chave = $_SESSION['key_id'];

    try {
        // Prepara e executa a atualização no banco de dados
        $stmt = $db->prepare("UPDATE chaves SET associated_ip = ? WHERE id = ?");
        $stmt->execute([$novo_ip, $id_da_chave]);

        // Define uma mensagem de sucesso na sessão
        $_SESSION['mensagem_cliente'] = "Seu IP autorizado foi atualizado com sucesso para: " . htmlspecialchars($novo_ip);
        $_SESSION['tipo_mensagem_cliente'] = 'sucesso';

    } catch (PDOException $e) {
        // Se houver um erro de banco de dados (ex: o IP já está em uso, caso a restrição UNIQUE exista)
        $_SESSION['mensagem_cliente'] = "Ocorreu um erro ao salvar o IP. Tente novamente.";
        $_SESSION['tipo_mensagem_cliente'] = 'erro';
    }

} else {
    // Se o campo de IP for enviado vazio
    $_SESSION['mensagem_cliente'] = "Erro: O campo de IP não pode estar vazio.";
    $_SESSION['tipo_mensagem_cliente'] = 'erro';
}

// Redireciona de volta para o painel do cliente após a ação
header('Location: client_dashboard.php');
exit;
?>