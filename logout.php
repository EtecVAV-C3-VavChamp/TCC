<?php
session_start();

// Limpa todos os dados da sessão
$_SESSION = [];
session_destroy();

// Redireciona para a página inicial
header("Location: index.php");
exit();
