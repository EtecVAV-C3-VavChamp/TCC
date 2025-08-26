<?php
// Start session at the very beginning
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once "../classes/Usuario.php";

$erroLogin = "";

// Handle login attempt
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = $_POST["email"] ?? "";
    $senha = $_POST["senha"] ?? "";
    
    if (Usuario::login($email, $senha)) {
        header("Location: ../index.php");
        exit();
    } else {
        $erroLogin = "Login inválido. Verifique suas credenciais.";
    }
}

// Now include the header
include "../includes/header.php";
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Sistema de Campeonatos</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* RESET AND BASE STYLES */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        html, body {
            width: 100%;
            height: 100%;
        }
        
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
        
        /* FULL-WIDTH HEADER FIX */
        body {
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            background: linear-gradient(135deg, #f0f9ff 0%, #e6f7ff 100%);
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, 'Open Sans', 'Helvetica Neue', sans-serif;
            color: var(--gray-800);
        }
        
        body > header {
            width: 100%;
            background: linear-gradient(120deg, var(--gray-800), var(--gray-900));
            color: white;
            padding: 1rem 0;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        
        .header-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        /* LOGIN STYLES */
        .login-container {
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
            max-width: 440px;
            width: 100%;
            margin: 0 auto;
            padding: 20px;
        }
        
        .login-header {
            text-align: center;
            margin-bottom: 2.5rem;
        }
        
        .login-logo {
            width: 80px;
            height: 80px;
            background: linear-gradient(120deg, var(--accent-blue), var(--accent-teal));
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
            font-size: 2.5rem;
            box-shadow: var(--box-shadow);
        }
        
        .login-title {
            font-weight: 700;
            font-size: 2.2rem;
            color: var(--gray-800);
            margin-bottom: 0.5rem;
        }
        
        .login-subtitle {
            color: var(--gray-600);
            font-size: 1.1rem;
        }
        
        .login-card {
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            overflow: hidden;
            border: 1px solid var(--gray-200);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .login-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px -10px rgba(0, 0, 0, 0.15);
        }
        
        .card-header {
            background: linear-gradient(120deg, var(--accent-blue), var(--accent-teal));
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
        
        .alert-danger {
            background-color: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.2);
            color: var(--accent-red);
            padding: 0.75rem 1.25rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            font-weight: 500;
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
            margin-bottom: 1.5rem;
        }
        
        .form-control:focus {
            border-color: var(--accent-blue);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.2);
            outline: none;
        }
        
        .btn-primary {
            background: linear-gradient(120deg, var(--accent-blue), var(--accent-blue-dark));
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
            background: linear-gradient(120deg, var(--accent-blue-dark), var(--accent-blue));
            box-shadow: 0 10px 15px -3px rgba(59, 130, 246, 0.3), 0 4px 6px -2px rgba(59, 130, 246, 0.1);
            transform: translateY(-2px);
        }
        
        .login-links {
            text-align: center;
            margin-top: 1.5rem;
            color: var(--gray-600);
        }
        
        .login-links a {
            color: var(--accent-blue);
            text-decoration: none;
            transition: color 0.3s;
        }
        
        .login-links a:hover {
            color: var(--accent-blue-dark);
            text-decoration: underline;
        }
        
        .login-footer {
            text-align: center;
            margin-top: 2.5rem;
            color: var(--gray-500);
            font-size: 0.9rem;
        }
        
        @media (max-width: 576px) {
            .login-card {
                border-radius: 12px;
            }
            
            .card-body {
                padding: 1.5rem;
            }
            
            .login-title {
                font-size: 1.8rem;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <div class="login-logo">
                <i class="fas fa-lock"></i>
            </div>
            <h1 class="login-title">Acesso ao Sistema</h1>
            <p class="login-subtitle">Entre com suas credenciais para acessar o painel</p>
        </div>

        <div class="login-card">
            <div class="card-header">
                <h2>Login</h2>
            </div>
            
            <div class="card-body">
                <?php if (!empty($erroLogin)): ?>
                    <div class="alert-danger"><?= htmlspecialchars($erroLogin) ?></div>
                <?php endif; ?>

                <form method="POST">
                    <div class="form-group">
                        <label for="email" class="form-label">E-mail</label>
                        <input type="email" name="email" id="email" class="form-control" placeholder="Digite seu e-mail" required>
                    </div>

                    <div class="form-group">
                        <label for="senha" class="form-label">Senha</label>
                        <input type="password" name="senha" id="senha" class="form-control" placeholder="Digite sua senha" required>
                    </div>

                    <button type="submit" class="btn-primary">Entrar</button>
                </form>
                
                <div class="login-links"> 
                    <a href="register.php">| Criar nova conta |</a>
                </div>
            </div>
        </div>
        
        <div class="login-footer">
            &copy; <?= date('Y') ?> Sistema de Campeonatos. Todos os direitos reservados.
        </div>
    </div>

    <?php include "../includes/footer.php"; ?>
</body>
</html>