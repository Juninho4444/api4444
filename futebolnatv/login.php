<?php
require_once 'painel_config.php';
session_start();

if (isset($_SESSION['autenticado']) && $_SESSION['autenticado'] === true) {
    $destination = ($_SESSION['role'] === 'admin') ? 'painel.php' : 'client_dashboard.php';
    header("Location: $destination");
    exit;
}

$erro = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username_digitado = $_POST['username'] ?? '';
    $senha_digitada = $_POST['senha'] ?? '';
    $db = conectar_db();
    
    $stmt_admin = $db->prepare("SELECT * FROM admin_users WHERE username = ?");
    $stmt_admin->execute([$username_digitado]);
    $admin_user = $stmt_admin->fetch();

    if ($admin_user && password_verify($senha_digitada, $admin_user['password_hash'])) {
        $_SESSION['autenticado'] = true;
        $_SESSION['role'] = 'admin';
        $_SESSION['username'] = $admin_user['username'];
        header('Location: painel.php');
        exit;
    }
    
    $stmt_client = $db->prepare("SELECT * FROM chaves WHERE client_username = ?");
    $stmt_client->execute([$username_digitado]);
    $client_user = $stmt_client->fetch();

    if ($client_user && !empty($client_user['client_password_hash']) && password_verify($senha_digitada, $client_user['client_password_hash'])) {
        $_SESSION['autenticado'] = true;
        $_SESSION['role'] = 'client';
        $_SESSION['username'] = $client_user['client_username'];
        $_SESSION['key_id'] = $client_user['id'];
        header('Location: client_dashboard.php');
        exit;
    }
    $erro = "Usuário ou senha incorretos!";
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif; display: flex; justify-content: center; align-items: center; height: 100vh; background-color: #f4f4f4; margin: 0; }
        .login-box { padding: 30px; background: white; border-radius: 8px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); text-align: center; width: 300px; }
        h2 { color: #333; margin-bottom: 20px; }
        input[type="text"], input[type="password"] { padding: 12px; margin-bottom: 15px; width: 100%; box-sizing: border-box; border: 1px solid #ccc; border-radius: 4px; }
        input[type="submit"] { padding: 12px 20px; background-color: #007bff; color: white; border: none; cursor: pointer; border-radius: 4px; width: 100%; font-size: 16px; }
        input[type="submit"]:hover { background-color: #0056b3; }
        .erro { color: #dc3545; margin-bottom: 15px; font-weight: bold; }
    </style>
</head>
<body>
    <div class="login-box">
        <h2>Acesso ao Painel</h2>
        <form action="login.php" method="post">
            <?php if (!empty($erro)): ?>
                <p class="erro"><?php echo htmlspecialchars($erro); ?></p>
            <?php endif; ?>
            <input type="text" name="username" placeholder="Usuário" required>
            <br>
            <input type="password" name="senha" placeholder="Senha" required>
            <br>
            <input type="submit" value="Entrar">
        </form>
    </div>
</body>
</html>