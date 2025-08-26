<?php
require_once "../classes/Database.php";
require "../classes/Usuario.php";
session_start();

$pdo = Database::getConnection();
Usuario::isAdmin();

$id = $_GET["id"] ?? null;

if (!$id || !is_numeric($id)) {
    die("ID inválido.");
}

// Busca usuário a ser editado
$stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id = ?");
$stmt->execute([$id]);
$usuario = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$usuario) {
    die("Usuário não encontrado.");
}

$tipoLogado = $_SESSION["usuario"]["tipo"];
$tipoAlvo = (int) $usuario["tipo"];

// Impede que admin padrão edite super admin
if ($tipoLogado == 1 && $tipoAlvo == 2) {
    die("Acesso não autorizado.");
}

$erro = "";
$sucesso = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $nome = trim($_POST["nome"]);
    $email = trim($_POST["email"]);

    if ($tipoLogado == 2) {
        $tipo = in_array((int) $_POST["tipo"], [0, 1, 2])
            ? (int) $_POST["tipo"]
            : 0;
    } else {
        $tipo = (int) ($_POST["tipo"] == 1 ? 1 : 0);
    }

    $senha = $_POST["senha"];

    if (!$nome || !$email) {
        $erro = "Nome e e-mail são obrigatórios.";
    } else {
        if (!empty($senha)) {
            $senhaHash = password_hash($senha, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare(
                "UPDATE usuarios SET nome = ?, email = ?, tipo = ?, senha = ? WHERE id = ?"
            );
            $stmt->execute([$nome, $email, $tipo, $senhaHash, $id]);
        } else {
            $stmt = $pdo->prepare(
                "UPDATE usuarios SET nome = ?, email = ?, tipo = ? WHERE id = ?"
            );
            $stmt->execute([$nome, $email, $tipo, $id]);
        }

        $sucesso = "Usuário atualizado com sucesso.";
        $usuario["nome"] = $nome;
        $usuario["email"] = $email;
        $usuario["tipo"] = $tipo;
    }
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

    .alert-danger {
        background: rgba(239, 68, 68, 0.1);
        color: #dc3545;
    }

    .alert-success {
        background: rgba(34, 197, 94, 0.1);
        color: #198754;
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

    .form-actions {
        display: flex;
        justify-content: space-between;
        margin-top: 30px;
        padding-top: 20px;
        border-top: 1px solid var(--gray-200);
    }

    .btn1 {
        padding: 14px 28px;
        border: none;
        border-radius: 8px;
        font-weight: 500;
        cursor: pointer;
        transition: var(--transition);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        font-size: 1rem;
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

    .admin-badge {
        display: inline-block;
        padding: 4px 10px;
        border-radius: 20px;
        font-size: 0.8rem;
        font-weight: 600;
        margin-left: 8px;
    }

    .super-admin {
        background: linear-gradient(45deg, var(--accent-amber), #f97316);
        color: white;
    }

    .admin {
        background: linear-gradient(45deg, var(--accent-blue), #2563eb);
        color: white;
    }

    .user {
        background: var(--gray-300);
        color: var(--gray-800);
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
            <h1>Editar Usuário</h1>
        </div>
    </div>
</div>

<div class="page-container">
    <div class="main-content">
        <h2 class="form-title">Editar Informações do Usuário</h2>

        <?php if ($erro): ?>
            <div class="alert alert-danger"><?= $erro ?></div>
        <?php elseif ($sucesso): ?>
            <div class="alert alert-success"><?= $sucesso ?></div>
        <?php endif; ?>

        <form method="post">
            <div class="form-group">
                <label for="nome" class="form-label">Nome Completo:</label>
                <input type="text" class="form-control" name="nome" id="nome" value="<?= htmlspecialchars(
                    $usuario["nome"]
                ) ?>" required>
            </div>

            <div class="form-group">
                <label for="email" class="form-label">Endereço de E-mail:</label>
                <input type="email" class="form-control" name="email" id="email" value="<?= htmlspecialchars(
                    $usuario["email"]
                ) ?>" required>
            </div>

            <div class="form-group">
                <label for="tipo" class="form-label">Tipo de Usuário:</label>
                <select class="form-select" name="tipo" id="tipo">
                    <option value="0" <?= $usuario["tipo"] == 0
                        ? "selected"
                        : "" ?>>Usuário Comum</option>
                    <option value="1" <?= $usuario["tipo"] == 1
                        ? "selected"
                        : "" ?>>Administrador</option>
                    <?php if ($tipoLogado == 2): ?>
                        <option value="2" <?= $usuario["tipo"] == 2
                            ? "selected"
                            : "" ?>>Super Admin</option>
                    <?php endif; ?>
                </select>
                
                <div class="mt-2">
                    <small class="text-muted">Tipo atual: 
                        <?php if ($usuario["tipo"] == 2): ?>
                            <span class="admin-badge super-admin">Super Admin</span>
                        <?php elseif ($usuario["tipo"] == 1): ?>
                            <span class="admin-badge admin">Administrador</span>
                        <?php else: ?>
                            <span class="admin-badge user">Usuário Comum</span>
                        <?php endif; ?>
                    </small>
                </div>
            </div>

            <?php if ($tipoLogado == 2): ?>
                <div class="form-group">
                    <label for="senha" class="form-label">Nova Senha:</label>
                    <input type="password" class="form-control" name="senha" id="senha" placeholder="Deixe em branco para manter a senha atual">
                    <small class="text-muted">A senha deve ter pelo menos 8 caracteres</small>
                </div>
            <?php endif; ?>

            <div class="form-actions">
                <a href="users.php" class="btn btn1 btn-secondary">
                    <i class="fas fa-arrow-left"></i> Voltar
                </a>
                <button type="submit" class="btn btn1 btn-primary">
                    <i class="fas fa-save"></i> Salvar Alterações
                </button>
            </div>
        </form>
    </div>
</div>

<?php include "../includes/footer.php"; ?>