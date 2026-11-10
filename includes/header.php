<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$paginaAtual = basename($_SERVER['PHP_SELF']);
$nivelUsuario = $_SESSION['usuario_nivel'] ?? '';
$isNaRaiz = !str_contains($_SERVER['PHP_SELF'], '/admin/') && !str_contains($_SERVER['PHP_SELF'], '/revendedor/');
$prefixoCaminho = $isNaRaiz ? '' : '../';
?>
<!DOCTYPE html>
<html lang="pt-br" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Sistema de Pedidos</title>
    
    <!-- Google Fonts: Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Alpine.js -->
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    
    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>
    
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'system-ui', '-apple-system', 'sans-serif'],
                    },
                    colors: {
                        primaria: {
                            DEFAULT: '#214e35',
                            hover: '#183b28',
                            light: '#2d6847',
                            subtle: '#eaf4ee'
                        },
                        secundaria: {
                            DEFAULT: '#badb81',
                            hover: '#a5ca6a',
                            light: '#e8f4d1',
                            subtle: '#f4fae9'
                        }
                    },
                    boxShadow: {
                        'soft': '0 2px 15px -3px rgba(0, 0, 0, 0.05), 0 4px 6px -2px rgba(0, 0, 0, 0.03)',
                        'card': '0 0 0 1px rgba(0, 0, 0, 0.04), 0 4px 20px -2px rgba(0, 0, 0, 0.06)',
                        'glow': '0 0 25px -5px rgba(33, 78, 53, 0.2)'
                    }
                }
            }
        }
    </script>
    <style>
        /* Suavização de rolagem e fontes */
        html { scroll-behavior: smooth; }
        body { -webkit-font-smoothing: antialiased; -moz-osx-font-smoothing: grayscale; }
        /* Remove o flash de Alpine antes de carregar */
        [x-cloak] { display: none !important; }
        /* Scrollbar discreto */
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: #f1f5f9; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 9999px; }
        ::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
    </style>
</head>
<body class="bg-slate-50 font-sans text-gray-800 antialiased min-h-full flex flex-col selection:bg-secundaria selection:text-primaria">

