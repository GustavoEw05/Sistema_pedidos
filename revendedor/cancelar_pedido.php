<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/config.php';

verificarNivel('revendedor');

$pedido_id  = isset($_REQUEST['id']) ? (int)$_REQUEST['id'] : 0;
$usuario_id = (int)($_SESSION['usuario_id'] ?? 0);

if ($pedido_id > 0 && $usuario_id > 0) {
    try {
        $pdo->beginTransaction();

        // 1. Prevenção Rigorosa de IDOR: Garante que o pedido é do usuário logado e 'pendente'
        $stmtCheck = $pdo->prepare("SELECT id FROM pedidos WHERE id = ? AND usuario_id = ? AND status = 'pendente' FOR UPDATE");
        $stmtCheck->execute([$pedido_id, $usuario_id]);
        $pedido = $stmtCheck->fetch();

        if ($pedido) {
            // 2. Remove os itens atrelados ao pedido
            $stmtItens = $pdo->prepare("DELETE FROM itens_pedido WHERE pedido_id = ?");
            $stmtItens->execute([$pedido_id]);

            // 3. Deleta o pedido principal
            $stmtPedido = $pdo->prepare("DELETE FROM pedidos WHERE id = ?");
            $stmtPedido->execute([$pedido_id]);

            $pdo->commit();
            header("Location: painel.php?sucesso=cancelado");
            exit;
        } else {
            $pdo->rollBack();
            header("Location: painel.php?erro=nao_encontrado");
            exit;
        }

    } catch (\Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log("[Cancelar Pedido] Erro ao cancelar #{$pedido_id}: " . $e->getMessage());
        header("Location: painel.php?erro=falha_cancelamento");
        exit;
    }
}

header("Location: painel.php");
exit;
