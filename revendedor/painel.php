<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/config.php';

verificarNivel('revendedor');

$usuario_id = (int)$_SESSION['usuario_id'];

// 1. CAPTURA DOS FILTROS (GET)
$filtro_busca  = trim($_GET['busca'] ?? '');
$filtro_status = trim($_GET['status'] ?? '');
$data_inicio   = trim($_GET['data_inicio'] ?? '');
$data_fim      = trim($_GET['data_fim'] ?? '');

// 2. CONSTRUÇÃO DA QUERY OTIMIZADA (PostgreSQL)
$joinProdutos = ($filtro_busca !== '') ? " LEFT JOIN produtos pr ON ip.produto_id = pr.id " : "";

$sql = "SELECT p.id, p.status, p.data_pedido, u.nome as revendedor,
               COALESCE(SUM(ip.quantidade * ip.preco_unitario), 0) as valor_total
        FROM pedidos p
        JOIN usuarios u ON p.usuario_id = u.id
        LEFT JOIN itens_pedido ip ON p.id = ip.pedido_id
        {$joinProdutos}
        WHERE p.usuario_id = ?";

$params = [$usuario_id];

if ($filtro_busca !== '') {
    $sql .= " AND (pr.nome ILIKE ? OR CAST(p.id AS TEXT) LIKE ?)";
    $params[] = "%$filtro_busca%";
    $params[] = "%$filtro_busca%";
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

$sql .= " GROUP BY p.id, p.status, p.data_pedido, u.nome ORDER BY p.data_pedido DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$pedidos = $stmt->fetchAll(PDO::FETCH_ASSOC);

include __DIR__ . '/../includes/header.php';
?>

<div class="max-w-7xl mx-auto py-6 sm:py-10 px-4 sm:px-6 lg:px-8">
    
    <!-- Header Principal do Painel -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 sm:mb-8 gap-4">
        <div>
            <h1 class="text-2xl sm:text-3xl font-black text-gray-800 tracking-tight">Meu Painel de Pedidos</h1>
            <p class="text-xs sm:text-sm text-gray-500 mt-0.5">Olá, <strong class="text-gray-700"><?= htmlspecialchars($_SESSION['usuario_nome'] ?? 'Revendedor', ENT_QUOTES, 'UTF-8') ?></strong>! Acompanhe o andamento dos seus pedidos.</p>
        </div>
        <div class="flex flex-wrap items-center gap-2.5 w-full sm:w-auto">
            <div class="flex-1 sm:flex-none flex items-center justify-center bg-secundaria/30 px-3.5 py-2.5 rounded-xl border border-secundaria/60 text-xs font-bold text-primaria whitespace-nowrap">
                <i data-lucide="package" class="w-3.5 h-3.5 mr-1.5 shrink-0"></i>
                <span><?= count($pedidos) ?> <?= count($pedidos) === 1 ? 'pedido' : 'pedidos' ?></span>
            </div>
            <a href="novo_pedido.php" 
               class="flex-1 sm:flex-none inline-flex items-center justify-center bg-primaria text-white font-bold text-xs sm:text-sm px-4 py-2.5 rounded-xl hover:bg-primaria-hover transition-all shadow-sm active:scale-95 gap-1.5 whitespace-nowrap">
                <i data-lucide="plus-circle" class="w-4 h-4"></i>
                <span>Novo Pedido</span>
            </a>
        </div>
    </div>

    <!-- Feedbacks Visuais -->
    <?php if (isset($_GET['sucesso'])): ?>
        <?php 
            $msg = match($_GET['sucesso']) {
                'pedido_criado' => 'Pedido enviado com sucesso para a administração!',
                'pedido_editado' => 'Pedido atualizado com sucesso!',
                'cancelado' => 'Pedido cancelado e excluído do sistema.',
                default => 'Operação concluída com sucesso!'
            };
        ?>
        <div class="mb-6 bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 rounded-2xl flex items-center gap-2.5 text-xs sm:text-sm shadow-sm">
            <i data-lucide="check-circle-2" class="w-5 h-5 text-emerald-600 shrink-0"></i>
            <span><?= htmlspecialchars($msg, ENT_QUOTES, 'UTF-8') ?></span>
        </div>
    <?php elseif (isset($_GET['erro'])): ?>
        <div class="mb-6 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-2xl flex items-center gap-2.5 text-xs sm:text-sm shadow-sm">
            <i data-lucide="alert-triangle" class="w-5 h-5 text-red-500 shrink-0"></i>
            <span>Não foi possível processar a solicitação. Tente novamente ou contate o suporte.</span>
        </div>
    <?php endif; ?>

    <!-- Card de Filtros Avançados -->
    <div class="bg-white p-4 sm:p-6 rounded-3xl shadow-sm border border-gray-100 mb-8 transition-all">
        <form method="GET" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4 items-end">
            
            <div>
                <label class="block text-[11px] font-bold text-gray-400 uppercase tracking-wider mb-1.5">Buscar Produto / Nº</label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                        <i data-lucide="search" class="w-4 h-4"></i>
                    </div>
                    <input type="text" name="busca" value="<?= htmlspecialchars($filtro_busca, ENT_QUOTES, 'UTF-8') ?>" placeholder="Nome ou ID..."
                           class="w-full pl-9 pr-3 py-2.5 border border-gray-200 rounded-xl text-xs sm:text-sm text-gray-800 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-primaria/20 focus:border-primaria transition bg-gray-50/30">
                </div>
            </div>

            <div>
                <label class="block text-[11px] font-bold text-gray-400 uppercase tracking-wider mb-1.5">Status do Pedido</label>
                <select name="status" class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-xs sm:text-sm text-gray-800 focus:outline-none focus:ring-2 focus:ring-primaria/20 focus:border-primaria transition bg-gray-50/30 cursor-pointer">
                    <option value="">Todos os Status</option>
                    <option value="pendente" <?= $filtro_status === 'pendente' ? 'selected' : '' ?>>Pendentes (Em Aberto)</option>
                    <option value="concluido" <?= $filtro_status === 'concluido' ? 'selected' : '' ?>>Concluídos</option>
                </select>
            </div>

            <div>
                <label class="block text-[11px] font-bold text-gray-400 uppercase tracking-wider mb-1.5">Período de Solicitação</label>
                <div class="grid grid-cols-2 gap-2">
                    <input type="date" name="data_inicio" value="<?= htmlspecialchars($data_inicio, ENT_QUOTES, 'UTF-8') ?>" title="Data Inicial"
                           class="w-full px-2.5 py-2.5 border border-gray-200 rounded-xl text-xs text-gray-700 focus:outline-none focus:ring-2 focus:ring-primaria/20 focus:border-primaria transition bg-gray-50/30">
                    <input type="date" name="data_fim" value="<?= htmlspecialchars($data_fim, ENT_QUOTES, 'UTF-8') ?>" title="Data Final"
                           class="w-full px-2.5 py-2.5 border border-gray-200 rounded-xl text-xs text-gray-700 focus:outline-none focus:ring-2 focus:ring-primaria/20 focus:border-primaria transition bg-gray-50/30">
                </div>
            </div>

            <div class="flex gap-2">
                <button type="submit" 
                        class="flex-1 bg-primaria text-white font-bold h-[42px] rounded-xl hover:bg-primaria-hover transition shadow-sm active:scale-95 flex items-center justify-center gap-1.5 text-xs sm:text-sm cursor-pointer">
                    <i data-lucide="filter" class="w-4 h-4"></i>
                    <span>Filtrar</span>
                </button>
                <a href="painel.php" 
                   title="Limpar Filtros"
                   class="w-[42px] h-[42px] bg-gray-100 text-gray-500 hover:bg-gray-200 hover:text-gray-800 rounded-xl transition flex items-center justify-center shrink-0 border border-gray-200 active:scale-95">
                    <i data-lucide="rotate-ccw" class="w-4 h-4"></i>
                </a>
            </div>

        </form>
    </div>

    <!-- Lista de Pedidos em Cards Responsivos -->
    <?php if (empty($pedidos)): ?>
        <div class="bg-white rounded-3xl border border-gray-100 p-8 sm:p-14 text-center">
            <div class="w-16 h-16 bg-gray-50 rounded-2xl flex items-center justify-center mx-auto mb-4 text-gray-300">
                <i data-lucide="inbox" class="w-8 h-8"></i>
            </div>
            <h3 class="text-base font-bold text-gray-700 mb-1">Nenhum pedido encontrado</h3>
            <p class="text-xs text-gray-400 max-w-sm mx-auto mb-5">Não localizamos pedidos com os filtros selecionados ou você ainda não criou nenhum pedido.</p>
            <a href="novo_pedido.php" class="inline-flex items-center gap-2 bg-primaria text-white text-xs font-bold px-4 py-2.5 rounded-xl hover:bg-primaria-hover transition shadow-sm">
                <i data-lucide="plus-circle" class="w-4 h-4"></i>
                <span>Fazer Meu Primeiro Pedido</span>
            </a>
        </div>
    <?php else: ?>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-6">
            <?php foreach ($pedidos as $pedido): ?>
                <div class="bg-white rounded-2xl sm:rounded-3xl border border-gray-100 shadow-sm hover:shadow-md transition-all duration-200 flex flex-col justify-between overflow-hidden group">
                    
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

                        <!-- Data -->
                        <div class="mb-4">
                            <p class="text-xs text-gray-400 flex items-center">
                                <i data-lucide="calendar" class="w-3.5 h-3.5 mr-1 text-gray-300"></i>
                                <?= date('d/m/Y \à\s H:i', strtotime($pedido['data_pedido'])) ?>
                            </p>
                        </div>

                        <!-- Card de Valor Total -->
                        <div class="bg-gray-50/70 group-hover:bg-primaria/5 rounded-2xl p-3.5 border border-gray-100 transition-colors flex justify-between items-center">
                            <span class="text-[11px] font-bold text-gray-400 uppercase tracking-wider">Valor Total</span>
                            <span class="text-base sm:text-lg font-black text-primaria">
                                R$ <?= number_format($pedido['valor_total'], 2, ',', '.') ?>
                            </span>
                        </div>
                    </a>

                    <!-- Rodapé de Ações com Touch Targets Confortáveis -->
                    <div class="px-5 py-3.5 bg-gray-50/80 border-t border-gray-100 flex flex-wrap justify-between items-center gap-2 text-xs font-semibold">
                        <a href="detalhes_pedido.php?id=<?= (int)$pedido['id'] ?>" 
                           class="inline-flex items-center gap-1 text-primaria hover:underline font-bold py-1">
                            <i data-lucide="eye" class="w-3.5 h-3.5"></i>
                            <span>Ver Itens</span>
                        </a>

                        <div class="flex items-center gap-1 sm:gap-2">
                            <?php if ($pedido['status'] === 'pendente'): ?>
                                <a href="editar_pedido.php?id=<?= (int)$pedido['id'] ?>" 
                                   class="p-1.5 text-gray-500 hover:text-primaria hover:bg-white rounded-lg transition" 
                                   title="Editar Itens">
                                    <i data-lucide="edit-3" class="w-4 h-4"></i>
                                </a>
                                <a href="cancelar_pedido.php?id=<?= (int)$pedido['id'] ?>" 
                                   onclick="return confirm('Tem certeza que deseja cancelar este pedido?')"
                                   class="p-1.5 text-red-500 hover:text-red-700 hover:bg-white rounded-lg transition" 
                                   title="Cancelar Pedido">
                                    <i data-lucide="trash-2" class="w-4 h-4"></i>
                                </a>
                            <?php endif; ?>

                            <a href="../gerar_pdf.php?id=<?= (int)$pedido['id'] ?>" 
                               target="_blank" 
                               class="p-1.5 text-gray-500 hover:text-gray-800 hover:bg-white rounded-lg transition" 
                               title="Imprimir / Baixar Comprovante PDF">
                                <i data-lucide="file-text" class="w-4 h-4"></i>
                            </a>
                        </div>
                    </div>

                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
