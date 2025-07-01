<?php
// setup_admin.php - Execute apenas uma vez para criar a tabela de usuários do painel.
require_once 'painel_config.php'; // Usa as mesmas credenciais de DB

// --- DEFINA O LOGIN INICIAL DO PAINEL AQUI ---
$admin_user = 'admin';
$admin_pass = '14112001'; // Escolha uma senha forte!
// ---------------------------------------------

try {
    $db = conectar_db();
    echo "Conexão com o banco de dados MySQL estabelecida.<br>";

    // SQL para criar a tabela 'admin_users'
    $sql = "
        CREATE TABLE IF NOT EXISTS `admin_users` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `username` VARCHAR(50) NOT NULL UNIQUE,
            `password_hash` VARCHAR(255) NOT NULL,
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ";

    $db->exec($sql);
    echo "Tabela 'admin_users' criada ou já existente.<br>";
    
    // Gera o hash da senha definida
    $password_hash = password_hash($admin_pass, PASSWORD_DEFAULT);

    // Insere o primeiro usuário administrador, ignorando se ele já existir
    $stmt = $db->prepare("INSERT IGNORE INTO admin_users (username, password_hash) VALUES (?, ?)");
    $stmt->execute([$admin_user, $password_hash]);
    
    echo "Usuário administrador '<b>{$admin_user}</b>' criado/verificado com sucesso.<br>";
    echo "<b>Setup concluído!</b> Lembre-se de apagar este arquivo (`setup_admin.php`) do seu servidor.";

} catch (PDOException $e) {
    echo "Erro: " . $e->getMessage();
}
?>