<?php
require_once "../classes/Database.php";
require_once "../classes/Usuario.php";
session_start();

Usuario::isAdmin(); // Garante que o usuário é admin (tipo 1 ou 2)

$pdo = Database::getConnection();
$tipoLogado = $_SESSION["usuario"]["tipo"] ?? 0;

// Busca usuários, ocultando super admins se for admin comum
if ($tipoLogado == 1) {
    $stmt = $pdo->prepare(
        "SELECT id, nome, email, tipo FROM usuarios WHERE tipo != 2 ORDER BY nome"
    );
    $stmt->execute();
} else {
    $stmt = $pdo->query(
        "SELECT id, nome, email, tipo FROM usuarios ORDER BY nome"
    );
}

$usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);

function tipoParaTexto($tipo)
{
    return match ((int) $tipo) {
        0 => "Usuário Comum",
        1 => "Administrador",
        2 => "Super Admin",
        default => "Desconhecido",
    };
}

function tipoParaCor($tipo) {
    return match ((int) $tipo) {
        0 => "bg-blue-100 text-blue-800",
        1 => "bg-green-100 text-green-800",
        2 => "bg-purple-100 text-purple-800",
        default => "bg-gray-100 text-gray-800",
    };
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
        --accent-blue-dark: #2563eb;
        --accent-teal: #14b8a6;
        --accent-amber: #f59e0b;
        --accent-red: #ef4444;
        --border-radius: 12px;
        --box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05), 0 8px 10px -6px rgba(0, 0, 0, 0.02);
        --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    * {
        box-sizing: border-box;
        margin: 0;
        padding: 0;
    }

    body {
        background: linear-gradient(135deg, #f0f2f5 0%, #e4e7eb 100%);
        color: var(--gray-800);
        font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, 'Open Sans', 'Helvetica Neue', sans-serif;
        line-height: 1.6;
        min-height: 100vh;
        padding-bottom: 40px;
    }

    /* CONTAINER PRINCIPAL - MESMO PARA HEADER E CONTEÚDO */
    .page-container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 0 20px;
    }

    /* HEADER PERFEITAMENTE ALINHADO */
    .page-header {
        background: linear-gradient(120deg, var(--gray-800), var(--gray-900));
        border-radius: var(--border-radius);
        box-shadow: var(--box-shadow);
        overflow: hidden;
        position: relative;
        margin: 20px auto 2.5rem;
        padding: 3rem 0 2.5rem;
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
        padding-bottom: 20px;
        margin-bottom: 20px;
        z-index: 2;
    }

    .page-title h1 {
        font-weight: 700;
        font-size: 2.2rem;
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
        width: 60px;
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
        border: 1px solid var(--gray-100);
    }

    /* ... (o restante do CSS permanece igual ao seu original) ... */
    .section-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 30px;
        flex-wrap: wrap;
        gap: 15px;
    }

    .section-title {
        font-weight: 600;
        color: var(--gray-800);
        margin: 0;
        font-size: 1.8rem;
        display: flex;
        align-items: center;
    }

    .section-title i {
        margin-right: 12px;
        color: var(--accent-blue);
        font-size: 1.8rem;
    }

    /* Alertas */
    .alert {
        padding: 15px 20px;
        border-radius: 8px;
        margin-bottom: 25px;
        font-weight: 500;
        display: flex;
        align-items: center;
    }

    .alert i {
        margin-right: 10px;
        font-size: 1.2rem;
    }

    .alert-success {
        background-color: rgba(16, 185, 129, 0.1);
        border: 1px solid rgba(16, 185, 129, 0.2);
        color: #0f9d6e;
    }

    .alert-danger {
        background-color: rgba(239, 68, 68, 0.1);
        border: 1px solid rgba(239, 68, 68, 0.2);
        color: var(--accent-red);
    }

    .alert-warning {
        background-color: rgba(245, 158, 11, 0.1);
        border: 1px solid rgba(245, 158, 11, 0.2);
        color: #d97706;
    }

    /* Tabela estilizada */
    .users-table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
        background: white;
        border-radius: var(--border-radius);
        overflow: hidden;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
    }

    .users-table thead tr {
        background: linear-gradient(120deg, var(--accent-blue), var(--accent-teal));
        color: white;
    }

    .users-table th {
        padding: 15px 20px;
        text-align: left;
        font-weight: 600;
        font-size: 1rem;
    }

    .users-table tbody tr {
        transition: background-color 0.2s;
        border-bottom: 1px solid var(--gray-100);
    }

    .users-table tbody tr:last-child {
        border-bottom: none;
    }

    .users-table tbody tr:hover {
        background-color: var(--gray-50);
    }

    .users-table td {
        padding: 15px 20px;
        color: var(--gray-700);
        vertical-align: middle;
    }

    /* Badge de tipo de usuário */
    .user-type-badge {
        display: inline-block;
        padding: 5px 12px;
        border-radius: 20px;
        font-size: 0.85rem;
        font-weight: 500;
    }

    .bg-blue-100 { background-color: #dbeafe; }
    .text-blue-800 { color: #1e40af; }
    .bg-green-100 { background-color: #dcfce7; }
    .text-green-800 { color: #166534; }
    .bg-purple-100 { background-color: #f3e8ff; }
    .text-purple-800 { color: #6b21a8; }
    .bg-gray-100 { background-color: #f3f4f6; }
    .text-gray-800 { color: #374151; }

    /* Botões de ação */
    .action-buttons {
        display: flex;
        gap: 10px;
    }

    .btn1 {
        padding: 8px 16px;
        border-radius: 6px;
        font-weight: 500;
        font-size: 0.9rem;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        transition: var(--transition);
        border: none;
        cursor: pointer;
    }

    .btn1 i {
        margin-right: 6px;
    }

    .btn-edit {
        background: linear-gradient(120deg, var(--accent-blue), var(--accent-blue-dark));
        color: white;
        box-shadow: 0 4px 6px -1px rgba(59, 130, 246, 0.3), 0 2px 4px -1px rgba(59, 130, 246, 0.1);
    }

    .btn-edit:hover {
        transform: translateY(-2px);
        box-shadow: 0 7px 14px -3px rgba(59, 130, 246, 0.3), 0 4px 8px -2px rgba(59, 130, 246, 0.1);
    }

    .btn-delete {
        background: linear-gradient(120deg, var(--accent-red), #dc2626);
        color: white;
        box-shadow: 0 4px 6px -1px rgba(239, 68, 68, 0.3), 0 2px 4px -1px rgba(239, 68, 68, 0.1);
    }

    .btn-delete:hover {
        transform: translateY(-2px);
        box-shadow: 0 7px 14px -3px rgba(239, 68, 68, 0.3), 0 4px 8px -2px rgba(239, 68, 68, 0.1);
    }

    /* Estado vazio */
    .empty-state {
        text-align: center;
        padding: 50px 20px;
        background: var(--gray-50);
        border-radius: var(--border-radius);
        margin-top: 20px;
    }

    .empty-state i {
        font-size: 3.5rem;
        color: var(--gray-300);
        margin-bottom: 20px;
    }

    .empty-state h3 {
        font-weight: 500;
        color: var(--gray-600);
        margin-bottom: 10px;
        font-size: 1.5rem;
    }

    .empty-state p {
        color: var(--gray-500);
        max-width: 500px;
        margin: 0 auto;
    }

    /* Responsividade */
    @media (max-width: 768px) {
        .page-container {
            padding: 0 15px;
        }
        
        .main-content {
            padding: 20px;
        }
        
        .page-title h1 {
            font-size: 1.8rem;
        }
        
        .users-table {
            display: block;
            overflow-x: auto;
        }
        
        .action-buttons {
            flex-wrap: wrap;
            justify-content: center;
        }
        
        .btn1 {
            width: 100%;
            margin-bottom: 5px;
        }
        
        .page-header {
            padding: 2rem 0;
            margin: 15px auto 2rem;
        }
    }
</style>

<!-- HEADER SIMPLIFICADO E PERFEITAMENTE ALINHADO -->
<div class="page-container">
    <div class="page-header">
        <div class="page-title">
            <h1>Administração de Usuários</h1>
        </div>
    </div>
</div>

<div class="page-container">
    <div class="main-content">
        <div class="section-header">
            <h2 class="section-title">
                <i class="fas fa-users-cog"></i> Gerenciar Usuários
            </h2>
        </div>

        <?php if (isset($_GET["sucesso"])): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> Usuário excluído com sucesso.
            </div>
        <?php elseif (isset($_GET["erro"])): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i> Erro ao excluir o usuário.
            </div>
        <?php endif; ?>

        <?php if (empty($usuarios)): ?>
            <div class="empty-state">
                <i class="fas fa-user-slash"></i>
                <h3>Nenhum usuário cadastrado</h3>
                <p>Não há usuários para exibir no momento. Quando novos usuários forem cadastrados, eles aparecerão aqui.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="users-table">
                    <thead>
                        <tr>
                            <th>Nome</th>
                            <th>Email</th>
                            <th>Tipo</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($usuarios as $u): ?>
                            <tr>
                                <td><?= htmlspecialchars($u["nome"]) ?></td>
                                <td><?= htmlspecialchars($u["email"]) ?></td>
                                <td>
                                    <span class="user-type-badge <?= tipoParaCor($u["tipo"]) ?>">
                                        <?= tipoParaTexto($u["tipo"]) ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <a href="editar.php?id=<?= $u["id"] ?>" class="btn btn1 btn-edit">
                                            <i class="fas fa-edit"></i> Editar
                                        </a>
                                        <a href="excluir.php?id=<?= $u["id"] ?>" 
                                           class="btn btn1 btn-delete"
                                           onclick="return confirm('Tem certeza que deseja excluir este usuário?')">
                                           <i class="fas fa-trash-alt"></i> Excluir
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include "../includes/footer.php"; ?>