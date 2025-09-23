<?php
require_once "classes/Database.php";
require_once "classes/Campeonato.php";
require_once "classes/Times.php";
require_once "classes/Usuario.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$pdo = Database::getConnection();

// Buscar Modalidades
$modalidades = $pdo
    ->query("SELECT * FROM modalidades")
    ->fetchAll(PDO::FETCH_ASSOC);

// Verificar Filtro
$filtro = isset($_GET["modalidade"]) ? (int) $_GET["modalidade"] : null;
$sql = "SELECT c.*, m.nome AS modalidade_nome FROM campeonatos c
        LEFT JOIN modalidades m ON c.modalidade_id = m.id";
$params = [];

if ($filtro) {
    $sql .= " WHERE c.modalidade_id = ?";
    $params[] = $filtro;
}

$sql .= " ORDER BY c.nome";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$campeonatos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Times Exibição
$times = Times::listarAtivos();
$statusTexto = '';
?>

<?php include "includes/header.php"; ?>

<style>
    /* Paleta de Cores */
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
        display: flex;
        flex-direction: column;
    }

    .page-container {
        max-width: 1400px;
        margin: 0 auto;
        padding: 0 20px;
        width: 100%;
    }

    .page-header {
        background: linear-gradient(120deg, var(--gray-800), var(--gray-900));
        color: white;
        padding: 3rem 0 2.5rem;
        margin: 20px auto 2.5rem;
        position: relative;
        overflow: hidden;
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: var(--border-radius);
        box-shadow: var(--box-shadow);
        max-width: 1400px;
        width: calc(100% - 40px);
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

    .page-title p {
        font-size: 1.15rem;
        opacity: 0.85;
        max-width: 600px;
        margin: 0 auto 1.5rem;
        color: var(--gray-300);
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

    .stats-bar {
        display: flex;
        justify-content: space-around;
        background: rgba(255, 255, 255, 0.08);
        backdrop-filter: blur(10px);
        border-radius: var(--border-radius);
        padding: 20px;
        margin: 0 auto 35px;
        max-width: 800px;
        border: 1px solid rgba(255, 255, 255, 0.1);
    }

    .stat-item {
        text-align: center;
        padding: 0 15px;
    }

    .stat-number {
        font-weight: 700;
        font-size: 2.4rem;
        color: white;
        line-height: 1;
        margin-bottom: 5px;
        font-feature-settings: "tnum";
    }

    .stat-label {
        color: var(--gray-300);
        font-size: 0.9rem;
        text-transform: uppercase;
        letter-spacing: 1px;
    }

    .main-content {
        background: white;
        border-radius: var(--border-radius);
        box-shadow: var(--box-shadow);
        padding: 30px;
        margin-bottom: 40px;
    }

    .filter-section {
        margin-bottom: 35px;
    }

    .filter-title {
        font-weight: 600;
        color: var(--gray-700);
        margin-bottom: 18px;
        display: flex;
        align-items: center;
        gap: 12px;
        font-size: 1.25rem;
    }

    .filter-title i {
        color: var(--accent-blue);
        background: rgba(59, 130, 246, 0.1);
        width: 40px;
        height: 40px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
    }

    .filter-buttons {
        display: flex;
        flex-wrap: wrap;
        gap: 12px;
    }

    .filter-btn {
        padding: 11px 22px;
        border: none;
        border-radius: 50px;
        background: var(--gray-100);
        color: var(--gray-700);
        font-weight: 500;
        cursor: pointer;
        transition: var(--transition);
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 0.95rem;
    }

    .filter-btn:hover {
        background: var(--gray-200);
        transform: translateY(-2px);
    }

    .filter-btn.active {
        background: var(--accent-blue);
        color: white;
        box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
    }

    .championship-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 30px;
        margin-top: 20px;
    }

    @media (max-width: 1200px) {
        .championship-grid {
            grid-template-columns: repeat(2, 1fr);
        }
    }
    
    @media (max-width: 768px) {
        .championship-grid {
            grid-template-columns: 1fr;
        }
    }

    .championship-card {
        background: white;
        border-radius: var(--border-radius);
        overflow: hidden;
        box-shadow: var(--box-shadow);
        transition: var(--transition);
        display: flex;
        flex-direction: column;
        height: 100%;
        position: relative;
        border: 1px solid var(--gray-100);
    }

    .championship-card:hover {
        transform: translateY(-7px);
        box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.08), 0 10px 10px -5px rgba(0, 0, 0, 0.02);
    }

    .card-header {
        background: linear-gradient(90deg, var(--gray-700), var(--gray-800));
        color: white;
        padding: 22px;
        position: relative;
        overflow: hidden;
    }

    .card-header::after {
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
        font-size: 1.5rem;
        margin-bottom: 8px;
        position: relative;
        z-index: 1;
    }

    .championship-sport {
        background: rgba(255, 255, 255, 0.15);
        padding: 6px 14px;
        border-radius: 20px;
        font-size: 0.9rem;
        display: inline-block;
        position: relative;
        z-index: 1;
    }

    .card-body {
        padding: 25px;
        flex-grow: 1;
    }

    .info-item {
        display: flex;
        margin-bottom: 18px;
        align-items: flex-start;
    }

    .info-icon {
        width: 36px;
        height: 36px;
        background: var(--gray-100);
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-right: 14px;
        flex-shrink: 0;
        color: var(--accent-blue);
    }

    .info-content {
        flex: 1;
    }

    .info-content strong {
        color: var(--gray-700);
        display: block;
        margin-bottom: 3px;
        font-weight: 600;
    }

    .info-content div {
        color: var(--gray-600);
    }

    .teams-list {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        margin-top: 8px;
    }

    .team-tag {
        background: var(--gray-100);
        padding: 6px 12px;
        border-radius: 6px;
        font-size: 0.85rem;
        color: var(--gray-700);
        transition: var(--transition);
    }

    .team-tag:hover {
        background: var(--accent-teal);
        color: white;
        transform: translateY(-2px);
    }

    .card-footer {
        padding: 18px 25px;
        background: var(--gray-50);
        border-top: 1px solid var(--gray-100);
        display: flex;
        gap: 12px;
    }

    .action-btn {
        flex: 1;
        padding: 11px;
        border: none;
        border-radius: 8px;
        font-weight: 500;
        cursor: pointer;
        transition: var(--transition);
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        font-size: 0.95rem;
    }

    .btn-details {
        background: var(--accent-blue);
        color: white;
    }

    .btn-details:hover {
        background: #2563eb;
        box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
    }

    .btn-edit {
        background: var(--gray-200);
        color: var(--gray-700);
    }

    .btn-edit:hover {
        background: var(--gray-300);
        box-shadow: 0 4px 12px rgba(156, 163, 175, 0.2);
    }

    .empty-state {
        grid-column: 1 / -1;
        text-align: center;
        padding: 50px 20px;
        background: white;
        border-radius: var(--border-radius);
        box-shadow: var(--box-shadow);
        border: 1px solid var(--gray-100);
    }

    .empty-state i {
        font-size: 4rem;
        color: var(--gray-300);
        margin-bottom: 20px;
        opacity: 0.7;
    }

    .empty-state h3 {
        font-weight: 700;
        color: var(--gray-700);
        margin-bottom: 15px;
        font-size: 1.5rem;
    }

    .empty-state p {
        color: var(--gray-600);
        max-width: 500px;
        margin: 0 auto;
    }

    @media (max-width: 768px) {
        .stats-bar {
            flex-direction: column;
            gap: 25px;
        }
        
        .filter-buttons {
            justify-content: center;
        }
        
        .page-title h1 {
            font-size: 2.2rem;
        }

        .page-header {
            width: calc(100% - 30px);
            margin: 15px auto 2rem;
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

<div class="page-header">
    <div class="page-container">
        <div class="page-title">
            <h1>Campeonatos Ativos</h1>
            <p>Gerencie e acompanhe todos os torneios em andamento</p>
        </div>
        
        <!-- BARRA DE ESTATÍSTICAS ATUALIZADA -->
        <div class="stats-bar">
            <div class="stat-item">
                <div class="stat-number"><?= count($campeonatos) ?></div>
                <div class="stat-label">Campeonatos</div>
            </div>
            <div class="stat-item">
                <div class="stat-number"><?= count($times) ?></div>
                <div class="stat-label">Times</div>
            </div>
            <div class="stat-item">
                <div class="stat-number"><?= count($modalidades) ?></div>
                <div class="stat-label">Modalidades</div>
            </div>
        </div>
    </div>
</div>

<div class="page-container">
    <div class="main-content">
        <div class="filter-section">
            <h3 class="filter-title">
                <i class="fas fa-filter"></i>
                Filtrar por Modalidade
            </h3>
            <div class="filter-buttons">
                <a href="index.php" class="filter-btn <?= is_null($filtro) ? 'active' : '' ?>">
                    <i class="fas fa-list"></i> Todos
                </a>
                <?php foreach ($modalidades as $m): ?>
                    <a href="?modalidade=<?= $m["id"] ?>" class="filter-btn <?= $filtro == $m["id"] ? 'active' : '' ?>">
                        <?= htmlspecialchars($m["nome"]) ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>

        <?php if (empty($campeonatos)): ?>
            <div class="empty-state">
                <i class="fas fa-trophy"></i>
                <h3>Nenhum campeonato encontrado</h3>
                <p>Não encontramos campeonatos para a modalidade selecionada</p>
            </div>
        <?php else: ?>
            <div class="championship-grid">
                <?php foreach ($campeonatos as $camp): 
                    // Formatar datas
                    $data_limite_inscricao = $camp["data_limite_inscricao"] ? 
                        date('d/m/Y', strtotime($camp["data_limite_inscricao"])) : 
                        'Não definida';
                    
                    $data_inicio_camp = $camp["data_inicio"] ? 
                        date('d/m/Y', strtotime($camp["data_inicio"])) : 
                        'Não definida';

                    // ✅ Corrigido: agora dentro do foreach
                    $statusTexto = '';
                    if ($camp['ativo'] == 1) {
                        $statusTexto = 'Ativo';
                    } elseif ($camp['ativo'] == 2) {
                        $statusTexto = 'Finalizado';
                    }
                ?>
                    <div class="championship-card">
                        <div class="card-header">
                            <h3 class="championship-name"><?= htmlspecialchars($camp["nome"]) ?></h3>
                            <span class="championship-sport"><?= htmlspecialchars($camp["modalidade_nome"] ?? "Não definida") ?> - <?= $statusTexto ?></span>
                        </div>
                        <div class="card-body">
                            <div class="info-item">
                                <div class="info-icon">
                                    <i class="fas fa-calendar-check"></i>
                                </div>
                                <div class="info-content">
                                    <strong>Inscrições até</strong>
                                    <div><?= htmlspecialchars($data_limite_inscricao) ?></div>
                                </div>
                            </div>
                            <div class="info-item">
                                <div class="info-icon">
                                    <i class="fas fa-flag-checkered"></i>
                                </div>
                                <div class="info-content">
                                    <strong>Início do Campeonato</strong>
                                    <div><?= htmlspecialchars($data_inicio_camp) ?></div>
                                </div>
                            </div>
                            <div class="info-item">
                                <div class="info-icon">
                                    <i class="fas fa-money-bill-wave"></i>
                                </div>
                                <div class="info-content">
                                    <strong>Taxa de Inscrição</strong>
                                    <div>R$ <?= htmlspecialchars($camp["taxa"]) ?></div>
                                </div>
                            </div>
                            <div class="info-item">
                                <div class="info-icon">
                                    <i class="fas fa-users"></i>
                                </div>
                                <div class="info-content">
                                    <strong>Times Participantes</strong>
                                    <div class="teams-list">
                                        <?php
                                        $idsTimes = array_map("intval", explode(",", $camp["times_participantes"]));
                                        $nomesTimes = [];
                                        foreach ($times as $time) {
                                            if (in_array($time["id"], $idsTimes)) {
                                                $nomesTimes[] = $time["nome"];
                                            }
                                        }
                                        foreach ($nomesTimes as $nome): ?>
                                            <span class="team-tag"><?= htmlspecialchars($nome) ?></span>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="card-footer">
                            <a href="campeonatos/ver.php?id=<?= $camp["id"] ?>" class="action-btn btn-details">
                                <i class="fas fa-info-circle"></i> Ver Detalhes
                            </a>
                            <?php if (isset($_SESSION["usuario"]) && (int) $_SESSION["usuario"]["tipo"] != 0): ?>
                                <a href="campeonatos/editar.php?id=<?= $camp["id"] ?>" class="action-btn btn-edit">
                                    <i class="fas fa-edit"></i> Editar
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include "includes/footer.php"; ?>