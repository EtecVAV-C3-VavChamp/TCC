<?php
require_once "../includes/header.php";
require_once "../classes/Database.php";
require_once "../classes/Usuario.php";

Usuario::verificarLogin();
if (!Usuario::isAdmin()) {
    header("Location: ../index.php");
    exit();
}

$pdo = Database::getConnection();
$usuarios = $pdo
    ->query("SELECT id, email FROM usuarios ORDER BY email")
    ->fetchAll();

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $nome = trim($_POST["nome"]);
    $membros = $_POST["membros"] ?? [];
    $horario = $_POST["horario_preferencia"] ?? null;

    if ($nome && in_array($horario, ["10h", "12h"])) {
        $stmt = $pdo->prepare(
            "INSERT INTO times (nome, horario_preferencia) VALUES (?, ?)"
        );
        if ($stmt->execute([$nome, $horario])) {
            $time_id = $pdo->lastInsertId();

            $stmtMembro = $pdo->prepare(
                "INSERT INTO time_membros (time_id, usuario_id) VALUES (?, ?)"
            );
            foreach ($membros as $usuario_id) {
                $stmtMembro->execute([$time_id, (int) $usuario_id]);
            }

            echo "<div class='alert alert-success container mt-3'>Time e membros adicionados com sucesso.</div>";
        } else {
            echo "<div class='alert alert-danger container mt-3'>Erro ao adicionar time.</div>";
        }
    } else {
        echo "<div class='alert alert-warning container mt-3'>Preencha todos os campos corretamente.</div>";
    }
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
        --accent-green: #10b981;
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
        max-width: 800px;
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

    .form-title {
        font-weight: 700;
        color: var(--gray-800);
        margin-bottom: 25px;
        font-size: 1.8rem;
        text-align: center;
        position: relative;
        padding-bottom: 15px;
    }

    .form-title::after {
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

    .alert {
        padding: 15px 20px;
        border-radius: var(--border-radius);
        margin-bottom: 25px;
        font-weight: 500;
        border: none;
    }

    .alert-success {
        background: rgba(34, 197, 94, 0.1);
        color: #198754;
    }

    .alert-danger {
        background: rgba(239, 68, 68, 0.1);
        color: #dc3545;
    }

    .alert-warning {
        background: rgba(245, 158, 11, 0.1);
        color: #ca8a04;
    }

    .form-group {
        margin-bottom: 25px;
    }

    .form-label {
        font-weight: 600;
        color: var(--gray-700);
        margin-bottom: 10px;
        display: block;
    }

    .form-control {
        width: 100%;
        padding: 14px 18px;
        border: 1px solid var(--gray-300);
        border-radius: var(--border-radius);
        font-size: 1rem;
        transition: var(--transition);
        background-color: white;
    }

    .form-control:focus {
        border-color: var(--accent-blue);
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.2);
        outline: none;
    }

    .form-select {
        width: 100%;
        padding: 14px 18px;
        border: 1px solid var(--gray-300);
        border-radius: var(--border-radius);
        font-size: 1rem;
        background-color: white;
        transition: var(--transition);
        appearance: none;
        background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M6 8l4 4 4-4'/%3e%3c/svg%3e");
        background-repeat: no-repeat;
        background-position: right 0.75rem center;
        background-size: 1.5em 1.5em;
    }

    .form-select:focus {
        border-color: var(--accent-blue);
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.2);
        outline: none;
    }

    .form-text {
        color: var(--gray-500);
        font-size: 0.9rem;
        margin-top: 6px;
    }

    .form-actions {
        display: flex;
        justify-content: space-between;
        margin-top: 30px;
        padding-top: 20px;
        border-top: 1px solid var(--gray-200);
    }

    .btn {
        padding: 14px 28px;
        border: none;
        border-radius: 8px;
        font-weight: 500;
        cursor: pointer;
        transition: var(--transition);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        font-size: 1rem;
        text-decoration: none;
    }

    .btn-secondary {
        background: var(--gray-200);
        color: var(--gray-700);
    }

    .btn-secondary:hover {
        background: var(--gray-300);
        box-shadow: 0 4px 12px rgba(156, 163, 175, 0.2);
    }

    .btn-primary {
        background: var(--accent-blue);
        color: white;
    }

    .btn-primary:hover {
        background: #2563eb;
        box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
    }

    .team-icon {
        display: flex;
        justify-content: center;
        margin-bottom: 20px;
    }

    .team-icon i {
        font-size: 4rem;
        color: var(--accent-teal);
        background: rgba(20, 184, 166, 0.1);
        width: 100px;
        height: 100px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
    }

    @media (max-width: 768px) {
        .main-content {
            padding: 25px;
        }
        
        .form-actions {
            flex-direction: column;
            gap: 15px;
        }
        
        .btn {
            width: 100%;
        }
        
        .page-title h1 {
            font-size: 2.2rem;
        }
    }
</style>

<div class="page-header">
    <div class="page-container">
        <div class="page-title">
            <h1>Adicionar Novo Time</h1>
        </div>
    </div>
</div>

<div class="page-container">
    <div class="main-content">
        <div class="team-icon">
            <i class="fas fa-users"></i>
        </div>
        
        <h2 class="form-title">Criar Novo Time</h2>

        <?php if (isset($_GET["error"])): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($_GET["error"]) ?></div>
        <?php endif; ?>

        <form method="post">
            <div class="form-group">
                <label for="nome" class="form-label">Nome do Time</label>
                <input type="text" name="nome" id="nome" class="form-control" placeholder="Ex: Águias FC" required>
            </div>

            <div class="form-group">
                <label for="horario_preferencia" class="form-label">Horário Preferido</label>
                <select name="horario_preferencia" id="horario_preferencia" class="form-select" required>
                    <option value="" selected disabled>Selecione um horário</option>
                    <option value="10h">10h</option>
                    <option value="12h">12h</option>
                </select>
            </div>

            <div class="form-group">
                <label for="membros" class="form-label">Membros do Time</label>
                <select name="membros[]" id="membros" class="form-select" multiple>
                    <?php foreach ($usuarios as $usuario): ?>
                        <option value="<?= $usuario["id"] ?>">
                            <?= htmlspecialchars($usuario["email"]) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <div class="form-text">Segure Ctrl (ou Cmd) para selecionar múltiplos membros</div>
            </div>

            <div class="form-actions">
                <a href="listar.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Voltar
                </a>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-plus-circle"></i> Adicionar Time
                </button>
            </div>
        </form>
    </div>
</div>

<?php require_once "../includes/footer.php"; ?>