<?php
if (session_status() === PHP_SESSION_NONE) { 
    session_start(); 
}

// Se o usuário já estiver logado, redireciona para o painel correto
if (isset($_SESSION['usuario_id']) && isset($_SESSION['usuario_nivel'])) {
    if ($_SESSION['usuario_nivel'] === 'admin') {
        header("Location: admin/dashboard.php");
        exit;
    } else {
        header("Location: revendedor/painel.php");
        exit;
    }
}

include 'includes/header.php';
?>

<div class="min-h-[calc(100vh-8rem)] flex items-center justify-center py-10 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full bg-white p-7 sm:p-9 rounded-3xl shadow-xl shadow-gray-200/50 border border-gray-100 transition-all">
        
        <!-- Logotipo e Cabeçalho -->
        <div class="text-center mb-8">
            <div class="inline-flex p-3 rounded-2xl bg-gray-50/80 mb-3 border border-gray-100/80">
                <img src="uploads/logo.png" 
                     alt="Logo" 
                     class="h-14 sm:h-16 w-auto object-contain mx-auto transition-transform hover:scale-105" 
                     onerror="this.onerror=null; this.src='https://placehold.co/180x60?text=Sua+Logo';">
            </div>
            <h1 class="text-2xl font-black text-gray-800 tracking-tight">Bem-vindo(a) de volta</h1>
            <p class="text-xs font-semibold text-gray-400 mt-1 uppercase tracking-widest">Portal de Gestão de Pedidos</p>
        </div>

        <!-- Mensagens de Alerta/Erro -->
        <?php if (isset($_GET['erro'])): ?>
            <div class="mb-5 bg-red-50 border border-red-200/80 text-red-700 px-4 py-3 rounded-2xl text-xs font-semibold flex items-center gap-2.5 shadow-sm" role="alert">
                <i data-lucide="alert-circle" class="w-4 h-4 shrink-0 text-red-500"></i>
                <span>Usuário ou senha incorretos. Por favor, verifique suas credenciais.</span>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['msg']) && $_GET['msg'] === 'deslogado'): ?>
            <div class="mb-5 bg-emerald-50 border border-emerald-200/80 text-emerald-800 px-4 py-3 rounded-2xl text-xs font-semibold flex items-center gap-2.5 shadow-sm" role="alert">
                <i data-lucide="check-circle-2" class="w-4 h-4 shrink-0 text-emerald-600"></i>
                <span>Sua sessão foi encerrada com segurança.</span>
            </div>
        <?php endif; ?>

        <!-- Formulário de Login -->
        <form class="space-y-4" action="logar.php" method="POST">
            <div>
                <label for="usuario" class="block text-xs font-bold uppercase tracking-wider text-gray-500 mb-1.5">Usuário</label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-gray-400">
                        <i data-lucide="user" class="w-4 h-4"></i>
                    </div>
                    <input id="usuario" name="usuario" type="text" required autofocus
                           class="w-full pl-10 pr-4 py-3 border border-gray-200 rounded-xl text-sm text-gray-800 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-primaria/20 focus:border-primaria transition-all bg-gray-50/40" 
                           placeholder="Seu nome de usuário">
                </div>
            </div>

            <div>
                <label for="senha" class="block text-xs font-bold uppercase tracking-wider text-gray-500 mb-1.5">Senha</label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-gray-400">
                        <i data-lucide="lock" class="w-4 h-4"></i>
                    </div>
                    <input id="senha" name="senha" type="password" required 
                           class="w-full pl-10 pr-4 py-3 border border-gray-200 rounded-xl text-sm text-gray-800 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-primaria/20 focus:border-primaria transition-all bg-gray-50/40" 
                           placeholder="Sua senha secreta">
                </div>
            </div>

            <div class="pt-2">
                <button type="submit" 
                        class="w-full py-3.5 px-4 text-sm font-bold rounded-xl text-white bg-primaria hover:bg-primaria-hover focus:outline-none focus:ring-4 focus:ring-primaria/20 transition-all shadow-md active:scale-[0.99] flex items-center justify-center gap-2 cursor-pointer">
                    <span>Acessar Painel</span>
                    <i data-lucide="arrow-right" class="w-4 h-4"></i>
                </button>
            </div>
        </form>

    </div>
</div>

<?php include 'includes/footer.php'; ?>
