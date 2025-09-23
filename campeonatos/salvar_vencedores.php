<?php
require_once "../classes/Database.php";
session_start();

$pdo = Database::getConnection();

if (!isset($_SESSION["usuario"]) || $_SESSION["usuario"]["tipo"] == 0) {
    die("Acesso negado.");
}

if (!isset($_POST["vencedor"], $_POST["campeonato_id"])) {
    die("Dados incompletos.");
}

$campeonato_id = (int) $_POST["campeonato_id"];
$vencedores = $_POST["vencedor"];

function buscarVencedoresDaRodada($pdo, $campeonato_id, $fase, $rodada)
{
    $stmt = $pdo->prepare(
        "SELECT vencedor FROM confrontos WHERE campeonato_id = ? AND fase = ? AND rodada = ?"
    );
    $stmt->execute([$campeonato_id, $fase, $rodada]);
    return array_column($stmt->fetchAll(), "vencedor");
}

function todosConfrontosComVencedor($pdo, $campeonato_id, $fase, $rodada)
{
    $stmt = $pdo->prepare(
        "SELECT COUNT(*) FROM confrontos WHERE campeonato_id = ? AND fase = ? AND rodada = ? AND vencedor IS NULL"
    );
    $stmt->execute([$campeonato_id, $fase, $rodada]);
    return $stmt->fetchColumn() == 0;
}

function confrontoJaExiste($pdo, $campeonato_id, $fase, $time1, $time2)
{
    $stmt = $pdo->prepare(
        "SELECT COUNT(*) FROM confrontos WHERE campeonato_id = ? AND fase = ? AND ((time1 = ? AND time2 = ?) OR (time1 = ? AND time2 = ?))"
    );
    $stmt->execute([$campeonato_id, $fase, $time1, $time2, $time2, $time1]);
    return $stmt->fetchColumn() > 0;
}

foreach ($vencedores as $confronto_id => $vencedor_id) {
    $confronto_id = (int) $confronto_id;
    $vencedor_id = (int) $vencedor_id;

    $stmt = $pdo->prepare(
        "SELECT * FROM confrontos WHERE id = ? AND campeonato_id = ?"
    );
    $stmt->execute([$confronto_id, $campeonato_id]);
    $conf = $stmt->fetch();

    if (!$conf) {
        continue;
    }
    if ($vencedor_id != $conf["time1"] && $vencedor_id != $conf["time2"]) {
        continue;
    }

    $stmt = $pdo->prepare("UPDATE confrontos SET vencedor = ? WHERE id = ?");
    $stmt->execute([$vencedor_id, $confronto_id]);
}

$stmt = $pdo->prepare(
    "SELECT COUNT(*) FROM confrontos WHERE campeonato_id = ? AND fase IN ('final', 'grande_final') AND vencedor IS NOT NULL"
);
$stmt->execute([$campeonato_id]);
if ($stmt->fetchColumn() > 0) {
    header("Location: chaveamento.php?id=" . $campeonato_id);
    exit();
}

