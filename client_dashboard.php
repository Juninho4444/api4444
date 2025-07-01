<?php
require_once 'painel_config.php';
session_start();

// Segurança: Apenas clientes logados podem ver esta página
if (!isset($_SESSION['autenticado']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'client' || !isset($_SESSION['key_id'])) {
    header('Location: login.php');
    exit;
}

$db = conectar_db();
$stmt = $db->prepare("SELECT * FROM chaves WHERE id = ?");
$stmt->execute([$_SESSION['key_id']]);
$chave = $stmt->fetch();

// Se a chave foi deletada enquanto o usuário estava logado, faz logout
if (!$chave) {
    header('Location: logout.php');
    exit;
}

$mensagem = $_SESSION['mensagem_cliente'] ?? null;
$tipo_mensagem = $_SESSION['tipo_mensagem_cliente'] ?? null;
unset($_SESSION['mensagem_cliente'], $_SESSION['tipo_mensagem_cliente']);
?>
<!DOCTYPE html>
<html lang="pt-br" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel do Cliente - API Control</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    animation: {
                        'fade-in': 'fadeIn 0.3s ease-out',
                        'slide-in': 'slideIn 0.3s ease-out'
                    }
                }
            }
        }
    </script>
    <link rel="stylesheet" href="public/css/tailwind.css">
