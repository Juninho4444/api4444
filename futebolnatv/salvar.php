<?php
// salvar.php - v12 - Validação de campos obrigatórios

require_once 'painel_config.php';
session_start();

if (!isset($_SESSION['autenticado']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    die('Acesso não autorizado.');
}

$db = conectar_db();

// --- LÓGICA PARA ADICIONAR NOVA CHAVE ---
if (isset($_POST['add_key'])) {
    if (!empty($_POST['owner_name']) && !empty($_POST['associated_ip']) && !empty($_POST['expiration_date'])) {
        $token = 'tok_' . bin2hex(random_bytes(20));
        $owner = trim($_POST['owner_name']);
        $ip = trim($_POST['associated_ip']);
        $exp_date = trim($_POST['expiration_date']);
        $exp_date_db = $exp_date . ' 23:59:59';
        $creation_date = date('Y-m-d H:i:s');
        $permissoes = isset($_POST['permissions']) ? json_encode($_POST['permissions']) : json_encode([]);
        
        $client_username = !empty(trim($_POST['client_username'])) ? trim($_POST['client_username']) : null;
        $client_password = $_POST['client_password'] ?? null;
        $client_password_hash = null;
        if ($client_username && $client_password) {
            $client_password_hash = password_hash($client_password, PASSWORD_DEFAULT);
        }
        
        try {
            $stmt = $db->prepare(
                "INSERT INTO chaves (token_key, owner_name, associated_ip, creation_date, expiration_date, status, permissions, client_username, client_password_hash) 
                 VALUES (?, ?, ?, ?, ?, 1, ?, ?, ?)"
            );
            $stmt->execute([$token, $owner, $ip, $creation_date, $exp_date_db, $permissoes, $client_username, $client_password_hash]);
            
            $_SESSION['nova_chave_criada'] = [
                'owner_name' => $owner, 'token_key' => $token,
                'associated_ip' => $ip, 'expiration_date' => $exp_date,
                'client_username' => $client_username, 'client_password' => $client_password
            ];

        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                $mensagem_erro = "Ocorreu um erro de duplicidade. O IP ou o Nome de Usuário do Cliente já podem estar em uso.";
                 if (strpos($e->getMessage(), 'associated_ip') !== false) {
                     $mensagem_erro = "Erro: O endereço de IP '{$ip}' já está em uso por outra chave.";
                 } elseif (strpos($e->getMessage(), 'client_username') !== false) {
                     $mensagem_erro = "Erro: O nome de usuário '{$client_username}' já está em uso.";
                 }
                $_SESSION['mensagem'] = $mensagem_erro;
                $_SESSION['tipo_mensagem'] = 'erro';
            } else {
                $_SESSION['mensagem'] = "Erro de banco de dados: " . $e->getMessage();
                $_SESSION['tipo_mensagem'] = 'erro';
            }
        }
    } else {
        $_SESSION['mensagem'] = "Erro: Nome, IP e Validade são obrigatórios para adicionar uma nova chave.";
        $_SESSION['tipo_mensagem'] = 'erro';
    }
}

// --- LÓGICA PARA ATUALIZAR UMA CHAVE EXISTENTE ---
if (isset($_POST['update_key']) && !empty($_POST['id'])) {
    $id = $_POST['id'];
    $owner = trim($_POST['owner_name']);
    $ip = trim($_POST['associated_ip']);
    $exp_date = trim($_POST['expiration_date']);
    $permissoes = isset($_POST['permissions']) ? json_encode($_POST['permissions']) : json_encode([]);

    if (empty($owner) || empty($ip) || empty($exp_date)) {
        $_SESSION['mensagem'] = "Erro: Ao editar, os campos Nome, IP e Validade não podem ficar vazios.";
        $_SESSION['tipo_mensagem'] = 'erro';
    } else {
        $exp_date_db = $exp_date . ' 23:59:59';
        $stmt = $db->prepare("UPDATE chaves SET owner_name = ?, associated_ip = ?, expiration_date = ?, permissions = ? WHERE id = ?");
        $stmt->execute([$owner, $ip, $exp_date_db, $permissoes, $id]);
        $_SESSION['mensagem'] = "Chave de '{$owner}' atualizada!";
        $_SESSION['tipo_mensagem'] = 'sucesso';
    }
}

