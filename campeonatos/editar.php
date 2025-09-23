<?php 
include "../includes/header.php";
require_once "../classes/Usuario.php";
require_once "../classes/Campeonato.php";
require_once "../classes/Times.php";
require_once "../classes/Database.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

Usuario::verificarLogin();

if (!isset($_SESSION["usuario"]) || (int) $_SESSION["usuario"]["tipo"] === 0) {
    header("Location: ../index.php");
    exit();
}

$pdo = Database::getConnection();
$id = $_GET["id"] ?? 0;
$camp = Campeonato::obterPorId($id);

if (!$camp) {
    $_SESSION['toast'] = [
        'type' => 'error',
        'message' => 'Campeonato não encontrado!'
    ];
    header("Location: ../index.php");
    exit();
}

// === AÇÕES ===
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    try {
        if (isset($_POST["excluir_chaveamento"])) {
            $pdo->prepare("DELETE FROM confrontos WHERE campeonato_id = ?")->execute([$id]);
            $_SESSION['toast'] = [
                'type' => 'success',
                'message' => 'Chaveamento excluído com sucesso!'
            ];
            header("Location: editar.php?id=$id");
            exit();
            
        } elseif (isset($_POST["excluir_campeonato"])) {
            $pdo->prepare("DELETE FROM confrontos WHERE campeonato_id = ?")->execute([$id]);
            $pdo->prepare("DELETE FROM campeonatos WHERE id = ?")->execute([$id]);
            $_SESSION['toast'] = [
                'type' => 'success',
                'message' => 'Campeonato excluído com sucesso!'
            ];
            header("Location: ../index.php");
            exit();
            
        } elseif (isset($_POST["gerar_chaveamento"])) {
            Campeonato::gerarChaveamento($id);
            $_SESSION['toast'] = [
                'type' => 'success',
                'message' => 'Chaveamento gerado com sucesso!'
            ];
            header("Location: editar.php?id=$id");
            exit();
            
        } elseif (isset($_POST["salvar_campeonato"])) {
            $_POST["times_participantes"] = $_POST["times_participantes"] ?? [];
            if (Campeonato::atualizar($id, $_POST)) {
                $_SESSION['toast'] = [
                    'type' => 'success',
                    'message' => 'Campeonato atualizado com sucesso!'
                ];
                header("Location: editar.php?id=$id");
                exit();
            } else {
                throw new Exception("Erro ao atualizar o campeonato");
            }
        }
    } catch (Exception $e) {
        $_SESSION['toast'] = [
            'type' => 'error',
            'message' => $e->getMessage()
        ];
        header("Location: editar.php?id=$id");
        exit();
    }
}

$listaTimes = Times::listarAtivos();
$timesSelecionados = array_map("intval", explode(",", $camp["times_participantes"]));
$modalidades = $pdo->query("SELECT * FROM modalidades")->fetchAll(PDO::FETCH_ASSOC);