foreach (["winners", "losers"] as $fase) {
    $stmt = $pdo->prepare(
        "SELECT MAX(rodada) FROM confrontos WHERE campeonato_id = ? AND fase = ?"
    );
    $stmt->execute([$campeonato_id, $fase]);
    $ultimaRodada = (int) $stmt->fetchColumn();

    if (
        $ultimaRodada < 1 ||
        !todosConfrontosComVencedor($pdo, $campeonato_id, $fase, $ultimaRodada)
    ) {
        continue;
    }

    $vencedores = buscarVencedoresDaRodada(
        $pdo,
        $campeonato_id,
        $fase,
        $ultimaRodada
    );
    if (count($vencedores) <= 1) {
        continue;
    }

    shuffle($vencedores);
    for ($i = 0; $i < count($vencedores); $i += 2) {
        $t1 = $vencedores[$i];
        $t2 = $vencedores[$i + 1] ?? null;

        if ($t2 && confrontoJaExiste($pdo, $campeonato_id, $fase, $t1, $t2)) {
            continue;
        }

        if (!$t2) {
            $stmt = $pdo->prepare(
                "INSERT INTO confrontos (campeonato_id, time1, time2, vencedor, fase, rodada) VALUES (?, ?, NULL, ?, ?, ?)"
            );
            $stmt->execute([
                $campeonato_id,
                $t1,
                $t1,
                $fase,
                $ultimaRodada + 1,
            ]);
        } else {
            $stmt = $pdo->prepare(
                "INSERT INTO confrontos (campeonato_id, time1, time2, fase, rodada) VALUES (?, ?, ?, ?, ?)"
            );
            $stmt->execute([
                $campeonato_id,
                $t1,
                $t2,
                $fase,
                $ultimaRodada + 1,
            ]);
        }
    }

    if ($fase === "winners") {
        $stmt = $pdo->prepare(
            "SELECT * FROM confrontos WHERE campeonato_id = ? AND fase = 'winners' AND rodada = ?"
        );
        $stmt->execute([$campeonato_id, $ultimaRodada]);
        $confs = $stmt->fetchAll();

        $perdedores = [];
        foreach ($confs as $conf) {
            if (!$conf["vencedor"]) {
                continue;
            }
            $perdedor =
                $conf["vencedor"] == $conf["time1"]
                    ? $conf["time2"]
                    : $conf["time1"];
            if ($perdedor) {
                $perdedores[] = $perdedor;
            }
        }

        $vencedoresLosersAnteriores = [];
        if (
            $ultimaRodada > 1 &&
            todosConfrontosComVencedor(
                $pdo,
                $campeonato_id,
                "losers",
                $ultimaRodada - 1
            )
        ) {
            $vencedoresLosersAnteriores = buscarVencedoresDaRodada(
                $pdo,
                $campeonato_id,
                "losers",
                $ultimaRodada - 1
            );
        }

        if (
            count($perdedores) === 1 &&
            count($vencedoresLosersAnteriores) === 1
        ) {
            $t1 = $vencedoresLosersAnteriores[0];
            $t2 = $perdedores[0];

            if (!confrontoJaExiste($pdo, $campeonato_id, "losers", $t1, $t2)) {
                $stmt = $pdo->prepare(
                    "INSERT INTO confrontos (campeonato_id, time1, time2, fase, rodada) VALUES (?, ?, ?, 'losers', ?)"
                );
                $stmt->execute([$campeonato_id, $t1, $t2, $ultimaRodada]);
            }
        } elseif (count($perdedores) > 0) {
            $times = array_merge($vencedoresLosersAnteriores, $perdedores);
            shuffle($times);
            for ($i = 0; $i < count($times); $i += 2) {
                $t1 = $times[$i];
                $t2 = $times[$i + 1] ?? null;

                if (
                    $t2 &&
                    confrontoJaExiste($pdo, $campeonato_id, "losers", $t1, $t2)
                ) {
                    continue;
                }

                if (!$t2) {
                    $stmt = $pdo->prepare(
                        "INSERT INTO confrontos (campeonato_id, time1, time2, vencedor, fase, rodada) VALUES (?, ?, NULL, ?, 'losers', ?)"
                    );
                    $stmt->execute([$campeonato_id, $t1, $t1, $ultimaRodada]);
                } else {
                    $stmt = $pdo->prepare(
                        "INSERT INTO confrontos (campeonato_id, time1, time2, fase, rodada) VALUES (?, ?, ?, 'losers', ?)"
                    );
                    $stmt->execute([$campeonato_id, $t1, $t2, $ultimaRodada]);
                }
            }
        }
    }
}

// Garante que o perdedor da final da winners caia para a losers
$stmt = $pdo->prepare(
    "SELECT * FROM confrontos WHERE campeonato_id = ? AND fase = 'winners' ORDER BY rodada DESC LIMIT 1"
);
$stmt->execute([$campeonato_id]);
$confrontoFinalWinners = $stmt->fetch();

