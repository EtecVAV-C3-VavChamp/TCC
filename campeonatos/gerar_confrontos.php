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
function registrarBye($pdo, $time_id, $campeonato_id, $rodada = 1) {
    $stmt = $pdo->prepare(
        "INSERT INTO confrontos (campeonato_id, time1, vencedor, fase, rodada) 
        VALUES (?, ?, ?, 'winners', ?)"
    );
    return $stmt->execute([$campeonato_id, $time_id, $time_id, $rodada]);
}

// Função para criar confronto na losers
function criarConfrontoLosers($pdo, $campeonato_id, $time1, $time2, $rodada) {
    if ($time2) {
        $stmt = $pdo->prepare(
            "INSERT INTO confrontos (campeonato_id, time1, time2, fase, rodada) 
            VALUES (?, ?, ?, 'losers', ?)"
        );
        return $stmt->execute([$campeonato_id, $time1, $time2, $rodada]);
    } else {
        $stmt = $pdo->prepare(
            "INSERT INTO confrontos (campeonato_id, time1, vencedor, fase, rodada) 
            VALUES (?, ?, ?, 'losers', ?)"
        );
        return $stmt->execute([$campeonato_id, $time1, $time1, $rodada]);
    }
}

// Gerar confrontos DOUBLE ELIMINATION CORRETO
try {
    $pdo->beginTransaction();
    
    shuffle($times);
    $ids_restantes = array_column($times, 'id');
    $total_times = count($ids_restantes);
    
    echo "<!-- Total de times: $total_times -->";
    
    // Lidar com número ímpar de times - primeiro time recebe bye
    $bye_time = null;
    if ($total_times % 2 != 0) {
        $bye_time = array_pop($ids_restantes);
        registrarBye($pdo, $bye_time, $campeonato_id, 1);
    }
    
    // Gerar primeira rodada do winners bracket
    $rodada_winners = 1;
    $confrontos_winners_1 = [];
    
    while (count($ids_restantes) > 0) {
        $time1_id = array_shift($ids_restantes);
        $time2_id = array_shift($ids_restantes);
        
        $stmt = $pdo->prepare(
            "INSERT INTO confrontos 
            (campeonato_id, fase, rodada, time1, time2) 
            VALUES (?, 'winners', ?, ?, ?)"
        );
        $stmt->execute([$campeonato_id, $rodada_winners, $time1_id, $time2_id]);
        $confronto_id = $pdo->lastInsertId();
        
        $confrontos_winners_1[] = [
            'id' => $confronto_id,
            'time1' => $time1_id,
            'time2' => $time2_id
        ];
    }
    
    // GERAR PRIMEIRA RODADA DA LOSERS BRACKET CORRETAMENTE
    // Na double elimination, a primeira rodada da losers tem:
    // - Perdedores da primeira rodada da winners se enfrentam entre si
    
    // Simular perdedores da primeira rodada (vamos considerar que os times com ID menor perdem)
    // Na prática, esses confrontos serão preenchidos quando os resultados forem salvos
    $perdedores_rodada_1 = [];
    
    /*foreach ($confrontos_winners_1 as $confronto) {
        // Simular que o time2 perde (apenas para criar a estrutura)
        $perdedores_rodada_1[] = $confronto['time2'];
    }*/
    
    // Se houve bye, o time do bye não tem perdedor correspondente
    if ($bye_time) {
        // Time do bye avança automaticamente, então não tem perdedor
    }
    
    // Criar confrontos da primeira rodada da losers
    $rodada_losers = 1;
    $perdedores_restantes = $perdedores_rodada_1;
    
    // Em double elimination, os perdedores da primeira rodada se enfrentam entre si
    while (count($perdedores_restantes) > 0) {
        $time1_id = array_shift($perdedores_restantes);
        $time2_id = count($perdedores_restantes) > 0 ? array_shift($perdedores_restantes) : null;
        
        criarConfrontoLosers($pdo, $campeonato_id, $time1_id, $time2_id, $rodada_losers);
    }
    
    $pdo->commit();
    
    $_SESSION['toast'] = [
        'type' => 'success',
        'message' => 'Chaveamento Double Elimination gerado com sucesso! Estrutura criada para ' . $total_times . ' times.'
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
?>