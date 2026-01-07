<?php
require_once __DIR__ . '/../app/init.php';
require_once __DIR__ . '/../app/Auth.php';

bootstrap(); // inicializa e migra o banco, se necessário

$db   = db(); // <-- agora usa PDO
$auth = new Auth($db);

// já logado? manda pra home
if ($auth->verificarAutenticacao()) {
    header('Location: /credencial-app/public/index.php');
    exit;
}

$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $senha = $_POST['senha'] ?? '';

    if ($email === '' || $senha === '') {
        $erro = 'Preencha todos os campos';
    } else {
        $resultado = $auth->login($email, $senha);
        if (!empty($resultado['success'])) {
            header('Location: /credencial-app/public/index.php');
            exit;
        }
        $erro = $resultado['message'] ?? 'Credenciais inválidas.';
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Sistema de Credenciais</title>
    <style>
        *{margin:0;padding:0;box-sizing:border-box}
        body{
            font-family: "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            background: linear-gradient(135deg, #f6f3e9 0%, #f1e4b3 40%, #e2cc71 100%);
            min-height:100vh;
            display:flex;
            align-items:center;
            justify-content:center;
            padding:20px;
            color:#2f2f2f;
        }

        .login-container{
            background:#fffef9;
            border-radius:16px;
            box-shadow:0 10px 40px rgba(0,0,0,.1);
            width:100%;
            max-width:420px;
            padding:40px 36px;
            transition: box-shadow 0.3s;
        }

        .login-container:hover{
            box-shadow:0 16px 45px rgba(0,0,0,.15);
        }

        .logo{
            text-align:center;
            margin-bottom:30px;
        }

        .logo h1{
            color:#c5a200;
            font-size:28px;
            margin-bottom:5px;
            letter-spacing:0.5px;
        }

        .logo p{
            color:#5c5c5c;
            font-size:14px;
        }

        .form-group{margin-bottom:20px}

        label{
            display:block;
            margin-bottom:8px;
            color:#333;
            font-weight:500;
            font-size:14px;
        }

        input[type="email"],input[type="password"]{
            width:100%;
            padding:12px 15px;
            border:2px solid #e0d9b7;
            border-radius:8px;
            font-size:15px;
            transition:all .3s ease;
            background:#fffdf5;
        }

        input[type="email"]:focus,input[type="password"]:focus{
            outline:none;
            border-color:#c5a200;
            box-shadow:0 0 0 3px rgba(197,162,0,0.15);
            background:#fffef8;
        }

        .btn-login{
            width:100%;
            padding:14px;
            background:linear-gradient(135deg, #e8c500 0%, #c5a200 100%);
            color:#fff;
            border:none;
            border-radius:8px;
            font-size:16px;
            font-weight:600;
            cursor:pointer;
            transition:transform .2s, box-shadow .2s;
        }

        .btn-login:hover{
            transform:translateY(-2px);
            box-shadow:0 8px 20px rgba(197,162,0,.35);
        }

        .btn-login:active{
            transform:translateY(0);
        }

        .alert{
            padding:12px 15px;
            border-radius:8px;
            margin-bottom:20px;
            font-size:14px;
        }

        .alert-danger{
            background:#fff1f1;
            color:#b30000;
            border:1px solid #ffd6d6;
        }

        .alert-info{
            background:#fffdf1;
            color:#6d5a00;
            border:1px solid #f4e7a1;
        }

        .footer-text{
            text-align:center;
            margin-top:20px;
            color:#777;
            font-size:13px;
        }

        .footer-text strong {
            color:#c5a200;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="logo">
            <h1> Sistema de Credenciais</h1>
            <p>Secretaria de Trânsito — SEMTRANSP</p>
        </div>

        <?php if ($erro): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($erro) ?></div>
        <?php endif; ?>

        <div class="alert alert-info">
            <strong>Acesso restrito:</strong><br>
            Somente funcionários autorizados da Secretaria.
        </div>

        <form method="POST" action="/credencial-app/public/login.php">
            <div class="form-group">
                <label for="email">E-mail</label>
                <input type="email" id="email" name="email" required autocomplete="email"
                       value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                       placeholder="seu@email.com.br">
            </div>

            <div class="form-group">
                <label for="senha">Senha</label>
                <input type="password" id="senha" name="senha" required autocomplete="current-password"
                       placeholder="Digite sua senha">
            </div>

            <button type="submit" class="btn-login">Entrar no Sistema</button>
        </form>

        <div class="footer-text">
            <strong>SEMTRANSP</strong> — Sistema de Credenciais © 2025
        </div>
    </div>
</body>
</html>
