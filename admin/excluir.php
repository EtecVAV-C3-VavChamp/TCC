<?php
require_once "../classes/Usuario.php";
session_start();

// Verifica se o usuário está logado e tem permissão
if (!isset($_SESSION["usuario"]) || (int) $_SESSION["usuario"]["tipo"] === 0) {
    die("Acesso negado.");
}

// Verifica se o ID foi fornecido
if (!isset($_GET["id"]) || !is_numeric($_GET["id"])) {
    die("ID de usuário não especificado ou inválido.");
}

$id = (int) $_GET["id"];

// Tenta excluir o usuário
if (Usuario::excluir($id)) {
    header("Location: users.php?sucesso=1");
    exit();
} else {
    header("Location: users.php?erro=1");
    exit();
}
