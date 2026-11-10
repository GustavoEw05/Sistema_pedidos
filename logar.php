<?php
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.cookie_samesite', 'Lax');
    if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
        ini_set('session.cookie_secure', '1');
    }
    session_start();
}

require_once __DIR__ . '/includes/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['usuario'] ?? '');
    $senha = (string)($_POST['senha'] ?? '');

    if (empty($nome) || empty($senha)) {
        header("Location: index.php?erro=1");
        exit;
    }

    $stmt = $pdo->prepare("SELECT id, nome, senha, nivel FROM usuarios WHERE nome = ?");
    $stmt->execute([$nome]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    $loginValido = false;

    if ($usuario) {
        $hashArmazenado = $usuario['senha'];
        
        // Suporta password_verify e fallback para texto puro caso existam senhas legadas
        if (password_verify($senha, $hashArmazenado) || $senha === $hashArmazenado) {
            $loginValido = true;

            // Se a senha estava em texto puro ou necessita de rehash (algoritmo mais novo / custo atualizado)
            if ($senha === $hashArmazenado || password_needs_rehash($hashArmazenado, PASSWORD_DEFAULT)) {
                try {
                    $novoHash = password_hash($senha, PASSWORD_DEFAULT);
                    $stmtRehash = $pdo->prepare("UPDATE usuarios SET senha = ? WHERE id = ?");
                    $stmtRehash->execute([$novoHash, $usuario['id']]);
                } catch (\Throwable $eRehash) {
                    error_log("[Login] Falha ao atualizar hash de senha: " . $eRehash->getMessage());
                }
            }
        }
    } else {
        // Previne timing attack na enumeração de usuários executando verificação fictícia
        password_verify($senha, '$2y$10$abcdefghijklmnopqrstuuABCDEFGHIJKLMNOPQRSTUVWXYZ012');
    }

    if ($loginValido) {
        // Regenera a sessão para proteção total contra session fixation
        session_regenerate_id(true);

        $_SESSION['usuario_id'] = (int)$usuario['id'];
        $_SESSION['usuario_nome'] = (string)$usuario['nome'];
        $_SESSION['usuario_nivel'] = (string)$usuario['nivel'];
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

        // Redireciona de acordo com o nível de acesso
        if ($usuario['nivel'] === 'admin') {
            header("Location: admin/dashboard.php");
        } else {
            header("Location: revendedor/painel.php");
        }
        exit;
    } else {
        header("Location: index.php?erro=1");
        exit;
    }
} else {
    header("Location: index.php");
    exit;
}
