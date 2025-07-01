<?php
// painel_config.php - v5 Final

// --- DADOS DE ACESSO AO BANCO DE DADOS ---
define('DB_HOST', 'localhost');
define('DB_NAME', 'gestor_api');
define('DB_USER', 'gestor_api');
define('DB_PASS', 'Ngq1wuMzVwu15?n~');

/**
 * Cria e retorna uma conexão PDO com o banco de dados.
 */
function conectar_db() {
    try {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        return new PDO($dsn, DB_USER, DB_PASS, $options);
    } catch (PDOException $e) {
        // Em produção, isso deveria ser logado, não exibido.
        die("Erro de conexão com o banco de dados: " . $e->getMessage());
    }
}

/**
 * Pega o endereço de IP real do cliente, mesmo atrás de proxies como a Cloudflare.
 * @return string O endereço de IP do cliente.
 */
function get_client_ip() {
    // A ordem de verificação é importante por segurança.
    // CF-Connecting-IP é o mais confiável se você usa Cloudflare.
    if (isset($_SERVER['HTTP_CF_CONNECTING_IP'])) {
        return $_SERVER['HTTP_CF_CONNECTING_IP'];
    }
    // X-Forwarded-For pode conter uma lista de IPs, o primeiro é o original.
    if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        return explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
    }
    // X-Real-IP é outro cabeçalho comum de proxy.
    if (isset($_SERVER['HTTP_X_REAL_IP'])) {
        return $_SERVER['HTTP_X_REAL_IP'];
    }
    // Fallback para o método padrão.
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}
?>