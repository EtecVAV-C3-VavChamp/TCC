<?php include "../includes/header.php"; ?>
<?php
require_once "../classes/Usuario.php";
$erroRegistro = "";
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $nome = trim($_POST["nome"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $senha = $_POST["senha"] ?? "";
    $tipo = 0; // Registro padrão como usuário comum
    
    if (!$nome || !$email || !$senha) {
        $erroRegistro = "Preencha todos os campos.";
    } elseif (Usuario::registrar($nome, $email, $senha, $tipo)) {
        header("Location: ../index.php");
        exit();
    } else {
        $erroRegistro = "Erro ao registrar usuário. O e-mail já pode estar em uso.";
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro - Sistema de Campeonatos</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        /* Variáveis de cores */
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
            --box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.05);
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        /* Estilos gerais */
        body {
            background: linear-gradient(135deg, #f0f9ff 0%, #e6f7ff 100%);
            min-height: 100vh;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, 'Open Sans', 'Helvetica Neue', sans-serif;
            color: var(--gray-800);
            margin: 0;
            padding: 0;
        }
        
        /* Header fixo no topo */
        .main-header {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            background: white;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            z-index: 1000;
            padding: 1rem 2rem;
        }
        
        /* Container principal - ajustado para o header fixo */
        .main-content {
            padding-top: 80px; /* Espaço para o header */
            display: flex;
            flex-direction: column;
            justify-content: center;
            min-height: calc(100vh - 80px);
            padding: 80px 20px 20px;
        }
        
        .register-container {
            max-width: 500px;
            margin: 0 auto;
            width: 100%;
        }
        
        /* Cabeçalho da página */
        .register-header {
            text-align: center;
            margin-bottom: 2.5rem;
        }
        
        .register-logo {
            width: 80px;
            height: 80px;
            background: linear-gradient(120deg, var(--accent-teal), var(--accent-blue));
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
            font-size: 2.5rem;
            box-shadow: var(--box-shadow);
        }
        
        .register-title {
            font-weight: 700;
            font-size: 2.2rem;
            color: var(--gray-800);
            margin-bottom: 0.5rem;
        }
        
        .register-subtitle {
            color: var(--gray-600);
            font-size: 1.1rem;
            max-width: 400px;
            margin: 0 auto;
        }
        
        /* Card de registro */
        .register-card {
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            overflow: hidden;
            border: 1px solid var(--gray-200);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .register-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px -10px rgba(0, 0, 0, 0.15);
        }
        
        .card-header {
            background: linear-gradient(120deg, var(--accent-teal), var(--accent-blue));
            color: white;
            padding: 1.5rem;
            text-align: center;
        }
        
        .card-header h2 {
            font-weight: 600;
            margin: 0;
            font-size: 1.5rem;
        }
        
        .card-body {
            padding: 2rem;
        }
        
        /* Mensagem de erro */
        .alert-danger {
            background-color: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.2);
            color: var(--accent-red);
            padding: 0.75rem 1.25rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            font-weight: 500;
        }
        
        /* Campos do formulário */
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-label {
            font-weight: 500;
            color: var(--gray-700);
            margin-bottom: 0.5rem;
            display: block;
        }
        
        .form-control {
            width: 100%;
            padding: 0.8rem 1rem;
            border: 1px solid var(--gray-300);
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.3s, box-shadow 0.3s;
        }
        
        .form-control:focus {
            border-color: var(--accent-blue);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.2);
            outline: none;
        }
        
        /* Botão de registro */
        .btn-primary {
            background: linear-gradient(120deg, var(--accent-teal), var(--accent-blue));
            color: white;
            border: none;
            border-radius: 8px;
            padding: 0.9rem;
            font-size: 1.05rem;
            font-weight: 600;
            width: 100%;
            cursor: pointer;
            transition: var(--transition);
            box-shadow: 0 4px 6px -1px rgba(59, 130, 246, 0.3), 0 2px 4px -1px rgba(59, 130, 246, 0.1);
        }
        
        .btn-primary:hover {
            background: linear-gradient(120deg, var(--accent-blue), var(--accent-teal));
            box-shadow: 0 10px 15px -3px rgba(59, 130, 246, 0.3), 0 4px 6px -2px rgba(59, 130, 246, 0.1);
            transform: translateY(-2px);
        }
        
        .btn-primary:active {
            transform: translateY(0);
        }
        
        /* Links adicionais */
        .register-links {
            text-align: center;
            margin-top: 1.5rem;
            color: var(--gray-600);
        }
        
        .register-links a {
            color: var(--accent-blue);
            text-decoration: none;
            transition: color 0.3s;
            font-weight: 500;
        }
        
        .register-links a:hover {
            color: var(--accent-blue-dark);
            text-decoration: underline;
        }
        
        /* Rodapé */
        .register-footer {
            text-align: center;
            margin-top: 2.5rem;
            color: var(--gray-500);
            font-size: 0.9rem;
        }
        
        /* Responsividade */
        @media (max-width: 576px) {
            .register-card {
                border-radius: 12px;
            }
            
            .card-body {
                padding: 1.5rem;
            }
            
            .register-title {
                font-size: 1.8rem;
            }
            
            .main-header {
                padding: 0.8rem 1rem;
            }
            
            .main-content {
                padding-top: 70px;
                padding: 70px 15px 15px;
            }
        }
    </style>
</head>
<body>
    <div class="main-content">
        <div class="register-container">
            <div class="register-header">
                <div class="register-logo">
                    <i class="fas fa-user-plus"></i>
                </div>
                <h1 class="register-title">Criar Nova Conta</h1>
                <p class="register-subtitle">Junte-se à nossa comunidade de campeonatos esportivos</p>
            </div>

            <div class="register-card">
                <div class="card-header">
                    <h2>Registro</h2>
                </div>
                
                <div class="card-body">
                    <?php if (!empty($erroRegistro)): ?>
                        <div class="alert-danger"><?= $erroRegistro ?></div>
                    <?php endif; ?>

                    <form method="post">
                        <div class="form-group">
                            <label for="nome" class="form-label">Nome Completo</label>
                            <input type="text" name="nome" id="nome" class="form-control" placeholder="Digite seu nome completo" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="email" class="form-label">E-mail</label>
                            <input type="email" name="email" id="email" class="form-control" placeholder="Digite seu e-mail" required>
                        </div>

                        <div class="form-group">
                            <label for="senha" class="form-label">Senha</label>
                            <input type="password" name="senha" id="senha" class="form-control" placeholder="Crie uma senha segura" required>
                        </div>

                        <button type="submit" class="btn-primary">Criar Conta</button>
                    </form>
                    
                    <div class="register-links"> 
                        Já possui uma conta? <a href="login.php">Faça login aqui</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include "../includes/footer.php"; ?>
</body>
</html>