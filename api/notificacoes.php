<?php
/**
 * API REST para Consulta e Atualização de Notificações do Sistema.
 * Suporta Admin e Revendedor com isolamento rigoroso por sessão e perfil.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../includes/config.php';

header('Content-Type: application/json; charset=UTF-8');

// 1. Validação de Autenticação via Sessão
$usuario_id = isset($_SESSION['usuario_id']) ? (int)$_SESSION['usuario_id'] : 0;
$nivel_raw  = $_SESSION['usuario_nivel'] ?? $_SESSION['nivel'] ?? '';

if ($usuario_id <= 0 || empty($nivel_raw)) {
    http_response_code(401);
    echo json_encode([
        'sucesso' => false,
        'erro'    => 'Não autorizado ou sessão expirada.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// 2. Normalização do perfil de acesso
$isAdmin      = in_array(strtolower(trim((string)$nivel_raw)), ['admin', 'administrador'], true);
$tipo_usuario = $isAdmin ? 'admin' : 'revendedor';

$acao = $_GET['acao'] ?? 'listar';

// Helper para executar queries com fallback resiliente para o enum tipo_usuario_enum
function executarQueryNotificacoes(PDO $pdo, string $sqlEnum, string $sqlFallback, array $params) {
    try {
        $stmt = $pdo->prepare($sqlEnum);
        $stmt->execute($params);
        return $stmt;
    } catch (\Throwable $e) {
        $stmt = $pdo->prepare($sqlFallback);
        $stmt->execute($params);
        return $stmt;
    }
}

// ==========================================
// AÇÃO: LISTAR NOTIFICAÇÕES
// ==========================================
if ($acao === 'listar') {
    try {
        if ($isAdmin) {
            // Administrador: vê notificações destinadas a administradores globais ou a si próprio
            $sqlListarEnum = "
                SELECT id, titulo, mensagem, link, lida, criado_em 
                FROM notificacoes 
                WHERE (usuario_id = ? OR usuario_id IS NULL) 
                  AND tipo_usuario = ?::tipo_usuario_enum
                ORDER BY criado_em DESC 
                LIMIT 15
            ";
            $sqlListarFallback = "
                SELECT id, titulo, mensagem, link, lida, criado_em 
                FROM notificacoes 
                WHERE (usuario_id = ? OR usuario_id IS NULL) 
                  AND tipo_usuario = ?
                ORDER BY criado_em DESC 
                LIMIT 15
            ";

            $sqlCountEnum = "
                SELECT COUNT(*) 
                FROM notificacoes 
                WHERE (usuario_id = ? OR usuario_id IS NULL) 
                  AND tipo_usuario = ?::tipo_usuario_enum
                  AND (lida = FALSE OR lida IS NULL)
            ";
            $sqlCountFallback = "
                SELECT COUNT(*) 
                FROM notificacoes 
                WHERE (usuario_id = ? OR usuario_id IS NULL) 
                  AND tipo_usuario = ?
                  AND (lida = FALSE OR lida IS NULL)
            ";

            $params = [$usuario_id, 'admin'];

        } else {
            // Revendedor: vê estritamente suas próprias notificações
            $sqlListarEnum = "
                SELECT id, titulo, mensagem, link, lida, criado_em 
                FROM notificacoes 
                WHERE usuario_id = ? 
                  AND tipo_usuario = ?::tipo_usuario_enum
                ORDER BY criado_em DESC 
                LIMIT 15
            ";
            $sqlListarFallback = "
                SELECT id, titulo, mensagem, link, lida, criado_em 
                FROM notificacoes 
                WHERE usuario_id = ? 
                  AND tipo_usuario = ?
                ORDER BY criado_em DESC 
                LIMIT 15
            ";

            $sqlCountEnum = "
                SELECT COUNT(*) 
                FROM notificacoes 
                WHERE usuario_id = ? 
                  AND tipo_usuario = ?::tipo_usuario_enum
                  AND (lida = FALSE OR lida IS NULL)
            ";
            $sqlCountFallback = "
                SELECT COUNT(*) 
                FROM notificacoes 
                WHERE usuario_id = ? 
                  AND tipo_usuario = ?
                  AND (lida = FALSE OR lida IS NULL)
            ";

            $params = [$usuario_id, 'revendedor'];
        }

        // Busca lista
        $stmtListar = executarQueryNotificacoes($pdo, $sqlListarEnum, $sqlListarFallback, $params);
        $rawRows    = $stmtListar->fetchAll(PDO::FETCH_ASSOC);

        // Formata os tipos para o JSON
        $notificacoes = array_map(function($item) {
            return [
                'id'        => (int)$item['id'],
                'titulo'    => (string)$item['titulo'],
                'mensagem'  => (string)$item['mensagem'],
                'link'      => $item['link'] ? (string)$item['link'] : null,
                'lida'      => (bool)$item['lida'],
                'criado_em' => (string)$item['criado_em']
            ];
        }, $rawRows);

        // Busca contagem de não lidas
        $stmtCount = executarQueryNotificacoes($pdo, $sqlCountEnum, $sqlCountFallback, $params);
        $nao_lidas = (int)$stmtCount->fetchColumn();

        echo json_encode([
            'sucesso'      => true,
            'nao_lidas'    => $nao_lidas,
            'notificacoes' => $notificacoes
        ], JSON_UNESCAPED_UNICODE);
        exit;

    } catch (\Throwable $e) {
        error_log("[API Notificações] Erro ao listar: " . $e->getMessage());
        http_response_code(500);
        echo json_encode([
            'sucesso' => false,
            'erro'    => 'Erro interno ao consultar notificações.'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
}

// ==========================================
// AÇÃO: MARCAR COMO LIDAS
// ==========================================
if ($acao === 'marcar_lidas') {
    try {
        if ($isAdmin) {
            // Marca como lidas notificações do admin
            $sqlUpdateEnum = "
                UPDATE notificacoes 
                SET lida = TRUE 
                WHERE (usuario_id = ? OR usuario_id IS NULL) 
                  AND tipo_usuario = ?::tipo_usuario_enum
                  AND (lida = FALSE OR lida IS NULL)
            ";
            $sqlUpdateFallback = "
                UPDATE notificacoes 
                SET lida = TRUE 
                WHERE (usuario_id = ? OR usuario_id IS NULL) 
                  AND tipo_usuario = ?
                  AND (lida = FALSE OR lida IS NULL)
            ";
            $params = [$usuario_id, 'admin'];

        } else {
            // Marca como lidas estritamente as do revendedor logado
            $sqlUpdateEnum = "
                UPDATE notificacoes 
                SET lida = TRUE 
                WHERE usuario_id = ? 
                  AND tipo_usuario = ?::tipo_usuario_enum
                  AND (lida = FALSE OR lida IS NULL)
            ";
            $sqlUpdateFallback = "
                UPDATE notificacoes 
                SET lida = TRUE 
                WHERE usuario_id = ? 
                  AND tipo_usuario = ?
                  AND (lida = FALSE OR lida IS NULL)
            ";
            $params = [$usuario_id, 'revendedor'];
        }

        executarQueryNotificacoes($pdo, $sqlUpdateEnum, $sqlUpdateFallback, $params);

        echo json_encode([
            'sucesso' => true
        ], JSON_UNESCAPED_UNICODE);
        exit;

    } catch (\Throwable $e) {
        error_log("[API Notificações] Erro ao marcar lidas: " . $e->getMessage());
        http_response_code(500);
        echo json_encode([
            'sucesso' => false,
            'erro'    => 'Erro interno ao atualizar notificações.'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
}

// Ação desconhecida
http_response_code(400);
echo json_encode([
    'sucesso' => false,
    'erro'    => 'Ação inválida.'
], JSON_UNESCAPED_UNICODE);
exit;
