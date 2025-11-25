<?php
/**
 * salvar_vencedores.php
 * 
 * Script responsável por processar os vencedores dos confrontos
 * Lógica simplificada: finalista da losers enfrenta finalista da winners na final
 */

require_once "../classes/Database.php";
session_start();

$pdo = Database::getConnection();

// Verifica permissões do usuário
if (!isset($_SESSION["usuario"]) || $_SESSION["usuario"]["tipo"] == 0) {
    die("Acesso negado.");
}

// Verifica dados obrigatórios
if (!isset($_POST["vencedor"], $_POST["campeonato_id"])) {
    die("Dados incompletos.");
}

$campeonato_id = (int) $_POST["campeonato_id"];
$vencedores = $_POST["vencedor"];

try {
    $pdo->beginTransaction();

    // FASE 1: Atualizar todos os vencedores
    foreach ($vencedores as $confronto_id => $vencedor_id) {
        $confronto_id = (int) $confronto_id;
        $vencedor_id = (int) $vencedor_id;

        // Busca dados do confronto
        $stmt = $pdo->prepare(
            "SELECT * FROM confrontos WHERE id = ? AND campeonato_id = ?"
        );
        $stmt->execute([$confronto_id, $campeonato_id]);
        $confronto = $stmt->fetch();

        if (!$confronto) continue;
        
        // Verifica se o vencedor é válido
        if ($vencedor_id != $confronto["time1"] && $vencedor_id != $confronto["time2"]) {
            continue;
        }

        // Atualiza o vencedor no banco
        $stmt = $pdo->prepare("UPDATE confrontos SET vencedor = ? WHERE id = ?");
        $stmt->execute([$vencedor_id, $confronto_id]);
        
        // CORREÇÃO: Marca perdedor como eliminado se for losers bracket
        if ($confronto["fase"] === "losers") {
            $perdedor_id = ($vencedor_id == $confronto["time1"]) ? $confronto["time2"] : $confronto["time1"];
            
            if ($perdedor_id && $perdedor_id > 0) {
                marcarTimeComoEliminado($pdo, $campeonato_id, $perdedor_id);
            }
        }
        
        // Só adiciona perdedor à losers se for do winners bracket
        if ($confronto["fase"] === "winners") {
            $perdedor_id = ($vencedor_id == $confronto["time1"]) ? $confronto["time2"] : $confronto["time1"];
            
            if ($perdedor_id && $perdedor_id > 0) {
                adicionarPerdedorLosers($pdo, $campeonato_id, $perdedor_id, $confronto["rodada"]);
            }
        }
    }

    // FASE 2: Gerar próximas rodadas
    gerarProximaRodadaWinners($pdo, $campeonato_id);
    gerarProximaRodadaLosers($pdo, $campeonato_id);
    
    // FASE 3: Verificar e criar final
    verificarEFinal($pdo, $campeonato_id);

    $pdo->commit();
    
    $_SESSION['toast'] = [
        'type' => 'success',
        'message' => 'Vencedores salvos com sucesso!'
    ];

} catch (Exception $e) {
    $pdo->rollBack();
    error_log("Erro ao salvar vencedores: " . $e->getMessage());
    $_SESSION['toast'] = [
        'type' => 'error',
        'message' => 'Erro ao salvar vencedores: ' . $e->getMessage()
    ];
}

header("Location: chaveamento.php?id=" . $campeonato_id);
exit();

/**
 * Marca um time como eliminado no campeonato
 * Cria um registro na tabela confrontos com fase = 'eliminado'
 */
