<?php 
session_start();
include "../includes/header.php"; 

// Função para formatar datas no formato brasileiro
function formatarData($data) {
    return $data ? date('d/m/Y', strtotime($data)) : 'Não definida';
}

require_once "../classes/Campeonato.php";
require_once "../classes/Times.php";
require_once "../classes/Confrontos.php";
require_once "../classes/Usuario.php";
require_once "../classes/Modalidades.php";

// Verifica se o usuário é admin
$isAdmin = false;
if (isset($_SESSION['usuario']) && in_array($_SESSION['usuario']['tipo'], [1, 2])) {
    $isAdmin = true;
}

$id = $_GET["id"] ?? 0;
$camp = Campeonato::obterPorId($id);
if (!$camp) {
    echo "<div class='alert alert-warning container mt-4'>Campeonato não encontrado.</div>";
    include "../includes/footer.php";
    exit();
}
$times = Times::listar();
$idsTimes = array_map("intval", explode(",", $camp["times_participantes"]));
$nomesTimes = [];
foreach ($times as $time) {
    if (in_array($time["id"], $idsTimes)) {
        $nomesTimes[$time["id"]] = $time["nome"];
    }
}
// Busca confrontos com vencedores
$confrontos = Confronto::listarPorCampeonato($id);
$confrontos_organizados = [];
foreach ($confrontos as $c) {
    if ($c["vencedor"]) {
        $fase = $c["fase"];
        $rodada = $c["rodada"];
        $confrontos_organizados[$fase][$rodada][] = $c;
    }
}
$modalidadeNome = Modalidade::nomePorId($camp["modalidade_id"] ?? 0);
$statusTexto = '';
if ($camp['ativo'] == 1) {
    $statusTexto = 'Ativo';
} elseif ($camp['ativo'] == 2) {
    $statusTexto = 'Finalizado';
}
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
        margin: 0; /* Reset de margens */
    }

    .page-container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 0 20px;
    }

    /* HEADER MODIFICADO - MESMA BORDA E TAMANHO DA DIV ABAIXO */
    .page-header-container {
        max-width: 1200px;
        margin: 20px auto 2.5rem;
        padding: 0 20px;
    }
    
    .page-header {
        background: linear-gradient(120deg, var(--gray-800), var(--gray-900));
        color: white;
        padding: 3rem 0 2.5rem;
        position: relative;
        overflow: hidden;
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: var(--border-radius);
        box-shadow: var(--box-shadow);
        width: 100%;
    }

    .page-header::before {
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

    .championship-details-card {
        background: white;
        border-radius: var(--border-radius);
        overflow: hidden;
        box-shadow: var(--box-shadow);
        margin-bottom: 30px;
        border: 1px solid var(--gray-100);
    }

    .championship-header {
        background: linear-gradient(90deg, var(--gray-700), var(--gray-800));
        color: white;
        padding: 25px;
        position: relative;
        overflow: hidden;
    }

    .championship-header::after {
        content: "";
        position: absolute;
        top: 0;
        right: 0;
        width: 80px;
        height: 80px;
        background: linear-gradient(45deg, transparent, rgba(255, 255, 255, 0.1));
        border-radius: 0 0 0 100%;
    }

    .championship-name {
        font-weight: 700;
        font-size: 1.8rem;
        margin-bottom: 10px;
        position: relative;
        z-index: 1;
    }

    .championship-sport {
        background: rgba(255, 255, 255, 0.15);
        padding: 8px 16px;
        border-radius: 20px;
        font-size: 1rem;
        display: inline-block;
        position: relative;
        z-index: 1;
    }

    .championship-body {
        padding: 25px;
    }

    .info-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 25px;
        margin-bottom: 25px;
    }

    .info-item {
        background: var(--gray-50);
        border-radius: var(--border-radius);
        padding: 20px;
    }

    .info-label {
        font-weight: 600;
        color: var(--gray-700);
        margin-bottom: 8px;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .info-label i {
        color: var(--accent-blue);
        font-size: 1.2rem;
    }

    .info-value {
        color: var(--gray-600);
        font-size: 1.1rem;
    }

    .teams-section {
        margin-top: 30px;
    }

    .teams-title {
        font-weight: 600;
        color: var(--gray-700);
        margin-bottom: 15px;
        font-size: 1.3rem;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .teams-list {
        display: flex;
        flex-wrap: wrap;
        gap: 12px;
    }

    .team-tag {
        background: var(--gray-100);
        padding: 8px 16px;
        border-radius: 6px;
        font-size: 0.95rem;
        color: var(--gray-700);
        transition: var(--transition);
    }

    .team-tag:hover {
        background: var(--accent-teal);
        color: white;
        transform: translateY(-3px);
    }

    .action-center {
        text-align: center;
        margin-top: 30px;
    }

    .btn-chaveamento {
        padding: 12px 35px;
        border: none;
        border-radius: 8px;
        font-weight: 500;
        cursor: pointer;
        transition: var(--transition);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        font-size: 1.05rem;
        background: var(--accent-blue);
        color: white;
        text-decoration: none !important;
    }

    .btn-chaveamento:hover {
        background: #2563eb;
        box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
        transform: translateY(-3px);
    }

    .winners-section {
        background: white;
        border-radius: var(--border-radius);
        box-shadow: var(--box-shadow);
        padding: 30px;
        margin-top: 30px;
        border: 1px solid var(--gray-100);
    }

    .section-title {
        font-weight: 700;
        color: var(--gray-800);
        margin-bottom: 25px;
        font-size: 1.6rem;
        text-align: center;
        position: relative;
        padding-bottom: 15px;
    }

    .section-title::after {
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

    .bracket-container {
        margin-bottom: 35px;
    }

    .bracket-title {
        font-weight: 600;
        color: var(--accent-blue);
        margin-bottom: 15px;
        font-size: 1.3rem;
        text-transform: uppercase;
        letter-spacing: 1px;
        padding-bottom: 10px;
        border-bottom: 1px solid var(--gray-200);
    }

    .round-container {
        margin-bottom: 20px;
        padding-left: 20px;
    }

    .round-title {
        font-weight: 600;
        color: var(--gray-700);
        margin-bottom: 12px;
        font-size: 1.1rem;
    }

    .match-list {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
        gap: 15px;
    }

    .match-item {
        background: var(--gray-50);
        border-radius: var(--border-radius);
        padding: 15px;
        border-left: 3px solid var(--accent-teal);
    }

    .match-teams {
        font-weight: 500;
        margin-bottom: 8px;
    }

    .match-winner {
        font-weight: 600;
        color: var(--accent-blue);
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .empty-winners {
        text-align: center;
        padding: 40px 20px;
        background: var(--gray-50);
        border-radius: var(--border-radius);
    }

    .empty-winners i {
        font-size: 3rem;
        color: var(--gray-300);
        margin-bottom: 20px;
        opacity: 0.7;
    }

    .empty-winners h4 {
        font-weight: 600;
        color: var(--gray-600);
        margin-bottom: 15px;
    }

    @media (max-width: 768px) {
        .info-grid {
            grid-template-columns: 1fr;
        }
        
        .match-list {
            grid-template-columns: 1fr;
        }
        
        .page-title h1 {
            font-size: 2.2rem;
        }
        
        .page-header-container {
            padding: 0 15px;
        }
    }

    a.action-btn,
    a.action-btn:hover,
    a.filter-btn,
    a.filter-btn:visited,
    a.filter-btn:hover,
    a.filter-btn:active {
        text-decoration: none !important;
    }
</style>

<!-- Container para o header com mesma largura do conteúdo -->
<div class="page-header-container">
    <div class="page-header">
        <div class="page-title">
            <h1>Detalhes do Campeonato</h1>
        </div>
    </div>
</div>

<div class="page-container">
    <div class="main-content">
        <div class="championship-details-card">
            <div class="championship-header">
                <h2 class="championship-name"><?= htmlspecialchars($camp["nome"]) ?></h2>
                <span class="championship-sport"><?= htmlspecialchars($modalidadeNome) ?> - <?= $statusTexto ?></span>
            </div>
            
            <div class="championship-body">
                <div class="info-grid">
                    <div class="info-item">
                        <div class="info-label">
                            <i class="fas fa-calendar-check"></i>
                            Data limite de inscrição
                        </div>
                        <div class="info-value"><?= formatarData($camp["data_limite_inscricao"]) ?></div>
                    </div>
                    
                    <div class="info-item">
                        <div class="info-label">
                            <i class="fas fa-flag-checkered"></i>
                            Início do Campeonato
                        </div>
                        <div class="info-value"><?= formatarData($camp["data_inicio"]) ?></div>
                    </div>
                    
                    <div class="info-item">
                        <div class="info-label">
                            <i class="fas fa-money-bill-wave"></i>
                            Taxa de inscrição
                        </div>
                        <div class="info-value">R$ <?= number_format($camp["taxa"], 2, ',', '.') ?></div>
                    </div>
                </div>
                
                <div class="teams-section">
                    <div class="teams-title">
                        <i class="fas fa-users"></i>
                        Times Participantes
                    </div>
                    
                    <?php if (!empty($nomesTimes)): ?>
                        <div class="teams-list">
                            <?php foreach ($nomesTimes as $nome): ?>
                                <span class="team-tag"><?= htmlspecialchars($nome) ?></span>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-light">Nenhum time inscrito neste campeonato</div>
                    <?php endif; ?>
                </div>
                
                <?php if ($isAdmin): ?>
                    <div class="action-center">
                        <a href="chaveamento.php?id=<?= $camp["id"] ?>" class="btn-chaveamento">
                            <i class="fas fa-sitemap"></i> Ver Chaveamento
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <?php if (!empty($confrontos_organizados)): ?>
            <div class="winners-section">
                <h3 class="section-title">Vencedores por Rodada</h3>
                
                <?php foreach ($confrontos_organizados as $fase => $rodadas): ?>
                    <div class="bracket-container">
                        <h4 class="bracket-title"><?= ucfirst(str_replace("_", " ", $fase)) ?> Bracket</h4>
                        
                        <?php foreach ($rodadas as $rodada => $lista): ?>
                            <div class="round-container">
                                <h5 class="round-title">Rodada <?= $rodada ?></h5>
                                
                                <div class="match-list">
                                    <?php foreach ($lista as $c): ?>
                                        <div class="match-item">
                                            <div class="match-teams">
                                                <?= $nomesTimes[$c["time1"]] ?? "Time " . $c["time1"] ?>
                                                <strong>vs</strong>
                                                <?= $nomesTimes[$c["time2"]] ?? "Time " . $c["time2"] ?>
                                            </div>
                                            <div class="match-winner">
                                                <i class="fas fa-trophy"></i>
                                                Vencedor: <?= $nomesTimes[$c["vencedor"]] ?? "Time " . $c["vencedor"] ?>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="winners-section">
                <h3 class="section-title">Vencedores por Rodada</h3>
                <div class="empty-winners">
                    <i class="fas fa-trophy"></i>
                    <h4>Ainda não há confrontos com vencedores registrados</h4>
                    <p>Os resultados serão exibidos aqui após o início do campeonato</p>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include "../includes/footer.php"; ?>