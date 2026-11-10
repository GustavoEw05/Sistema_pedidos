<?php 
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/config.php';

verificarNivel('admin');

$id = (int)($_GET['id'] ?? 0);

if ($id <= 0) {
    header("Location: produtos.php");
    exit;
}

// Buscar dados do produto existente
$stmt = $pdo->prepare("SELECT * FROM produtos WHERE id = ?");
$stmt->execute([$id]);
$produto = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$produto) {
    header("Location: produtos.php");
    exit;
}

$erro = null;

$linhas_disponiveis = [
    'Linha Humana',
    'Linha OzonPet',
    'Linha Premium',
    'Linha Slim',
    'Linha Creme'
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? null;

    if (!validarCsrfToken($token)) {
        $erro = "Sessão expirada ou token de segurança inválido. Tente novamente.";
    } else {
        $nome  = trim($_POST['nome'] ?? '');
        $preco = (float)str_replace(',', '.', $_POST['preco'] ?? 0);
        $linha = trim($_POST['linha'] ?? 'Linha Humana');
        
        if (!in_array($linha, $linhas_disponiveis, true)) {
            $linha = 'Linha Humana';
        }

        if (empty($nome) || $preco < 0) {
            $erro = "Preencha o nome do produto e informe um preço válido.";
        } else {
            $nome_imagem = (string)$produto['imagem'];

            // Processar nova imagem se enviada
            if (isset($_FILES['imagem']) && $_FILES['imagem']['error'] === UPLOAD_ERR_OK) {
                $fileTmp  = $_FILES['imagem']['tmp_name'];
                $fileSize = $_FILES['imagem']['size'];

                if ($fileSize > 5 * 1024 * 1024) {
                    $erro = "A imagem selecionada excede o limite de 5MB.";
                } else {
                    $finfo = new finfo(FILEINFO_MIME_TYPE);
                    $mimeReal = $finfo->file($fileTmp);

                    $mimesPermitidos = [
                        'image/jpeg' => 'jpg',
                        'image/png'  => 'png',
                        'image/webp' => 'webp'
                    ];

                    if (!isset($mimesPermitidos[$mimeReal]) || @getimagesize($fileTmp) === false) {
                        $erro = "Arquivo inválido! Envie apenas imagens autênticas nos formatos JPG, PNG ou WEBP.";
                    } else {
                        $extensaoSegura = $mimesPermitidos[$mimeReal];
                        $nova_imagem = 'prod_' . bin2hex(random_bytes(16)) . '.' . $extensaoSegura;
                        $destino = __DIR__ . '/../uploads/' . $nova_imagem;

                        if (move_uploaded_file($fileTmp, $destino)) {
                            if ($nome_imagem && $nome_imagem !== 'default.png' && $nome_imagem !== 'default.jpg') {
                                $caminhoAntigo = __DIR__ . '/../uploads/' . basename($nome_imagem);
                                if (file_exists($caminhoAntigo)) {
                                    @unlink($caminhoAntigo);
                                }
                            }
                            $nome_imagem = $nova_imagem;
                        } else {
                            $erro = "Falha ao gravar nova imagem no servidor.";
                        }
                    }
                }
            }

            if (!$erro) {
                try {
                    $stmt_update = $pdo->prepare("UPDATE produtos SET nome = ?, preco = ?, imagem = ?, linha = ? WHERE id = ?");
                    $stmt_update->execute([$nome, $preco, $nome_imagem, $linha, $id]);

                    header("Location: produtos.php?sucesso=1");
                    exit;
                } catch (\Throwable $e) {
                    error_log("[Editar Produto] Erro: " . $e->getMessage());
                    $erro = "Erro interno ao atualizar produto no banco de dados.";
                }
            }
        }
    }
}

$linha_atual = $produto['linha'] ?? (($produto['id'] <= 19) ? 'Linha Humana' : 'Linha Premium');

include __DIR__ . '/../includes/header.php'; 
?>