function marcarTimeComoEliminado($pdo, $campeonato_id, $time_id) {
    // Verifica se o time já está marcado como eliminado
    $stmt = $pdo->prepare(
        "SELECT COUNT(*) FROM confrontos 
         WHERE campeonato_id = ? AND fase = 'eliminado' AND time1 = ?"
    );
    $stmt->execute([$campeonato_id, $time_id]);
    
    if ($stmt->fetchColumn() > 0) return; // Já está eliminado
    
    // Marca o time como eliminado
    $stmt = $pdo->prepare(
        "INSERT INTO confrontos (campeonato_id, time1, fase, rodada) 
         VALUES (?, ?, 'eliminado', -10000)"
    );
    $stmt->execute([$campeonato_id, $time_id]);
}

/**
 * Verifica se um time está eliminado no campeonato
 */
function timeEstaEliminado($pdo, $campeonato_id, $time_id) {
    $stmt = $pdo->prepare(
        "SELECT COUNT(*) FROM confrontos 
         WHERE campeonato_id = ? AND fase = 'eliminado' AND time1 = ?"
    );
    $stmt->execute([$campeonato_id, $time_id]);
    return $stmt->fetchColumn() > 0;
}

/**
 * Adiciona um perdedor do winners bracket para o losers bracket
 * Lógica simples: cria confronto vazio ou completa existente
 */
function adicionarPerdedorLosers($pdo, $campeonato_id, $perdedor_id, $rodada_winners) {
    // Verifica se o time já está eliminado
    if (timeEstaEliminado($pdo, $campeonato_id, $perdedor_id)) {
        error_log("Time $perdedor_id eliminado, não pode entrar na losers");
        return; // Time já está eliminado, não pode entrar na losers
    }
    
    // Perdedor vai para a mesma rodada no losers bracket
    $rodada_losers = $rodada_winners;
    
    // Verifica se o time já está em algum confronto ativo na losers
    $stmt = $pdo->prepare(
        "SELECT COUNT(*) FROM confrontos 
         WHERE campeonato_id = ? AND fase = 'losers' 
         AND (time1 = ? OR time2 = ?) AND vencedor IS NULL"
    );
    $stmt->execute([$campeonato_id, $perdedor_id, $perdedor_id]);
    
    if ($stmt->fetchColumn() > 0) return;
    
    // Busca confronto existente que precisa de oponente
    $stmt = $pdo->prepare(
        "SELECT id, time1 FROM confrontos 
         WHERE campeonato_id = ? AND fase = 'losers' 
         AND rodada = ? AND time2 IS NULL AND vencedor IS NULL
         ORDER BY id LIMIT 1"
    );
    $stmt->execute([$campeonato_id, $rodada_losers]);
    $confronto_existente = $stmt->fetch();
    
    if ($confronto_existente) {
        // Completa o confronto existente
        $stmt = $pdo->prepare("UPDATE confrontos SET time2 = ? WHERE id = ?");
        $stmt->execute([$perdedor_id, $confronto_existente["id"]]);
    } else {
        // Cria novo confronto vazio
        $stmt = $pdo->prepare(
            "INSERT INTO confrontos (campeonato_id, time1, fase, rodada) 
             VALUES (?, ?, 'losers', ?)"
        );
        $stmt->execute([$campeonato_id, $perdedor_id, $rodada_losers]);
    }
}

/**
 * Gera próxima rodada do winners bracket
 * Simples: pega vencedores da rodada atual e cria novos confrontos
 */
