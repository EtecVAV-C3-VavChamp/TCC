<?php
require_once "Database.php";

class Campeonato
{
    // Lista campeonatos ativos com JOIN em modalidades (usado na tela inicial)
    public static function listarAtivos()
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->query(
            "SELECT c.*, m.nome AS modalidade_nome
             FROM campeonatos c
             LEFT JOIN modalidades m ON c.modalidade_id = m.id
             WHERE c.ativo = 1
             ORDER BY c.data_limite_inscricao ASC",
        );
        return $stmt->fetchAll();
    }

    public static function obterPorId($id)
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT * FROM campeonatos WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public static function criar($dados)
    {
        $pdo = Database::getConnection();

        $stmt = $pdo->prepare(
            "INSERT INTO campeonatos (nome, descr, data_limite_inscricao, data_inicio, taxa, times_participantes, modalidade_id)
             VALUES (:nome, :descr, :data_limite_inscricao, :data_inicio, :taxa, :times_participantes, :modalidade_id)",
        );

        $times = is_array($dados["times_participantes"])
            ? implode(",", $dados["times_participantes"])
            : $dados["times_participantes"];

        return $stmt->execute([
            ":nome" => $dados["nome"],
            ":descr" => $dados["descr"],
            ":data_limite_inscricao" => $dados["data_limite_inscricao"],
            ":data_inicio" => $dados["data_inicio"],
            ":taxa" => $dados["taxa"],
            ":times_participantes" => $times,
            ":modalidade_id" => $dados["modalidade_id"],
        ]);
    }

    public static function atualizar($id, $dados) {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("UPDATE campeonatos 
            SET nome = ?, descr = ?, data_limite_inscricao = ?, data_inicio = ?, taxa = ?, 
                times_participantes = ?, modalidade_id = ?, ativo = ? 
            WHERE id = ?");
        return $stmt->execute([
            $dados['nome'],
            $dados['descr'],
            $dados['data_limite_inscricao'],
            $dados['data_inicio'],
            $dados['taxa'],
            implode(',', $dados['times_participantes']),
            $dados['modalidade_id'],
            $dados['ativo'],
            $id
        ]);
    }

    public static function gerarChaveamento($campeonato_id)
    {
        $pdo = Database::getConnection();

        // Verifica se já existem confrontos
        $verifica = $pdo->prepare(
            "SELECT COUNT(*) FROM confrontos WHERE campeonato_id = ?",
        );
        $verifica->execute([$campeonato_id]);

        if ($verifica->fetchColumn() > 0) {
            return false; // Já existem confrontos
        }

        // Obtém os times participantes
        $stmt = $pdo->prepare(
            "SELECT times_participantes FROM campeonatos WHERE id = ?",
        );
        $stmt->execute([$campeonato_id]);
        $lista = $stmt->fetchColumn();
        $ids = array_filter(array_map("intval", explode(",", $lista)));

        // Embaralha os times
        shuffle($ids);

        // Cria confrontos da rodada 1 da fase 'winners'
        $rodada = 1;
        for ($i = 0; $i < count($ids); $i += 2) {
            $time1 = $ids[$i];
            $time2 = $ids[$i + 1] ?? null;

            $insert = $pdo->prepare(
                "INSERT INTO confrontos (campeonato_id, fase, rodada, time1, time2) VALUES (?, 'winners', ?, ?, ?)",
            );
            $insert->execute([$campeonato_id, $rodada, $time1, $time2]);
        }

        return true;
    }
}

?>
