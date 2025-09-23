<?php include "../includes/header.php"; ?>
<?php
require_once "../classes/Usuario.php";
require_once "../classes/Campeonato.php";
require_once "../classes/Times.php";
require_once "../classes/Database.php";
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
Usuario::verificarLogin();
Usuario::isAdmin();
$pdo = Database::getConnection();
$times = Times::listar();
$modalidades = $pdo
    ->query("SELECT id, nome FROM modalidades")
    ->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Converter datas de dd/mm/aaaa para aaaa-mm-dd antes de salvar
    if (isset($_POST["data_limite_inscricao"])) {
        $data = explode('/', $_POST["data_limite_inscricao"]);
        if (count($data) === 3) {
            $_POST["data_limite_inscricao"] = $data[2] . '-' . $data[1] . '-' . $data[0];
        }
    }
    
    if (isset($_POST["data_inicio"])) {
        $data = explode('/', $_POST["data_inicio"]);
        if (count($data) === 3) {
            $_POST["data_inicio"] = $data[2] . '-' . $data[1] . '-' . $data[0];
        }
    }

    $_POST["times_participantes"] = $_POST["times_participantes"] ?? [];
    if (Campeonato::criar($_POST)) {
        $_SESSION['toast'] = [
            'type' => 'success',
            'message' => 'Campeonato criado com sucesso!'
        ];
        header("Location: ../index.php");
        exit();
    } else {
        $_SESSION['toast'] = [
            'type' => 'error',
            'message' => 'Erro ao cadastrar campeonato!'
        ];
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

    .alert-danger {
        background: rgba(239, 68, 68, 0.1);
        color: #dc3545;
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
        justify-content: center;
        margin-top: 30px;
    }

    .btn1 {
        padding: 14px 35px;
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
    }

    .btn-success {
        background: var(--accent-teal);
        color: white;
    }

    .btn-success:hover {
        background: #0d9488;
        box-shadow: 0 4px 12px rgba(20, 184, 166, 0.3);
        transform: translateY(-3px);
    }

    .tournament-icon {
        display: flex;
        justify-content: center;
        margin-bottom: 20px;
    }

    .tournament-icon i {
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

    .form-row {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 20px;
        margin-bottom: 20px;
    }

    /* Toast Notification Styles */
    .toast {
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 15px 25px;
        border-radius: var(--border-radius);
        color: white;
        font-weight: bold;
        box-shadow: var(--box-shadow);
        z-index: 1000;
        animation: slideIn 0.5s, fadeOut 0.5s 2.5s forwards;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    .toast.success {
        background-color: var(--accent-teal);
    }
    
    .toast.error {
        background-color: #ef4444;
    }
    
    .toast i {
        font-size: 1.2rem;
    }
    
    @keyframes slideIn {
        from { transform: translateX(100%); opacity: 0; }
        to { transform: translateX(0); opacity: 1; }
    }
    
    @keyframes fadeOut {
        from { opacity: 1; }
        to { opacity: 0; }
    }

    @media (max-width: 768px) {
        .main-content {
            padding: 25px;
        }
        
        .form-row {
            grid-template-columns: 1fr;
        }
        
        .page-title h1 {
            font-size: 2.2rem;
        }
    }
</style>

<div class="page-header">
    <div class="page-container">
        <div class="page-title">
            <h1>Cadastrar Novo Campeonato</h1>
        </div>
    </div>
</div>

<div class="page-container">
    <div class="main-content">
        
        <h2 class="form-title">Criar Novo Torneio</h2>

        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?= $error ?></div>
        <?php endif; ?>

        <form method="post" id="form-campeonato">
            <div class="form-row">
                <div class="form-group">
                    <label for="nome" class="form-label">Nome do Campeonato</label>
                    <input type="text" name="nome" id="nome" class="form-control" placeholder="Ex: Copa Cidade 2023" required>
                </div>
                
                <div class="form-group">
                    <label for="modalidade_id" class="form-label">Modalidade</label>
                    <select name="modalidade_id" id="modalidade_id" class="form-select" required>
                        <option value="" selected disabled>Selecione a modalidade</option>
                        <?php foreach ($modalidades as $modalidade): ?>
                            <option value="<?= $modalidade["id"] ?>"><?= htmlspecialchars($modalidade["nome"]) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            
            <div class="form-group">
                <label for="descr" class="form-label">Descrição</label>
                <textarea name="descr" id="descr" class="form-control" rows="3" placeholder="Descreva o campeonato, formato de disputa, premiações, etc." required></textarea>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="data_limite_inscricao" class="form-label">Data Limite de Inscrição</label>
                    <input type="text" name="data_limite_inscricao" id="data_limite_inscricao" class="form-control" 
                           placeholder="dd/mm/aaaa" required pattern="\d{2}/\d{2}/\d{4}">
                </div>
                
                <div class="form-group">
                    <label for="data_inicio" class="form-label">Data de Início</label>
                    <input type="text" name="data_inicio" id="data_inicio" class="form-control" 
                           placeholder="dd/mm/aaaa" required pattern="\d{2}/\d{2}/\d{4}">
                </div>
            </div>
            
            <div class="form-group">
                <label for="taxa" class="form-label">Taxa de Inscrição (R$)</label>
                <input type="number" step="0.01" name="taxa" id="taxa" class="form-control" placeholder="Ex: 150.00" required>
            </div>
            
            <div class="form-group">
                <label for="times_participantes" class="form-label">Times Participantes</label>
                <select name="times_participantes[]" id="times_participantes" class="form-select" multiple required>
                    <?php foreach ($times as $time): ?>
                        <option value="<?= $time["id"] ?>"><?= htmlspecialchars($time["nome"]) ?></option>
                    <?php endforeach; ?>
                </select>
                <div class="form-text">Segure Ctrl (ou Cmd) para selecionar múltiplos times</div>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn btn1 btn-success" onclick="return confirmarCriacao()">
                    <i class="fas fa-plus-circle"></i> Criar Campeonato
                </button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Máscara para os campos de data (dd/mm/aaaa)
    function aplicarMascaraData(input) {
        input.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            
            if (value.length > 2) {
                value = value.substring(0, 2) + '/' + value.substring(2);
            }
            if (value.length > 5) {
                value = value.substring(0, 5) + '/' + value.substring(5, 9);
            }
            
            e.target.value = value;
        });
    }
    
    // Validação para garantir que a data está no formato correto
    function validarData(input) {
        input.addEventListener('blur', function(e) {
            const pattern = /^\d{2}\/\d{2}\/\d{4}$/;
            if (!pattern.test(e.target.value)) {
                e.target.setCustomValidity('Por favor, insira uma data no formato dd/mm/aaaa');
            } else {
                e.target.setCustomValidity('');
            }
        });
    }
    
    const dataLimiteInput = document.getElementById('data_limite_inscricao');
    const dataInicioInput = document.getElementById('data_inicio');
    
    aplicarMascaraData(dataLimiteInput);
    aplicarMascaraData(dataInicioInput);
    validarData(dataLimiteInput);
    validarData(dataInicioInput);
    
    // Validação de times participantes
    const timesSelect = document.getElementById('times_participantes');
    timesSelect.addEventListener('change', function() {
        if (this.selectedOptions.length < 2) {
            alert('Selecione pelo menos 2 times para participar do campeonato!');
        }
    });
});

function confirmarCriacao() {
    const timesSelecionados = document.getElementById('times_participantes').selectedOptions.length;
    
    if (timesSelecionados < 2) {
        alert('Selecione pelo menos 2 times para criar o campeonato!');
        return false;
    }
    
    return confirm('Deseja criar este campeonato?');
}

// Exibir toast se houver na sessão
<?php if (isset($_SESSION['toast'])): ?>
    const toast = document.createElement('div');
    toast.className = `toast <?= $_SESSION['toast']['type'] === 'success' ? 'success' : 'error' ?>`;
    
    // Adiciona ícone conforme o tipo
    const icon = document.createElement('i');
    icon.className = `fas fa-<?= $_SESSION['toast']['type'] === 'success' ? 'check-circle' : 'exclamation-circle' ?>`;
    toast.appendChild(icon);
    
    // Adiciona mensagem
    const message = document.createTextNode(`<?= $_SESSION['toast']['message'] ?>`);
    toast.appendChild(message);
    
    document.body.appendChild(toast);
    
    // Remove após 3 segundos
    setTimeout(() => {
        toast.remove();
        <?php unset($_SESSION['toast']); ?>
    }, 3000);
<?php endif; ?>
</script>

<?php include "../includes/footer.php"; ?>