function gerarProximaRodadaWinners($pdo, $campeonato_id) {
    // Busca última rodada do winners
    $stmt = $pdo->prepare(
        "SELECT MAX(rodada) FROM confrontos WHERE campeonato_id = ? AND fase = 'winners'"
    );
    $stmt->execute([$campeonato_id]);
    $ultima_rodada = (int) $stmt->fetchColumn();
    
    if ($ultima_rodada < 1) return;
    
    // Verifica se todos os confrontos da última rodada têm vencedor
    $stmt = $pdo->prepare(
        "SELECT COUNT(*) FROM confrontos 
         WHERE campeonato_id = ? AND fase = 'winners' AND rodada = ? AND vencedor IS NULL"
    );
    $stmt->execute([$campeonato_id, $ultima_rodada]);
    
    if ($stmt->fetchColumn() > 0) return;
    
    // Busca vencedores da última rodada
    $stmt = $pdo->prepare(
        "SELECT vencedor FROM confrontos 
         WHERE campeonato_id = ? AND fase = 'winners' AND rodada = ? AND vencedor IS NOT NULL"
    );
    $stmt->execute([$campeonato_id, $ultima_rodada]);
    $vencedores = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (count($vencedores) < 2) return;
    
    // Verifica se próxima rodada já existe
    $stmt = $pdo->prepare(
        "SELECT COUNT(*) FROM confrontos 
         WHERE campeonato_id = ? AND fase = 'winners' AND rodada = ?"
    );
    $stmt->execute([$campeonato_id, $ultima_rodada + 1]);
    
    if ($stmt->fetchColumn() > 0) return;
    
    // Cria novos confrontos
    shuffle($vencedores);
    for ($i = 0; $i < count($vencedores); $i += 2) {
        $time1 = $vencedores[$i];
        $time2 = isset($vencedores[$i + 1]) ? $vencedores[$i + 1] : null;
        
        if ($time2) {
            $stmt = $pdo->prepare(
                "INSERT INTO confrontos (campeonato_id, time1, time2, fase, rodada) 
                 VALUES (?, ?, ?, 'winners', ?)"
            );
            $stmt->execute([$campeonato_id, $time1, $time2, $ultima_rodada + 1]);
        } else {
            // Bye para time sem oponente
            $stmt = $pdo->prepare(
                "INSERT INTO confrontos (campeonato_id, time1, vencedor, fase, rodada) 
                 VALUES (?, ?, ?, 'winners', ?)"
            );
            $stmt->execute([$campeonato_id, $time1, $time1, $ultima_rodada + 1]);
        }
    }
}

/**
 * Gera próxima rodada do losers bracket
 * CORREÇÃO: Impede confrontos envolvendo times eliminados
 */
