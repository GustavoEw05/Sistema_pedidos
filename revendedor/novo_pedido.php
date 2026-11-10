<?php 
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/config.php';

verificarNivel('revendedor');

$produtos = $pdo->query("SELECT id, nome, preco, imagem FROM produtos ORDER BY nome ASC")->fetchAll(PDO::FETCH_ASSOC);

include __DIR__ . '/../includes/header.php'; 
?>

<div class="max-w-7xl mx-auto py-6 sm:py-10 px-4 sm:px-6 lg:px-8" 
     x-data="{
        carrinho: [],
        produtoSelecionadoId: '',
        quantidade: 1,
        produtosCatalogo: <?= htmlspecialchars(json_encode($produtos, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP), ENT_QUOTES, 'UTF-8') ?>,
        
        get produtoAtual() {
            if (!this.produtoSelecionadoId) return null;
            return this.produtosCatalogo.find(p => String(p.id) === String(this.produtoSelecionadoId)) || null;
        },

        init() {
            this.$watch('carrinho', () => {
                this.$nextTick(() => {
                    if (typeof lucide !== 'undefined') {
                        lucide.createIcons();
                    }
                });
            });
        },

        adicionarItem() {
            if (!this.produtoAtual || this.quantidade <= 0) return;
            
            const prod = this.produtoAtual;
            const itemExistente = this.carrinho.find(item => String(item.id) === String(prod.id));
            
            if (itemExistente) {
                itemExistente.qtd += parseInt(this.quantidade);
            } else {
                this.carrinho.push({ 
                    id: parseInt(prod.id), 
                    nome: prod.nome, 
                    preco: parseFloat(prod.preco), 
                    imagem: prod.imagem || 'default.png',
                    qtd: parseInt(this.quantidade) 
                });
            }
            
            this.produtoSelecionadoId = ''; 
            this.quantidade = 1;
        },
        
        alterarQtd(index, delta) {
            const novaQtd = this.carrinho[index].qtd + delta;
            if (novaQtd > 0) {
                this.carrinho[index].qtd = novaQtd;
            } else {
                this.removerItem(index);
            }
        },

        removerItem(index) {
            this.carrinho.splice(index, 1);
        },
        
        get total() {
            return this.carrinho.reduce((sum, item) => sum + (item.preco * item.qtd), 0);
        },

        get totalItens() {
            return this.carrinho.reduce((sum, item) => sum + item.qtd, 0);
        }
     }">
    
    <!-- Topo / Navegação -->
    <div class="mb-6 sm:mb-8 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3">
        <div>
            <div class="flex items-center gap-2">
                <span class="w-2 h-6 bg-primaria rounded-full inline-block"></span>
                <h1 class="text-2xl sm:text-3xl font-black text-gray-800 tracking-tight">Criar Novo Pedido</h1>
            </div>
            <p class="text-xs sm:text-sm text-gray-500 mt-1">Selecione os itens do catálogo e defina as quantidades.</p>
        </div>
        <a href="painel.php" 
           class="inline-flex items-center gap-1.5 text-xs sm:text-sm text-gray-500 hover:text-primaria font-semibold px-3.5 py-2 rounded-xl bg-white border border-gray-200/80 shadow-sm transition active:scale-95">
            <i data-lucide="arrow-left" class="w-4 h-4"></i>
            <span>Voltar ao painel</span>
        </a>
    </div>

    <!-- Layout Grid em 12 Colunas Responsivo -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
        
        <!-- Painel Esquerdo: Seleção de Produtos (5 Colunas) -->
        <div class="lg:col-span-5 bg-white p-5 sm:p-7 rounded-3xl border border-gray-100 shadow-sm">
            <div class="flex items-center gap-2 mb-5 pb-3 border-b border-gray-100">
                <i data-lucide="package-plus" class="w-5 h-5 text-primaria"></i>
                <h2 class="text-sm font-bold text-gray-800 uppercase tracking-wider">Adicionar Produto</h2>
            </div>
            
            <div class="space-y-4">
                <!-- Seletor do Produto -->
                <div>
                    <label class="block text-xs font-bold uppercase text-gray-400 mb-1.5 tracking-wider">Escolha o Produto</label>
                    <select x-model="produtoSelecionadoId"
                            class="w-full border border-gray-200 rounded-xl px-3.5 py-3 text-xs sm:text-sm bg-gray-50/50 focus:bg-white focus:ring-2 focus:ring-primaria/20 focus:border-primaria outline-none transition cursor-pointer">
                        <option value="">Selecione um produto do catálogo...</option>
                        <template x-for="p in produtosCatalogo" :key="p.id">
                            <option :value="p.id" x-text="p.nome + ' (R$ ' + parseFloat(p.preco).toLocaleString('pt-BR', {minimumFractionDigits: 2}) + ')'"></option>
                        </template>
                    </select>
                </div>

                <!-- Preview Visual do Produto Selecionado -->
                <div x-show="produtoAtual" 
                     x-transition 
                     class="p-3.5 bg-gray-50 rounded-2xl border border-gray-100 flex items-center gap-3.5" 
                     x-cloak>
                    <img :src="produtoAtual && produtoAtual.imagem ? '../uploads/' + produtoAtual.imagem : '../uploads/default.png'" 
                         class="w-14 h-14 object-contain rounded-xl bg-white border border-gray-100 p-1 shrink-0" 
                         onerror="this.src='../uploads/default.png'">
                    <div class="min-w-0 flex-1">
                        <h4 class="text-xs font-bold text-gray-800 truncate" x-text="produtoAtual ? produtoAtual.nome : ''"></h4>
                        <span class="text-sm font-black text-primaria block mt-0.5" 
                              x-text="'R$ ' + (produtoAtual ? parseFloat(produtoAtual.preco).toLocaleString('pt-BR', {minimumFractionDigits: 2}) : '0,00')">
                        </span>
                    </div>
                </div>

                <!-- Quantidade -->
                <div>
                    <label class="block text-xs font-bold uppercase text-gray-400 mb-1.5 tracking-wider">Quantidade Solicitada</label>
                    <div class="flex items-center gap-2">
                        <button type="button" @click="if(quantidade > 1) quantidade--" 
                                class="w-11 h-11 rounded-xl bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold flex items-center justify-center transition active:scale-95 shrink-0">
                            <i data-lucide="minus" class="w-4 h-4"></i>
                        </button>
                        <input type="number" min="1" x-model="quantidade" 
                               class="w-full text-center border border-gray-200 rounded-xl h-11 text-sm font-bold text-gray-800 bg-gray-50/50 focus:bg-white focus:ring-2 focus:ring-primaria/20 focus:border-primaria outline-none transition">
                        <button type="button" @click="quantidade++" 
                                class="w-11 h-11 rounded-xl bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold flex items-center justify-center transition active:scale-95 shrink-0">
                            <i data-lucide="plus" class="w-4 h-4"></i>
                        </button>
                    </div>
                </div>

                <!-- Botão Adicionar -->
                <button type="button" 
                        @click="adicionarItem()" 
                        :disabled="!produtoAtual || quantidade <= 0"
                        class="w-full h-12 bg-primaria text-white rounded-xl font-bold hover:bg-primaria-hover transition shadow-sm disabled:opacity-40 disabled:cursor-not-allowed flex items-center justify-center gap-2 text-sm cursor-pointer active:scale-98">
                    <i data-lucide="shopping-cart" class="w-4 h-4"></i>
                    <span>Adicionar ao Carrinho</span>
                </button>
            </div>
        </div>

        <!-- Painel Direito: Resumo do Carrinho (7 Colunas) -->
        <div class="lg:col-span-7 bg-white p-5 sm:p-7 rounded-3xl border border-gray-100 shadow-sm flex flex-col justify-between">
            <div>
                <div class="flex justify-between items-center mb-5 pb-3 border-b border-gray-100">
                    <div class="flex items-center gap-2">
                        <i data-lucide="shopping-bag" class="w-5 h-5 text-primaria"></i>
                        <h2 class="text-sm font-bold text-gray-800 uppercase tracking-wider">Itens do Pedido</h2>
                    </div>
                    <span class="text-xs font-bold px-2.5 py-1 rounded-full bg-gray-100 text-gray-600" 
                          x-text="totalItens + (totalItens === 1 ? ' unidade' : ' unidades')">
                    </span>
                </div>
                
                <!-- Estado Vazio -->
                <div x-show="carrinho.length === 0" 
                     class="text-center py-12 px-4 border-2 border-dashed border-gray-100 rounded-2xl flex flex-col items-center justify-center">
                    <div class="w-12 h-12 rounded-2xl bg-gray-50 flex items-center justify-center text-gray-300 mb-3">
                        <i data-lucide="shopping-cart" class="w-6 h-6"></i>
                    </div>
                    <h4 class="text-sm font-bold text-gray-700 mb-0.5">Seu carrinho está vazio</h4>
                    <p class="text-xs text-gray-400">Escolha os produtos ao lado para montar seu pedido.</p>
                </div>

                <!-- Lista de Itens Responsiva (Cards em Mobile, Linhas Fluidas em Desktop) -->
                <div x-show="carrinho.length > 0" class="space-y-3 max-h-[380px] overflow-y-auto pr-1" x-cloak>
                    <template x-for="(item, index) in carrinho" :key="item.id">
                        <div class="flex items-center justify-between p-3.5 bg-gray-50/70 hover:bg-gray-50 rounded-2xl border border-gray-100 transition gap-3">
                            
                            <!-- Imagem e Nome -->
                            <div class="flex items-center gap-3 min-w-0 flex-1">
                                <img :src="item.imagem ? '../uploads/' + item.imagem : '../uploads/default.png'" 
                                     class="w-12 h-12 object-contain rounded-xl bg-white border border-gray-100 p-1 shrink-0" 
                                     onerror="this.src='../uploads/default.png'">
                                <div class="min-w-0 flex-1">
                                    <h4 class="text-xs sm:text-sm font-bold text-gray-800 truncate" x-text="item.nome"></h4>
                                    <span class="text-[11px] text-gray-400 font-medium" 
                                          x-text="'R$ ' + item.preco.toLocaleString('pt-BR', {minimumFractionDigits: 2}) + ' un.'">
                                    </span>
                                </div>
                            </div>

                            <!-- Controle de Quantidade -->
                            <div class="flex items-center gap-1.5 shrink-0 bg-white px-2 py-1 rounded-xl border border-gray-200/80 shadow-2xs">
                                <button type="button" @click="alterarQtd(index, -1)" 
                                        class="w-6 h-6 rounded-lg text-gray-500 hover:bg-gray-100 flex items-center justify-center transition">
                                    <i data-lucide="minus" class="w-3 h-3"></i>
                                </button>
                                <span class="w-7 text-center text-xs font-black text-gray-800" x-text="item.qtd"></span>
                                <button type="button" @click="alterarQtd(index, 1)" 
                                        class="w-6 h-6 rounded-lg text-gray-500 hover:bg-gray-100 flex items-center justify-center transition">
                                    <i data-lucide="plus" class="w-3 h-3"></i>
                                </button>
                            </div>

                            <!-- Subtotal e Excluir -->
                            <div class="text-right shrink-0 min-w-[70px]">
                                <span class="block text-xs sm:text-sm font-black text-gray-900" 
                                      x-text="'R$ ' + (item.preco * item.qtd).toLocaleString('pt-BR', {minimumFractionDigits: 2, maximumFractionDigits: 2})">
                                </span>
                            </div>

                            <button type="button" @click="removerItem(index)" 
                                    class="p-2 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded-xl transition shrink-0" 
                                    title="Remover produto">
                                <i data-lucide="trash-2" class="w-4 h-4"></i>
                            </button>
                        </div>
                    </template>
                </div>
            </div>

            <!-- Formulário e Rodapé de Fechamento -->
            <form action="processar_pedido.php" method="POST" class="mt-6 pt-5 border-t border-gray-100">
                <?= campoCsrf() ?>
                <input type="hidden" name="carrinho_json" id="carrinho_json" :value="JSON.stringify(carrinho)">

                <!-- Card de Total Geral -->
                <div class="flex justify-between items-center mb-5 bg-gradient-to-r from-gray-50 to-emerald-50/30 p-4 rounded-2xl border border-gray-100">
                    <div>
                        <span class="text-xs font-bold uppercase text-gray-400 tracking-wider block">Total Previsto:</span>
                        <span class="text-[11px] text-gray-500" x-text="carrinho.length + ' item(ns) listado(s)'"></span>
                    </div>
                    <span class="text-2xl sm:text-3xl font-black text-primaria" 
                          x-text="'R$ ' + total.toLocaleString('pt-BR', {minimumFractionDigits: 2, maximumFractionDigits: 2})">
                    </span>
                </div>

                <!-- Botões de Ação -->
                <div class="flex flex-col-reverse sm:flex-row gap-3">
                    <a href="painel.php" 
                       class="w-full sm:w-auto px-5 py-3 border border-gray-200 text-center rounded-xl text-gray-600 font-bold hover:bg-gray-50 transition text-xs sm:text-sm flex items-center justify-center">
                        Cancelar
                    </a>
                    <button type="submit" 
                            :disabled="carrinho.length === 0" 
                            class="flex-1 bg-primaria text-white py-3.5 px-6 rounded-xl font-bold hover:bg-primaria-hover transition shadow-md disabled:opacity-40 disabled:cursor-not-allowed text-xs sm:text-sm flex items-center justify-center gap-2 cursor-pointer active:scale-98">
                        <i data-lucide="check-circle-2" class="w-4 h-4"></i>
                        <span>Confirmar e Finalizar Pedido</span>
                    </button>
                </div>
            </form>
        </div>

    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
