<?php
require_once "../includes/header.php";
require_once "../classes/Database.php";
require_once "../classes/Usuario.php";

// Verifica autenticação e permissões
Usuario::verificarLogin();
if (!Usuario::isAdmin()) {
    header("Location: ../index.php");
    exit();
}

$pdo = Database::getConnection();
$id = isset($_GET["id"]) ? (int)$_GET["id"] : 0;

// Verifica se o time existe
$stmt = $pdo->prepare("SELECT * FROM times WHERE id = ?");
$stmt->execute([$id]);
$time = $stmt->fetch();

if (!$time) {
    echo "<div class='alert alert-danger container mt-4'>Time não encontrado.</div>";
    require_once "../includes/footer.php";
    exit();
}

// Processa a exclusão do time
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["excluir_time"])) {
    try {
        $pdo->beginTransaction();
        
        // 1. Remove o time de qualquer campeonato
        $stmt = $pdo->prepare("SELECT id, times_participantes FROM campeonatos WHERE FIND_IN_SET(?, times_participantes) > 0");
        $stmt->execute([$id]);
        $campeonatos = $stmt->fetchAll();
        
        foreach ($campeonatos as $camp) {
            $timesArray = explode(',', $camp['times_participantes']);
            $timesArray = array_filter($timesArray, function($value) use ($id) {
                return $value != $id;
            });
            $novosTimes = implode(',', $timesArray);
            
            $pdo->prepare("UPDATE campeonatos SET times_participantes = ? WHERE id = ?")
               ->execute([$novosTimes, $camp['id']]);
        }
        
        // 2. Remove confrontos relacionados
        $pdo->prepare("DELETE FROM confrontos WHERE time1 = ? OR time2 = ? OR vencedor = ?")
           ->execute([$id, $id, $id]);
        
        // 3. Remove membros do time
        $pdo->prepare("DELETE FROM time_membros WHERE time_id = ?")->execute([$id]);
        
        // 4. Remove o time
        $stmt = $pdo->prepare("DELETE FROM times WHERE id = ?");
        $stmt->execute([$id]);
        
        if ($stmt->rowCount() > 0) {
            $pdo->commit();
            $_SESSION["mensagem"] = "<div class='alert alert-success'>Time excluído com sucesso.</div>";
            header("Location: listar.php");
            exit();
        } else {
            throw new Exception("Nenhum time foi excluído.");
        }
    } catch (PDOException $e) {
        $pdo->rollBack();
        $mensagem = "<div class='alert alert-danger'>Erro ao excluir time: " . htmlspecialchars($e->getMessage()) . "</div>";
    } catch (Exception $e) {
        $pdo->rollBack();
        $mensagem = "<div class='alert alert-danger'>" . htmlspecialchars($e->getMessage()) . "</div>";
    }
}

// Processa a atualização do time
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["atualizar_time"])) {
    $nome = trim($_POST["nome"]);
    $horario = $_POST["horario_preferencia"];
    $novosMembros = $_POST["membros"] ?? [];

    try {
        $pdo->beginTransaction();
        
        // Atualiza informações básicas do time
        $stmt = $pdo->prepare("UPDATE times SET nome = ?, horario_preferencia = ? WHERE id = ?");
        $stmt->execute([$nome, $horario, $id]);
        
        // Atualiza membros do time
        $pdo->prepare("DELETE FROM time_membros WHERE time_id = ?")->execute([$id]);
        $stmtInsert = $pdo->prepare("INSERT INTO time_membros (time_id, usuario_id) VALUES (?, ?)");
        
        foreach ($novosMembros as $uid) {
            $stmtInsert->execute([$id, (int)$uid]);
        }
        
        $pdo->commit();
        $mensagem = "<div class='alert alert-success'>Time atualizado com sucesso.</div>";
    } catch (PDOException $e) {
        $pdo->rollBack();
        $mensagem = "<div class='alert alert-danger'>Erro ao atualizar time: " . htmlspecialchars($e->getMessage()) . "</div>";
    }
}

// Busca dados atualizados
$stmt = $pdo->prepare("SELECT * FROM times WHERE id = ?");
$stmt->execute([$id]);
$time = $stmt->fetch();

// Busca todos os usuários disponíveis
$usuarios = $pdo->query("SELECT id, email FROM usuarios ORDER BY email")->fetchAll();

