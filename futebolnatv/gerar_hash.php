<?php
// gerar_hash.php

// 1. Coloque a senha que você quer usar aqui dentro das aspas
$suaSenhaSecreta = "senha-forte-que-voce-vai-escolher";

// 2. Este código vai gerar o hash seguro
$hash = password_hash($suaSenhaSecreta, PASSWORD_DEFAULT);

// 3. Exibe o hash na tela
echo "Seu hash de senha é: <br><br>";
echo "<textarea rows='3' cols='80' readonly>" . htmlspecialchars($hash) . "</textarea>";
echo "<br><br>Copie esta longa string de caracteres e guarde-a para o próximo passo. Depois, apague este arquivo do servidor.";

?>