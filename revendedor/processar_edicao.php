<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/config.php';

verificarNivel('revendedor');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validação de CSRF Token
    $csrfToken = $_POST['csrf_token'] ?? null;
    if ($csrfToken !== null && !validarCsrfToken($csrfToken)) {
        header("Location: painel.php?erro=sessao_expirada");
        exit;
    }

    $usuario_id    = (int)($_SESSION['usuario_id'] ?? 0);
    $pedido_id     = filter_input(INPUT_POST, 'pedido_id', FILTER_VALIDATE_INT);
    $carrinho_json = $_POST['carrinho_json'] ?? '[]';
    $carrinho      = json_decode($carrinho_json, true);

    if (!$pedido_id || empty($carrinho) || !is_array($carrinho)) {
        header("Location: painel.php?erro=pedido_invalido");
        exit;
    }

    // Agrupamento e sanitização dos itens
    $itensValidados = [];
    foreach ($carrinho as $item) {
        $produto_id = (int)($item['id'] ?? 0);
        $quantidade = (int)($item['qtd'] ?? 0);

        if ($produto_id > 0 && $quantidade > 0) {
            $itensValidados[$produto_id] = ($itensValidados[$produto_id] ?? 0) + $quantidade;
        }
    }

    if (empty($itensValidados)) {
        header("Location: painel.php?erro=carrinho_vazio");
        exit;
    }

    try {
        $pdo->beginTransaction();

        // 1. Prevenção rigorosa de IDOR: Garante que o pedido pertence ao usuário logado e continua pendente
        $stmtCheck = $pdo->prepare("SELECT id FROM pedidos WHERE id = ? AND usuario_id = ? AND status = 'pendente' FOR UPDATE");
        $stmtCheck->execute([$pedido_id, $usuario_id]);
        
        if (!$stmtCheck->fetch()) { 
            throw new Exception("Pedido não encontrado ou não permitido para alteração."); 
        }

        // 2. Remove os itens antigos
        $stmtDelete = $pdo->prepare("DELETE FROM itens_pedido WHERE pedido_id = ?");
        $stmtDelete->execute([$pedido_id]);

        // 3. Consulta em Batch dos Preços no Catálogo (Prevenção de N+1)
        $produtoIds = array_keys($itensValidados);
        $placeholders = implode(',', array_fill(0, count($produtoIds), '?'));

        $stmtPrecos = $pdo->prepare("SELECT id, preco FROM produtos WHERE id IN ($placeholders)");
        $stmtPrecos->execute($produtoIds);
        $tabelaPrecos = $stmtPrecos->fetchAll(PDO::FETCH_KEY_PAIR);

        // 4. Inserção em Lote (Multi-row Insert)
        $rowPlaceholders = [];
        $paramsInsert = [];

        foreach ($itensValidados as $pId => $qtd) {
            if (isset($tabelaPrecos[$pId])) {
                $rowPlaceholders[] = "(?, ?, ?, ?)";
                $paramsInsert[] = $pedido_id;
                $paramsInsert[] = $pId;
                $paramsInsert[] = $qtd;
                $paramsInsert[] = (float)$tabelaPrecos[$pId];
            }
        }

        if (empty($rowPlaceholders)) {
            throw new Exception("Nenhum item válido pôde ser inserido.");
        }

        $sqlInsert = "INSERT INTO itens_pedido (pedido_id, produto_id, quantidade, preco_unitario) VALUES " 
                   . implode(', ', $rowPlaceholders);
        $stmtInsert = $pdo->prepare($sqlInsert);
        $stmtInsert->execute($paramsInsert);

        $pdo->commit();
        header("Location: painel.php?sucesso=pedido_editado");
        exit;

    } catch (\Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log("[Editar Pedido] Erro: " . $e->getMessage());
        header("Location: painel.php?erro=pedido_invalido");
        exit;
    }
} else {
    header("Location: painel.php");
    exit;
}
