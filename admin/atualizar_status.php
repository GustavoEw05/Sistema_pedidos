<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/notificacoes_helper.php';

verificarNivel('admin');

$id = isset($_GET['id']) ? (int)$_GET['id'] : null;
$status = $_GET['status'] ?? null;

if ($id && ($status === 'concluido' || $status === 'pendente')) {
    try {
        // 1. Resgata o revendedor dono do pedido
        $stmtBusca = $pdo->prepare("SELECT usuario_id, status FROM pedidos WHERE id = ?");
        $stmtBusca->execute([$id]);
        $pedido = $stmtBusca->fetch(PDO::FETCH_ASSOC);

        if ($pedido) {
            $revendedor_id = (int)$pedido['usuario_id'];
            $statusAnterior = $pedido['status'];

            // 2. Atualiza o status do pedido
            $stmtUpdate = $pdo->prepare("UPDATE pedidos SET status = ? WHERE id = ?");
            $stmtUpdate->execute([$status, $id]);

            // 3. Dispara notificação apenas se o status realmente mudou ou foi reafirmado
            if ($revendedor_id > 0 && function_exists('criarNotificacao')) {
                $statusLegivel = ($status === 'concluido') ? 'Concluído' : 'Pendente';
                $titulo = ($status === 'concluido') ? 'Pedido Concluído ✅' : 'Pedido Atualizado! 📦';
                $mensagem = "O seu pedido #{$id} foi atualizado para {$statusLegivel}.";
                $link = "detalhes_pedido.php?id={$id}";

                try {
                    criarNotificacao(
                        $pdo,
                        $revendedor_id,
                        'revendedor',
                        $titulo,
                        $mensagem,
                        $link
                    );
                } catch (\Throwable $eNotif) {
                    error_log("[Atualizar Status] Falha ao criar notificação para revendedor: " . $eNotif->getMessage());
                }
            }

            header("Location: dashboard.php?sucesso=" . $status);
            exit;
        } else {
            header("Location: dashboard.php?erro=pedido_nao_encontrado");
            exit;
        }

    } catch (\Throwable $e) {
        error_log("[Atualizar Status] Erro ao atualizar status do pedido #{$id}: " . $e->getMessage());
        header("Location: dashboard.php?erro=falha_atualizacao");
        exit;
    }
}

header("Location: dashboard.php");
exit;
