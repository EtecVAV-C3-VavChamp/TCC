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

// Adição de membro via POST
if (
    $_SERVER["REQUEST_METHOD"] === "POST" &&
    isset($_POST["time_id"], $_POST["usuario_id"])
) {
    $stmt = $pdo->prepare(
        "INSERT INTO time_membros (time_id, usuario_id) VALUES (?, ?)"
    );
    $stmt->execute([(int) $_POST["time_id"], (int) $_POST["usuario_id"]]);
    echo "<div class='alert alert-success container mt-3'>Membro adicionado com sucesso.</div>";
}

// Buscar dados
$times = $pdo->query("SELECT * FROM times ORDER BY nome")->fetchAll();
$usuarios = $pdo
    ->query("SELECT id, email FROM usuarios ORDER BY email")
    ->fetchAll();
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
        padding: 30px;
        margin-bottom: 40px;
        border: 1px solid var(--gray-100);
    }

    .page-header-content {
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 20px;
    }

    .page-title-section {
        flex: 1;
    }

    .section-title {
        font-weight: 700;
        color: var(--gray-800);
        margin-bottom: 25px;
        font-size: 1.8rem;
        position: relative;
        padding-bottom: 15px;
    }

    .section-title::after {
        content: "";
        position: absolute;
        bottom: 0;
        left: 0;
        width: 60px;
        height: 3px;
        background: linear-gradient(to right, var(--accent-blue), var(--accent-teal));
        border-radius: 2px;
    }

    .btn1 {
        padding: 12px 25px;
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
        text-decoration: none;
    }

    .btn-success {
        background: var(--accent-green);
        color: white;
    }

    .btn-success:hover {
        background: #059669;
        box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
    }

    .team-list {
        display: grid;
        grid-template-columns: 1fr;
        gap: 20px;
    }

    .team-card {
        background: white;
        border-radius: var(--border-radius);
        overflow: hidden;
        box-shadow: var(--box-shadow);
        transition: var(--transition);
        border: 1px solid var(--gray-200);
        padding: 25px;
    }

    .team-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 15px 30px -10px rgba(0, 0, 0, 0.1);
    }

    .team-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
        padding-bottom: 15px;
        border-bottom: 1px solid var(--gray-200);
    }

    .team-name {
        font-weight: 700;
        font-size: 1.5rem;
        color: var(--gray-800);
    }

    .btn-edit {
        padding: 8px 15px;
        background: var(--gray-100);
        color: var(--gray-700);
        border-radius: 6px;
        font-weight: 500;
        transition: var(--transition);
        text-decoration: none;
    }

    .btn-edit:hover {
        background: var(--accent-blue);
        color: white;
    }

    .add-member-form {
        background: var(--gray-50);
        border-radius: var(--border-radius);
        padding: 20px;
        margin-top: 20px;
    }

    .form-title {
        font-weight: 600;
        color: var(--gray-700);
        margin-bottom: 15px;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .form-row {
        display: grid;
        grid-template-columns: 1fr auto;
        gap: 15px;
    }

    .form-select {
        width: 100%;
        padding: 12px 15px;
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

    .btn-add {
        padding: 12px 20px;
        background: var(--accent-teal);
        color: white;
        border-radius: 6px;
        font-weight: 500;
        transition: var(--transition);
        border: none;
        cursor: pointer;
        white-space: nowrap;
    }

    .btn-add:hover {
        background: #0d9488;
        box-shadow: 0 4px 12px rgba(20, 184, 166, 0.3);
    }

    .members-section {
        margin-top: 20px;
    }

    .members-title {
        font-weight: 600;
        color: var(--gray-700);
        margin-bottom: 15px;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .members-list {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
        gap: 15px;
    }

    .member-item {
        background: var(--gray-50);
        border-radius: var(--border-radius);
        padding: 15px;
        display: flex;
        align-items: center;
        gap: 12px;
        border: 1px solid var(--gray-200);
    }

    .member-icon {
        width: 36px;
        height: 36px;
        background: var(--accent-teal);
        color: white;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .member-email {
        flex: 1;
        color: var(--gray-700);
        font-weight: 500;
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

    @media (max-width: 768px) {
        .form-row {
            grid-template-columns: 1fr;
        }
        
        .main-content {
            padding: 25px;
        }
        
        .page-header-content {
            flex-direction: column;
            align-items: flex-start;
        }
        
        .page-title h1 {
            font-size: 2.2rem;
        }
        
        .team-header {
            flex-direction: column;
            align-items: flex-start;
            gap: 15px;
        }
    }
</style>

<div class="page-header">
    <div class="page-container">
        <div class="page-header-content">
            <div class="page-title-section">
                <h1 class="page-title">Times Cadastrados</h1>
            </div>
            <a href="adicionar.php" class="btn btn1 btn-success">
                <i class="fas fa-plus-circle"></i> Novo Time
            </a>
        </div>
    </div>
</div>

<div class="page-container">
    <div class="main-content">
        <?php if (isset($_GET["success"])): ?>
            <div class="alert alert-success">Operação realizada com sucesso!</div>
        <?php endif; ?>

        <div class="team-list">
            <?php if (empty($times)): ?>
                <div class="text-center py-5">
                    <i class="fas fa-users fa-3x text-gray-400 mb-3"></i>
                    <h3>Nenhum time cadastrado</h3>
                    <p>Comece cadastrando seu primeiro time</p>
                    <a href="adicionar.php" class="btn btn1 btn-success mt-3">
                        <i class="fas fa-plus-circle"></i> Cadastrar Time
                    </a>
                </div>
            <?php else: ?>
                <?php foreach ($times as $time): ?>
                    <div class="team-card">
                        <div class="team-header">
                            <h3 class="team-name"><?= htmlspecialchars($time["nome"]) ?></h3>
                            <a href="editar.php?id=<?= $time["id"] ?>" class="btn-edit">
                                <i class="fas fa-edit"></i> Editar
                            </a>
                        </div>
                        
                        <div class="add-member-form">
                            <h4 class="form-title">
                                <i class="fas fa-user-plus"></i> Adicionar Membro
                            </h4>
                            <form method="post" class="form-row">
                                <input type="hidden" name="time_id" value="<?= $time["id"] ?>">
                                <select name="usuario_id" class="form-select" required>
                                    <option value="" selected disabled>Selecionar usuário</option>
                                    <?php foreach ($usuarios as $usuario): ?>
                                        <option value="<?= $usuario["id"] ?>"><?= htmlspecialchars($usuario["email"]) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <button type="submit" class="btn-add">
                                    <i class="fas fa-plus"></i> Adicionar
                                </button>
                            </form>
                        </div>
                        
                        <div class="members-section">
                            <h4 class="members-title">
                                <i class="fas fa-users"></i> Membros do Time
                            </h4>
                            <?php
                            $stmtM = $pdo->prepare("
                                SELECT u.email FROM time_membros tm
                                JOIN usuarios u ON u.id = tm.usuario_id
                                WHERE tm.time_id = ?
                                ORDER BY u.email
                            ");
                            $stmtM->execute([$time["id"]]);
                            $membros = $stmtM->fetchAll();
                            ?>
                            
                            <?php if (empty($membros)): ?>
                                <div class="alert alert-light">Nenhum membro adicionado a este time</div>
                            <?php else: ?>
                                <div class="members-list">
                                    <?php foreach ($membros as $membro): ?>
                                        <div class="member-item">
                                            <div class="member-icon">
                                                <i class="fas fa-user"></i>
                                            </div>
                                            <div class="member-email"><?= htmlspecialchars($membro["email"]) ?></div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once "../includes/footer.php"; ?>