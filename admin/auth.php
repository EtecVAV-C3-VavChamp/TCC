<?php
session_start();

// Redireciona se não estiver logado
if (!isset($_SESSION["usuario"]["id"])) {
    header("Location: login.php");
    exit();
}

// Função para verificar se é administrador
function is_admin()
{
    return isset($_SESSION["usuario"]) &&
        (int) $_SESSION["usuario"]["tipo"] === 1;
}
?>
