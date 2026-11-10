<?php
/**
 * Módulo Central de Autenticação, Sessão e Proteção CSRF / Headers de Segurança.
 */

// 1. Configurações de Segurança de Cookies de Sessão (antes do session_start)
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.cookie_samesite', 'Lax');
    
    // Se estiver rodando sobre HTTPS, ativa a flag Secure
    if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
        ini_set('session.cookie_secure', '1');
    }

    session_start();
}

// 2. Headers de Segurança HTTP (Defesa em Profundidade)
if (!headers_sent()) {
    header('X-Frame-Options: SAMEORIGIN');
    header('X-Content-Type-Options: nosniff');
    header('X-XSS-Protection: 1; mode=block');
    header('Referrer-Policy: strict-origin-when-cross-origin');
}

// 3. Verificação de Autenticação Geral
if (!isset($_SESSION['usuario_id'])) {
    $baseUrl = defined('BASE_URL') ? BASE_URL . '/index.php' : '../index.php';
    header("Location: " . $baseUrl);
    exit;
}

/**
 * Verifica se o usuário autenticado possui o nível de acesso necessário.
 *
 * @param string|array $nivelNecessario 'admin', 'revendedor' ou lista
 */
function verificarNivel($nivelNecessario) {
    $nivelAtual = $_SESSION['usuario_nivel'] ?? null;
    $temPermissao = false;

    if (is_array($nivelNecessario)) {
        $temPermissao = in_array($nivelAtual, $nivelNecessario, true);
    } else {
        $temPermissao = ($nivelAtual === $nivelNecessario);
    }

    if (!$temPermissao) {
        http_response_code(403);
        echo '<!DOCTYPE html>
        <html lang="pt-BR">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <script src="https://cdn.tailwindcss.com"></script>
            <title>Acesso Negado</title>
        </head>
        <body class="bg-gray-100 flex items-center justify-center min-h-screen p-4">
            <div class="bg-white p-8 rounded-2xl shadow-lg max-w-md w-full text-center border border-gray-100">
                <div class="w-16 h-16 bg-red-100 text-red-500 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                </div>
                <h1 class="text-2xl font-bold text-gray-800 mb-2">Acesso Negado</h1>
                <p class="text-gray-600 text-sm mb-6">Você não tem permissão para visualizar esta página.</p>
                <a href="javascript:history.back()" class="inline-block bg-gray-800 text-white font-bold px-6 py-2.5 rounded-xl hover:bg-gray-900 transition">
                    Voltar
                </a>
            </div>
        </body>
        </html>';
        exit;
    }
}

/**
 * Retorna o token CSRF atual ou gera um novo caso não exista.
 *
 * @return string
 */
function obterCsrfToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Valida o token CSRF recebido contra o token armazenado na sessão.
 *
 * @param string|null $token
 * @return bool
 */
function validarCsrfToken(?string $token): bool {
    if (empty($token) || empty($_SESSION['csrf_token'])) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Gera o campo oculto HTML para inclusão em formulários POST.
 *
 * @return string
 */
function campoCsrf(): string {
    $token = htmlspecialchars(obterCsrfToken(), ENT_QUOTES, 'UTF-8');
    return '<input type="hidden" name="csrf_token" value="' . $token . '">';
}