<?php if (isset($_SESSION['usuario_id'])): ?>
<nav x-data="{ open: false, notifOpen: false }" class="bg-white/95 backdrop-blur-md shadow-sm border-b border-gray-100 sticky top-0 z-50 transition-all duration-200">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16 items-center">
            
            <!-- Logo & Links Desktop -->
            <div class="flex items-center space-x-6 sm:space-x-8">
                <a href="<?= $nivelUsuario === 'admin' ? $prefixoCaminho . 'admin/dashboard.php' : $prefixoCaminho . 'revendedor/painel.php' ?>" 
                   class="flex items-center gap-2 group transition-transform active:scale-95">
                    <img src="<?= $prefixoCaminho ?>uploads/logo.png" 
                         alt="Logo" 
                         class="h-9 sm:h-10 w-auto object-contain transition-transform group-hover:scale-105" 
                         onerror="this.onerror=null; this.src='https://placehold.co/150x50?text=Sua+Logo';">
                </a>
                
                <!-- Links Desktop -->
                <div class="hidden md:flex items-center space-x-1.5">
                    <?php if ($nivelUsuario === 'admin'): ?>
                        <?php 
                            $ativoDashboard = in_array($paginaAtual, ['dashboard.php', 'detalhes_pedido.php']);
                            $ativoProdutos = in_array($paginaAtual, ['produtos.php', 'cadastrar_produto.php', 'editar_produto.php']);
                            $ativoRelatorio = ($paginaAtual === 'relatorio.php');
                        ?>
                        <a href="<?= $prefixoCaminho ?>admin/dashboard.php" 
                           class="px-3.5 py-2 rounded-xl text-sm font-semibold transition-all flex items-center gap-2 <?= $ativoDashboard ? 'bg-primaria text-white shadow-sm' : 'text-gray-600 hover:text-primaria hover:bg-primaria/5' ?>">
                            <i data-lucide="layout-dashboard" class="w-4 h-4"></i> Pedidos
                        </a>
                        <a href="<?= $prefixoCaminho ?>admin/produtos.php" 
                           class="px-3.5 py-2 rounded-xl text-sm font-semibold transition-all flex items-center gap-2 <?= $ativoProdutos ? 'bg-primaria text-white shadow-sm' : 'text-gray-600 hover:text-primaria hover:bg-primaria/5' ?>">
                            <i data-lucide="package" class="w-4 h-4"></i> Produtos
                        </a>
                        <a href="<?= $prefixoCaminho ?>admin/relatorio.php" 
                           class="px-3.5 py-2 rounded-xl text-sm font-semibold transition-all flex items-center gap-2 <?= $ativoRelatorio ? 'bg-primaria text-white shadow-sm' : 'text-gray-600 hover:text-primaria hover:bg-primaria/5' ?>">
                            <i data-lucide="bar-chart-3" class="w-4 h-4"></i> Relatórios
                        </a>
                    <?php else: ?>
                        <?php 
                            $ativoPainel = in_array($paginaAtual, ['painel.php', 'detalhes_pedido.php', 'editar_pedido.php']);
                            $ativoNovoPedido = ($paginaAtual === 'novo_pedido.php');
                        ?>
                        <a href="<?= $prefixoCaminho ?>revendedor/painel.php" 
                           class="px-3.5 py-2 rounded-xl text-sm font-semibold transition-all flex items-center gap-2 <?= $ativoPainel ? 'bg-primaria text-white shadow-sm' : 'text-gray-600 hover:text-primaria hover:bg-primaria/5' ?>">
                            <i data-lucide="shopping-bag" class="w-4 h-4"></i> Meus Pedidos
                        </a>
                        <a href="<?= $prefixoCaminho ?>revendedor/novo_pedido.php" 
                           class="px-3.5 py-2 rounded-xl text-sm font-semibold transition-all flex items-center gap-2 <?= $ativoNovoPedido ? 'bg-primaria text-white shadow-sm' : 'text-gray-600 hover:text-primaria hover:bg-primaria/5' ?>">
                            <i data-lucide="plus-circle" class="w-4 h-4"></i> Novo Pedido
                        </a>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Ações Desktop: Notificações & Perfil -->
            <div class="hidden md:flex items-center space-x-3">
                
                <!-- Sino de Notificações -->
                <div class="relative" @click.away="notifOpen = false">
                    <button @click="notifOpen = !notifOpen" 
                            id="btnNotificacoes" 
                            class="relative p-2.5 text-gray-500 hover:text-primaria hover:bg-gray-100/80 rounded-xl transition focus:outline-none focus:ring-2 focus:ring-primaria/20 active:scale-95"
                            title="Notificações">
                        <i data-lucide="bell" class="w-5 h-5"></i>
                        <span id="badgeNotificacoes" class="hidden absolute top-1.5 right-1.5 bg-red-500 text-white text-[10px] font-black w-4 h-4 rounded-full flex items-center justify-center ring-2 ring-white animate-pulse">
                            0
                        </span>
                    </button>

                    <!-- Dropdown Notificações Desktop -->
                    <div x-show="notifOpen" 
                         x-transition:enter="transition ease-out duration-150"
                         x-transition:enter-start="opacity-0 scale-95 translate-y-2"
                         x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                         x-transition:leave="transition ease-in duration-100"
                         x-transition:leave-start="opacity-100 scale-100"
                         x-transition:leave-end="opacity-0 scale-95"
                         class="absolute right-0 mt-2 w-80 sm:w-96 bg-white rounded-2xl shadow-xl border border-gray-100 z-50 overflow-hidden" 
                         x-cloak>
                        <div class="p-3.5 border-b border-gray-100 flex justify-between items-center bg-gray-50/70">
                            <div class="flex items-center gap-2">
                                <i data-lucide="bell" class="w-4 h-4 text-primaria"></i>
                                <h3 class="font-bold text-gray-800 text-sm">Notificações</h3>
                            </div>
                            <button id="btnMarcarLidas" class="text-xs text-primaria font-semibold hover:underline cursor-pointer">Marcar como lidas</button>
                        </div>

                        <div id="listaNotificacoes" class="max-h-80 overflow-y-auto divide-y divide-gray-50">
                            <div class="p-5 text-center text-xs text-gray-400">Carregando notificações...</div>
                        </div>
                    </div>
                </div>

                <!-- Divisor Vertical -->
                <div class="h-6 w-px bg-gray-200"></div>

                <!-- Chip de Identificação do Usuário -->
                <?php if (isset($_SESSION['usuario_nome'])): ?>
                    <div class="flex items-center gap-2 px-3 py-1.5 bg-gray-50 rounded-xl border border-gray-100">
                        <div class="w-6 h-6 rounded-lg bg-primaria/10 text-primaria flex items-center justify-center font-bold text-xs uppercase">
                            <?= mb_substr($_SESSION['usuario_nome'], 0, 1) ?>
                        </div>
                        <span class="text-xs font-bold text-gray-700 max-w-[120px] truncate" title="<?= htmlspecialchars($_SESSION['usuario_nome']) ?>">
                            <?= htmlspecialchars($_SESSION['usuario_nome']) ?>
                        </span>
                        <span class="text-[10px] font-bold px-1.5 py-0.5 rounded-md uppercase tracking-wider <?= $nivelUsuario === 'admin' ? 'bg-amber-100 text-amber-800' : 'bg-emerald-100 text-emerald-800' ?>">
                            <?= $nivelUsuario === 'admin' ? 'Admin' : 'Revenda' ?>
                        </span>
                    </div>
                <?php endif; ?>

                <!-- Botão Sair -->
                <a href="<?= $prefixoCaminho ?>logout.php" 
                   class="text-gray-500 hover:text-red-600 text-sm font-semibold flex items-center transition p-2 rounded-xl hover:bg-red-50 focus:outline-none"
                   title="Sair do Sistema">
                    <i data-lucide="log-out" class="w-4 h-4"></i>
                </a>
            </div>

            <!-- Controles Mobile (Sino + Menu Hambúrguer) -->
            <div class="flex items-center space-x-1 md:hidden">
                
                <!-- Sino Mobile -->
                <div class="relative" @click.away="notifOpen = false">
                    <button @click="notifOpen = !notifOpen" 
                            class="relative p-2.5 text-gray-600 hover:text-primaria rounded-xl focus:outline-none active:bg-gray-100"
                            aria-label="Notificações">
                        <i data-lucide="bell" class="w-5 h-5"></i>
                        <span id="badgeNotificacoesMobile" class="hidden absolute top-1.5 right-1.5 bg-red-500 text-white text-[10px] font-bold w-4 h-4 rounded-full flex items-center justify-center ring-2 ring-white animate-pulse">
                            0
                        </span>
                    </button>

                    <!-- Dropdown Mobile -->
                    <div x-show="notifOpen" 
                         x-transition 
                         class="absolute right-0 mt-2 w-[calc(100vw-2rem)] max-w-sm bg-white rounded-2xl shadow-2xl border border-gray-100 z-50 overflow-hidden" 
                         x-cloak>
                        <div class="p-3 border-b border-gray-100 flex justify-between items-center bg-gray-50">
                            <h3 class="font-bold text-gray-800 text-xs flex items-center gap-1.5">
                                <i data-lucide="bell" class="w-3.5 h-3.5 text-primaria"></i> Notificações
                            </h3>
                            <button id="btnMarcarLidasMobile" class="text-[11px] text-primaria font-bold hover:underline cursor-pointer">Marcar lidas</button>
                        </div>
                        <div id="listaNotificacoesMobile" class="max-h-72 overflow-y-auto divide-y divide-gray-50">
                            <div class="p-4 text-center text-xs text-gray-400">Carregando...</div>
                        </div>
                    </div>
                </div>

                <!-- Botão Menu Mobile -->
                <button @click="open = !open" 
                        class="text-gray-600 hover:text-primaria p-2.5 rounded-xl focus:outline-none active:bg-gray-100"
                        aria-label="Abrir Menu">
                    <i data-lucide="menu" x-show="!open" class="w-6 h-6"></i>
                    <i data-lucide="x" x-show="open" class="w-6 h-6" x-cloak></i>
                </button>
            </div>
        </div>
    </div>

    <!-- Menu Gaveta Mobile com Transição Suave -->
    <div x-show="open" 
         x-transition:enter="transition ease-out duration-150"
         x-transition:enter-start="opacity-0 -translate-y-2"
         x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-100"
         x-transition:leave-start="opacity-100 translate-y-0"
         x-transition:leave-end="opacity-0 -translate-y-2"
         class="md:hidden border-t border-gray-100 bg-white px-4 pt-3 pb-5 space-y-1.5 shadow-lg" 
         x-cloak>
        
        <?php if ($nivelUsuario === 'admin'): ?>
            <a href="<?= $prefixoCaminho ?>admin/dashboard.php" 
               class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-semibold <?= $ativoDashboard ? 'bg-primaria text-white shadow-sm' : 'text-gray-700 hover:bg-gray-50' ?>">
                <i data-lucide="layout-dashboard" class="w-4 h-4"></i> Painel de Pedidos
            </a>
            <a href="<?= $prefixoCaminho ?>admin/produtos.php" 
               class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-semibold <?= $ativoProdutos ? 'bg-primaria text-white shadow-sm' : 'text-gray-700 hover:bg-gray-50' ?>">
                <i data-lucide="package" class="w-4 h-4"></i> Catálogo de Produtos
            </a>
            <a href="<?= $prefixoCaminho ?>admin/relatorio.php" 
               class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-semibold <?= $ativoRelatorio ? 'bg-primaria text-white shadow-sm' : 'text-gray-700 hover:bg-gray-50' ?>">
                <i data-lucide="bar-chart-3" class="w-4 h-4"></i> Relatório de Desempenho
            </a>
        <?php else: ?>
            <a href="<?= $prefixoCaminho ?>revendedor/painel.php" 
               class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-semibold <?= $ativoPainel ? 'bg-primaria text-white shadow-sm' : 'text-gray-700 hover:bg-gray-50' ?>">
                <i data-lucide="shopping-bag" class="w-4 h-4"></i> Meus Pedidos
            </a>
            <a href="<?= $prefixoCaminho ?>revendedor/novo_pedido.php" 
               class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-semibold <?= $ativoNovoPedido ? 'bg-primaria text-white shadow-sm' : 'text-gray-700 hover:bg-gray-50' ?>">
                <i data-lucide="plus-circle" class="w-4 h-4"></i> Novo Pedido
            </a>
        <?php endif; ?>
        
        <!-- Rodapé do Menu Mobile: Identificação e Logout -->
        <div class="pt-3 mt-2 border-t border-gray-100 flex justify-between items-center px-1">
            <?php if (isset($_SESSION['usuario_nome'])): ?>
                <div class="flex items-center gap-2">
                    <div class="w-7 h-7 rounded-lg bg-primaria/10 text-primaria flex items-center justify-center font-bold text-xs uppercase">
                        <?= mb_substr($_SESSION['usuario_nome'], 0, 1) ?>
                    </div>
                    <div>
                        <p class="text-xs font-bold text-gray-800 leading-none"><?= htmlspecialchars($_SESSION['usuario_nome']) ?></p>
                        <span class="text-[10px] text-gray-400 capitalize"><?= $nivelUsuario ?></span>
                    </div>
                </div>
            <?php endif; ?>
            <a href="<?= $prefixoCaminho ?>logout.php" class="text-red-600 font-semibold text-xs flex items-center gap-1 py-1.5 px-3 rounded-lg hover:bg-red-50 transition">
                <i data-lucide="log-out" class="w-3.5 h-3.5"></i> Sair
            </a>
        </div>
    </div>
