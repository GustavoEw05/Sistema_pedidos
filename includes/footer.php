</main>

    <!-- Rodapé Minimalista -->
    <footer class="mt-auto border-t border-gray-200/60 bg-white py-4 px-4 text-center text-xs text-gray-400">
        <div class="max-w-7xl mx-auto flex flex-col sm:flex-row justify-between items-center gap-2">
            <span class="font-medium text-gray-500">Sistema de Gestão de Pedidos &bull; &copy; <?= date('Y') ?></span>
            <div class="flex items-center gap-3 text-[11px]">
                <span class="inline-flex items-center gap-1 text-emerald-600 font-semibold">
                    <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span> Sistema Online
                </span>
                <span class="text-gray-300">|</span>
                <span>Ambiente Seguro</span>
            </div>
        </div>
    </footer>

    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <script>
        // Inicializa Lucide Icons
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }

        // Processa parâmetros de URL para feedback visual com SweetAlert2
        const urlParams = new URLSearchParams(window.location.search);

        if (urlParams.has('sucesso')) {
            const tipo = urlParams.get('sucesso');
            let config = { 
                confirmButtonColor: '#214e35', 
                icon: 'success',
                customClass: {
                    popup: 'rounded-2xl shadow-xl border border-gray-100',
                    confirmButton: 'rounded-xl font-bold px-6 py-2.5 text-sm shadow-md'
                }
            };

            switch (tipo) {
                case 'pedido_criado':
                case 'criado':
                    config.title = 'Pedido Realizado!';
                    config.text = 'O pedido foi enviado com sucesso para a administração.';
                    break;
                case 'pedido_editado':
                case 'editado':
                    config.title = 'Pedido Atualizado!';
                    config.text = 'As alterações do pedido foram salvas.';
                    break;
                case 'concluido':
                    config.title = 'Pedido Concluído!';
                    config.text = 'O status do pedido foi alterado para concluído.';
                    break;
                case 'pendente':
                    config.title = 'Pedido Reaberto!';
                    config.text = 'O pedido retornou para o status pendente.';
                    config.icon = 'info';
                    break;
                case 'cancelado':
                    config.title = 'Pedido Cancelado!';
                    config.text = 'O pedido foi cancelado e removido do sistema.';
                    config.icon = 'warning';
                    break;
                case 'excluido':
                    config.title = 'Item Removido!';
                    config.text = 'O registro foi excluído com êxito.';
                    break;
                default:
                    config.title = 'Operação Concluída!';
                    config.text = 'Ação processada com sucesso no sistema.';
            }

            Swal.fire(config).then(() => {
                const urlLimpa = window.location.pathname;
                window.history.replaceState({}, document.title, urlLimpa);
            });
        }
    </script>
</body>
</html>