// --- LÓGICA PARA ATUALIZAR LOGIN DO CLIENTE ---
if (isset($_POST['update_client_login']) && !empty($_POST['id'])) {
    $id = $_POST['id'];
    $novo_username = trim($_POST['client_username']);
    $nova_senha = $_POST['client_password'];
    
    if (empty($novo_username)) {
        $_SESSION['mensagem'] = "Erro: O nome de usuário do cliente não pode ser vazio.";
        $_SESSION['tipo_mensagem'] = 'erro';
    } else {
        try {
            if (!empty($nova_senha)) {
                $novo_hash = password_hash($nova_senha, PASSWORD_DEFAULT);
                $stmt = $db->prepare("UPDATE chaves SET client_username = ?, client_password_hash = ? WHERE id = ?");
                $stmt->execute([$novo_username, $novo_hash, $id]);
            } else {
                $stmt = $db->prepare("UPDATE chaves SET client_username = ? WHERE id = ?");
                $stmt->execute([$novo_username, $id]);
            }
            $_SESSION['mensagem'] = "Login do cliente atualizado com sucesso!";
            $_SESSION['tipo_mensagem'] = 'sucesso';
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                $_SESSION['mensagem'] = "Erro: Este nome de usuário já está em uso por outra chave.";
                $_SESSION['tipo_mensagem'] = 'erro';
            } else {
                $_SESSION['mensagem'] = "Erro de banco de dados ao atualizar login.";
                $_SESSION['tipo_mensagem'] = 'erro';
            }
        }
    }
}

// --- LÓGICA PARA ATIVAR/DESATIVAR ---
if (isset($_POST['toggle_status']) && !empty($_POST['id'])) {
    $id = $_POST['id'];
    $stmt = $db->prepare("UPDATE chaves SET status = CASE WHEN status = 1 THEN 0 ELSE 1 END WHERE id = ?");
    $stmt->execute([$id]);
    $_SESSION['mensagem'] = "Status da chave alterado!";
    $_SESSION['tipo_mensagem'] = 'sucesso';
}

// --- LÓGICA PARA RENOVAR CHAVE ---
if (isset($_POST['renew_key']) && !empty($_POST['id'])) {
    $id = $_POST['id'];
    $stmt = $db->prepare("SELECT expiration_date, status FROM chaves WHERE id = ?");
    $stmt->execute([$id]);
    $chave_atual = $stmt->fetch();
    $validade_atual = $chave_atual['expiration_date'];

    $base_para_renovacao = (!empty($validade_atual) && new DateTime($validade_atual) > new DateTime())
        ? $validade_atual : 'now';
    
    $nova_validade = date('Y-m-d H:i:s', strtotime($base_para_renovacao . ' +1 month'));

    $update_stmt = $db->prepare("UPDATE chaves SET status = 1, expiration_date = ? WHERE id = ?");
    $update_stmt->execute([$nova_validade, $id]);
    
    $_SESSION['mensagem'] = "Chave renovada! Nova validade: " . date('d/m/Y', strtotime($nova_validade));
    $_SESSION['tipo_mensagem'] = 'sucesso';
}

// --- LÓGICA PARA REMOVER ---
if (isset($_POST['delete_key']) && !empty($_POST['id'])) {
    $id = $_POST['id'];
    $stmt = $db->prepare("DELETE FROM chaves WHERE id = ?");
    $stmt->execute([$id]);
    $_SESSION['mensagem'] = "Chave removida com sucesso!";
    $_SESSION['tipo_mensagem'] = 'sucesso';
}

// --- LÓGICA PARA ALTERAR SENHA DO ADMIN ---
if (isset($_POST['change_password'])) {
    $senha_antiga = $_POST['senha_antiga'] ?? '';
    $nova_senha = $_POST['nova_senha'] ?? '';
    $confirmar_nova_senha = $_POST['confirmar_nova_senha'] ?? '';

    $stmt = $db->prepare("SELECT password_hash FROM admin_users WHERE username = ?");
    $stmt->execute([$_SESSION['username']]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($senha_antiga, $user['password_hash'])) {
        $_SESSION['mensagem'] = "Erro: A senha atual está incorreta.";
        $_SESSION['tipo_mensagem'] = 'erro';
    } elseif (empty($nova_senha) || $nova_senha !== $confirmar_nova_senha) {
        $_SESSION['mensagem'] = "Erro: A nova senha e a confirmação não correspondem.";
        $_SESSION['tipo_mensagem'] = 'erro';
    } else {
        $novo_hash = password_hash($nova_senha, PASSWORD_DEFAULT);
        $update_stmt = $db->prepare("UPDATE admin_users SET password_hash = ? WHERE username = ?");
        $update_stmt->execute([$novo_hash, $_SESSION['username']]);
        $_SESSION['mensagem'] = "Sua senha de administrador foi alterada com sucesso!";
        $_SESSION['tipo_mensagem'] = 'sucesso';
    }
}

// Redireciona de volta para o painel após qualquer ação
header('Location: painel.php');
exit;
?>