<?php 
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/config.php';

verificarNivel('admin');

// Query agregada com alta performance aproveitando os índices criados
$query = "
    SELECT u.id,
           u.nome, 
           COALESCE(SUM(ip.quantidade * ip.preco_unitario), 0) as total_vendas, 
           COUNT(DISTINCT p.id) as total_pedidos
    FROM pedidos p
    JOIN usuarios u ON p.usuario_id = u.id
    JOIN itens_pedido ip ON p.id = ip.pedido_id
    WHERE p.status = 'concluido'
    GROUP BY u.id, u.nome
    ORDER BY total_vendas DESC
";
$relatorio = $pdo->query($query)->fetchAll(PDO::FETCH_ASSOC);

$total_geral = array_sum(array_column($relatorio, 'total_vendas'));
$total_pedidos_geral = array_sum(array_column($relatorio, 'total_pedidos'));

include __DIR__ . '/../includes/header.php'; 
?>

<div class="max-w-5xl mx-auto py-6 sm:py-10 px-4 sm:px-6 lg:px-8">
    
    <!-- Topo -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 sm:mb-8 gap-4">
        <div>
            <div class="flex items-center gap-2">
                <span class="w-2 h-6 bg-primaria rounded-full inline-block"></span>
                <h1 class="text-2xl sm:text-3xl font-black text-gray-800 tracking-tight">Relatório de Performance</h1>
            </div>
            <p class="text-xs sm:text-sm text-gray-500 mt-0.5">Ranking de faturamento e volume por revendedor (Apenas pedidos concluídos).</p>
        </div>
        <a href="dashboard.php" 
           class="inline-flex items-center gap-1.5 text-xs sm:text-sm font-bold text-gray-600 hover:text-primaria bg-white px-4 py-2.5 rounded-xl border border-gray-200/80 shadow-2xs transition active:scale-95">
            <i data-lucide="arrow-left" class="w-4 h-4"></i>
            <span>Voltar ao Dashboard</span>
        </a>
    </div>

    <!-- CARDS DE RESUMO GERAL -->
    <?php if (!empty($relatorio)): ?>
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-8">
        <div class="bg-white p-5 sm:p-6 rounded-3xl border border-gray-100 shadow-sm flex items-center justify-between">
            <div>
                <span class="text-[11px] font-black uppercase text-gray-400 tracking-wider">Faturamento Total Entregue</span>
                <p class="text-2xl sm:text-3xl font-black text-primaria mt-0.5">R$ <?= number_format($total_geral, 2, ',', '.') ?></p>
            </div>
            <div class="p-3.5 bg-secundaria/30 rounded-2xl text-primaria">
                <i data-lucide="dollar-sign" class="w-6 h-6"></i>
            </div>
        </div>
        <div class="bg-white p-5 sm:p-6 rounded-3xl border border-gray-100 shadow-sm flex items-center justify-between">
            <div>
                <span class="text-[11px] font-black uppercase text-gray-400 tracking-wider">Total de Pedidos Concluídos</span>
                <p class="text-2xl sm:text-3xl font-black text-gray-800 mt-0.5"><?= (int)$total_pedidos_geral ?></p>
            </div>
            <div class="p-3.5 bg-gray-100 rounded-2xl text-gray-600">
                <i data-lucide="package-check" class="w-6 h-6"></i>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- LISTA DE DESEMPENHO EM CARDS FLUIDOS -->
    <div class="space-y-3.5">
        <?php if (empty($relatorio)): ?>
            <div class="bg-white p-8 sm:p-14 text-center rounded-3xl border border-gray-100 shadow-sm">
                <div class="w-16 h-16 bg-gray-50 rounded-2xl flex items-center justify-center mx-auto mb-3 text-gray-300">
                    <i data-lucide="bar-chart-3" class="w-8 h-8"></i>
                </div>
                <h4 class="text-base font-bold text-gray-700 mb-1">Nenhum pedido concluído ainda</h4>
                <p class="text-xs text-gray-400 max-w-sm mx-auto">Assim que os pedidos forem marcados como concluídos, as métricas e o ranking serão gerados automaticamente.</p>
            </div>
        <?php else: ?>
            <?php foreach ($relatorio as $index => $row): ?>
            <?php
                $posicao = $index + 1;
                $estiloPosicao = match($posicao) {
                    1 => 'bg-amber-100 text-amber-800 border-amber-200',
                    2 => 'bg-slate-200 text-slate-800 border-slate-300',
                    3 => 'bg-orange-100 text-orange-800 border-orange-200',
                    default => 'bg-gray-100 text-gray-600 border-gray-200'
                };
                $bordaLateral = ($posicao === 1) ? 'border-l-4 border-l-amber-400' : 'border-l-4 border-l-primaria';
            ?>
            <div class="bg-white p-4 sm:p-6 rounded-2xl sm:rounded-3xl shadow-sm border border-gray-100 <?= $bordaLateral ?> flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 transition hover:shadow-md">
                
                <!-- Informações do Revendedor -->
                <div class="flex items-center gap-3.5 sm:gap-4 min-w-0">
                    <div class="w-10 h-10 sm:w-11 sm:h-11 rounded-2xl flex items-center justify-center font-black text-sm shrink-0 border <?= $estiloPosicao ?>">
                        #<?= $posicao ?>
                    </div>
                    <div class="min-w-0">
                        <div class="flex items-center gap-2 mb-0.5">
                            <?php if ($posicao === 1): ?>
                                <span class="text-[10px] font-black uppercase tracking-wider text-amber-600 bg-amber-50 px-2 py-0.5 rounded-md border border-amber-200 flex items-center gap-1">
                                    <span>👑</span> Líder de Vendas
                                </span>
                            <?php else: ?>
                                <span class="text-[10px] font-bold uppercase tracking-wider text-gray-400">Revendedor</span>
                            <?php endif; ?>
                        </div>
                        <h3 class="text-base sm:text-lg font-bold text-gray-800 truncate"><?= htmlspecialchars((string)$row['nome'], ENT_QUOTES, 'UTF-8') ?></h3>
                        <p class="text-xs text-gray-400 font-medium">
                            <?= (int)$row['total_pedidos'] ?> <?= (int)$row['total_pedidos'] === 1 ? 'pedido concluído' : 'pedidos concluídos' ?>
                        </p>
                    </div>
                </div>

                <!-- Total Vendido -->
                <div class="text-left sm:text-right border-t sm:border-t-0 pt-3 sm:pt-0 w-full sm:w-auto flex sm:flex-col justify-between sm:justify-start items-center sm:items-end">
                    <span class="text-[11px] font-bold uppercase text-gray-400 tracking-wider">Total em Vendas</span>
                    <p class="text-xl sm:text-2xl font-black text-primaria">
                        R$ <?= number_format($row['total_vendas'], 2, ',', '.') ?>
                    </p>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
