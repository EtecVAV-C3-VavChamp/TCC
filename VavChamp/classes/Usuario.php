<?php
require_once "Database.php";

class Usuario
{
    public static function login($email, $senha)
    {
        $pdo = Database::getConnection();

        $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE email = ?");
        $stmt->execute([$email]);
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
        //var_dump($usuario);
        //exit();
        if ($usuario && password_verify($senha, $usuario["senha"])) {
            // Inicia a sessão se ainda não estiver iniciada
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }

            // Define os dados na sessão
            $_SESSION["usuario"] = [
                "id" => $usuario["id"],
                "nome" => $usuario["nome"],
                "email" => $usuario["email"],
                "tipo" => $usuario["tipo"],
            ];

            return true;
        }

        return false;
    }

    public static function registrar($nome, $email, $senha, $tipo = 0)
    {
        $pdo = Database::getConnection();
        $hash = password_hash($senha, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare(
            "INSERT INTO usuarios (nome, email, senha, tipo) VALUES (?, ?, ?, ?)"
        );
        return $stmt->execute([$nome, $email, $hash, (int) $tipo]);
    }

    public static function isAdmin()
    {
        if (
            !isset($_SESSION["usuario"]) ||
            !in_array($_SESSION["usuario"]["tipo"], [1, 2])
        ) {
            header("Location: ../index.php");
            exit();
        }

        return true;
    }

    public static function verificarLogin()
    {
        if (!isset($_SESSION["usuario"])) {
            header("Location: login.php");
            exit();
        }
    }

    public static function excluir($id)
    {
        $pdo = Database::getConnection();

        $stmt = $pdo->prepare("DELETE FROM usuarios WHERE id = ?");
        return $stmt->execute([$id]);
    }

    public static function logout()
    {
        session_destroy();
        header("Location: /Vav_Champ/index.php");
        exit();
    }
}
?>
