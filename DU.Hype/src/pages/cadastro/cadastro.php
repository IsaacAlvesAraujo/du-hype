<?php 
require_once '../../../db_config.php'; 

$cadastro_error = '';
$cadastro_success = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'] ?? '';
    $senha = $_POST['senha'] ?? '';
    $senha_confirma = $_POST['senha_confirma'] ?? '';
    $nome = $_POST['nome'] ?? ''; // Pega o nome do formulário

    // Validações Básicas
    if (empty($email) || empty($senha) || empty($senha_confirma) || empty($nome)) {
        $cadastro_error = "Todos os campos (inclusive Nome) são obrigatórios.";
    } elseif ($senha !== $senha_confirma) {
        $cadastro_error = "As senhas não coincidem.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $cadastro_error = "Formato de e-mail inválido.";
    } else {
        // Verificar se o e-mail já existe
        try {
            $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE email = ?");
            $stmt->execute([$email]);
            
            if ($stmt->fetch()) {
                $cadastro_error = "Este e-mail já está cadastrado.";
            } else {
                // Criar novo usuário
                $senha_hash = password_hash($senha, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO usuarios (email, nome, senha_hash) VALUES (?, ?, ?)");
                
                if ($stmt->execute([$email, $nome, $senha_hash])) {
                    $cadastro_success = "Cadastro realizado com sucesso! <a href='" . BASE_URL . "/src/pages/login/login.php'>Clique aqui para entrar</a>.";
                } else {
                    $cadastro_error = "Erro ao salvar no banco de dados.";
                }
            }
        } catch (PDOException $e) {
            // Se der erro de conexão, vai cair aqui
            $cadastro_error = "Erro de sistema: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Criar Conta - DU.HYPE</title>
    
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/src/pages/cadastro/style.css">
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;700&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body>

    <div class="header-container">
        <header class="header">
            <div class="logo">
                <a href="<?php echo BASE_URL; ?>/home.php">
                    <img src="<?php echo BASE_URL; ?>/src/assets/Images/LogoDu.Hype.svg" alt="Logo duhype">
                </a>
            </div>
            <nav class="nav">
                <a href="<?php echo BASE_URL; ?>/home.php">Início</a>
                <a href="<?php echo BASE_URL; ?>/src/pages/marcas/marcas.php">Marcas</a>
                <a href="<?php echo BASE_URL; ?>/src/pages/fedd/feed.php">Feed</a>
                <a href="<?php echo BASE_URL; ?>/src/pages/novidades/novidades.php">Novidades</a>
            </nav>
            <div class="icons">
                <a href="<?php echo BASE_URL; ?>/src/pages/fedd/feed.php"><i class="fas fa-search"></i></a>
                <a href="<?php echo BASE_URL; ?>/src/pages/carrinho/carrinho.php"><i class="fas fa-shopping-cart"></i></a>
                <a href="<?php echo BASE_URL; ?>/src/pages/cadastro/cadastro.php"><i class="fas fa-user"></i></a>
            </div>
        </header>
    </div>
    
    <main class="main-content">
        <section class="form-section">
            <div class="login-container">
                <h2 class="form-title">Crie sua Conta</h2>
                
                <form class="login-form" method="POST" action="<?php echo BASE_URL; ?>/src/pages/cadastro/cadastro.php">
                    <div class="input-group">
                        <i class="fas fa-user"></i>
                        <input type="text" name="nome" placeholder="Digite seu nome" required>
                    </div>
                    
                    <div class="input-group">
                        <i class="fas fa-envelope"></i>
                        <input type="email" name="email" placeholder="Digite seu email" required>
                    </div>
                    
                    <div class="input-group">
                        <i class="fas fa-lock"></i>
                        <input type="password" name="senha" placeholder="Crie uma senha" required>
                    </div>
                    
                    <div class="input-group">
                        <i class="fas fa-lock"></i>
                        <input type="password" name="senha_confirma" placeholder="Confirme sua senha" required>
                    </div>
                    
                    <?php if (!empty($cadastro_error)): ?>
                        <p style="color: red; font-size: 0.9em; margin-bottom: 15px;"><?php echo $cadastro_error; ?></p>
                    <?php endif; ?>
                    
                    <?php if (!empty($cadastro_success)): ?>
                        <p style="color: green; font-size: 0.9em; margin-bottom: 15px;"><?php echo $cadastro_success; ?></p>
                    <?php endif; ?>

                    <button type="submit" class="social-btn apple" style="margin-top: 10px; border: none; background-color: #000; color: #fff;">
                        Cadastrar
                    </button>
                </form>
                
                <div class="or-separator">
                    <span>ou</span>
                </div>
                
                <div class="social-login">
                    <a href="#" class="social-btn google">
                        <i class="fab fa-google"></i> Registre-se com Google
                    </a>
                    <a href="#" class="social-btn facebook">
                        <i class="fab fa-facebook-f"></i> Registre-se com Facebook
                    </a>
                </div>
                
                <div class="signup-link">
                    Já tem conta? <a href="<?php echo BASE_URL; ?>/src/pages/login/login.php">Fazer Login</a>
                </div>
            </div>
        </section>

        <section class="image-section">
            </section>
    </main>
    
    <?php include '../../../libras/libras.php'; ?>

</body>
</html>