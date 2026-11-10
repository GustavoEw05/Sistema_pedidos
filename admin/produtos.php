<?php 
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/config.php';

verificarNivel('admin');

$erro = null;

// Processar exclusão segura via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao']) && $_POST['acao'] === 'excluir') {
    $token = $_POST['csrf_token'] ?? null;
    $id_excluir = (int)($_POST['id'] ?? 0);

    if (!validarCsrfToken($token)) {
        $erro = "Sessão expirada ou token de segurança inválido.";
    } elseif ($id_excluir <= 0) {
        $erro = "ID de produto inválido.";
    } else {
        try {
            $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM itens_pedido WHERE produto_id = ?");
            $stmtCheck->execute([$id_excluir]);
            $temPedidos = (int)$stmtCheck->fetchColumn();

            if ($temPedidos > 0) {
                $erro = "Não é possível excluir este produto, pois ele já faz parte de {$temPedidos} pedido(s) registrado(s).";
            } else {
                $stmt_img = $pdo->prepare("SELECT imagem FROM produtos WHERE id = ?");
                $stmt_img->execute([$id_excluir]);
                $produto_img = $stmt_img->fetchColumn();

                if ($produto_img && $produto_img !== 'default.png' && $produto_img !== 'default.jpg') {
                    $caminhoImg = __DIR__ . '/../uploads/' . basename($produto_img);
                    if (file_exists($caminhoImg)) {
                        @unlink($caminhoImg);
                    }
                }

                $stmt = $pdo->prepare("DELETE FROM produtos WHERE id = ?");
                $stmt->execute([$id_excluir]);

                header("Location: produtos.php?sucesso=excluido");
                exit;
            }
        } catch (\Throwable $e) {
            error_log("[Excluir Produto] Erro: " . $e->getMessage());
            $erro = "Erro interno ao tentar excluir produto.";
        }
    }
}

// Buscar produtos ordenados alfabeticamente
$todos_produtos = $pdo->query("SELECT id, nome, preco, imagem, linha FROM produtos ORDER BY nome ASC")->fetchAll(PDO::FETCH_ASSOC);

// Agrupar produtos por Linha
$produtos_por_linha = [];
foreach ($todos_produtos as $prod) {
    $linha = !empty($prod['linha']) ? $prod['linha'] : (($prod['id'] <= 19) ? 'Linha Humana' : 'Outras Linhas');
    $produtos_por_linha[$linha][] = $prod;
}

include __DIR__ . '/../includes/header.php'; 
?>