</head>
<body class="h-full bg-gray-50 dark:bg-gray-900 client-dashboard">
    <div class="min-h-full">
        <!-- Navigation -->
        <nav class="bg-white dark:bg-gray-800 shadow-sm border-b border-gray-200 dark:border-gray-700">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between items-center h-16">
                    <div class="flex items-center">
                        <div class="h-8 w-8 bg-blue-600 rounded-lg flex items-center justify-center">
                            <svg class="h-5 w-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                            </svg>
                        </div>
                        <h1 class="ml-3 text-xl font-semibold text-gray-900 dark:text-white">
                            Painel do Cliente
                        </h1>
                    </div>
                    <div class="flex items-center space-x-4">
                        <span class="text-sm text-gray-600 dark:text-gray-400">
                            Olá, <span class="font-medium text-gray-900 dark:text-white"><?php echo htmlspecialchars($chave['owner_name']); ?></span>
                        </span>
                        <!-- Theme toggle -->
                        <button id="theme-toggle" class="p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-700">
                            <svg class="h-5 w-5 dark:hidden" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 2a1 1 0 011 1v1a1 1 0 11-2 0V3a1 1 0 011-1zm4 8a4 4 0 11-8 0 4 4 0 018 0zm-.464 4.95l.707.707a1 1 0 001.414-1.414l-.707-.707a1 1 0 00-1.414 1.414zm2.12-10.607a1 1 0 010 1.414l-.706.707a1 1 0 11-1.414-1.414l.707-.707a1 1 0 011.414 0zM17 11a1 1 0 100-2h-1a1 1 0 100 2h1zm-7 4a1 1 0 011 1v1a1 1 0 11-2 0v-1a1 1 0 011-1zM5.05 6.464A1 1 0 106.465 5.05l-.708-.707a1 1 0 00-1.414 1.414l.707.707zm1.414 8.486l-.707.707a1 1 0 01-1.414-1.414l.707-.707a1 1 0 011.414 1.414zM4 11a1 1 0 100-2H3a1 1 0 000 2h1z" clip-rule="evenodd"></path>
                            </svg>
                            <svg class="h-5 w-5 hidden dark:block" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M17.293 13.293A8 8 0 016.707 2.707a8.001 8.001 0 1010.586 10.586z"></path>
                            </svg>
                        </button>
                        <a href="logout.php" class="btn btn-danger btn-sm">
                            <svg class="h-4 w-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                            </svg>
                            Sair
                        </a>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Main content -->
        <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <!-- Messages -->
            <?php if ($mensagem): ?>
                <div class="mb-6 animate-fade-in">
                    <div class="rounded-md p-4 <?php echo $tipo_mensagem === 'sucesso' ? 'bg-green-50 border border-green-200 text-green-800 dark:bg-green-900/20 dark:border-green-800 dark:text-green-200' : 'bg-red-50 border border-red-200 text-red-800 dark:bg-red-900/20 dark:border-red-800 dark:text-red-200'; ?>">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <?php if ($tipo_mensagem === 'sucesso'): ?>
                                    <svg class="h-5 w-5 text-green-400" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                    </svg>
                                <?php else: ?>
                                    <svg class="h-5 w-5 text-red-400" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                                    </svg>
                                <?php endif; ?>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm font-medium"><?php echo htmlspecialchars($mensagem); ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- API Key Details -->
            <div class="card mb-8">
                <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-6">Detalhes da sua Chave de API</h2>
                
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Sua Chave (Token)</label>
                            <div class="flex items-center space-x-2">
                                <code id="client-token" class="flex-1 text-sm bg-gray-100 dark:bg-gray-700 px-3 py-2 rounded border font-mono break-all">
                                    <?php echo htmlspecialchars($chave['token_key']); ?>
                                </code>
                                <button id="copy-token-btn" class="btn btn-secondary btn-sm">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                                    </svg>
                                </button>
                            </div>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Status</label>
                            <?php 
                                $is_expired = !empty($chave['expiration_date']) && new DateTime() > new DateTime($chave['expiration_date']);
                                if ($chave['status'] == 1 && !$is_expired): 
                            ?>
                                <span class="status-badge status-active">
                                    <svg class="h-3 w-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                    </svg>
                                    Ativo
                                </span>
                            <?php else: ?>
                                <span class="status-badge status-inactive">
                                    <svg class="h-3 w-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                                    </svg>
                                    Inativo/Expirado
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Válido até</label>
                            <p class="text-sm text-gray-900 dark:text-gray-100 bg-gray-50 dark:bg-gray-700 px-3 py-2 rounded border">
                                <?php echo $chave['expiration_date'] ? date('d/m/Y', strtotime($chave['expiration_date'])) : 'Não expira'; ?>
                            </p>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">IP Autorizado</label>
                            <p class="text-sm text-gray-900 dark:text-gray-100 bg-gray-50 dark:bg-gray-700 px-3 py-2 rounded border font-mono">
                                <?php echo htmlspecialchars($chave['associated_ip']); ?>
                            </p>
                        </div>
                    </div>
                </div>
                
                <div class="mt-8 pt-6 border-t border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Alterar IP Autorizado</h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                        Se o seu servidor mudar de IP, você pode atualizá-lo abaixo.
                    </p>
                    <form id="ip-form" action="salvar_cliente.php" method="post" class="flex flex-col sm:flex-row gap-4">
                        <input 
                            type="text" 
                            name="associated_ip" 
                            value="<?php echo htmlspecialchars($chave['associated_ip']); ?>" 
                            required 
                            class="input flex-1"
                            placeholder="Novo endereço IP"
                        >
                        <button type="submit" name="update_ip" class="btn btn-success">
                            <svg class="h-4 w-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            Salvar Novo IP
                        </button>
                    </form>
                </div>
            </div>

            <!-- Access History -->
            <div class="card">
                <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-6">Seu Histórico de Acessos Recentes</h2>
                
                <!-- Filter buttons -->
                <div class="flex flex-wrap gap-2 mb-6">
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300 self-center mr-2">Filtrar por:</span>
                    <button data-filter="todos" class="btn btn-sm bg-blue-600 text-white">Todos</button>
                    <button data-filter="sucesso" class="btn btn-sm bg-gray-200 text-gray-700 dark:bg-gray-600 dark:text-gray-300">Sucesso</button>
                    <button data-filter="falha" class="btn btn-sm bg-gray-200 text-gray-700 dark:bg-gray-600 dark:text-gray-300">Falhas</button>
                </div>
                
                <!-- Logs container -->
                <div id="logs-container" class="space-y-2 max-h-96 overflow-y-auto border border-gray-200 dark:border-gray-700 rounded-lg">
                    <div class="animate-pulse p-4">Carregando logs...</div>
                </div>
                
                <!-- Load more button -->
                <div class="mt-4 text-center">
                    <button id="load-more-btn" class="btn btn-secondary" style="display: none;">
                        Carregar Mais 10
                    </button>
                </div>
            </div>
        </main>
    </div>

    <script src="public/js/theme.js"></script>
    <script src="public/js/utils.js"></script>
    <script src="public/js/client.js"></script>
</body>
</html>