function gerarProximaRodadaLosers($pdo, $campeonato_id) {
    // Busca última rodada da losers
    $stmt = $pdo->prepare(
        "SELECT MAX(rodada) FROM confrontos WHERE campeonato_id = ? AND fase = 'losers'"
    );
    $stmt->execute([$campeonato_id]);
    $ultima_rodada = (int) $stmt->fetchColumn();
    
    if ($ultima_rodada < 1) return;
    
    // Verifica se TODOS os confrontos da última rodada têm vencedor
    $stmt = $pdo->prepare(
        "SELECT COUNT(*) FROM confrontos 
         WHERE campeonato_id = ? AND fase = 'losers' AND rodada = ? AND vencedor IS NULL"
    );
    $stmt->execute([$campeonato_id, $ultima_rodada]);
    
    if ($stmt->fetchColumn() > 0) return; // Ainda há confrontos sem vencedor
    
    // Busca TODOS os vencedores da última rodada da losers
    $stmt = $pdo->prepare(
        "SELECT vencedor FROM confrontos 
         WHERE campeonato_id = ? AND fase = 'losers' AND rodada = ? AND vencedor IS NOT NULL"
    );
    $stmt->execute([$campeonato_id, $ultima_rodada]);
    $vencedores_losers = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    // Remove duplicatas
    $vencedores_losers = array_unique($vencedores_losers);
    $vencedores_losers = array_values($vencedores_losers);
    
    // CORREÇÃO: Remove times eliminados da lista de vencedores
    $vencedores_validos = [];
    foreach ($vencedores_losers as $time_id) {
        if (!timeEstaEliminado($pdo, $campeonato_id, $time_id)) {
            $vencedores_validos[] = $time_id;
        } else {
            error_log("Time $time_id eliminado removido da próxima rodada");
        }
    }
    
    $vencedores_losers = $vencedores_validos;
    
    // Se não há vencedores válidos, não faz nada
    if (count($vencedores_losers) === 0) {
        error_log("Nenhum vencedor válido encontrado para próxima rodada");
        return;
    }
    
    // Verifica se próxima rodada já existe
    $stmt = $pdo->prepare(
        "SELECT COUNT(*) FROM confrontos 
         WHERE campeonato_id = ? AND fase = 'losers' AND rodada = ?"
    );
    $stmt->execute([$campeonato_id, $ultima_rodada + 1]);
    
    if ($stmt->fetchColumn() > 0) return;
    
    // Se só tem 1 vencedor válido, verifica se deve criar bye
    if (count($vencedores_losers) === 1) {
        $time_com_bye = $vencedores_losers[0];
        
        // Verifica se já existe final
        $stmt = $pdo->prepare(
            "SELECT COUNT(*) FROM confrontos WHERE campeonato_id = ? AND fase IN ('final', 'grande_final')"
        );
        $stmt->execute([$campeonato_id]);
        $final_existe = $stmt->fetchColumn() > 0;
        
        // Se não há final ainda, cria bye para continuar o bracket
        if (!$final_existe) {
            // Verifica se já existe bye para evitar duplicação
            $stmt = $pdo->prepare(
                "SELECT COUNT(*) FROM confrontos 
                 WHERE campeonato_id = ? AND fase = 'losers' 
                 AND rodada = ? AND time1 = ? AND time2 IS NULL"
            );
            $stmt->execute([$campeonato_id, $ultima_rodada + 1, $time_com_bye]);
            
            if ($stmt->fetchColumn() == 0) {
                // CORREÇÃO: Verifica se o time do bye não está eliminado
                if (!timeEstaEliminado($pdo, $campeonato_id, $time_com_bye)) {
                    $stmt = $pdo->prepare(
                        "INSERT INTO confrontos (campeonato_id, time1, vencedor, fase, rodada) 
                         VALUES (?, ?, ?, 'losers', ?)"
                    );
                    $stmt->execute([$campeonato_id, $time_com_bye, $time_com_bye, $ultima_rodada + 1]);
                    error_log("Bye criado para time $time_com_bye na rodada " . ($ultima_rodada + 1));
                } else {
                    error_log("Time $time_com_bye eliminado, bye não criado");
                }
            }
        }
        return;
    }
    
    // Cria confrontos para a próxima rodada
    shuffle($vencedores_losers);
    
    error_log("Criando " . floor(count($vencedores_losers) / 2) . " confrontos para rodada " . ($ultima_rodada + 1));
    
    for ($i = 0; $i < count($vencedores_losers); $i += 2) {
        $time1 = $vencedores_losers[$i];
        $time2 = isset($vencedores_losers[$i + 1]) ? $vencedores_losers[$i + 1] : null;
        
        // CORREÇÃO: Verifica se ambos os times não estão eliminados
        $time1_eliminado = timeEstaEliminado($pdo, $campeonato_id, $time1);
        $time2_eliminado = $time2 ? timeEstaEliminado($pdo, $campeonato_id, $time2) : false;
        
        if ($time2 && !$time1_eliminado && !$time2_eliminado) {
            // Verifica se o confronto já existe
            $stmt = $pdo->prepare(
                "SELECT COUNT(*) FROM confrontos 
                 WHERE campeonato_id = ? AND fase = 'losers' 
                 AND rodada = ? AND (
                    (time1 = ? AND time2 = ?) OR 
                    (time1 = ? AND time2 = ?)
                 )"
            );
            $stmt->execute([$campeonato_id, $ultima_rodada + 1, $time1, $time2, $time2, $time1]);
            
            if ($stmt->fetchColumn() == 0) {
                // Cria novo confronto
                $stmt = $pdo->prepare(
                    "INSERT INTO confrontos (campeonato_id, time1, time2, fase, rodada) 
                     VALUES (?, ?, ?, 'losers', ?)"
                );
                $stmt->execute([$campeonato_id, $time1, $time2, $ultima_rodada + 1]);
                error_log("Confronto criado: $time1 vs $time2 na rodada " . ($ultima_rodada + 1));
            }
        } else if (!$time1_eliminado) {
            // CORREÇÃO: Time ímpar recebe bye apenas se não estiver eliminado
            $stmt = $pdo->prepare(
                "INSERT INTO confrontos (campeonato_id, time1, vencedor, fase, rodada) 
                 VALUES (?, ?, ?, 'losers', ?)"
            );
            $stmt->execute([$campeonato_id, $time1, $time1, $ultima_rodada + 1]);
            error_log("Bye criado para time $time1 na rodada " . ($ultima_rodada + 1));
        } else {
            error_log("Time $time1 eliminado, confronto/bye não criado");
        }
    }
}