// Busca membros atuais do time
$stmtM = $pdo->prepare("
    SELECT u.id, u.email
    FROM time_membros tm
    JOIN usuarios u ON u.id = tm.usuario_id
    WHERE tm.time_id = ?
    ORDER BY u.email
");
$stmtM->execute([$id]);
$membrosAtuais = $stmtM->fetchAll();
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Time - Sistema de Campeonatos</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
            padding: 2rem 0;
            margin-bottom: 2rem;
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
            padding-bottom: 15px;
        }

        .page-title h1 {
            font-weight: 700;
            font-size: 2.2rem;
            letter-spacing: -0.025em;
            margin-bottom: 0.5rem;
            color: white;
        }

        .page-title p {
            font-size: 1rem;
            opacity: 0.85;
            max-width: 600px;
            margin: 0 auto;
            color: var(--gray-300);
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

        .main-card {
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            overflow: hidden;
            margin-bottom: 30px;
            border: 1px solid var(--gray-100);
        }

        .card-header {
            background: linear-gradient(90deg, var(--gray-700), var(--gray-800));
            color: white;
            padding: 18px;
        }

        .card-header-title {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 0;
        }

        .card-body {
            padding: 25px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-label {
            font-weight: 600;
            color: var(--gray-700);
            margin-bottom: 8px;
            display: block;
        }

        .form-control, .form-select {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid var(--gray-200);
            border-radius: var(--border-radius);
            font-size: 1rem;
            transition: var(--transition);
            background-color: white;
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--accent-blue);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.2);
            outline: none;
        }

        .form-text {
            color: var(--gray-500);
            font-size: 0.85rem;
            margin-top: 6px;
        }

        .btn {
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: 600;
            transition: var(--transition);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            border: none;
            cursor: pointer;
            font-size: 0.95rem;
        }

        .btn-secondary {
            background: var(--gray-500);
            color: white;
        }

        .btn-secondary:hover {
            background: var(--gray-600);
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }

        .btn-primary {
            background: var(--accent-blue);
            color: white;
        }

        .btn-primary:hover {
            background: #2563eb;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(52, 152, 219, 0.3);
        }

        .btn-danger {
            background: #e53e3e;
            color: white;
        }

        .btn-danger:hover {
            background: #c53030;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(229, 62, 62, 0.3);
        }

        .d-flex {
            display: flex;
        }

        .justify-content-between {
            justify-content: space-between;
        }

        .align-items-center {
            align-items: center;
        }

        .gap-2 {
            gap: 10px;
        }

        .mt-3 {
            margin-top: 1rem;
        }

        .mt-4 {
            margin-top: 1.5rem;
        }

        .mb-3 {
            margin-bottom: 1rem;
        }

        .mb-4 {
            margin-bottom: 1.5rem;
        }

        .alert {
            padding: 12px 18px;
            border-radius: var(--border-radius);
            margin-bottom: 20px;
            font-weight: 500;
            border: none;
            font-size: 0.95rem;
        }

        .alert-danger {
            background: rgba(229, 62, 62, 0.1);
            color: #c53030;
        }

        .alert-success {
            background: rgba(72, 187, 120, 0.1);
            color: #38a169;
        }

        .alert-warning {
            background: rgba(246, 173, 85, 0.1);
            color: #dd6b20;
        }

        .membros-list {
            margin-top: 20px;
            border: 1px solid var(--gray-200);
            border-radius: var(--border-radius);
            padding: 15px;
        }

        .membro-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px solid var(--gray-100);
        }

        .membro-item:last-child {
            border-bottom: none;
        }

        @media (max-width: 768px) {
            .d-flex {
                flex-direction: column;
            }
            
            .justify-content-between {
                gap: 15px;
            }
            
            .gap-2 {
                width: 100%;
            }
            
            .btn {
                width: 100%;
            }
            
            .page-title h1 {
                font-size: 1.8rem;
            }
            
            .card-header-title {
                font-size: 1.3rem;
            }
        }
    </style>
</head>
<body>
    <div class="page-header">
        <div class="page-container">
            <div class="page-title">
                <h1><i class="fas fa-users"></i> Editar Time</h1>
                <p>Gerencie as informações do seu time</p>
            </div>
        </div>
    </div>

    <div class="page-container">
        <?php if (isset($mensagem)): ?>
            <?= $mensagem ?>
        <?php endif; ?>

        <div class="main-card">
            <div class="card-header">
                <h2 class="card-header-title"><i class="fas fa-edit"></i> Informações do Time</h2>
            </div>
            <div class="card-body">
                <form method="post" id="edit-form">
                    <input type="hidden" name="atualizar_time" value="1">

                    <div class="form-group">
                        <label for="nome" class="form-label">Nome do Time</label>
                        <input type="text" name="nome" id="nome" class="form-control" value="<?= htmlspecialchars($time["nome"]) ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="horario_preferencia" class="form-label">Horário de Preferência</label>
                        <select name="horario_preferencia" id="horario_preferencia" class="form-select" required>
                            <option value="10h" <?= $time["horario_preferencia"] == "10h" ? "selected" : "" ?>>10h</option>
                            <option value="12h" <?= $time["horario_preferencia"] == "12h" ? "selected" : "" ?>>12h</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="membros" class="form-label">Adicionar Membros</label>
                        <select name="membros[]" id="membros" class="form-select" multiple>
                            <?php foreach ($usuarios as $u): ?>
                                <option value="<?= $u["id"] ?>" <?= in_array($u["id"], array_column($membrosAtuais, "id")) ? "selected" : "" ?>>
                                    <?= htmlspecialchars($u["email"]) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="form-text">Segure a tecla Ctrl (Cmd no Mac) para selecionar múltiplos membros</div>
                    </div>

                    <?php /*if (!empty($membrosAtuais)): ?>
                    <div class="form-group">
                        <label class="form-label">Membros Atuais</label>
                        <div class="membros-list">
                            <?php foreach ($membrosAtuais as $membro): ?>
                                <div class="membro-item">
                                    <span><?= htmlspecialchars($membro["email"]) ?></span>
                                    <form method="post" style="display: inline;">
                                        <input type="hidden" name="remover_usuario_id" value="<?= $membro["id"] ?>">
                                        <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Tem certeza que deseja remover este membro?')">
                                            <i class="fas fa-times"></i> Remover
                                        </button>
                                    </form>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; */?>

                    <div class="d-flex justify-content-between mt-4">
                        <a href="listar.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Voltar
                        </a>
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-danger" onclick="if(confirm('Tem certeza que deseja excluir este time permanentemente? Esta ação removerá todos os dados associados!')) { document.getElementById('delete-form').submit(); }">
                                <i class="fas fa-trash"></i> Excluir Time
                            </button>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Salvar Alterações
                            </button>
                        </div>
                    </div>
                </form>

                <!-- Formulário oculto para exclusão -->
                <form method="post" id="delete-form" style="display: none;">
                    <input type="hidden" name="excluir_time" value="1">
                </form>
            </div>
        </div>
    </div>

    <?php require_once "../includes/footer.php"; ?>
</body>
</html>