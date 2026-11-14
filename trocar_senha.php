<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';

// Se o usuário não tem primeiro acesso pendente, redireciona diretamente ao painel correspondente
if (empty($_SESSION['primeiro_acesso'])) {
    if (isset($_SESSION['usuario_nivel']) && $_SESSION['usuario_nivel'] === 'admin') {
        header("Location: admin/dashboard.php");
    } else {
        header("Location: revendedor/painel.php");
    }
    exit;
}

$erros = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken = $_POST['csrf_token'] ?? null;
    if (!validarCsrfToken($csrfToken)) {
        $erros[] = "Sessão expirada ou solicitação inválida. Por favor, atualize a página e tente novamente.";
    }

    $novaSenha = (string)($_POST['nova_senha'] ?? '');
    $confirmarSenha = (string)($_POST['confirmar_senha'] ?? '');

    // Validação 1: Mínimo 8 caracteres
    if (mb_strlen($novaSenha) < 8) {
        $erros[] = "A nova senha deve possuir no mínimo 8 caracteres.";
    }

    // Validação 2: Pelo menos uma letra maiúscula
    if (!preg_match('/[A-Z]/', $novaSenha)) {
        $erros[] = "A nova senha deve conter pelo menos uma letra maiúscula (A-Z).";
    }

    // Validação 3: Pelo menos um número
    if (!preg_match('/[0-9]/', $novaSenha)) {
        $erros[] = "A nova senha deve conter pelo menos um número (0-9).";
    }

    // Validação 4: Pelo menos um símbolo ou caractere especial
    if (!preg_match('/[\W_]/', $novaSenha)) {
        $erros[] = "A nova senha deve conter pelo menos um símbolo especial (ex: @, #, $, %, &, *, !, ?).";
    }

    // Validação 5: Confirmação de senha
    if ($novaSenha !== $confirmarSenha) {
        $erros[] = "A confirmação de senha não coincide com a nova senha digitada.";
    }

    // Se passou em todas as regras de segurança
    if (empty($erros)) {
        try {
            // Criptografa a senha com algoritmo seguro nativo do PHP (BCRYPT / Argon2 conforme padrão)
            $hashCriptografado = password_hash($novaSenha, PASSWORD_DEFAULT);

            $stmt = $pdo->prepare("UPDATE usuarios SET senha = ?, primeiro_acesso = FALSE WHERE id = ?");
            $stmt->execute([$hashCriptografado, (int)$_SESSION['usuario_id']]);

            // Atualiza o estado da sessão
            $_SESSION['primeiro_acesso'] = false;
            unset($_SESSION['primeiro_acesso']);

            // Redireciona com feedback de sucesso
            if (isset($_SESSION['usuario_nivel']) && $_SESSION['usuario_nivel'] === 'admin') {
                header("Location: admin/dashboard.php?sucesso=senha_alterada");
            } else {
                header("Location: revendedor/painel.php?sucesso=senha_alterada");
            }
            exit;
        } catch (\Throwable $e) {
            error_log("[Trocar Senha] Erro ao atualizar senha no banco: " . $e->getMessage());
            $erros[] = "Ocorreu uma falha interna ao salvar sua nova senha. Por favor, tente novamente.";
        }
    }
}

include 'includes/header.php';
?>

