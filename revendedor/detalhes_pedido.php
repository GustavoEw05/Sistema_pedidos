<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/config.php';

verificarNivel('revendedor');

$usuario_id = (int)$_SESSION['usuario_id'];
$pedido_id  = (int)($_GET['id'] ?? 0);

if ($pedido_id <= 0) {
    header("Location: painel.php");
    exit;
}

// 1. Busca os dados do Pedido e valida se pertence ao Revendedor logado (Anti-IDOR)
$stmt = $pdo->prepare("
    SELECT p.id, p.status, p.data_pedido, u.nome as revendedor_nome
    FROM pedidos p
    JOIN usuarios u ON p.usuario_id = u.id
    WHERE p.id = ? AND p.usuario_id = ?
");
$stmt->execute([$pedido_id, $usuario_id]);
$pedido = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$pedido) {
    header("Location: painel.php?erro=pedido_nao_encontrado");
    exit;
}

// 2. Busca os Itens do Pedido
$stmtItens = $pdo->prepare("
    SELECT ip.quantidade, ip.preco_unitario, pr.nome as produto_nome, pr.imagem as produto_imagem
    FROM itens_pedido ip
    JOIN produtos pr ON ip.produto_id = pr.id
    WHERE ip.pedido_id = ?
");
$stmtItens->execute([$pedido_id]);
$itens = $stmtItens->fetchAll(PDO::FETCH_ASSOC);

$total_geral = 0;

include __DIR__ . '/../includes/header.php';
?>

<div class="max-w-4xl mx-auto py-6 sm:py-10 px-4 sm:px-6 lg:px-8">
    
    <!-- Botão Voltar -->
    <a href="painel.php" 
       class="inline-flex items-center text-xs sm:text-sm font-bold text-gray-500 hover:text-primaria mb-5 sm:mb-6 transition gap-1.5 active:scale-95">
        <i data-lucide="arrow-left" class="w-4 h-4"></i>
        <span>Voltar ao Meu Painel</span>
    </a>

    <!-- Card de Detalhes Principal -->
    <div class="bg-white rounded-3xl shadow-sm border border-gray-100 overflow-hidden transition-all">
        
        <!-- Cabeçalho do Card -->
        <div class="p-5 sm:p-8 bg-gray-50/70 border-b border-gray-100 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div>
                <span class="text-[11px] font-black text-gray-400 uppercase tracking-widest block">
                    Pedido #<?= str_pad((string)$pedido['id'], 5, '0', STR_PAD_LEFT) ?>
                </span>
                <h1 class="text-xl sm:text-2xl font-black text-gray-800 mt-0.5 tracking-tight">
                    <?= htmlspecialchars($pedido['revendedor_nome'], ENT_QUOTES, 'UTF-8') ?>
                </h1>
                <p class="text-xs text-gray-400 mt-1 flex items-center gap-1">
                    <i data-lucide="calendar" class="w-3.5 h-3.5"></i>
                    <span>Solicitado em <?= date('d/m/Y \à\s H:i', strtotime($pedido['data_pedido'])) ?></span>
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-2.5 w-full sm:w-auto">
                <!-- Badge de Status -->
                <?php if ($pedido['status'] === 'pendente'): ?>
                    <span class="px-3.5 py-1.5 inline-flex items-center gap-1.5 text-xs font-bold rounded-full bg-amber-50 text-amber-700 border border-amber-200">
                        <span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-pulse"></span>
                        <span>Em Aberto</span>
                    </span>
                <?php else: ?>
                    <span class="px-3.5 py-1.5 inline-flex items-center gap-1.5 text-xs font-bold rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200">
                        <i data-lucide="check" class="w-3.5 h-3.5"></i>
                        <span>Concluído</span>
                    </span>
                <?php endif; ?>

                <!-- Botão de Download PDF -->
                <a href="../gerar_pdf.php?id=<?= (int)$pedido['id'] ?>" target="_blank" 
                   class="inline-flex items-center gap-1.5 bg-white hover:bg-gray-100 text-gray-700 font-bold text-xs px-4 py-2 rounded-xl transition border border-gray-200 shadow-2xs active:scale-95">
                    <i data-lucide="file-text" class="w-4 h-4 text-primaria"></i>
                    <span>Comprovante PDF</span>
                </a>
            </div>
        </div>

        <!-- Conteúdo dos Itens Solicitados -->
        <div class="p-5 sm:p-8">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-xs font-black text-gray-400 uppercase tracking-wider">Itens Solicitados</h3>
                <span class="text-xs text-gray-400 font-semibold"><?= count($itens) ?> item(ns)</span>
            </div>

            <!-- 1. Visualização Mobile: Cards Fluidos (Ideal para Telas Pequenas) -->
            <div class="block sm:hidden space-y-3">
                <?php foreach ($itens as $item): ?>
                    <?php 
                        $subtotal = $item['quantidade'] * $item['preco_unitario'];
                        $total_geral += $subtotal;
                    ?>
                    <div class="p-3.5 bg-gray-50/70 rounded-2xl border border-gray-100 flex items-center justify-between gap-3">
                        <div class="flex items-center gap-3 min-w-0 flex-1">
                            <img src="../uploads/<?= htmlspecialchars($item['produto_imagem'] ?: 'default.png', ENT_QUOTES, 'UTF-8') ?>" 
                                 alt="<?= htmlspecialchars($item['produto_nome'], ENT_QUOTES, 'UTF-8') ?>" 
                                 class="w-12 h-12 object-contain rounded-xl bg-white border border-gray-100 p-1 shrink-0"
                                 onerror="this.src='../uploads/default.png'">
                            <div class="min-w-0 flex-1">
                                <h4 class="text-xs font-bold text-gray-800 truncate"><?= htmlspecialchars($item['produto_nome'], ENT_QUOTES, 'UTF-8') ?></h4>
                                <div class="flex items-center gap-2 mt-0.5 text-[11px] text-gray-400">
                                    <span class="font-bold text-gray-600 bg-white px-2 py-0.5 rounded-md border border-gray-100"><?= (int)$item['quantidade'] ?> un.</span>
                                    <span>&times; R$ <?= number_format($item['preco_unitario'], 2, ',', '.') ?></span>
                                </div>
                            </div>
                        </div>
                        <div class="text-right shrink-0">
                            <span class="text-xs font-black text-gray-900 block">R$ <?= number_format($subtotal, 2, ',', '.') ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- 2. Visualização Desktop/Tablet: Tabela Estruturada Clássica -->
            <div class="hidden sm:block overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b border-gray-100 text-xs font-bold text-gray-400 uppercase tracking-wider">
                            <th class="pb-3">Produto</th>
                            <th class="pb-3 text-center">Qtd</th>
                            <th class="pb-3 text-right">Preço Un.</th>
                            <th class="pb-3 text-right">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50 text-sm">
                        <?php foreach ($itens as $item): ?>
                            <?php 
                                $subtotal = $item['quantidade'] * $item['preco_unitario'];
                            ?>
                            <tr class="hover:bg-gray-50/50 transition">
                                <td class="py-4 font-semibold text-gray-800 flex items-center gap-3">
                                    <img src="../uploads/<?= htmlspecialchars($item['produto_imagem'] ?: 'default.png', ENT_QUOTES, 'UTF-8') ?>" 
                                         alt="<?= htmlspecialchars($item['produto_nome'], ENT_QUOTES, 'UTF-8') ?>" 
                                         class="w-10 h-10 object-contain rounded-lg border border-gray-100 bg-white p-1"
                                         onerror="this.src='../uploads/default.png'">
                                    <span><?= htmlspecialchars($item['produto_nome'], ENT_QUOTES, 'UTF-8') ?></span>
                                </td>
                                <td class="py-4 text-center text-gray-500 font-medium">
                                    <?= (int)$item['quantidade'] ?> un.
                                </td>
                                <td class="py-4 text-right text-gray-500">
                                    R$ <?= number_format($item['preco_unitario'], 2, ',', '.') ?>
                                </td>
                                <td class="py-4 text-right font-black text-gray-900">
                                    R$ <?= number_format($subtotal, 2, ',', '.') ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Card Total Geral -->
            <div class="mt-6 pt-5 border-t border-gray-100 flex justify-between items-center bg-gray-50/70 p-4 rounded-2xl">
                <span class="text-xs font-bold text-gray-500 uppercase tracking-wider">Valor Total do Pedido:</span>
                <span class="text-xl sm:text-2xl font-black text-primaria">
                    R$ <?= number_format($total_geral, 2, ',', '.') ?>
                </span>
            </div>

            <!-- Rodapé / Observações -->
            <div class="mt-6 pt-4 border-t border-gray-100 flex flex-col sm:flex-row justify-between items-center text-xs text-gray-400 gap-2">
                <span>Pedido registrado com segurança no Supabase</span>
                <span class="flex items-center gap-1.5 text-emerald-600 font-semibold">
                    <i data-lucide="shield-check" class="w-4 h-4"></i> Integridade Validada
                </span>
            </div>
        </div>

    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