$statusClass = $camp["ativo"] == 1 ? "status-active" : "status-pending";
$statusLabel = $camp["ativo"] == 1 ? "Ativo" : "Finalizado";
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Campeonato</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
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
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
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

        .form-group {
            margin-bottom: 25px;
        }

        .form-label {
            font-weight: 600;
            color: var(--gray-700);
            margin-bottom: 10px;
            display: block;
        }

        .form-control,
        .form-select {
            width: 100%;
            padding: 14px 18px;
            border: 1px solid var(--gray-300);
            border-radius: var(--border-radius);
            font-size: 1rem;
            transition: var(--transition);
            background-color: white;
        }

        .form-control:focus,
        .form-select:focus {
            border-color: var(--accent-blue);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.2);
            outline: none;
        }

        .form-actions {
            display: flex;
            justify-content: space-between;
            flex-wrap: wrap;
            margin-top: 40px;
            padding-top: 30px;
            border-top: 1px solid var(--gray-200);
            gap: 15px;
        }

        .btn1 {
            padding: 14px 18px;
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
            white-space: nowrap;
            flex: 1;
            min-width: 200px;
            text-align: center;
            color: white;
        }

        .btn-primary { 
            background: var(--accent-blue); 
        }

        .btn-success { 
            background: var(--accent-green); 
            text-decoration: none !important;
        }

        .btn-warning { 
            background: var(--accent-amber); 
        }

        .btn-danger { 
            background: var(--accent-red); 
        }

        .btn-primary:hover { 
            background: #2563eb; 
        }

        .btn-success:hover { 
            background: #0d9488; 
        }

        .btn-warning:hover { 
            background: #e67e22; 
        }

        .btn-danger:hover { 
            background: #dc2626; 
        }

        .tournament-status {
            display: flex;
            align-items: center;
            gap: 10px;
            background: var(--gray-100);
            padding: 15px;
            border-radius: var(--border-radius);
            margin-bottom: 25px;
        }

        .status-badge {
            padding: 6px 14px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.9rem;
        }

        .status-active {
            background: var(--accent-teal);
            color: white;
        }

        .status-pending {
            background: var(--accent-amber);
            color: white;
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
            display: flex;
            align-items: center;
            gap: 10px;
            opacity: 0;
            transform: translateY(-20px);
            transition: all 0.3s ease;
        }
        
        .toast.show {
            opacity: 1;
            transform: translateY(0);
            animation: slideIn 0.5s forwards;
        }
        
        .toast.success {
            background-color: var(--accent-green);
        }
        
        .toast.error {
            background-color: var(--accent-red);
        }
        
        .toast.warning {
            background-color: var(--accent-amber);
        }
        
        .toast.info {
            background-color: var(--accent-blue);
        }
        
        @keyframes slideIn {
            from { 
                transform: translateX(100%); 
                opacity: 0; 
            }
            to { 
                transform: translateX(0); 
                opacity: 1; 
            }
        }
        
        @keyframes fadeOut {
            from { opacity: 1; }
            to { opacity: 0; }
        }
        
        /* Loading spinner */
        .spinner {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(255,255,255,.3);
            border-radius: 50%;
            border-top-color: white;
            animation: spin 1s ease-in-out infinite;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <div class="page-header">
        <div class="page-container">
            <div class="page-title">
                <h1>Editar Campeonato</h1>
            </div>
        </div>
    </div>

    <div class="page-container">
        <div class="main-content">
            <h2 class="form-title">Configurações do Campeonato</h2>
            
            <div class="tournament-status">
                <div class="status-badge <?= $statusClass ?>"><?= $statusLabel ?></div>
                <div><?= count($timesSelecionados) ?> times participantes</div>
            </div>

            <form id="campeonato-form" method="post">
                <input type="hidden" name="salvar_campeonato" value="1">

                <div class="form-group">
                    <label for="nome" class="form-label">Nome do Campeonato</label>
                    <input type="text" name="nome" id="nome" class="form-control" value="<?= htmlspecialchars($camp["nome"]) ?>" required>
                </div>

                <div class="form-group">
                    <label for="descr" class="form-label">Descrição</label>
                    <textarea name="descr" id="descr" class="form-control" rows="3"><?= htmlspecialchars($camp["descr"]) ?></textarea>
                </div>

                <div class="form-group">
                    <label for="data_limite_inscricao" class="form-label">Data Limite de Inscrição</label>
                    <input type="date" name="data_limite_inscricao" id="data_limite_inscricao" class="form-control" value="<?= $camp["data_limite_inscricao"] ?>" required>
                </div>

                <div class="form-group">
                    <label for="data_inicio" class="form-label">Data de Início</label>
                    <input type="date" name="data_inicio" id="data_inicio" class="form-control" value="<?= $camp["data_inicio"] ?>" required>
                </div>

                <div class="form-group">
                    <label for="taxa" class="form-label">Taxa de Inscrição (R$)</label>
                    <input type="number" step="0.01" name="taxa" id="taxa" class="form-control" value="<?= $camp["taxa"] ?>" required>
                </div>

                <div class="form-group">
                    <label for="times_participantes" class="form-label">Times Participantes</label>
                    <select name="times_participantes[]" id="times_participantes" class="form-select" multiple required>
                        <?php foreach ($listaTimes as $time): ?>
                            <option value="<?= $time["id"] ?>" <?= in_array($time["id"], $timesSelecionados) ? "selected" : "" ?>>
                                <?= htmlspecialchars($time["nome"]) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="modalidade_id" class="form-label">Modalidade</label>
                    <select name="modalidade_id" id="modalidade_id" class="form-select" required>
                        <?php foreach ($modalidades as $m): ?>
                            <option value="<?= $m["id"] ?>" <?= $m["id"] == $camp["modalidade_id"] ? "selected" : "" ?>>
                                <?= htmlspecialchars($m["nome"]) ?>
                            </option>
                        <?php endforeach; ?>
                </select>
                </div>

                <div class="form-group">
                    <label for="ativo" class="form-label">Status do Campeonato</label>
                    <select name="ativo" id="ativo" class="form-select" required>
                        <option value="1" <?= $camp["ativo"] == 1 ? "selected" : "" ?>>Ativo</option>
                        <option value="0" <?= $camp["ativo"] == 0 ? "selected" : "" ?>>Finalizado</option>
                    </select>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn1 btn-primary" name="salvar_campeonato" value="1">
                        <i class="fas fa-save"></i> Salvar Alterações
                    </button>
                    <button type="button" class="btn1 btn-success" id="gerar-chaveamento">
                        <i class="fas fa-sitemap"></i> Gerar Chaveamento
                    </button>
                    <button type="button" class="btn1 btn-warning" id="excluir-chaveamento">
                        <i class="fas fa-trash-alt"></i> Excluir Chaveamento
                    </button>
                    <button type="button" class="btn1 btn-danger" id="excluir-campeonato">
                        <i class="fas fa-trash"></i> Excluir Campeonato
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Função para mostrar toast
        function showToast(type, message) {
            // Criar elemento de toast
            const toast = document.createElement('div');
            toast.className = `toast ${type}`;
            
            // Adicionar ícone
            const icon = document.createElement('i');
            icon.className = `fas fa-${
                type === 'success' ? 'check-circle' : 
                type === 'error' ? 'exclamation-circle' : 
                type === 'warning' ? 'exclamation-triangle' : 
                'info-circle'
            }`;
            toast.appendChild(icon);
            
            // Adicionar mensagem
            const text = document.createTextNode(message);
            toast.appendChild(text);
            
            // Adicionar ao corpo do documento
            document.body.appendChild(toast);
            
            // Mostrar toast
            setTimeout(() => {
                toast.classList.add('show');
            }, 10);
            
            // Remover após 3 segundos
            setTimeout(() => {
                toast.classList.remove('show');
                setTimeout(() => {
                    toast.remove();
                }, 300);
            }, 3000);
        }
        
        // Exibir toast da sessão se existir
        <?php if (isset($_SESSION['toast'])): ?>
            document.addEventListener('DOMContentLoaded', function() {
                showToast('<?= $_SESSION['toast']['type'] ?>', '<?= $_SESSION['toast']['message'] ?>');
            });
            <?php unset($_SESSION['toast']); ?>
        <?php endif; ?>
        
        // Validação de times
        document.addEventListener('DOMContentLoaded', function() {
            const selectTimes = document.getElementById('times_participantes');
            if (selectTimes) {
                selectTimes.addEventListener('change', function() {
                    if (this.selectedOptions.length < 2) {
                        showToast('error', 'Selecione pelo menos 2 times para o campeonato!');
                    }
                });
            }
            
            // Evento de submit do formulário principal
            document.getElementById('campeonato-form').addEventListener('submit', function(e) {
                e.preventDefault();
                
                // Validação de pelo menos 2 times
                if (selectTimes.selectedOptions.length < 2) {
                    showToast('error', 'Selecione pelo menos 2 times para o campeonato!');
                    return;
                }
                
                // Mostrar loader no botão
                const saveBtn = this.querySelector('[name="salvar_campeonato"]');
                const originalHtml = saveBtn.innerHTML;
                saveBtn.innerHTML = `<span class="spinner"></span> Salvando...`;
                saveBtn.disabled = true;
                
                // Enviar formulário
                this.submit();
            });
            
            // Eventos para as ações específicas
            document.getElementById('gerar-chaveamento').addEventListener('click', function() {
                Swal.fire({
                    title: 'Gerar Chaveamento',
                    text: 'Deseja gerar um novo chaveamento? O atual será substituído.',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#10b981',
                    cancelButtonColor: '#6b7280',
                    confirmButtonText: 'Sim, gerar!',
                    cancelButtonText: 'Cancelar'
                }).then((result) => {
                    if (result.isConfirmed) {
                        // Criar formulário temporário
                        const form = document.createElement('form');
                        form.method = 'post';
                        form.action = 'editar.php?id=<?= $id ?>';
                        
                        const input = document.createElement('input');
                        input.type = 'hidden';
                        input.name = 'gerar_chaveamento';
                        input.value = '1';
                        
                        form.appendChild(input);
                        document.body.appendChild(form);
                        form.submit();
                    }
                });
            });
            
            document.getElementById('excluir-chaveamento').addEventListener('click', function() {
                Swal.fire({
                    title: 'Excluir Chaveamento',
                    text: 'Deseja realmente excluir o chaveamento atual?',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#f59e0b',
                    cancelButtonColor: '#6b7280',
                    confirmButtonText: 'Sim, excluir!',
                    cancelButtonText: 'Cancelar'
                }).then((result) => {
                    if (result.isConfirmed) {
                        // Criar formulário temporário
                        const form = document.createElement('form');
                        form.method = 'post';
                        form.action = 'editar.php?id=<?= $id ?>';
                        
                        const input = document.createElement('input');
                        input.type = 'hidden';
                        input.name = 'excluir_chaveamento';
                        input.value = '1';
                        
                        form.appendChild(input);
                        document.body.appendChild(form);
                        form.submit();
                    }
                });
            });
            
            document.getElementById('excluir-campeonato').addEventListener('click', function() {
                Swal.fire({
                    title: 'Excluir Campeonato',
                    text: 'Deseja excluir permanentemente este campeonato? Esta ação não pode ser desfeita!',
                    icon: 'error',
                    showCancelButton: true,
                    confirmButtonColor: '#ef4444',
                    cancelButtonColor: '#6b7280',
                    confirmButtonText: 'Sim, excluir!',
                    cancelButtonText: 'Cancelar'
                }).then((result) => {
                    if (result.isConfirmed) {
                        // Criar formulário temporário
                        const form = document.createElement('form');
                        form.method = 'post';
                        form.action = 'editar.php?id=<?= $id ?>';
                        
                        const input = document.createElement('input');
                        input.type = 'hidden';
                        input.name = 'excluir_campeonato';
                        input.value = '1';
                        
                        form.appendChild(input);
                        document.body.appendChild(form);
                        form.submit();
                    }
                });
            });
        });
    </script>
</body>
</html>