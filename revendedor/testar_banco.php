<?php
require_once '../includes/auth.php';
require_once '../includes/config.php';

verificarNivel('revendedor');

echo "<h2>🔎 Verificando Pedidos Gravados no Banco</h2>";

try {
    // 1. Exibe qual banco/host o PHP está conectado
    echo "<p><strong>Driver:</strong> " . $pdo->getAttribute(PDO::ATTR_DRIVER_NAME) . "</p>";
    
    // 2. Busca os últimos 5 pedidos gravados
    $stmt = $pdo->query("SELECT * FROM pedidos ORDER BY id DESC LIMIT 5");
    $pedidos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "<h3>Últimos Pedidos na Tabela 'pedidos':</h3>";
    echo "<pre>";
    print_r($pedidos);
    echo "</pre>";

    // 3. Busca os últimos itens inseridos
    $stmtItens = $pdo->query("SELECT * FROM itens_pedido ORDER BY id DESC LIMIT 5");
    $itens = $stmtItens->fetchAll(PDO::FETCH_ASSOC);

    echo "<h3>Últimos Itens na Tabela 'itens_pedido':</h3>";
    echo "<pre>";
    print_r($itens);
    echo "</pre>";

} catch (PDOException $e) {
    echo "<p style='color:red;'>Erro ao consultar banco: " . $e->getMessage() . "</p>";
}