<div class="max-w-2xl mx-auto py-6 sm:py-10 px-4 sm:px-6">

    <!-- Botão Voltar -->
    <a href="produtos.php" 
       class="inline-flex items-center text-xs sm:text-sm font-bold text-gray-500 hover:text-primaria mb-5 sm:mb-6 transition gap-1.5 active:scale-95">
        <i data-lucide="arrow-left" class="w-4 h-4"></i>
        <span>Voltar para a lista</span>
    </a>

    <?php if ($erro): ?>
        <div class="mb-6 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-2xl flex items-center gap-2.5 text-xs sm:text-sm shadow-sm">
            <i data-lucide="alert-circle" class="w-5 h-5 text-red-500 shrink-0"></i>
            <span><?= htmlspecialchars($erro, ENT_QUOTES, 'UTF-8') ?></span>
        </div>
    <?php endif; ?>

    <!-- Form Edição -->
    <div class="bg-white p-6 sm:p-9 rounded-3xl shadow-sm border border-gray-100 transition-all">
        <div class="flex items-center gap-2.5 mb-6 pb-4 border-b border-gray-100">
            <div class="w-8 h-8 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center">
                <i data-lucide="edit-3" class="w-4 h-4"></i>
            </div>
            <div>
                <h2 class="text-lg sm:text-xl font-black text-gray-800 tracking-tight">Editar Produto #<?= (int)$produto['id'] ?></h2>
                <p class="text-xs text-gray-400">Atualize as informações comerciais deste item.</p>
            </div>
        </div>

        <form method="POST" enctype="multipart/form-data" class="space-y-4 sm:space-y-5">
            <?= campoCsrf() ?>

            <div>
                <label class="block text-xs font-bold text-gray-400 uppercase tracking-wider mb-1.5">Linha do Produto</label>
                <select name="linha" class="w-full border border-gray-200 rounded-xl px-3.5 py-3 text-xs sm:text-sm bg-gray-50/50 focus:bg-white focus:ring-2 focus:ring-primaria/20 focus:border-primaria outline-none transition cursor-pointer">
                    <?php foreach ($linhas_disponiveis as $opcao_linha): ?>
                        <option value="<?= $opcao_linha ?>" <?= ($linha_atual === $opcao_linha) ? 'selected' : '' ?>>
                            <?= $opcao_linha ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label class="block text-xs font-bold text-gray-400 uppercase tracking-wider mb-1.5">Nome do Produto</label>
                <input type="text" name="nome" value="<?= htmlspecialchars((string)$produto['nome'], ENT_QUOTES, 'UTF-8') ?>" required 
                       class="w-full border border-gray-200 rounded-xl px-3.5 py-3 text-xs sm:text-sm text-gray-800 bg-gray-50/50 focus:bg-white focus:ring-2 focus:ring-primaria/20 focus:border-primaria outline-none transition">
            </div>

            <div>
                <label class="block text-xs font-bold text-gray-400 uppercase tracking-wider mb-1.5">Preço de Venda (R$)</label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-gray-400 text-xs font-bold">R$</span>
                    <input type="number" step="0.01" min="0" name="preco" value="<?= htmlspecialchars((string)$produto['preco'], ENT_QUOTES, 'UTF-8') ?>" required 
                           class="w-full pl-10 pr-4 py-3 border border-gray-200 rounded-xl text-xs sm:text-sm font-bold text-gray-800 bg-gray-50/50 focus:bg-white focus:ring-2 focus:ring-primaria/20 focus:border-primaria outline-none transition">
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-gray-400 uppercase tracking-wider mb-1.5">Imagem Atual do Catálogo</label>
                <div class="flex items-center gap-3.5 p-3.5 bg-gray-50/80 rounded-2xl border border-gray-100 mb-3">
                    <img src="../uploads/<?= htmlspecialchars((string)($produto['imagem'] ?: 'default.png'), ENT_QUOTES, 'UTF-8') ?>" 
                         class="w-14 h-14 object-contain rounded-xl border border-gray-200 bg-white p-1" 
                         alt="Imagem Atual"
                         onerror="this.src='../uploads/default.png'">
                    <div class="min-w-0 flex-1">
                        <p class="text-xs text-gray-700 font-bold truncate"><?= htmlspecialchars((string)($produto['imagem'] ?: 'default.png'), ENT_QUOTES, 'UTF-8') ?></p>
                        <p class="text-[11px] text-gray-400 mt-0.5">Envie um novo arquivo abaixo para substituir.</p>
                    </div>
                </div>

                <label class="block text-xs font-bold text-gray-400 uppercase tracking-wider mb-1.5">Substituir Imagem (Max 5MB - JPG, PNG, WEBP)</label>
                <div class="border-2 border-dashed border-gray-200 hover:border-primaria/50 rounded-2xl p-4 sm:p-5 text-center bg-gray-50/30 transition-colors">
                    <input type="file" name="imagem" accept="image/jpeg,image/png,image/webp" 
                           class="w-full text-xs text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-primaria/10 file:text-primaria hover:file:bg-primaria/20 cursor-pointer">
                </div>
            </div>

            <div class="pt-4 flex flex-col-reverse sm:flex-row gap-3">
                <a href="produtos.php" 
                   class="w-full sm:w-1/2 py-3 text-center bg-gray-100 text-gray-600 rounded-xl font-bold hover:bg-gray-200 transition text-xs sm:text-sm flex items-center justify-center">
                    Cancelar
                </a>
                <button type="submit" 
                        class="w-full sm:w-1/2 bg-primaria text-white py-3.5 rounded-xl font-bold hover:bg-primaria-hover transition shadow-sm text-xs sm:text-sm flex items-center justify-center gap-2 cursor-pointer active:scale-98">
                    <i data-lucide="check" class="w-4 h-4"></i>
                    <span>Salvar Alterações</span>
                </button>
            </div>
        </form>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