if ($confrontoFinalWinners && $confrontoFinalWinners["vencedor"]) {
    $vencedor = $confrontoFinalWinners["vencedor"];
    $perdedor =
        $vencedor == $confrontoFinalWinners["time1"]
            ? $confrontoFinalWinners["time2"]
            : $confrontoFinalWinners["time1"];

    $stmt = $pdo->prepare(
        "SELECT COUNT(*) FROM confrontos WHERE campeonato_id = ? AND fase = 'losers' AND (time1 = ? OR time2 = ?)"
    );
    $stmt->execute([$campeonato_id, $perdedor, $perdedor]);
    $jaCaiu = $stmt->fetchColumn();

    if ($perdedor && $jaCaiu == 0) {
        $stmt = $pdo->prepare(
            "SELECT MAX(rodada) FROM confrontos WHERE campeonato_id = ? AND fase = 'losers'"
        );
        $stmt->execute([$campeonato_id]);
        $rodadaLosers = ((int) $stmt->fetchColumn()) ?: 1;

        $stmt = $pdo->prepare(
            "SELECT vencedor FROM confrontos WHERE campeonato_id = ? AND fase = 'losers' AND rodada = ?"
        );
        $stmt->execute([$campeonato_id, $rodadaLosers]);
        $vencedores = array_column($stmt->fetchAll(), "vencedor");

        $oponente = $vencedores[0] ?? null;

        if ($oponente) {
            $stmt = $pdo->prepare(
                "INSERT INTO confrontos (campeonato_id, time1, time2, fase, rodada) VALUES (?, ?, ?, 'losers', ?)"
            );
            $stmt->execute([
                $campeonato_id,
                $perdedor,
                $oponente,
                $rodadaLosers + 1,
            ]);
        } else {
            $stmt = $pdo->prepare(
                "INSERT INTO confrontos (campeonato_id, time1, time2, vencedor, fase, rodada) VALUES (?, ?, NULL, ?, 'losers', ?)"
            );
            $stmt->execute([
                $campeonato_id,
                $perdedor,
                $perdedor,
                $rodadaLosers + 1,
            ]);
        }
    }
}

// Final e grande final
$stmt = $pdo->prepare(
    "SELECT vencedor FROM confrontos WHERE campeonato_id = ? AND fase = 'winners' ORDER BY rodada DESC LIMIT 1"
);
$stmt->execute([$campeonato_id]);
$vencedorWinners = $stmt->fetchColumn();

$stmt = $pdo->prepare(
    "SELECT vencedor FROM confrontos WHERE campeonato_id = ? AND fase = 'losers' ORDER BY rodada DESC LIMIT 1"
);
$stmt->execute([$campeonato_id]);
$vencedorLosers = $stmt->fetchColumn();

$stmt = $pdo->prepare(
    "SELECT COUNT(*) FROM confrontos WHERE campeonato_id = ? AND fase = 'final'"
);
$stmt->execute([$campeonato_id]);
$finalExiste = $stmt->fetchColumn() > 0;

$stmt = $pdo->prepare(
    "SELECT MAX(rodada) FROM confrontos WHERE campeonato_id = ? AND fase = 'losers'"
);
$stmt->execute([$campeonato_id]);
$ultimaRodadaLosers = (int) $stmt->fetchColumn();

$losersResolvido = todosConfrontosComVencedor(
    $pdo,
    $campeonato_id,
    "losers",
    $ultimaRodadaLosers
);

if ($vencedorWinners && $vencedorLosers && !$finalExiste && $losersResolvido) {
    $stmt = $pdo->prepare(
        "INSERT INTO confrontos (campeonato_id, time1, time2, fase, rodada) VALUES (?, ?, ?, 'final', 1)"
    );
    $stmt->execute([$campeonato_id, $vencedorWinners, $vencedorLosers]);
}

$stmt = $pdo->prepare(
    "SELECT * FROM confrontos WHERE campeonato_id = ? AND fase = 'final' AND vencedor IS NOT NULL"
);
$stmt->execute([$campeonato_id]);
$final = $stmt->fetch();

if ($final) {
    $vencedorFinal = $final["vencedor"];
    $vencedorLosers = $final["time2"];

    $stmt = $pdo->prepare(
        "SELECT COUNT(*) FROM confrontos WHERE campeonato_id = ? AND fase = 'grande_final'"
    );
    $stmt->execute([$campeonato_id]);
    $grandeFinalExiste = $stmt->fetchColumn();

    if ($vencedorFinal == $vencedorLosers && $grandeFinalExiste == 0) {
        $stmt = $pdo->prepare(
            "INSERT INTO confrontos (campeonato_id, time1, time2, fase, rodada) VALUES (?, ?, ?, 'grande_final', 1)"
        );
        $stmt->execute([$campeonato_id, $final["time1"], $final["time2"]]);
    }
}

header("Location: chaveamento.php?id=" . $campeonato_id);
exit();
