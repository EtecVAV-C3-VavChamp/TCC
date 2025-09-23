<?php
require_once "../classes/Database.php";
session_start();

if (
    !isset($_SESSION["usuario"]) ||
    !in_array($_SESSION["usuario"]["tipo"], [1, 2])
) {
    die("Acesso negado.");
}

$pdo = Database::getConnection();

// Mensagens de feedback
$mensagem = '';
$tipoMensagem = '';

// Processar adição de nova modalidade
if ($_SERVER["REQUEST_METHOD"] === "POST" && !empty($_POST["nome"])) {
    $nome = trim($_POST["nome"]);
    
    // Verificar se a modalidade já existe
    $stmtVerificar = $pdo->prepare("SELECT COUNT(*) FROM modalidades WHERE nome = ?");
    $stmtVerificar->execute([$nome]);
    $existe = $stmtVerificar->fetchColumn();
    
    if ($existe) {
        $mensagem = "Esta modalidade já está cadastrada.";
        $tipoMensagem = "danger";
    } else {
        $stmt = $pdo->prepare("INSERT INTO modalidades (nome) VALUES (?)");
        if ($stmt->execute([$nome])) {
            $mensagem = "Modalidade adicionada com sucesso!";
            $tipoMensagem = "success";
        } else {
            $mensagem = "Erro ao adicionar modalidade.";
            $tipoMensagem = "danger";
        }
    }
}

// Processar remoção de modalidade
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["excluir_id"])) {
    $id = $_POST["excluir_id"];
    $stmt = $pdo->prepare("DELETE FROM modalidades WHERE id = ?");
    if ($stmt->execute([$id])) {
        $mensagem = "Modalidade removida com sucesso!";
        $tipoMensagem = "success";
    } else {
        $mensagem = "Erro ao remover modalidade.";
        $tipoMensagem = "danger";
    }
}

$modalidades = $pdo
    ->query("SELECT * FROM modalidades ORDER BY nome")
    ->fetchAll(PDO::FETCH_ASSOC);
?>

<?php include "../includes/header.php"; ?>

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
        --accent-teal-dark: #0d9488;
        --accent-amber: #f59e0b;
        --accent-red: #ef4444;
        --accent-red-dark: #dc2626;
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
        padding: 2.5rem 0 2rem;
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
        padding-bottom: 20px;
        margin-bottom: 20px;
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

    .section-title {
        font-weight: 600;
        color: var(--gray-800);
        margin-bottom: 20px;
        font-size: 1.5rem;
        padding-bottom: 15px;
        border-bottom: 2px solid var(--gray-100);
        display: flex;
        align-items: center;
    }

    .section-title i {
        margin-right: 10px;
        color: var(--accent-teal);
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

    /* Formulário de adição */
    .add-form {
        background: var(--gray-50);
        border-radius: var(--border-radius);
        padding: 25px;
        margin-bottom: 30px;
        border: 1px solid var(--gray-200);
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
    }

    .form-group {
        display: flex;
        gap: 15px;
        margin-bottom: 0;
    }

    .form-control {
        flex: 1;
        padding: 12px 15px;
        border: 1px solid var(--gray-300);
        border-radius: 8px;
        font-size: 1rem;
        transition: border-color 0.3s, box-shadow 0.3s;
    }

    .form-control:focus {
        border-color: var(--accent-blue);
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15);
        outline: none;
    }

    .btn-primary {
        background: linear-gradient(120deg, var(--accent-blue), var(--accent-blue-dark));
        color: white;
        border: none;
        border-radius: 8px;
        padding: 12px 25px;
        font-weight: 500;
        cursor: pointer;
        transition: var(--transition);
        box-shadow: 0 4px 6px -1px rgba(59, 130, 246, 0.3), 0 2px 4px -1px rgba(59, 130, 246, 0.1);
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
    }

    .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 7px 14px -3px rgba(59, 130, 246, 0.3), 0 4px 8px -2px rgba(59, 130, 246, 0.1);
    }

    /* Lista de modalidades */
    .modalidades-list {
        background: white;
        border-radius: var(--border-radius);
        overflow: hidden;
        border: 1px solid var(--gray-200);
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
    }

    .modalidade-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 15px 20px;
        border-bottom: 1px solid var(--gray-100);
        transition: background-color 0.3s;
    }

    .modalidade-item:last-child {
        border-bottom: none;
    }

    .modalidade-item:hover {
        background-color: var(--gray-50);
    }

    .modalidade-icon {
        width: 40px;
        height: 40px;
        background: linear-gradient(120deg, var(--accent-teal), var(--accent-teal-dark));
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 1.2rem;
        margin-right: 15px;
        flex-shrink: 0;
    }

    .modalidade-name {
        flex: 1;
        font-weight: 500;
        color: var(--gray-700);
        font-size: 1.1rem;
    }

    .modalidade-actions {
        display: flex;
        gap: 10px;
    }

    .btn-delete {
        background: linear-gradient(120deg, var(--accent-red), var(--accent-red-dark));
        color: white;
        border: none;
        border-radius: 6px;
        padding: 8px 15px;
        font-size: 0.9rem;
        font-weight: 500;
        cursor: pointer;
        transition: var(--transition);
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        display: flex;
        align-items: center;
        gap: 5px;
    }

    .btn-delete:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
    }

    .empty-state {
        text-align: center;
        padding: 40px 20px;
        color: var(--gray-500);
    }

    .empty-state i {
        font-size: 3rem;
        color: var(--gray-300);
        margin-bottom: 15px;
    }

    .empty-state h3 {
        font-weight: 500;
        margin-bottom: 10px;
        color: var(--gray-600);
    }

    /* Confirmação de exclusão */
    .confirmation-dialog {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0.5);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 1000;
        opacity: 0;
        visibility: hidden;
        transition: all 0.3s ease;
    }
    
    .confirmation-dialog.active {
        opacity: 1;
        visibility: visible;
    }
    
    .confirmation-box {
        background: white;
        border-radius: var(--border-radius);
        padding: 30px;
        max-width: 450px;
        width: 90%;
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
    }
    
    .confirmation-buttons {
        display: flex;
        gap: 15px;
        margin-top: 20px;
    }
    
    .confirmation-buttons button {
        flex: 1;
        padding: 12px;
        border-radius: 8px;
        font-weight: 500;
        cursor: pointer;
        transition: var(--transition);
    }
    
    .btn-confirm {
        background: linear-gradient(120deg, var(--accent-red), var(--accent-red-dark));
        color: white;
        border: none;
    }
    
    .btn-cancel {
        background: var(--gray-100);
        color: var(--gray-700);
        border: 1px solid var(--gray-300);
    }
    
    .btn-confirm:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(239, 68, 68, 0.3);
    }
    
    .btn-cancel:hover {
        background: var(--gray-200);
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
        
        .form-group {
            flex-direction: column;
            gap: 10px;
        }
        
        .btn-primary, .btn-delete {
            width: 100%;
            justify-content: center;
        }
        
        .modalidade-item {
            flex-direction: column;
            align-items: flex-start;
            gap: 15px;
        }
        
        .modalidade-actions {
            width: 100%;
        }
    }
