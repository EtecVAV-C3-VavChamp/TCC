<?php
require_once "auth.php";
require "../classes/Usuario.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

Usuario::isAdmin();

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
        --accent-red: #ef4444;
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
        max-width: 1000px;
        margin: 0 auto;
        padding: 0 20px;
    }

    .page-header {
        background: linear-gradient(120deg, var(--gray-800), var(--gray-900));
        color: white;
        padding: 3rem 0 2.5rem;
        margin-bottom: 2.5rem;
        position: relative;
        overflow: hidden;
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        border-radius:12px;box-shadow:0 10px 25px -5px rgba(0,0,0,.05),0 8px 10px -6px rgba(0,0,0,.02);
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
        padding: 40px;
        margin-bottom: 40px;
        border: 1px solid var(--gray-100);
    }

    .welcome-section {
        text-align: center;
        margin-bottom: 40px;
        padding-bottom: 30px;
        border-bottom: 1px solid var(--gray-200);
    }

    .welcome-title {
        font-weight: 700;
        color: var(--gray-800);
        margin-bottom: 15px;
        font-size: 1.8rem;
    }

    .welcome-text {
        color: var(--gray-600);
        max-width: 600px;
        margin: 0 auto;
    }

    .admin-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: 25px;
    }

    .admin-card {
        background: white;
        border-radius: var(--border-radius);
        overflow: hidden;
        box-shadow: var(--box-shadow);
        transition: var(--transition);
        display: flex;
        flex-direction: column;
        height: 100%;
        border: 1px solid var(--gray-200);
    }

    .admin-card:hover {
        transform: translateY(-7px);
        box-shadow: 0 15px 30px -10px rgba(0, 0, 0, 0.1);
    }

    .card-icon {
        background: linear-gradient(120deg, var(--accent-blue), var(--accent-teal));
        color: white;
        font-size: 2.5rem;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 25px;
    }

    .card-content {
        padding: 25px;
        flex-grow: 1;
    }

    .card-title {
        font-weight: 600;
        color: var(--gray-800);
        margin-bottom: 15px;
        font-size: 1.3rem;
    }

    .card-description {
        color: var(--gray-600);
        margin-bottom: 20px;
    }

    .card-action {
        display: block;
        width: 100%;
        padding: 12px;
        background: var(--gray-100);
        color: var(--gray-800);
        text-align: center;
        font-weight: 500;
        border-radius: 6px;
        transition: var(--transition);
        text-decoration: none;
    }

    .card-action:hover {
        background: var(--accent-blue);
        color: white;
    }

    .logout-card {
        border-top: 3px solid var(--accent-red);
    }

    .logout-card .card-icon {
        background: linear-gradient(120deg, var(--accent-red), #dc2626);
    }

    .logout-card .card-action {
        background: rgba(239, 68, 68, 0.1);
        color: var(--accent-red);
    }

    .logout-card .card-action:hover {
        background: var(--accent-red);
        color: white;
    }

    .admin-stats {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 20px;
        margin-top: 40px;
        padding-top: 30px;
        border-top: 1px solid var(--gray-200);
    }

    .stat-card {
        background: var(--gray-50);
        border-radius: var(--border-radius);
        padding: 20px;
        text-align: center;
        border: 1px solid var(--gray-200);
    }

    .stat-number {
        font-weight: 700;
        font-size: 2.2rem;
        color: var(--accent-blue);
        margin-bottom: 10px;
    }

    .stat-label {
        color: var(--gray-600);
        font-weight: 500;
    }

    @media (max-width: 768px) {
        .admin-grid {
            grid-template-columns: 1fr;
        }
        
        .main-content {
            padding: 25px;
        }
        
        .page-title h1 {
            font-size: 2.2rem;
        }
    }
</style>

<div class="page-header">
    <div class="page-container">
        <div class="page-title">
            <h1>Painel Administrativo</h1>
        </div>
    </div>
</div>

<div class="page-container">
    <div class="main-content">
        <div class="welcome-section">
            <h2 class="welcome-title">Bem-vindo, Administrador</h2>
            <p class="welcome-text">Gerencie todos os aspectos do sistema através das opções abaixo.</p>
        </div>

        <div class="admin-grid">
            <div class="admin-card">
                <div class="card-icon">
                    <i class="fas fa-trophy"></i>
                </div>
                <div class="card-content">
                    <h3 class="card-title">Criar Campeonato</h3>
                    <p class="card-description">Cadastre um novo campeonato e defina suas configurações.</p>
                    <a href="../campeonatos/cadastrar.php" class="card-action">
                        Acessar <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
            </div>

            <div class="admin-card">
                <div class="card-icon">
                    <i class="fas fa-users"></i>
                </div>
                <div class="card-content">
                    <h3 class="card-title">Gerenciar Times</h3>
                    <p class="card-description">Visualize, edite e gerencie todos os times cadastrados.</p>
                    <a href="../times/listar.php" class="card-action">
                        Acessar <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
            </div>

            <div class="admin-card">
                <div class="card-icon">
                    <i class="fas fa-user-friends"></i>
                </div>
                <div class="card-content">
                    <h3 class="card-title">Gerenciar Usuários</h3>
                    <p class="card-description">Controle as contas de usuários e administradores.</p>
                    <a href="users.php" class="card-action">
                        Acessar <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
            </div>

            <div class="admin-card">
                <div class="card-icon">
                    <i class="fas fa-medal"></i>
                </div>
                <div class="card-content">
                    <h3 class="card-title">Modalidades</h3>
                    <p class="card-description">Adicione ou remova modalidades esportivas.</p>
                    <a href="modalidades.php" class="card-action">
                        Acessar <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include "../includes/footer.php"; ?>