<div class="max-w-7xl mx-auto py-6 sm:py-10 px-4 sm:px-6 lg:px-8">

    <!-- Feedback de Sucesso -->
    <?php if (isset($_GET['sucesso'])): ?>
        <div class="mb-6 bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 rounded-2xl flex items-center gap-2.5 text-xs sm:text-sm shadow-sm">
            <i data-lucide="check-circle-2" class="w-5 h-5 text-emerald-600 shrink-0"></i>
            <span>Operação realizada com sucesso!</span>
        </div>
    <?php endif; ?>

    <!-- Feedback de Erro -->
    <?php if ($erro): ?>
        <div class="mb-6 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-2xl flex items-center gap-2.5 text-xs sm:text-sm shadow-sm">
            <i data-lucide="alert-triangle" class="w-5 h-5 text-red-500 shrink-0"></i>
            <span><?= htmlspecialchars($erro, ENT_QUOTES, 'UTF-8') ?></span>
        </div>
    <?php endif; ?>

    <!-- Cabeçalho da Página -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 sm:mb-8 gap-4">
        <div>
            <div class="flex items-center gap-2">
                <span class="w-2 h-6 bg-primaria rounded-full inline-block"></span>
                <h1 class="text-2xl sm:text-3xl font-black text-gray-800 tracking-tight">Catálogo de Produtos</h1>
            </div>
            <p class="text-xs sm:text-sm text-gray-500 mt-0.5">Gerencie os produtos ativos organizados por linha comercial.</p>
        </div>
        <a href="cadastrar_produto.php" 
           class="inline-flex items-center justify-center bg-primaria text-white px-4 sm:px-5 py-2.5 rounded-xl font-bold text-xs sm:text-sm hover:bg-primaria-hover transition shadow-sm gap-2 active:scale-95 whitespace-nowrap w-full sm:w-auto">
            <i data-lucide="plus-circle" class="w-4 h-4"></i>
            <span>Cadastrar Produto</span>
        </a>
    </div>

    <!-- Barra de Pesquisa Rápida em Tempo Real -->
    <div class="bg-white p-2.5 sm:p-3 rounded-2xl shadow-sm border border-gray-100 mb-8 flex items-center gap-2 transition focus-within:ring-2 focus-within:ring-primaria/20 focus-within:border-primaria">
        <div class="pl-2 text-gray-400">
            <i data-lucide="search" class="w-5 h-5"></i>
        </div>
        <input type="text" 
               id="campoPesquisa" 
               placeholder="Filtrar produto em tempo real pelo nome..." 
               class="w-full bg-transparent border-none text-gray-800 text-xs sm:text-sm focus:outline-none placeholder-gray-400 font-medium py-1"
               onkeyup="filtrarProdutos()">
        <button type="button" 
                onclick="document.getElementById('campoPesquisa').value = ''; filtrarProdutos();"
                class="p-1.5 text-gray-300 hover:text-gray-500 rounded-lg transition"
                title="Limpar pesquisa">
            <i data-lucide="x" class="w-4 h-4"></i>
        </button>
    </div>

    <!-- Exibição de Produtos em Grid por Linha -->
    <?php if (empty($produtos_por_linha)): ?>
        <div class="bg-white rounded-3xl border border-gray-100 p-8 sm:p-14 text-center">
            <div class="w-16 h-16 bg-gray-50 rounded-2xl flex items-center justify-center mx-auto mb-4 text-gray-300">
                <i data-lucide="package-open" class="w-8 h-8"></i>
            </div>
            <h3 class="text-base font-bold text-gray-700 mb-1">Nenhum produto cadastrado</h3>
            <p class="text-xs text-gray-400 max-w-sm mx-auto mb-5">Cadastre o primeiro item para compor o catálogo de vendas.</p>
            <a href="cadastrar_produto.php" class="inline-flex items-center gap-2 bg-primaria text-white text-xs font-bold px-4 py-2.5 rounded-xl hover:bg-primaria-hover transition shadow-sm">
                <i data-lucide="plus-circle" class="w-4 h-4"></i>
                <span>Novo Produto</span>
            </a>
        </div>
    <?php else: ?>
        <div class="space-y-8 sm:space-y-10" id="containerLinhas">
            <?php foreach ($produtos_por_linha as $nome_linha => $produtos): ?>
                <section class="secao-linha">
                    <!-- Título da Linha -->
                    <div class="flex items-center justify-between mb-4 pb-2 border-b border-gray-100">
                        <div class="flex items-center gap-2">
                            <span class="w-2 h-5 bg-primaria rounded-full inline-block"></span>
                            <h2 class="text-base sm:text-lg font-bold text-gray-800 uppercase tracking-wider"><?= htmlspecialchars((string)$nome_linha, ENT_QUOTES, 'UTF-8') ?></h2>
                        </div>
                        <span class="text-[11px] font-bold text-gray-400 bg-gray-50 px-2.5 py-0.5 rounded-full border border-gray-100">
                            <?= count($produtos) ?> <?= count($produtos) === 1 ? 'item' : 'itens' ?>
                        </span>
                    </div>

                    <!-- Grid de Cards Responsivo: 2 colunas em mobile, 3 em tablet, 4 em desktop -->
                    <div class="grid grid-cols-2 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3.5 sm:gap-6 grid-produtos">
                        <?php foreach ($produtos as $p): ?>
                            <div class="card-produto bg-white rounded-2xl sm:rounded-3xl border border-gray-100 shadow-sm hover:shadow-md transition-all duration-200 flex flex-col justify-between overflow-hidden group"
                                 data-nome="<?= htmlspecialchars(mb_strtolower($p['nome'], 'UTF-8'), ENT_QUOTES, 'UTF-8') ?>">
                                
                                <div class="p-3.5 sm:p-5 flex-1 flex flex-col">
                                    <!-- Imagem com Proporção Perfeita -->
                                    <div class="w-full h-32 sm:h-44 bg-gray-50/70 rounded-xl sm:rounded-2xl mb-3 sm:mb-4 overflow-hidden flex items-center justify-center p-2 border border-gray-100">
                                        <img src="../uploads/<?= htmlspecialchars((string)($p['imagem'] ?: 'default.png'), ENT_QUOTES, 'UTF-8') ?>" 
                                             alt="<?= htmlspecialchars((string)$p['nome'], ENT_QUOTES, 'UTF-8') ?>"
                                             class="max-h-full max-w-full object-contain group-hover:scale-105 transition-transform duration-200"
                                             onerror="this.src='../uploads/default.png'">
                                    </div>

                                    <!-- Nome e Preço -->
                                    <div class="flex-1 flex flex-col justify-between">
                                        <h3 class="font-bold text-gray-800 text-xs sm:text-sm mb-2 line-clamp-2 leading-snug">
                                            <?= htmlspecialchars((string)$p['nome'], ENT_QUOTES, 'UTF-8') ?>
                                        </h3>
                                        <div class="text-sm sm:text-lg font-black text-primaria">
                                            R$ <?= number_format($p['preco'], 2, ',', '.') ?>
                                        </div>
                                    </div>
                                </div>

                                <!-- Rodapé de Ações -->
                                <div class="px-3.5 sm:px-4 py-2.5 sm:py-3 bg-gray-50/80 border-t border-gray-100 flex justify-between items-center gap-2">
                                    <a href="editar_produto.php?id=<?= (int)$p['id'] ?>" 
                                       class="inline-flex items-center text-xs text-blue-600 hover:text-blue-800 font-bold transition gap-1 py-1">
                                        <i data-lucide="edit-3" class="w-3.5 h-3.5"></i>
                                        <span>Editar</span>
                                    </a>

                                    <form method="POST" onsubmit="return confirm('Deseja realmente excluir este produto?');" class="inline">
                                        <?= campoCsrf() ?>
                                        <input type="hidden" name="acao" value="excluir">
                                        <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
                                        <button type="submit" 
                                                class="inline-flex items-center text-xs text-red-500 hover:text-red-700 font-bold transition gap-1 bg-transparent border-none cursor-pointer py-1">
                                            <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                                            <span>Excluir</span>
                                        </button>
                                    </form>
                                </div>

                            </div>
                        <?php endforeach; ?>
                    </div>
                </section>
            <?php endforeach; ?>

            <div id="semResultados" class="hidden bg-white rounded-3xl border border-gray-100 p-8 sm:p-12 text-center text-gray-400 italic">
                Nenhum produto encontrado para o termo pesquisado.
            </div>
        </div>
    <?php endif; ?>

</div>

<!-- Script do Filtro Dinâmico -->
<script>
function filtrarProdutos() {
    const termo = document.getElementById('campoPesquisa').value.toLowerCase().trim();
    const linhas = document.querySelectorAll('.secao-linha');
    const msgSemResultados = document.getElementById('semResultados');
    let totalVisiveis = 0;

    linhas.forEach(linha => {
        const cards = linha.querySelectorAll('.card-produto');
        let visiveisNaLinha = 0;

        cards.forEach(card => {
            const nome = card.getAttribute('data-nome');
            if (nome.includes(termo)) {
                card.style.display = 'flex';
                visiveisNaLinha++;
                totalVisiveis++;
            } else {
                card.style.display = 'none';
            }
        });

        if (visiveisNaLinha === 0) {
            linha.style.display = 'none';
        } else {
            linha.style.display = 'block';
        }
    });

    if (totalVisiveis === 0 && termo !== '') {
        msgSemResultados.classList.remove('hidden');
    } else {
        msgSemResultados.classList.add('hidden');
    }
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
