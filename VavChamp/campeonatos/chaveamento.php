<?php
require_once "../classes/Database.php";
session_start();

$pdo = Database::getConnection();

if (!isset($_GET["id"])) {
    die("Campeonato não especificado.");
}
$campeonato_id = (int) $_GET["id"];

// Busca confrontos
$stmt = $pdo->prepare(
    "SELECT * FROM confrontos WHERE campeonato_id = ? ORDER BY fase, rodada, id"
);
$stmt->execute([$campeonato_id]);
$confrontos = $stmt->fetchAll();

// Organiza confrontos por fase e rodada
$organizado = [];
foreach ($confrontos as $c) {
    $organizado[$c["fase"]][$c["rodada"]][] = $c;
}

function nome_time($pdo, $id)
{
    if (!$id) {
        return "(W.O.)";
    }

    $stmt = $pdo->prepare(
        "SELECT nome, horario_preferencia FROM times WHERE id = ?"
    );
    $stmt->execute([$id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        return "Time $id";
    }

    $mostrarHorario =
        isset($_SESSION["usuario"]) && $_SESSION["usuario"]["tipo"] != 0;
    return $mostrarHorario
        ? $row["nome"] . " (" . $row["horario_preferencia"] . ")"
        : $row["nome"];
}

include "../includes/header.php";
?>

<style>
    /* Mantendo a mesma paleta de cores da página principal */
    :root {
        --gray-50: #f9fafb;
        --gray-100: #f3f4f6;
        --gray-200: #e5e7eb;
        --gray-300: #d1d5db;
        --gray-400: #9ca3af;
        --gray-500: #6b7280;
        --gray-600: #4b5563;
        --gray-700: #374151;
        --gray-800: #1f2937;
        --gray-900: #111827;
        --accent-blue: #3b82f6;
        --accent-teal: #14b8a6;
        --accent-amber: #f59e0b;
        --border-radius: 12px;
        --box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05), 0 8px 10px -6px rgba(0, 0, 0, 0.02);
        --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    body {
        background: linear-gradient(135deg, #f0f2f5 0%, #e4e7eb 100%);
        color: var(--gray-800);
        font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, 'Open Sans', 'Helvetica Neue', sans-serif;
        line-height: 1.6;
        min-height: 100vh;
        padding-bottom: 40px;
    }

    .page-container {
        max-width: 1400px;
        margin: 0 auto;
        padding: 0 20px;
    }

    /* Cabeçalho com bordas arredondadas em todos os lados */
    .page-header-container {
        background: linear-gradient(120deg, var(--gray-800), var(--gray-900));
        color: white;
        padding: 3rem 0 2.5rem;
        margin: 2.5rem auto;
        position: relative;
        overflow: hidden;
        border-radius: var(--border-radius);
        box-shadow: var(--box-shadow);
    }

    .page-header-container::before {
        content: "";
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: radial-gradient(circle at top right, rgba(59, 130, 246, 0.1) 0%, transparent 40%);
        pointer-events: none;
    }

    .page-title {
        text-align: center;
        position: relative;
        padding-bottom: 25px;
        margin-bottom: 25px;
    }

    .page-title h1 {
        font-weight: 700;
        font-size: 2.5rem;
        letter-spacing: -0.025em;
        margin-bottom: 0.5rem;
        color: white;
    }

    .page-title::after {
        content: "";
        position: absolute;
        bottom: 0;
        left: 50%;
        transform: translateX(-50%);
        width: 80px;
        height: 4px;
        background: linear-gradient(to right, var(--accent-blue), var(--accent-teal));
        border-radius: 2px;
    }

    .main-content {
        background: white;
        border-radius: var(--border-radius);
        box-shadow: var(--box-shadow);
        padding: 30px;
        margin-bottom: 40px;
    }

    .bracket-section {
        margin-top: 30px;
    }

    .bracket-title {
        font-weight: 700;
        color: var(--gray-800);
        margin-bottom: 25px;
        font-size: 1.6rem;
        text-align: center;
        position: relative;
        padding-bottom: 15px;
    }

    .bracket-title::after {
        content: "";
        position: absolute;
        bottom: 0;
        left: 50%;
        transform: translateX(-50%);
        width: 60px;
        height: 3px;
        background: linear-gradient(to right, var(--accent-blue), var(--accent-teal));
        border-radius: 2px;
    }

    .fase-container {
        display: flex;
        flex-wrap: nowrap;
        overflow-x: auto;
        gap: 30px;
        padding: 20px 10px 30px;
        margin-bottom: 30px;
    }

    .fase {
        min-width: 320px;
        background: var(--gray-50);
        border-radius: var(--border-radius);
        padding: 20px;
        box-shadow: var(--box-shadow);
    }

    .fase-header {
        padding-bottom: 15px;
        margin-bottom: 20px;
        border-bottom: 1px solid var(--gray-200);
    }

    .fase-titulo {
        font-weight: 600;
        font-size: 1.3rem;
        color: var(--accent-blue);
        text-transform: uppercase;
        letter-spacing: 1px;
    }

    .confronto {
        background-color: white;
        border-radius: var(--border-radius);
        box-shadow: var(--box-shadow);
        padding: 20px;
        margin-bottom: 20px;
        transition: var(--transition);
        border: 1px solid var(--gray-200);
    }

    .confronto:hover {
        transform: translateY(-5px);
        box-shadow: 0 15px 30px -10px rgba(0, 0, 0, 0.1);
    }

    .match-info {
        display: flex;
        flex-direction: column;
        gap: 12px;
    }

    .team-option {
        display: flex;
        align-items: center;
        padding: 12px 15px;
        border-radius: 8px;
        cursor: pointer;
        transition: var(--transition);
        border: 1px solid var(--gray-200);
    }

    .team-option:hover {
        background: var(--gray-50);
    }

    .team-option.selected {
        background: rgba(59, 130, 246, 0.1);
        border-color: var(--accent-blue);
    }

    .team-option input[type="radio"] {
        margin-right: 12px;
        width: 18px;
        height: 18px;
        cursor: pointer;
    }

    .team-option label {
        flex: 1;
        cursor: pointer;
        font-weight: 500;
        color: var(--gray-700);
    }

    .vs-separator {
        text-align: center;
        margin: 8px 0;
        font-weight: 600;
        color: var(--gray-500);
        position: relative;
    }

    .vs-separator::before,
    .vs-separator::after {
        content: "";
        position: absolute;
        top: 50%;
        width: 40%;
        height: 1px;
        background: var(--gray-300);
    }

    .vs-separator::before {
        left: 0;
    }

    .vs-separator::after {
        right: 0;
    }

    .form-actions {
        text-align: center;
        margin-top: 40px;
        padding-top: 30px;
        border-top: 1px solid var(--gray-200);
    }

    .btn-save {
        padding: 14px 40px;
        border: none;
        border-radius: 8px;
        font-weight: 500;
        cursor: pointer;
        transition: var(--transition);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        font-size: 1.1rem;
        background: var(--accent-teal);
        color: white;
    }

    .btn-save:hover {
        background: #0d9488;
        box-shadow: 0 4px 12px rgba(20, 184, 166, 0.3);
        transform: translateY(-3px);
    }

    .no-brackets {
        text-align: center;
        padding: 50px 20px;
    }

    .no-brackets i {
        font-size: 4rem;
        color: var(--gray-300);
        margin-bottom: 20px;
        opacity: 0.7;
    }

    .no-brackets h3 {
        font-weight: 700;
        color: var(--gray-700);
        margin-bottom: 15px;
        font-size: 1.5rem;
    }

    .no-brackets p {
        color: var(--gray-600);
        max-width: 500px;
        margin: 0 auto;
    }

    @media (max-width: 768px) {
        .fase {
            min-width: 280px;
        }
        
        .page-title h1 {
            font-size: 2.2rem;
        }
        
        .page-header-container {
            margin: 1.5rem auto;
        }
    }
</style>

<!-- Cabeçalho com o mesmo comprimento do conteúdo e bordas arredondadas -->
<div class="page-container">
    <div class="page-header-container">
        <div class="page-title">
            <h1>Chaveamento do Campeonato</h1>
        </div>
    </div>
</div>

<div class="page-container">
    <div class="main-content">
        <?php if (!empty($organizado)): ?>
            <?php if (
                isset($_SESSION["usuario"]) &&
                $_SESSION["usuario"]["tipo"] != 0
            ): ?>
                <form action="salvar_vencedores.php" method="post">
                    <input type="hidden" name="campeonato_id" value="<?= $campeonato_id ?>">
            <?php endif; ?>
            
            <?php foreach (
                ["winners", "losers", "final", "grande_final"]
                as $fase_nome
            ): ?>
                <?php if (!empty($organizado[$fase_nome])): ?>
                    <div class="bracket-section">
                        <h3 class="bracket-title"><?= ucfirst(
                            str_replace("_", " ", $fase_nome)
                        ) ?> Bracket</h3>
                        
                        <div class="fase-container">
                            <?php foreach (
                                $organizado[$fase_nome]
                                as $rodada => $confrontos
                            ): ?>
                                <div class="fase">
                                    <div class="fase-header">
                                        <h4 class="fase-titulo">Rodada <?= $rodada ?></h4>
                                    </div>
                                    
                                    <?php foreach ($confrontos as $c): ?>
                                        <div class="confronto">
                                            <div class="match-info">
                                                <div class="team-option <?= $c["vencedor"] == $c["time1"] ? "selected" : "" ?>">
                                                    <input 
                                                        type="radio" 
                                                        name="vencedor[<?= $c["id"] ?>]" 
                                                        value="<?= $c["time1"] ?>" 
                                                        <?= $c["vencedor"] == $c["time1"] ? "checked" : "" ?> 
                                                        <?= (isset($_SESSION["usuario"]) && $_SESSION["usuario"]["tipo"] != 0) ? "required" : "disabled" ?>
                                                    >
                                                    <label><?= nome_time($pdo, $c["time1"]) ?></label>
                                                </div>
                                                
                                                <div class="vs-separator">VS</div>
                                                
                                                <div class="team-option <?= $c["vencedor"] == $c["time2"] ? "selected" : "" ?>">
                                                    <input 
                                                        type="radio" 
                                                        name="vencedor[<?= $c["id"] ?>]" 
                                                        value="<?= $c["time2"] ?>" 
                                                        <?= $c["vencedor"] == $c["time2"] ? "checked" : "" ?> 
                                                        <?= $c["time2"] && (isset($_SESSION["usuario"]) && $_SESSION["usuario"]["tipo"] != 0) ? "" : "disabled" ?>
                                                    >
                                                    <label><?= nome_time($pdo, $c["time2"]) ?></label>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endforeach; ?>
            
            <?php if (
                isset($_SESSION["usuario"]) &&
                $_SESSION["usuario"]["tipo"] != 0
            ): ?>
                <div class="form-actions">
                    <button type="submit" class="btn-save">
                        <i class="fas fa-save"></i> Salvar Vencedores
                    </button>
                </div>
                </form>
            <?php endif; ?>
        <?php else: ?>
            <div class="no-brackets">
                <i class="fas fa-sitemap"></i>
                <h3>Chaveamento não disponível</h3>
                <p>O chaveamento deste campeonato ainda não foi gerado ou está indisponível no momento.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include "../includes/footer.php"; ?>