</style>

<div class="page-header">
    <div class="page-container">
        <div class="page-title">
            <h1>Gerenciamento de Modalidades</h1>
        </div>
    </div>
</div>

<div class="page-container">
    <div class="main-content">
        <h2 class="section-title">
            <i class="fas fa-medal"></i> Modalidades Esportivas
        </h2>
        
        <?php if ($mensagem): ?>
            <div class="alert alert-<?= $tipoMensagem ?>">
                <i class="fas <?= $tipoMensagem === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle' ?>"></i>
                <?= htmlspecialchars($mensagem) ?>
            </div>
        <?php endif; ?>
        
        <div class="add-form">
            <form method="post">
                <div class="form-group">
                    <input type="text" name="nome" class="form-control" placeholder="Nome da nova modalidade" required>
                    <button type="submit" class="btn-primary">
                        <i class="fas fa-plus"></i> Adicionar
                    </button>
                </div>
            </form>
        </div>
        
        <div class="modalidades-list">
            <?php if (empty($modalidades)): ?>
                <div class="empty-state">
                    <i class="fas fa-inbox"></i>
                    <h3>Nenhuma modalidade cadastrada</h3>
                    <p>Adicione uma nova modalidade usando o formulário acima</p>
                </div>
            <?php else: ?>
                <?php foreach ($modalidades as $m): ?>
                    <div class="modalidade-item">
                        <div class="modalidade-info">
                            <div class="modalidade-name"><?= htmlspecialchars($m["nome"]) ?></div>
                        </div>
                        <div class="modalidade-actions">
                            <form method="post" class="delete-form">
                                <input type="hidden" name="excluir_id" value="<?= $m['id'] ?>">
                                <button type="button" class="btn-delete" data-id="<?= $m['id'] ?>" data-name="<?= htmlspecialchars($m['nome']) ?>">
                                    <i class="fas fa-trash-alt"></i> Remover
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Diálogo de confirmação -->
<div class="confirmation-dialog" id="confirmationDialog">
    <div class="confirmation-box">
        <h3>Confirmar Exclusão</h3>
        <p>Tem certeza que deseja excluir a modalidade "<span id="modalidadeNome"></span>"?</p>
        <div class="confirmation-buttons">
            <button class="btn-cancel" id="btnCancel">Cancelar</button>
            <button class="btn-confirm" id="btnConfirm">Excluir</button>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const deleteButtons = document.querySelectorAll('.btn-delete');
        const confirmationDialog = document.getElementById('confirmationDialog');
        const modalidadeNome = document.getElementById('modalidadeNome');
        const btnCancel = document.getElementById('btnCancel');
        const btnConfirm = document.getElementById('btnConfirm');
        
        let currentForm = null;
        
        deleteButtons.forEach(button => {
            button.addEventListener('click', function() {
                const modalidadeName = this.getAttribute('data-name');
                modalidadeNome.textContent = modalidadeName;
                currentForm = this.closest('.delete-form');
                confirmationDialog.classList.add('active');
            });
        });
        
        btnCancel.addEventListener('click', function() {
            confirmationDialog.classList.remove('active');
            currentForm = null;
        });
        
        btnConfirm.addEventListener('click', function() {
            if (currentForm) {
                currentForm.submit();
            }
        });
        
        // Fechar diálogo ao clicar fora
        confirmationDialog.addEventListener('click', function(e) {
            if (e.target === confirmationDialog) {
                confirmationDialog.classList.remove('active');
                currentForm = null;
            }
        });
    });
</script>

<?php include "../includes/footer.php"; ?>