<div class="min-h-[calc(100vh-10rem)] flex items-center justify-center py-8 px-4 sm:px-6 lg:px-8">
    <div x-data="{
            novaSenha: '',
            confirmarSenha: '',
            mostrarSenha: false,
            mostrarConfirmar: false,
            get temMinimo() { return this.novaSenha.length >= 8; },
            get temMaiuscula() { return /[A-Z]/.test(this.novaSenha); },
            get temNumero() { return /[0-9]/.test(this.novaSenha); },
            get temSimbolo() { return /[^a-zA-Z0-9]/.test(this.novaSenha); },
            get senhasIguais() { return this.novaSenha.length > 0 && this.novaSenha === this.confirmarSenha; },
            get pontuacaoForca() {
                let pontos = 0;
                if (this.temMinimo) pontos += 25;
                if (this.temMaiuscula) pontos += 25;
                if (this.temNumero) pontos += 25;
                if (this.temSimbolo) pontos += 25;
                return pontos;
            },
            get textoForca() {
                if (this.novaSenha.length === 0) return 'Digite uma senha';
                if (this.pontuacaoForca <= 50) return 'Fraca';
                if (this.pontuacaoForca <= 75) return 'Média';
                return 'Forte e Segura';
            },
            get corForca() {
                if (this.novaSenha.length === 0) return 'bg-gray-200';
                if (this.pontuacaoForca <= 50) return 'bg-red-500';
                if (this.pontuacaoForca <= 75) return 'bg-amber-500';
                return 'bg-emerald-500';
            },
            get formularioValido() {
                return this.temMinimo && this.temMaiuscula && this.temNumero && this.temSimbolo && this.senhasIguais;
            }
         }" 
         class="max-w-md w-full bg-white p-6 sm:p-9 rounded-3xl shadow-xl shadow-gray-200/50 border border-gray-100 transition-all">
        
        <!-- Cabeçalho do Card -->
        <div class="text-center mb-6">
            <div class="inline-flex p-3.5 rounded-2xl bg-amber-50 text-amber-600 mb-3 border border-amber-100 shadow-sm">
                <i data-lucide="shield-check" class="w-8 h-8"></i>
            </div>
            <h1 class="text-2xl font-black text-gray-800 tracking-tight">Primeiro Acesso</h1>
            <p class="text-xs text-gray-500 mt-1.5 leading-relaxed">
                Olá, <strong class="text-gray-800 font-bold"><?= htmlspecialchars($_SESSION['usuario_nome'] ?? 'Usuário') ?></strong>! 
                Por questões de segurança, cadastre uma nova senha pessoal para liberar seu acesso completo.
            </p>
        </div>

        <!-- Alertas de Erro (se houver) -->
        <?php if (!empty($erros)): ?>
            <div class="mb-5 bg-red-50 border border-red-200/80 text-red-700 px-4 py-3 rounded-2xl text-xs font-semibold shadow-sm space-y-1" role="alert">
                <div class="flex items-center gap-2 text-red-800 font-bold mb-1">
                    <i data-lucide="alert-triangle" class="w-4 h-4 shrink-0 text-red-500"></i>
                    <span>Atenção aos seguintes pontos:</span>
                </div>
                <ul class="list-disc list-inside space-y-0.5 text-[11px] text-red-600 pl-1">
                    <?php foreach ($erros as $erro): ?>
                        <li><?= htmlspecialchars($erro) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <!-- Formulário de Troca de Senha -->
        <form class="space-y-4" action="trocar_senha.php" method="POST">
            <?= campoCsrf() ?>

            <!-- Campo Nova Senha -->
            <div>
                <label for="nova_senha" class="block text-xs font-bold uppercase tracking-wider text-gray-600 mb-1.5">
                    Nova Senha
                </label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-gray-400">
                        <i data-lucide="lock" class="w-4 h-4"></i>
                    </div>
                    <input id="nova_senha" 
                           name="nova_senha" 
                           :type="mostrarSenha ? 'text' : 'password'" 
                           x-model="novaSenha"
                           required 
                           autofocus
                           autocomplete="new-password"
                           class="w-full pl-10 pr-10 py-3 border border-gray-200 rounded-xl text-sm text-gray-800 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-primaria/20 focus:border-primaria transition-all bg-gray-50/40" 
                           placeholder="Digite sua nova senha">
                    <button type="button" 
                            @click="mostrarSenha = !mostrarSenha"
                            class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-gray-400 hover:text-gray-600 focus:outline-none"
                            title="Alternar visibilidade da senha">
                        <i :data-lucide="mostrarSenha ? 'eye-off' : 'eye'" class="w-4 h-4"></i>
                    </button>
                </div>
            </div>

            <!-- Medidor de Força da Senha -->
            <div class="space-y-1.5 pt-0.5" x-show="novaSenha.length > 0" x-cloak>
                <div class="flex justify-between items-center text-[11px] font-semibold text-gray-500">
                    <span>Força da Senha:</span>
                    <span :class="{
                        'text-red-500': pontuacaoForca <= 50,
                        'text-amber-500': pontuacaoForca > 50 && pontuacaoForca <= 75,
                        'text-emerald-600': pontuacaoForca > 75
                    }" x-text="textoForca"></span>
                </div>
                <div class="w-full bg-gray-100 rounded-full h-1.5 overflow-hidden">
                    <div class="h-1.5 rounded-full transition-all duration-300" 
                         :class="corForca" 
                         :style="'width: ' + pontuacaoForca + '%'"></div>
                </div>
            </div>

            <!-- Campo Confirmar Senha -->
            <div>
                <label for="confirmar_senha" class="block text-xs font-bold uppercase tracking-wider text-gray-600 mb-1.5">
                    Confirmar Nova Senha
                </label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-gray-400">
                        <i data-lucide="check-check" class="w-4 h-4"></i>
                    </div>
                    <input id="confirmar_senha" 
                           name="confirmar_senha" 
                           :type="mostrarConfirmar ? 'text' : 'password'" 
                           x-model="confirmarSenha"
                           required 
                           autocomplete="new-password"
                           class="w-full pl-10 pr-10 py-3 border border-gray-200 rounded-xl text-sm text-gray-800 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-primaria/20 focus:border-primaria transition-all bg-gray-50/40" 
                           placeholder="Confirme exatamente a mesma senha">
                    <button type="button" 
                            @click="mostrarConfirmar = !mostrarConfirmar"
                            class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-gray-400 hover:text-gray-600 focus:outline-none"
                            title="Alternar visibilidade da senha">
                        <i :data-lucide="mostrarConfirmar ? 'eye-off' : 'eye'" class="w-4 h-4"></i>
                    </button>
                </div>
            </div>

            <!-- Checklist Interativo de Requisitos -->
            <div class="bg-gray-50/80 border border-gray-200/60 rounded-2xl p-3.5 space-y-2 mt-3">
                <p class="text-[11px] font-bold uppercase tracking-wider text-gray-500 mb-1 flex items-center gap-1.5">
                    <i data-lucide="shield" class="w-3.5 h-3.5 text-primaria"></i>
                    Requisitos Obrigatórios:
                </p>
                <ul class="space-y-1.5 text-xs text-gray-600">
                    <li class="flex items-center gap-2 transition-colors" :class="temMinimo ? 'text-emerald-600 font-semibold' : 'text-gray-500'">
                        <span class="w-4 h-4 rounded-full flex items-center justify-center shrink-0" 
                              :class="temMinimo ? 'bg-emerald-100 text-emerald-600' : 'bg-gray-200 text-gray-400'">
                            <i data-lucide="check" class="w-3 h-3" x-show="temMinimo"></i>
                        </span>
                        <span>Mínimo de 8 caracteres</span>
                    </li>
                    <li class="flex items-center gap-2 transition-colors" :class="temMaiuscula ? 'text-emerald-600 font-semibold' : 'text-gray-500'">
                        <span class="w-4 h-4 rounded-full flex items-center justify-center shrink-0" 
                              :class="temMaiuscula ? 'bg-emerald-100 text-emerald-600' : 'bg-gray-200 text-gray-400'">
                            <i data-lucide="check" class="w-3 h-3" x-show="temMaiuscula"></i>
                        </span>
                        <span>Pelo menos uma letra maiúscula (A-Z)</span>
                    </li>
                    <li class="flex items-center gap-2 transition-colors" :class="temNumero ? 'text-emerald-600 font-semibold' : 'text-gray-500'">
                        <span class="w-4 h-4 rounded-full flex items-center justify-center shrink-0" 
                              :class="temNumero ? 'bg-emerald-100 text-emerald-600' : 'bg-gray-200 text-gray-400'">
                            <i data-lucide="check" class="w-3 h-3" x-show="temNumero"></i>
                        </span>
                        <span>Pelo menos um número (0-9)</span>
                    </li>
                    <li class="flex items-center gap-2 transition-colors" :class="temSimbolo ? 'text-emerald-600 font-semibold' : 'text-gray-500'">
                        <span class="w-4 h-4 rounded-full flex items-center justify-center shrink-0" 
                              :class="temSimbolo ? 'bg-emerald-100 text-emerald-600' : 'bg-gray-200 text-gray-400'">
                            <i data-lucide="check" class="w-3 h-3" x-show="temSimbolo"></i>
                        </span>
                        <span>Pelo menos um símbolo especial (!, @, #, $, %, etc.)</span>
                    </li>
                    <li class="flex items-center gap-2 transition-colors" :class="senhasIguais ? 'text-emerald-600 font-semibold' : 'text-gray-500'">
                        <span class="w-4 h-4 rounded-full flex items-center justify-center shrink-0" 
                              :class="senhasIguais ? 'bg-emerald-100 text-emerald-600' : 'bg-gray-200 text-gray-400'">
                            <i data-lucide="check" class="w-3 h-3" x-show="senhasIguais"></i>
                        </span>
                        <span>As duas senhas são idênticas</span>
                    </li>
                </ul>
            </div>

            <!-- Botão de Ação -->
            <div class="pt-2">
                <button type="submit" 
                        :disabled="!formularioValido"
                        :class="formularioValido ? 'bg-primaria hover:bg-primaria-hover cursor-pointer shadow-md active:scale-[0.99]' : 'bg-gray-300 cursor-not-allowed opacity-75'"
                        class="w-full py-3.5 px-4 text-sm font-bold rounded-xl text-white focus:outline-none focus:ring-4 focus:ring-primaria/20 transition-all flex items-center justify-center gap-2">
                    <i data-lucide="save" class="w-4 h-4"></i>
                    <span>Salvar Nova Senha e Acessar</span>
                </button>
            </div>
        </form>

        <!-- Botão Sair/Cancelar -->
        <div class="text-center mt-5 pt-4 border-t border-gray-100">
            <a href="logout.php" class="text-xs font-semibold text-gray-500 hover:text-red-600 transition flex items-center justify-center gap-1.5">
                <i data-lucide="log-out" class="w-3.5 h-3.5"></i>
                <span>Cancelar e Sair da Conta</span>
            </a>
        </div>

    </div>
</div>

<?php include 'includes/footer.php'; ?>