</nav>
<?php endif; ?>

<script>
document.addEventListener("DOMContentLoaded", () => {
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }

    const badgeDesktop = document.getElementById('badgeNotificacoes');
    const badgeMobile = document.getElementById('badgeNotificacoesMobile');
    const listaDesktop = document.getElementById('listaNotificacoes');
    const listaMobile = document.getElementById('listaNotificacoesMobile');
    const btnMarcarDesktop = document.getElementById('btnMarcarLidas');
    const btnMarcarMobile = document.getElementById('btnMarcarLidasMobile');

    function carregarNotificacoes() {
        fetch('<?= $prefixoCaminho ?>api/notificacoes.php?acao=listar')
            .then(res => res.json())
            .then(data => {
                if (!data.sucesso) return;

                const textoNaoLidas = data.nao_lidas > 99 ? '99+' : data.nao_lidas;
                
                [badgeDesktop, badgeMobile].forEach(badge => {
                    if (badge) {
                        if (data.nao_lidas > 0) {
                            badge.textContent = textoNaoLidas;
                            badge.classList.remove('hidden');
                        } else {
                            badge.classList.add('hidden');
                        }
                    }
                });

                let html = '';
                if (!data.notificacoes || data.notificacoes.length === 0) {
                    html = '<div class="p-6 text-center text-xs text-gray-400 italic flex flex-col items-center gap-2"><i data-lucide="bell-off" class="w-6 h-6 text-gray-300"></i> Nenhuma notificação encontrada.</div>';
                } else {
                    html = data.notificacoes.map(item => `
                        <a href="${item.link || '#'}" class="block p-3.5 hover:bg-gray-50 transition-all ${item.lida ? 'opacity-65' : 'bg-emerald-50/50 border-l-2 border-primaria'}">
                            <div class="flex justify-between items-start mb-1 gap-2">
                                <h4 class="text-xs font-bold text-gray-800 leading-tight">${item.titulo}</h4>
                                <span class="text-[10px] text-gray-400 whitespace-nowrap shrink-0">${new Date(item.criado_em).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})}</span>
                            </div>
                            <p class="text-xs text-gray-600 leading-snug line-clamp-2">${item.mensagem}</p>
                        </a>
                    `).join('');
                }

                if (listaDesktop) listaDesktop.innerHTML = html;
                if (listaMobile) listaMobile.innerHTML = html;
                if (typeof lucide !== 'undefined') lucide.createIcons();
            })
            .catch(() => {});
    }

    function marcarLidas() {
        fetch('<?= $prefixoCaminho ?>api/notificacoes.php?acao=marcar_lidas', { method: 'POST' })
            .then(res => res.json())
            .then(data => {
                if (data.sucesso) carregarNotificacoes();
            });
    }

    if (btnMarcarDesktop) btnMarcarDesktop.addEventListener('click', marcarLidas);
    if (btnMarcarMobile) btnMarcarMobile.addEventListener('click', marcarLidas);

    carregarNotificacoes();
    setInterval(carregarNotificacoes, 20000);
});
</script>

<!-- Container principal flexível com min-h responsivo -->
<main class="flex-1 w-full">
