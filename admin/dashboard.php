<?php 
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/config.php';

verificarNivel('admin');

// 1. CAPTURA DOS FILTROS (GET)
$filtro_empresa = trim($_GET['empresa'] ?? '');
$filtro_status  = trim($_GET['status'] ?? '');
$data_inicio    = trim($_GET['data_inicio'] ?? '');
$data_fim       = trim($_GET['data_fim'] ?? '');

// 2. CONSTRUÇÃO DA QUERY DINÂMICA
$sql = "SELECT p.id, u.nome as revendedor, p.status, p.data_pedido,
               COALESCE(SUM(ip.quantidade * ip.preco_unitario), 0) as valor_total
        FROM pedidos p
        JOIN usuarios u ON p.usuario_id = u.id
        LEFT JOIN itens_pedido ip ON p.id = ip.pedido_id
        WHERE 1=1";

$params = [];

if ($filtro_empresa !== '') {
    $sql .= " AND u.nome ILIKE ?";
    $params[] = "%$filtro_empresa%";
}

if ($filtro_status !== '' && in_array($filtro_status, ['pendente', 'concluido'], true)) {
    $sql .= " AND p.status = ?";
    $params[] = $filtro_status;
}

if ($data_inicio !== '' && $data_fim !== '') {
    $sql .= " AND DATE(p.data_pedido) BETWEEN ? AND ?";
    $params[] = $data_inicio;
    $params[] = $data_fim;
}

$sql .= " GROUP BY p.id, u.nome, p.status, p.data_pedido ORDER BY p.data_pedido DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$pedidos = $stmt->fetchAll(PDO::FETCH_ASSOC);

include __DIR__ . '/../includes/header.php'; 
?>

