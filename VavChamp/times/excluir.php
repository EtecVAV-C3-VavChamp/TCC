<?php
require_once "../includes/header.php";
require_once "../classes/Database.php";
require_once "../classes/Usuario.php";

// Verifica se o usuário está logado e é admin
Usuario::verificarLogin();
if (!Usuario::isAdmin()) {
    $_SESSION['mensagem'] = "<div class='alert alert-danger'>Acesso negado. Você não tem permissão.</div>";
    header("Location: ../index.php");
    exit();
}

// Verifica se o ID foi recebido
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['mensagem'] = "<div class='alert alert-danger'>ID inválido ou não fornecido.</div>";
    header("Location: listar.php");
    exit();
}

$id = (int)$_GET['id'];
$pdo = Database::getConnection();

try {
    $pdo->beginTransaction();
    
    // 1. Verifica se o time existe
    $stmt = $pdo->prepare("SELECT id FROM times WHERE id = ?");
    $stmt->execute([$id]);
    
    if (!$stmt->fetch()) {
        throw new Exception("Time não encontrado.");
    }
    
    // 2. Remove os membros do time (se houver)
    $pdo->prepare("DELETE FROM time_membros WHERE time_id = ?")->execute([$id]);
    
    // 3. Remove o time
    $stmt = $pdo->prepare("DELETE FROM times WHERE id = ?");
    $stmt->execute([$id]);
    
    // Verifica se alguma linha foi afetada
    if ($stmt->rowCount() === 0) {
        throw new Exception("Nenhum time foi excluído. Verifique o ID fornecido.");
    }
    
    $pdo->commit();
    
    $_SESSION['mensagem'] = "<div class='alert alert-success'>Time excluído com sucesso!</div>";
} catch (PDOException $e) {
    $pdo->rollBack();
    $_SESSION['mensagem'] = "<div class='alert alert-danger'>Erro no banco de dados: " . htmlspecialchars($e->getMessage()) . "</div>";
} catch (Exception $e) {
    $pdo->rollBack();
    $_SESSION['mensagem'] = "<div class='alert alert-danger'>" . htmlspecialchars($e->getMessage()) . "</div>";
}

header("Location: listar.php");
exit();