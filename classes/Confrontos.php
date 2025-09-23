<?php
require_once "Database.php";

class Confronto
{
    public static function listarPorCampeonato($campeonato_id)
    {
        $pdo = Database::getConnection();

        $stmt = $pdo->prepare("
            SELECT id, campeonato_id, fase, rodada, time1, time2, vencedor
            FROM confrontos
            WHERE campeonato_id = ?
            ORDER BY fase, rodada, id
        ");
        $stmt->execute([$campeonato_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    public static function gerarChaveamento(int $campeonato_id): bool
    {
        $pdo = Database::getConnection();

        // Verifica se já existem confrontos
        $stmt = $pdo->prepare(
            "SELECT COUNT(*) FROM confrontos WHERE campeonato_id = ?",
        );
        $stmt->execute([$campeonato_id]);
        if ($stmt->fetchColumn() > 0) {
            throw new Exception(
                "Confrontos já foram gerados para este campeonato.",
            );
        }

        // Buscar times com horário de preferência
        $stmt = $pdo->prepare(
            "SELECT id, horario_preferencia FROM times WHERE campeonato_id = ? ORDER BY horario_preferencia",
        );
        $stmt->execute([$campeonato_id]);
        $times = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Agrupar times por horário
        $grupos = [
            "10h" => [],
            "12h" => [],
            "outros" => [],
        ];

        foreach ($times as $time) {
            $hora = $time["horario_preferencia"];
            if ($hora === "10h") {
                $grupos["10h"][] = $time["id"];
            } elseif ($hora === "12h") {
                $grupos["12h"][] = $time["id"];
            } else {
                $grupos["outros"][] = $time["id"];
            }
        }

        // Gerar confrontos dentro de cada grupo
        self::gerarConfrontosGrupo($grupos["10h"], $pdo, $campeonato_id);
        self::gerarConfrontosGrupo($grupos["12h"], $pdo, $campeonato_id);

        // Lidar com times restantes
        $restantes = [];
        $restantes_10h =
            count($grupos["10h"]) % 2 != 0 ? array_pop($grupos["10h"]) : null;
        $restantes_12h =
            count($grupos["12h"]) % 2 != 0 ? array_pop($grupos["12h"]) : null;

        if ($restantes_10h && $restantes_12h) {
            self::inserirConfronto(
                $pdo,
                $campeonato_id,
                $restantes_10h,
                $restantes_12h,
            );
        } elseif ($restantes_10h) {
            $restantes[] = $restantes_10h;
        } elseif ($restantes_12h) {
            $restantes[] = $restantes_12h;
        }

        // Adicionar times sem preferência específica
        $restantes = array_merge($restantes, $grupos["outros"]);
        if (count($restantes) > 1) {
            self::gerarConfrontosGrupo($restantes, $pdo, $campeonato_id);
        } elseif (count($restantes) == 1) {
            self::inserirConfronto($pdo, $campeonato_id, $restantes[0]);
        }

        return true;
    }

    /**
     * Gera confrontos para um grupo de times
     * @param array $times Array de IDs de times
     * @param PDO $pdo Conexão com o banco
     * @param int $campeonato_id ID do campeonato
     */
    private static function gerarConfrontosGrupo(
        array &$times,
        PDO $pdo,
        int $campeonato_id,
    ): void {
        shuffle($times);
        $total_times = count($times);

        for ($i = 0; $i < $total_times; $i += 2) {
            $time1 = $times[$i] ?? null;
            $time2 = $times[$i + 1] ?? null;

            if ($time1 && $time2) {
                self::inserirConfronto($pdo, $campeonato_id, $time1, $time2);
            } elseif ($time1) {
                self::inserirConfronto($pdo, $campeonato_id, $time1);
            }
        }
    }

    /**
     * Insere um confronto no banco de dados
     * @param PDO $pdo Conexão com o banco
     * @param int $campeonato_id ID do campeonato
     * @param int $time1 ID do time 1
     * @param int|null $time2 ID do time 2 (opcional)
     */
    private static function inserirConfronto(
        PDO $pdo,
        int $campeonato_id,
        int $time1,
        ?int $time2 = null,
    ): void {
        $stmt = $pdo->prepare(
            "INSERT INTO confrontos (campeonato_id, fase, rodada, time1, time2) VALUES (?, 'winners', 1, ?, ?)",
        );
        $stmt->execute([$campeonato_id, $time1, $time2]);
    }
}

?>