/**
 * Verifica e cria a final
 * Lógica simplificada: finalista da losers enfrenta finalista da winners
 */
function verificarEFinal($pdo, $campeonato_id) {
    // Verifica se já existe final
    $stmt = $pdo->prepare(
        "SELECT COUNT(*) FROM confrontos WHERE campeonato_id = ? AND fase IN ('final', 'grande_final')"
    );
    $stmt->execute([$campeonato_id]);
    
    if ($stmt->fetchColumn() > 0) return;
    
    // Busca finalista do winners bracket
    $stmt = $pdo->prepare(
        "SELECT vencedor FROM confrontos 
         WHERE campeonato_id = ? AND fase = 'winners' 
         ORDER BY rodada DESC, id DESC LIMIT 1"
    );
    $stmt->execute([$campeonato_id]);
    $vencedor_winners = $stmt->fetchColumn();
    
    // Busca finalista do losers bracket
    $stmt = $pdo->prepare(
        "SELECT vencedor FROM confrontos 
         WHERE campeonato_id = ? AND fase = 'losers' 
         ORDER BY rodada DESC, id DESC LIMIT 1"
    );
    $stmt->execute([$campeonato_id]);
    $vencedor_losers = $stmt->fetchColumn();
    
    // Verifica se ambos os finalistas existem
    if ($vencedor_winners && $vencedor_losers) {
        
        // Verifica se ainda há confrontos pendentes no winners
        $stmt = $pdo->prepare(
            "SELECT COUNT(*) FROM confrontos 
             WHERE campeonato_id = ? AND fase = 'winners' AND vencedor IS NULL"
        );
        $stmt->execute([$campeonato_id]);
        $winners_pendentes = $stmt->fetchColumn();
        
        // Verifica se ainda há confrontos pendentes no losers
        $stmt = $pdo->prepare(
            "SELECT COUNT(*) FROM confrontos 
             WHERE campeonato_id = ? AND fase = 'losers' AND vencedor IS NULL"
        );
        $stmt->execute([$campeonato_id]);
        $losers_pendentes = $stmt->fetchColumn();
        
        // Só cria a final se não há mais confrontos pendentes
        if ($winners_pendentes == 0 && $losers_pendentes == 0) {
            $stmt = $pdo->prepare(
                "INSERT INTO confrontos (campeonato_id, time1, time2, fase, rodada) 
                 VALUES (?, ?, ?, 'final', 1)"
            );
            $stmt->execute([$campeonato_id, $vencedor_winners, $vencedor_losers]);
        }
    }
}

/**
 * Função auxiliar: Marca o perdedor da final como eliminado
 * (Para ser chamada quando o vencedor da final for definido)
 */
function marcarPerdedorFinal($pdo, $campeonato_id, $vencedor_final_id, $confronto_final) {
    $perdedor_final_id = ($vencedor_final_id == $confronto_final["time1"]) 
        ? $confronto_final["time2"] 
        : $confronto_final["time1"];
    
    if ($perdedor_final_id && $perdedor_final_id > 0) {
        marcarTimeComoEliminado($pdo, $campeonato_id, $perdedor_final_id);
    }
}
?>