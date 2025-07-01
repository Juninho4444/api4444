<?php
// debug_session.php

// Inicia a sessão para poder ler os dados dela
session_start();

// Define o cabeçalho para exibir como texto puro
header('Content-Type: text/plain');

echo "=================================\n";
echo "CONTEÚDO DA SESSÃO ATUAL:\n";
echo "=================================\n\n";

// Imprime o conteúdo da variável $_SESSION de forma legível
print_r($_SESSION);

?>