<div class="max-w-7xl mx-auto py-6 sm:py-10 px-4 sm:px-6 lg:px-8">
    
    <!-- Header Principal do Painel -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 sm:mb-8 gap-4">
        <div>
            <div class="flex items-center gap-2">
                <span class="w-2 h-6 bg-primaria rounded-full inline-block"></span>
                <h1 class="text-2xl sm:text-3xl font-black text-gray-800 tracking-tight">Painel de Pedidos</h1>
            </div>
            <p class="text-xs sm:text-sm text-gray-500 mt-0.5">Gerenciamento e acompanhamento em tempo real das solicitações de revenda.</p>
        </div>
        <div class="flex items-center bg-secundaria/30 px-4 py-2.5 rounded-2xl border border-secundaria/60 text-xs font-bold text-primaria whitespace-nowrap shadow-2xs">
            <i data-lucide="layers" class="w-4 h-4 mr-2"></i>
            <span>Total: <?= count($pedidos) ?> <?= count($pedidos) === 1 ? 'pedido registrado' : 'pedidos registrados' ?></span>
        </div>
    </div>

    <!-- Feedbacks de Sucesso -->
    <?php if (isset($_GET['sucesso'])): ?>
        <?php $msg = ($_GET['sucesso'] === 'concluido') ? 'Status do pedido alterado para concluído!' : 'Status do pedido alterado para pendente!'; ?>
        <div class="mb-6 bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 rounded-2xl flex items-center gap-2.5 text-xs sm:text-sm shadow-sm">
            <i data-lucide="check-circle-2" class="w-5 h-5 text-emerald-600 shrink-0"></i>
            <span><?= htmlspecialchars($msg, ENT_QUOTES, 'UTF-8') ?></span>
        </div>
    <?php endif; ?>

    <!-- Card de Filtros Avançados -->
    <div class="bg-white p-4 sm:p-6 rounded-3xl shadow-sm border border-gray-100 mb-8 transition-all">
        <form method="GET" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4 items-end">
            <div>
                <label class="block text-[11px] font-bold text-gray-400 uppercase tracking-wider mb-1.5">Revendedor / Empresa</label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                        <i data-lucide="user" class="w-4 h-4"></i>
                    </div>
                    <input type="text" name="empresa" value="<?= htmlspecialchars($filtro_empresa, ENT_QUOTES, 'UTF-8') ?>" placeholder="Buscar por nome..."
                           class="w-full pl-9 pr-3 py-2.5 border border-gray-200 rounded-xl text-xs sm:text-sm text-gray-800 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-primaria/20 focus:border-primaria transition bg-gray-50/30">
                </div>
            </div>

            <div>
                <label class="block text-[11px] font-bold text-gray-400 uppercase tracking-wider mb-1.5">Status do Pedido</label>
                <select name="status" class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-xs sm:text-sm text-gray-800 focus:outline-none focus:ring-2 focus:ring-primaria/20 focus:border-primaria transition bg-gray-50/30 cursor-pointer">
                    <option value="">Todos os Status</option>
                    <option value="pendente" <?= $filtro_status === 'pendente' ? 'selected' : '' ?>>Pendentes (Abertos)</option>
                    <option value="concluido" <?= $filtro_status === 'concluido' ? 'selected' : '' ?>>Concluídos</option>
                </select>
            </div>

            <div>
                <label class="block text-[11px] font-bold text-gray-400 uppercase tracking-wider mb-1.5">Intervalo de Datas</label>
                <div class="grid grid-cols-2 gap-2">
                    <input type="date" name="data_inicio" value="<?= htmlspecialchars($data_inicio, ENT_QUOTES, 'UTF-8') ?>" title="Data Início"
                           class="w-full px-2.5 py-2.5 border border-gray-200 rounded-xl text-xs text-gray-700 focus:outline-none focus:ring-2 focus:ring-primaria/20 focus:border-primaria transition bg-gray-50/30">
                    <input type="date" name="data_fim" value="<?= htmlspecialchars($data_fim, ENT_QUOTES, 'UTF-8') ?>" title="Data Fim"
                           class="w-full px-2.5 py-2.5 border border-gray-200 rounded-xl text-xs text-gray-700 focus:outline-none focus:ring-2 focus:ring-primaria/20 focus:border-primaria transition bg-gray-50/30">
                </div>
            </div>

            <div class="flex gap-2">
                <button type="submit" 
                        class="flex-1 bg-primaria text-white font-bold h-[42px] rounded-xl hover:bg-primaria-hover transition shadow-sm active:scale-95 flex items-center justify-center gap-1.5 text-xs sm:text-sm cursor-pointer">
                    <i data-lucide="search" class="w-4 h-4"></i>
                    <span>Filtrar</span>
                </button>
                <a href="dashboard.php" 
                   title="Limpar Filtros"
                   class="w-[42px] h-[42px] bg-gray-100 text-gray-500 hover:bg-gray-200 hover:text-gray-800 rounded-xl transition flex items-center justify-center shrink-0 border border-gray-200 active:scale-95">
                    <i data-lucide="rotate-ccw" class="w-4 h-4"></i>
                </a>
            </div>
        </form>
    </div>

    <!-- Lista de Pedidos em Cards Modernos -->
    <?php if (empty($pedidos)): ?>
        <div class="bg-white rounded-3xl border border-gray-100 p-8 sm:p-14 text-center">
            <div class="w-16 h-16 bg-gray-50 rounded-2xl flex items-center justify-center mx-auto mb-4 text-gray-300">
                <i data-lucide="inbox" class="w-8 h-8"></i>
            </div>
            <h3 class="text-base font-bold text-gray-700 mb-1">Nenhum pedido localizado</h3>
            <p class="text-xs text-gray-400 max-w-sm mx-auto">Tente alterar ou limpar os filtros de busca para visualizar outros pedidos.</p>
        </div>
    <?php else: ?>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-6">
            <?php foreach ($pedidos as $pedido): ?>
                <div class="bg-white rounded-2xl sm:rounded-3xl border border-gray-100 shadow-sm hover:shadow-md transition-all duration-200 flex flex-col justify-between overflow-hidden group">
                    
                    <!-- Corpo do Card -->
                    <a href="detalhes_pedido.php?id=<?= (int)$pedido['id'] ?>" class="p-5 sm:p-6 block flex-1">
                        
                        <!-- Topo: ID e Status -->
                        <div class="flex justify-between items-center mb-3">
                            <span class="text-xs font-black uppercase text-gray-400 tracking-wider">
                                Pedido #<?= str_pad((string)$pedido['id'], 5, '0', STR_PAD_LEFT) ?>
                            </span>
                            <?php if ($pedido['status'] === 'pendente'): ?>
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 text-[11px] font-bold rounded-full bg-amber-50 text-amber-700 border border-amber-200/80">
                                    <span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-pulse"></span>
                                    <span>Pendente</span>
                                </span>
                            <?php else: ?>
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 text-[11px] font-bold rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200/80">
                                    <i data-lucide="check" class="w-3 h-3"></i>
                                    <span>Concluído</span>
                                </span>
                            <?php endif; ?>
                        </div>

                        <!-- Revendedor -->
                        <div class="mb-4">
                            <h3 class="text-base sm:text-lg font-bold text-gray-800 group-hover:text-primaria transition-colors line-clamp-1">
                                <?= htmlspecialchars((string)$pedido['revendedor'], ENT_QUOTES, 'UTF-8') ?>
                            </h3>
                            <p class="text-xs text-gray-400 flex items-center mt-1">
                                <i data-lucide="calendar" class="w-3.5 h-3.5 mr-1 text-gray-300"></i>
                                <?= date('d/m/Y \à\s H:i', strtotime($pedido['data_pedido'])) ?>
                            </p>
                        </div>

                        <!-- Valor Total -->
                        <div class="bg-gray-50/70 group-hover:bg-primaria/5 rounded-2xl p-3.5 border border-gray-100 transition-colors flex justify-between items-center">
                            <span class="text-[11px] font-bold text-gray-400 uppercase tracking-wider">Valor Total</span>
                            <span class="text-base sm:text-lg font-black text-primaria">
                                R$ <?= number_format($pedido['valor_total'], 2, ',', '.') ?>
                            </span>
                        </div>
                    </a>

                    <!-- Rodapé de Ações -->
                    <div class="px-5 py-3.5 bg-gray-50/80 border-t border-gray-100 flex flex-wrap justify-between items-center gap-2 text-xs font-semibold">
                        
                        <!-- Ação de Status (Concluir/Reabrir) -->
                        <?php if ($pedido['status'] === 'pendente'): ?>
                            <a href="atualizar_status.php?id=<?= (int)$pedido['id'] ?>&status=concluido" 
                               class="inline-flex items-center gap-1.5 text-emerald-600 hover:text-emerald-800 font-bold transition active:scale-95 py-1">
                                <i data-lucide="check-circle" class="w-4 h-4"></i>
                                <span>Marcar Concluído</span>
                            </a>
                        <?php else: ?>
                            <a href="atualizar_status.php?id=<?= (int)$pedido['id'] ?>&status=pendente" 
                               class="inline-flex items-center gap-1.5 text-amber-600 hover:text-amber-800 font-bold transition active:scale-95 py-1">
                                <i data-lucide="rotate-ccw" class="w-4 h-4"></i>
                                <span>Reabrir Pedido</span>
                            </a>
                        <?php endif; ?>

                        <!-- Link Comprovante PDF -->
                        <a href="../gerar_pdf.php?id=<?= (int)$pedido['id'] ?>" target="_blank" 
                           class="inline-flex items-center gap-1 text-gray-500 hover:text-gray-800 hover:bg-white px-2.5 py-1 rounded-lg border border-transparent hover:border-gray-200 transition" 
                           title="Visualizar Comprovante PDF">
                            <i data-lucide="file-text" class="w-4 h-4"></i>
                            <span>PDF</span>
                        </a>
                    </div>

                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
