<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/config.php';

$usuario_id = (int)($_SESSION['usuario_id'] ?? 0);
$nivel      = $_SESSION['usuario_nivel'] ?? '';

$pedido_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$pedido_id || $pedido_id <= 0) {
    die("Pedido inválido.");
}

// 1. Busca informações do cabeçalho do pedido
$stmt = $pdo->prepare("
    SELECT p.id, p.status, p.data_pedido, p.usuario_id, u.nome as revendedor 
    FROM pedidos p 
    JOIN usuarios u ON p.usuario_id = u.id 
    WHERE p.id = ?
");
$stmt->execute([$pedido_id]);
$pedido = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$pedido) { 
    die("Pedido não encontrado."); 
}

// 2. Prevenção Rigorosa de IDOR: Bloqueia caso o revendedor tente visualizar PDF de outro usuário
if ($nivel === 'revendedor' && (int)$pedido['usuario_id'] !== $usuario_id) {
    http_response_code(403);
    die("Acesso negado: Você não tem permissão para visualizar este pedido.");
}

// 3. Busca itens do pedido (Usando índice idx_itens_pedido_pedido_id)
$stmtItens = $pdo->prepare("
    SELECT ip.quantidade, ip.preco_unitario, pr.nome 
    FROM itens_pedido ip 
    JOIN produtos pr ON ip.produto_id = pr.id 
    WHERE ip.pedido_id = ?
");
$stmtItens->execute([$pedido_id]);
$itens = $stmtItens->fetchAll(PDO::FETCH_ASSOC);

$total_geral = 0;
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pedido_<?= (int)$pedido['id'] ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @media print {
            .no-print { display: none !important; }
            body { background-color: #ffffff !important; padding: 0 !important; }
            .print-container { border: none !important; box-shadow: none !important; max-width: 100% !important; padding: 0 !important; }
        }
    </style>
</head>
<body class="bg-gray-100 text-gray-900 p-4 md:p-8" onload="window.print()">

    <!-- Barra de Ferramentas (Oculta na Impressão) -->
    <div class="max-w-3xl mx-auto mb-4 flex justify-between items-center no-print">
        <button onclick="window.close()" class="px-4 py-2 bg-gray-200 text-gray-700 text-sm font-semibold rounded-lg hover:bg-gray-300 transition">
            &larr; Fechar
        </button>
        <button onclick="window.print()" class="px-4 py-2 bg-blue-600 text-white text-sm font-semibold rounded-lg hover:bg-blue-700 transition flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
            Imprimir / Salvar PDF
        </button>
    </div>

    <!-- Container do Documento -->
    <div class="max-w-3xl mx-auto bg-white border border-gray-200 p-8 rounded-xl shadow-sm print-container">
        
        <!-- Cabeçalho -->
        <div class="flex justify-between items-start border-b border-gray-200 pb-6 mb-6">
            <div>
                <h1 class="text-2xl font-black uppercase tracking-tight text-gray-800">Comprovante de Pedido</h1>
                <p class="text-xs text-gray-500 mt-1">Gerado em: <?= date('d/m/Y \à\s H:i') ?></p>
            </div>
            <div class="text-right">
                <p class="text-xl font-extrabold text-gray-900">#<?= str_pad((string)$pedido['id'], 5, '0', STR_PAD_LEFT) ?></p>
                <span class="mt-1 text-xs px-2.5 py-1 rounded-full text-white inline-block font-bold uppercase tracking-wider <?= $pedido['status'] === 'pendente' ? 'bg-amber-500' : ($pedido['status'] === 'cancelado' ? 'bg-red-500' : 'bg-emerald-500') ?>">
                    <?= htmlspecialchars((string)$pedido['status'], ENT_QUOTES, 'UTF-8') ?>
                </span>
            </div>
        </div>

        <!-- Dados Gerais -->
        <div class="mb-6 bg-gray-50 p-4 rounded-lg text-sm border border-gray-100 grid grid-cols-2 gap-4">
            <div>
                <span class="text-xs uppercase font-bold text-gray-400 block">Emissor / Revendedor</span>
                <strong class="text-gray-800 text-base"><?= htmlspecialchars((string)$pedido['revendedor'], ENT_QUOTES, 'UTF-8') ?></strong>
            </div>
            <div>
                <span class="text-xs uppercase font-bold text-gray-400 block">Data de Solicitação</span>
                <strong class="text-gray-800 text-base"><?= date('d/m/Y \à\s H:i', strtotime($pedido['data_pedido'])) ?></strong>
            </div>
        </div>

        <!-- Tabela de Itens -->
        <div class="mb-6">
            <h2 class="text-xs font-black uppercase tracking-wider text-gray-400 mb-3">Itens do Pedido</h2>
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="border-b border-gray-200 text-xs font-bold text-gray-500 uppercase">
                        <th class="py-2">Item</th>
                        <th class="py-2 text-center">Qtd</th>
                        <th class="py-2 text-right">Preço Unit.</th>
                        <th class="py-2 text-right">Subtotal</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 text-sm">
                    <?php foreach ($itens as $item): ?>
                        <?php 
                            $subtotal = $item['quantidade'] * $item['preco_unitario'];
                            $total_geral += $subtotal;
                        ?>
                        <tr>
                            <td class="py-3 font-medium text-gray-800"><?= htmlspecialchars((string)$item['nome'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="py-3 text-center text-gray-500"><?= (int)$item['quantidade'] ?></td>
                            <td class="py-3 text-right text-gray-500">R$ <?= number_format($item['preco_unitario'], 2, ',', '.') ?></td>
                            <td class="py-3 text-right font-bold text-gray-800">R$ <?= number_format($subtotal, 2, ',', '.') ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr class="border-t-2 border-gray-200">
                        <td colspan="3" class="pt-4 text-right font-bold text-gray-600 text-sm uppercase">Total do Pedido:</td>
                        <td class="pt-4 text-right text-xl font-black text-gray-900">
                            R$ <?= number_format($total_geral, 2, ',', '.') ?>
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <!-- Rodapé do Documento -->
        <div class="border-t border-gray-200 pt-6 mt-8 flex justify-between items-center text-xs text-gray-400">
            <span>Este documento serve como comprovante de solicitação interna de pedidos.</span>
            <span>Página 1 de 1</span>
        </div>

    </div>

</body>
</html>
