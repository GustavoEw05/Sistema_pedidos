<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. Limpa todas as variáveis de sessão em memória
$_SESSION = [];

// 2. Apaga o cookie de sessão no navegador do usuário (se existir)
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(), 
        '', 
        time() - 3600,
        $params["path"], 
        $params["domain"],
        $params["secure"], 
        $params["httponly"]
    );
}

// 3. Destrói completamente a sessão no servidor
session_destroy();

// 4. Redireciona para o login informando que a sessão foi encerrada
header("Location: index.php?msg=deslogado");
exit;