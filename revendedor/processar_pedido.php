<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/notificacoes_helper.php';

verificarNivel('revendedor');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validação de CSRF Token
    $csrfToken = $_POST['csrf_token'] ?? null;
    if ($csrfToken !== null && !validarCsrfToken($csrfToken)) {
        header("Location: novo_pedido.php?erro=sessao_expirada");
        exit;
    }

    $usuario_id   = (int)($_SESSION['usuario_id'] ?? 0);
    $usuario_nome = trim($_SESSION['usuario_nome'] ?? 'Revendedor');

    $carrinho_json = $_POST['carrinho_json'] ?? '[]';
    $carrinho      = json_decode($carrinho_json, true);

    if ($usuario_id <= 0 || empty($carrinho) || !is_array($carrinho)) {
        header("Location: novo_pedido.php?erro=carrinho_vazio");
        exit;
    }

    // 1. Agrupamento e Sanitização dos Itens
    $itensValidados = [];
    foreach ($carrinho as $item) {
        $produto_id = (int)($item['id'] ?? 0);
        $quantidade = (int)($item['qtd'] ?? 0);

        if ($produto_id > 0 && $quantidade > 0) {
            $itensValidados[$produto_id] = ($itensValidados[$produto_id] ?? 0) + $quantidade;
        }
    }

    if (empty($itensValidados)) {
        header("Location: novo_pedido.php?erro=carrinho_vazio");
        exit;
    }

    $pedido_id = null;

    try {
        $pdo->beginTransaction();

        // 2. Consulta em Batch dos Preços dos Produtos (Elimina N+1 queries)
        $produtoIds = array_keys($itensValidados);
        $placeholders = implode(',', array_fill(0, count($produtoIds), '?'));
        
        $stmtPrecos = $pdo->prepare("SELECT id, preco FROM produtos WHERE id IN ($placeholders)");
        $stmtPrecos->execute($produtoIds);
        $tabelaPrecos = $stmtPrecos->fetchAll(PDO::FETCH_KEY_PAIR); // [id => preco]

        if (empty($tabelaPrecos)) {
            throw new Exception("Nenhum produto válido foi encontrado no catálogo.");
        }

        // 3. Criação do Pedido (Compatível com PostgreSQL)
        $stmtPedido = $pdo->prepare("INSERT INTO pedidos (usuario_id, status) VALUES (?, 'pendente') RETURNING id");
        $stmtPedido->execute([$usuario_id]);
        $pedido_id = (int)$stmtPedido->fetchColumn();

        if ($pedido_id <= 0) {
            throw new Exception("Falha ao resgatar o ID do pedido gerado.");
        }

        // 4. Inserção dos Itens em Batch Único (Multi-Row INSERT de Altíssima Performance)
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
            throw new Exception("Nenhum item do carrinho possui preço válido cadastrado.");
        }

        $sqlInsertItens = "INSERT INTO itens_pedido (pedido_id, produto_id, quantidade, preco_unitario) VALUES " 
                        . implode(', ', $rowPlaceholders);
        $stmtItens = $pdo->prepare($sqlInsertItens);
        $stmtItens->execute($paramsInsert);

        // 5. Efetiva o Pedido no Banco de Dados
        $pdo->commit();

    } catch (\Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log("[Processar Pedido] Erro na transação: " . $e->getMessage());
        header("Location: novo_pedido.php?erro=falha_processamento");
        exit;
    }

    // 6. Criação da Notificação para os Administradores (Isolada, após o commit)
    if ($pedido_id && function_exists('criarNotificacao')) {
        try {
            criarNotificacao(
                $pdo,
                null,
                'admin',
                'Novo Pedido Recebido! 🛒',
                "O revendedor {$usuario_nome} realizou o pedido #{$pedido_id}.",
                "detalhes_pedido.php?id={$pedido_id}"
            );
        } catch (\Throwable $eNotif) {
            error_log("[Processar Pedido] Falha não impeditiva ao criar notificação para o admin: " . $eNotif->getMessage());
        }
    }

    header("Location: painel.php?sucesso=pedido_criado");
    exit;

} else {
    header("Location: novo_pedido.php");
    exit;
}
