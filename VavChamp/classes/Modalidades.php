<?php
require_once "Database.php";

class Modalidade
{
    public static function obterPorId($id)
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT * FROM modalidades WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // 🔹 Função independente para retornar somente o nome
    public static function nomePorId($id)
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT nome FROM modalidades WHERE id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['nome'] ?? 'Não definida';
    }
}
