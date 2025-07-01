<?php
// logout.php
session_start();

// Destrói todas as variáveis da sessão
$_SESSION = [];

// Finaliza a sessão
session_destroy();

// Redireciona para a página de login
header('Location: login.php');
exit;
?>