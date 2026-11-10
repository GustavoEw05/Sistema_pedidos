<?php
/**
 * Helper centralizado para gerenciamento de notificações de sistema.
 * Compatível com PostgreSQL / Supabase e com suporte a transações seguras.
 */

if (!function_exists('criarNotificacao')) {

    /**
     * Cria uma nova notificação no banco de dados.
     *
     * @param PDO         $pdo          Instância ativa do PDO
     * @param int|null    $usuario_id   ID do destinatário (null para notificações globais de administradores)
     * @param string      $tipo_usuario 'admin' ou 'revendedor' (ENUM tipo_usuario_enum no PostgreSQL)
     * @param string      $titulo       Título da notificação
     * @param string      $mensagem     Corpo da mensagem
     * @param string|null $link         Link de redirecionamento opcional
     * @return bool                     True em caso de sucesso, False em falha
     */
    function criarNotificacao($pdo, $usuario_id, $tipo_usuario, $titulo, $mensagem, $link = null) {
        if (!$pdo instanceof PDO) {
            error_log("[Notificações] Instância de PDO inválida fornecida.");
            return false;
        }

        // Sanitização e normalização dos dados
        $targetUserId = (!empty($usuario_id) && (int)$usuario_id > 0) ? (int)$usuario_id : null;
        
        $tipoNormalizado = in_array(strtolower(trim((string)$tipo_usuario)), ['admin', 'administrador'], true)
            ? 'admin' 
            : 'revendedor';

        $tituloLimpo = trim((string)$titulo);
        $mensagemLimpa = trim((string)$mensagem);
        $linkLimpo = !empty($link) ? trim((string)$link) : null;

        // Se houver transação ativa do PDO no PostgreSQL, isolamos com SAVEPOINT
        // para garantir que nenhuma falha de notificação aborte a transação principal.
        $inTransaction = $pdo->inTransaction();
        $spName = 'sp_notif_' . bin2hex(random_bytes(4));

        if ($inTransaction) {
            try {
                $pdo->exec("SAVEPOINT {$spName}");
            } catch (\Throwable $e) {
                // Se falhar ao criar savepoint, continua sem ele
            }
        }

        try {
            // 1. Tenta a inserção padrão utilizando o cast explícito do ENUM PostgreSQL
            $stmt = $pdo->prepare("
                INSERT INTO notificacoes (usuario_id, tipo_usuario, titulo, mensagem, link, lida, criado_em) 
                VALUES (?, ?::tipo_usuario_enum, ?, ?, ?, FALSE, NOW())
            ");
            $sucesso = $stmt->execute([
                $targetUserId,
                $tipoNormalizado,
                $tituloLimpo,
                $mensagemLimpa,
                $linkLimpo
            ]);

            if ($inTransaction) {
                try {
                    $pdo->exec("RELEASE SAVEPOINT {$spName}");
                } catch (\Throwable $e) {}
            }

            return $sucesso;

        } catch (\Throwable $e) {
            // Se falhou e estávamos em transação, reverte para o SAVEPOINT antes de tentar fallback
            if ($inTransaction) {
                try {
                    $pdo->exec("ROLLBACK TO SAVEPOINT {$spName}");
                } catch (\Throwable $eSp) {}
            }

            // 2. Fallback caso a tabela/coluna aceite texto puro (sem o tipo ENUM estrito)
            try {
                if ($inTransaction) {
                    try {
                        $pdo->exec("SAVEPOINT {$spName}");
                    } catch (\Throwable $e) {}
                }

                $stmtFallback = $pdo->prepare("
                    INSERT INTO notificacoes (usuario_id, tipo_usuario, titulo, mensagem, link, lida, criado_em) 
                    VALUES (?, ?, ?, ?, ?, FALSE, NOW())
                ");
                $sucesso = $stmtFallback->execute([
                    $targetUserId,
                    $tipoNormalizado,
                    $tituloLimpo,
                    $mensagemLimpa,
                    $linkLimpo
                ]);

                if ($inTransaction) {
                    try {
                        $pdo->exec("RELEASE SAVEPOINT {$spName}");
                    } catch (\Throwable $e) {}
                }

                return $sucesso;

            } catch (\Throwable $eFallback) {
                if ($inTransaction) {
                    try {
                        $pdo->exec("ROLLBACK TO SAVEPOINT {$spName}");
                    } catch (\Throwable $eSp) {}
                }

                error_log("[Notificações] Falha ao registrar notificação: " . $eFallback->getMessage());
                return false;
            }
        }
    }
}
