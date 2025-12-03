<?php
require_once '../../../db_config.php'; 

$usuario_id = $_SESSION['user_id'] ?? null;
$carrinho_json = $_POST['carrinho_data'] ?? '[]';
$carrinho = json_decode($carrinho_json, true);
$metodo_pagamento = $_POST['metodo_pagamento'] ?? 'N/A';

if (empty($carrinho)) {
    // Se o carrinho estiver vazio, mostra mensagem e encerra
    echo "Seu carrinho está vazio. <a href='" . BASE_URL . "/home.php'>Voltar para a loja</a>";
    include '../../../libras/libras.php'; // Libras aqui também
    exit;
}

$pdo->beginTransaction();

try {
    // --- LÓGICA DE VERIFICAÇÃO DE ESTOQUE (A GRANDE MUDANÇA) ---
    
    // 1. Agrega o carrinho: (Cliente quer 2x tênis ID 8, tamanho 36)
    $itens_pedido = [];
    foreach ($carrinho as $item) {
        $produto_id = $item['id'];
        $tamanho = $item['tamanho'];
        $chave = $produto_id . "_" . $tamanho; // Cria uma chave única (ex: "8_36")

        if (!isset($itens_pedido[$chave])) {
            $itens_pedido[$chave] = [
                'produto_id' => $produto_id,
                'tamanho' => $tamanho,
                'quantidade' => 0
            ];
        }
        $itens_pedido[$chave]['quantidade']++; // Incrementa a quantidade
    }

    $valor_total = 0;
    
    // Prepara as queries
    $stmt_busca_estoque = $pdo->prepare(
        "SELECT quantidade, preco FROM estoque_tamanhos WHERE produto_id = ? AND tamanho = ?"
    );
    $stmt_busca_nome = $pdo->prepare("SELECT nome FROM produtos WHERE id = ?");

    // 2. Verifica o estoque e calcula o preço REAL
    foreach ($itens_pedido as $chave => $item) {
        $stmt_busca_estoque->execute([$item['produto_id'], $item['tamanho']]);
        $estoque_item = $stmt_busca_estoque->fetch();

        // ERRO: Item de estoque não existe (produto/tamanho não bate)
        if (!$estoque_item) {
            $stmt_busca_nome->execute([$item['produto_id']]);
            $nome = $stmt_busca_nome->fetchColumn();
            throw new Exception("O produto '" . htmlspecialchars($nome) . "' no tamanho " . $item['tamanho'] . " não está disponível.");
        }

        // ERRO: Cliente quer mais do que temos (ex: quer 2, mas só temos 1)
        if ($estoque_item['quantidade'] < $item['quantidade']) {
            $stmt_busca_nome->execute([$item['produto_id']]);
            $nome = $stmt_busca_nome->fetchColumn();
            throw new Exception("Estoque insuficiente para '" . htmlspecialchars($nome) . "' (Tamanho " . $item['tamanho'] . "). Pedido: " . $item['quantidade'] . ", Em estoque: " . $estoque_item['quantidade']);
        }

        // Se passou, adiciona ao valor total
        $valor_total += (float)$estoque_item['preco'] * $item['quantidade'];
        
        // Salva o preço unitário para depois
        $itens_pedido[$chave]['preco_unitario'] = $estoque_item['preco'];
    }
    
    // --- FIM DA VERIFICAÇÃO ---


    // 3. Criar o Pedido na tabela 'pedidos'
    $stmt_pedido = $pdo->prepare("INSERT INTO pedidos (usuario_id, valor_total, metodo_pagamento, status_pagamento) VALUES (?, ?, ?, 'Aprovado')");
    $stmt_pedido->execute([$usuario_id, $valor_total, $metodo_pagamento]);
    $pedido_id = $pdo->lastInsertId();

    // 4. Inserir os itens na tabela 'pedido_itens' E DAR BAIXA NO ESTOQUE
    $stmt_item = $pdo->prepare("INSERT INTO pedido_itens (pedido_id, produto_id, quantidade, tamanho, preco_unitario) VALUES (?, ?, ?, ?, ?)");
    $stmt_estoque = $pdo->prepare(
        "UPDATE estoque_tamanhos SET quantidade = quantidade - ? WHERE produto_id = ? AND tamanho = ?"
    );

    foreach ($itens_pedido as $item) {
        // Insere o item do pedido
        $stmt_item->execute([
            $pedido_id, 
            $item['produto_id'], 
            $item['quantidade'], 
            $item['tamanho'], 
            $item['preco_unitario']
        ]);
        
        // Dá baixa no estoque
        $stmt_estoque->execute([
            $item['quantidade'], 
            $item['produto_id'], 
            $item['tamanho']
        ]);
    }

    // 5. Se tudo deu certo, confirma a transação
    $pdo->commit();

    echo "<h1>Compra realizada com sucesso!</h1>";
    echo "<p>Obrigado por comprar na DU.Hype. O ID do seu pedido é <strong>#$pedido_id</strong>.</p>";
    echo "<a href='" . BASE_URL . "/home.php'>Voltar para a loja</a>";

    // --- INCLUINDO LIBRAS NO SUCESSO ---
    include '../../../libras/libras.php'; 


} catch (Exception $e) {
    // Se algo deu errado (ex: estoque insuficiente), desfaz tudo
    $pdo->rollBack();
    
    // Trocamos o 'die' por 'echo' para poder incluir o libras depois
    echo "Erro ao processar pedido: "."<br><br>" . $e->getMessage() . "<br><br><a href='" . BASE_URL . "/src/pages/carrinho/carrinho.php'>Voltar ao carrinho</a>";
    
    // --- INCLUINDO LIBRAS NO ERRO ---
    include '../../../libras/libras.php';
    
    exit; // Para o script aqui
}
?>