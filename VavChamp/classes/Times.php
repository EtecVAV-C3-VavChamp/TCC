<?php
require_once "Database.php";

class Times
{
    public static function listar()
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->query("SELECT * FROM times ORDER BY nome");
        return $stmt->fetchAll();
    }

    public static function listarAtivos()
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->query("SELECT * FROM times ORDER BY nome");
        return $stmt->fetchAll();
    }

    public static function obterPorId($id)
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT * FROM times WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public static function atualizar($id, $nome, $pagou)
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare(
            "UPDATE times SET nome = ?, pagou = ? WHERE id = ?"
        );
        return $stmt->execute([$nome, (int) $pagou, $id]);
    }

    public static function remover($id)
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("DELETE FROM times WHERE id = ?");
        return $stmt->execute([$id]);
    }

    public static function adicionar($nome, $pagou = 0)
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("INSERT INTO times (nome, pagou) VALUES (?, ?)");
        return $stmt->execute([$nome, (int) $pagou]);
    }

    public static function listarMembros($time_id)
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("
            SELECT u.id, u.nome, u.email
            FROM time_membros tm
            JOIN usuarios u ON tm.usuario_id = u.id
            WHERE tm.time_id = ?
            ORDER BY u.nome
        ");
        $stmt->execute([$time_id]);
        return $stmt->fetchAll();
    }

    public static function removerMembro($time_id, $usuario_id)
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare(
            "DELETE FROM time_membros WHERE time_id = ? AND usuario_id = ?"
        );
        return $stmt->execute([$time_id, $usuario_id]);
    }

    public static function adicionarMembro($time_id, $usuario_id)
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare(
            "INSERT INTO time_membros (time_id, usuario_id) VALUES (?, ?)"
        );
        return $stmt->execute([$time_id, $usuario_id]);
    }
}
?>
