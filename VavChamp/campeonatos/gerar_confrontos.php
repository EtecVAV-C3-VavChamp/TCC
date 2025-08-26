<?php
require_once "../classes/Database.php";
session_start();

if (!isset($_SESSION["usuario"])) {
    header("HTTP/1.1 403 Forbidden");
    die("Acesso negado. Faça login para continuar.");
}

if (!isset($_GET["id"]) || !filter_var($_GET["id"], FILTER_VALIDATE_INT)) {
    header("HTTP/1.1 400 Bad Request");
    die("ID do campeonato inválido ou não especificado.");
}

$campeonato_id = (int)$_GET["id"];
$pdo = Database::getConnection();

// Verificar confrontos existentes
$stmt = $pdo->prepare("SELECT COUNT(*) FROM confrontos WHERE campeonato_id = ?");
$stmt->execute([$campeonato_id]);
if ($stmt->fetchColumn() > 0) {
    $_SESSION['toast'] = [
        'type' => 'error',
        'message' => 'Confrontos já foram gerados para este campeonato!'
    ];
    header("Location: editar.php?id=$campeonato_id");
    exit();
}

// Obter dados do campeonato
$stmt = $pdo->prepare("SELECT times_participantes FROM campeonatos WHERE id = ?");
$stmt->execute([$campeonato_id]);
$campeonato = $stmt->fetch(PDO::FETCH_ASSOC);

if (empty($campeonato['times_participantes'])) {
    $_SESSION['toast'] = [
        'type' => 'error',
        'message' => 'Nenhum time participante encontrado!'
    ];
    header("Location: editar.php?id=$campeonato_id");
    exit();
}

// Processar times
$ids_times = array_filter(array_map('intval', explode(",", $campeonato['times_participantes'])));
if (count($ids_times) < 2) {
    $_SESSION['toast'] = [
        'type' => 'error',
        'message' => 'É necessário pelo menos 2 times!'
    ];
    header("Location: editar.php?id=$campeonato_id");
    exit();
}

// Obter dados dos times
$placeholders = implode(",", array_fill(0, count($ids_times), "?"));
$stmt = $pdo->prepare("SELECT id, nome FROM times WHERE id IN ($placeholders)");
$stmt->execute($ids_times);
$times = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Função para registrar bye
function registrarBye($time_id, $pdo, $campeonato_id, $rodada = 1) {
    $stmt = $pdo->prepare(
        "INSERT INTO confrontos (campeonato_id, time1, fase, rodada) 
        VALUES (?, ?, 'winners', ?)"
    );
    return $stmt->execute([$campeonato_id, $time_id, $rodada]);
}

// Gerar confrontos
try {
    $pdo->beginTransaction();
    
    shuffle($times);
    $ids_restantes = array_column($times, 'id');
    
    if (count($ids_restantes) % 2 != 0) {
        $time_avanca = array_pop($ids_restantes);
        registrarBye($time_avanca, $pdo, $campeonato_id);
    }
    
    while (count($ids_restantes) > 0) {
        $time1_id = array_shift($ids_restantes);
        $time2_id = array_shift($ids_restantes);
        
        $stmt = $pdo->prepare(
            "INSERT INTO confrontos 
            (campeonato_id, fase, rodada, time1, time2) 
            VALUES (?, 'winners', 1, ?, ?)"
        );
        $stmt->execute([$campeonato_id, $time1_id, $time2_id]);
    }
    
    $pdo->commit();
    
    $_SESSION['toast'] = [
        'type' => 'success',
        'message' => 'Chaveamento gerado com sucesso!'
    ];
    
} catch (Exception $e) {
    $pdo->rollBack();
    $_SESSION['toast'] = [
        'type' => 'error',
        'message' => 'Erro ao gerar confrontos: ' . $e->getMessage()
    ];
}

header("Location: editar.php?id=$campeonato_id");
exit();