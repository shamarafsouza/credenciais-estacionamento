<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/init.php';
require_once __DIR__ . '/../app/Auth.php';

// garante que o banco/colunas existem e abre a conexão PDO
bootstrap();

// cria o Auth com PDO
$auth = new Auth(db());

// encerra a sessão no banco e na app
$auth->logout();

// redireciona para o login
header('Location: /credencial-app/public/login.php');
exit;