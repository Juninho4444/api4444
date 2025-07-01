<?php
require_once 'painel_config.php';
session_start();

if (!isset($_SESSION['autenticado']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

$db = conectar_db();
$chaves = $db->query("SELECT * FROM chaves ORDER BY owner_name")->fetchAll(PDO::FETCH_ASSOC);
$apis_disponiveis = ['jogos_hoje', 'jogos_amanha', 'ufc_eventos'];

$mensagem = $_SESSION['mensagem'] ?? null;
$tipo_mensagem = $_SESSION['tipo_mensagem'] ?? null;
unset($_SESSION['mensagem'], $_SESSION['tipo_mensagem']);

$nova_chave = $_SESSION['nova_chave_criada'] ?? null;
unset($_SESSION['nova_chave_criada']);
?>
<!DOCTYPE html>
<html lang="pt-br" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel Administrativo - API Control</title>
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
<body class="h-full bg-gray-50 dark:bg-gray-900 admin-panel">
    <div class="flex h-full">
        <!-- Sidebar -->
        <div class="hidden md:flex md:w-64 md:flex-col">
            <div class="flex flex-col flex-grow pt-5 bg-white dark:bg-gray-800 border-r border-gray-200 dark:border-gray-700 overflow-y-auto">
                <div class="flex items-center flex-shrink-0 px-4">
                    <div class="h-8 w-8 bg-blue-600 rounded-lg flex items-center justify-center">
                        <svg class="h-5 w-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <h1 class="ml-3 text-xl font-semibold text-gray-900 dark:text-white">API Control</h1>
                </div>
                <div class="mt-8 flex-grow flex flex-col">
                    <nav class="flex-1 px-2 space-y-1">
                        <a href="painel.php" class="sidebar-nav-item sidebar-nav-item-active">
                            <svg class="mr-3 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-6 6c-3 0-5.5-1.5-5.5-4m0 0c0 2.5 2.5 4 5.5 4 3 0 5.5-1.5 5.5-4m-10 4H9m1.5-10H9m4.5 4H9"></path>
                            </svg>
                            Gerenciar Chaves
                        </a>
                        <a href="logs.php" class="sidebar-nav-item sidebar-nav-item-inactive">
                            <svg class="mr-3 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                            Histórico de Acessos
                        </a>
                    </nav>
                </div>
            </div>
        </div>

        <!-- Main content -->
        <div class="flex flex-col flex-1 overflow-hidden">
            <!-- Top header -->
            <header class="bg-white dark:bg-gray-800 shadow-sm border-b border-gray-200 dark:border-gray-700">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="flex justify-between items-center py-4">
                        <div class="flex items-center">
                            <button class="md:hidden p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-700">
                                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                                </svg>
                            </button>
                            <h2 class="ml-2 text-lg font-medium text-gray-900 dark:text-white">
                                Olá, <span class="font-semibold"><?php echo htmlspecialchars($_SESSION['username']); ?></span>!
                            </h2>
                        </div>
                        <div class="flex items-center space-x-4">
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
            </header>

            <!-- Main content area -->
            <main class="flex-1 overflow-y-auto">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
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

                    <!-- Keys management -->
                    <div class="card mb-8">
                        <div class="flex justify-between items-center mb-6">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-white">Chaves de API</h3>
                            <button id="add-key-btn" class="btn btn-primary">
                                <svg class="h-4 w-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                </svg>
                                Adicionar Nova Chave
                            </button>
                        </div>

                        <div class="table-responsive">
                            <table class="table">
                                <thead class="table-header">
                                    <tr>
                                        <th class="table-header-cell">Nome</th>
                                        <th class="table-header-cell">Token</th>
                                        <th class="table-header-cell">IP</th>
                                        <th class="table-header-cell">Permissões</th>
                                        <th class="table-header-cell">Status</th>
                                        <th class="table-header-cell">Validade</th>
                                        <th class="table-header-cell">Ações</th>
                                    </tr>
                                </thead>
                                <tbody class="table-body">
                                    <?php foreach ($chaves as $chave): 
                                        $permissoes_chave = json_decode($chave['permissions'] ?? '[]', true);
                                        $is_expired = !empty($chave['expiration_date']) && new DateTime() > new DateTime($chave['expiration_date']);
                                    ?>
                                        <tr>
                                            <form action="salvar.php" method="post">
                                                <input type="hidden" name="id" value="<?php echo $chave['id']; ?>">
                                                <td class="table-cell">
                                                    <input type="text" name="owner_name" value="<?php echo htmlspecialchars($chave['owner_name']); ?>" class="input text-sm">
                                                </td>
                                                <td class="table-cell">
                                                    <div class="flex items-center space-x-2">
                                                        <code id="token-<?php echo $chave['id']; ?>" class="text-xs bg-gray-100 dark:bg-gray-700 px-2 py-1 rounded font-mono max-w-32 truncate">
                                                            <?php echo htmlspecialchars($chave['token_key']); ?>
                                                        </code>
                                                        <button type="button" class="btn btn-secondary btn-sm" data-copy-token="token-<?php echo $chave['id']; ?>">
                                                            <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                                                            </svg>
                                                        </button>
                                                    </div>
                                                </td>
                                                <td class="table-cell">
                                                    <input type="text" name="associated_ip" value="<?php echo htmlspecialchars($chave['associated_ip']); ?>" class="input text-sm">
                                                </td>
                                                <td class="table-cell">
                                                    <div class="space-y-1">
                                                        <?php foreach ($apis_disponiveis as $api): ?>
                                                            <label class="flex items-center text-sm">
                                                                <input type="checkbox" name="permissions[]" value="<?php echo $api; ?>" <?php echo in_array($api, $permissoes_chave) ? 'checked' : ''; ?> class="mr-2 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                                                <?php echo htmlspecialchars($api); ?>
                                                            </label>
                                                        <?php endforeach; ?>
                                                    </div>
                                                </td>
                                                <td class="table-cell">
                                                    <?php if ($chave['status'] == 1 && !$is_expired): ?>
                                                        <span class="status-badge status-active">Ativo</span>
                                                    <?php elseif ($chave['status'] == 0): ?>
                                                        <span class="status-badge status-inactive">Inativo</span>
                                                    <?php else: ?>
                                                        <span class="status-badge status-expired">Expirado</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="table-cell">
                                                    <input type="date" name="expiration_date" value="<?php echo $chave['expiration_date'] ? date('Y-m-d', strtotime($chave['expiration_date'])) : ''; ?>" required class="input text-sm">
                                                </td>
                                                <td class="table-cell">
                                                    <div class="flex flex-wrap gap-1">
                                                        <button type="submit" name="update_key" class="btn btn-success btn-sm">Salvar</button>
                                                        <button type="submit" name="renew_key" class="btn btn-info btn-sm">Renovar</button>
                                                        <button type="submit" name="toggle_status" class="btn btn-warning btn-sm">
                                                            <?php echo $chave['status'] ? 'Desativar' : 'Ativar'; ?>
                                                        </button>
                                                        <?php if(!empty($chave['client_username'])): ?>
                                                            <button type="button" class="btn btn-secondary btn-sm" onclick="adminPanel.openEditLoginModal('<?php echo $chave['id']; ?>', '<?php echo htmlspecialchars(addslashes($chave['client_username'])); ?>')">
                                                                Login
                                                            </button>
                                                        <?php endif; ?>
                                                        <button type="submit" name="delete_key" class="btn btn-danger btn-sm" onclick="return confirm('Tem certeza que deseja remover esta chave?');">
                                                            Remover
                                                        </button>
                                                    </div>
                                                </td>
                                            </form>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Change admin password -->
                    <div class="card">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Alterar Senha do Administrador</h3>
                        <form action="salvar.php" method="post" class="grid grid-cols-1 md:grid-cols-4 gap-4" data-validate>
                            <input type="password" name="senha_antiga" placeholder="Senha Atual" required class="input">
                            <input type="password" name="nova_senha" placeholder="Nova Senha" required class="input">
                            <input type="password" name="confirmar_nova_senha" placeholder="Confirmar Nova Senha" required class="input">
                            <button type="submit" name="change_password" class="btn btn-success">Alterar Senha</button>
                        </form>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Add Key Modal -->
    <div id="add-key-modal" class="modal hidden">
        <div class="modal-backdrop"></div>
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="modal-content max-w-2xl w-full">
                <div class="flex justify-between items-center mb-6">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white">Gerar Nova Chave e Login de Cliente</h3>
                    <button onclick="Utils.closeModal('add-key-modal')" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
                
                <form action="salvar.php" method="post" class="space-y-6" data-validate>
                    <div>
                        <h4 class="text-md font-medium text-gray-900 dark:text-white mb-3">Dados da Chave</h4>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <input type="text" name="owner_name" placeholder="Nome do Cliente/App" required class="input">
                            <input type="text" name="associated_ip" placeholder="IP Associado" required class="input">
                            <input type="date" name="expiration_date" required class="input">
                        </div>
                    </div>
                    
                    <div>
                        <h4 class="text-md font-medium text-gray-900 dark:text-white mb-3">Permissões da Chave</h4>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <?php foreach ($apis_disponiveis as $api): ?>
                                <label class="flex items-center">
                                    <input type="checkbox" name="permissions[]" value="<?php echo $api; ?>" class="mr-2 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                    <span class="text-sm text-gray-700 dark:text-gray-300"><?php echo htmlspecialchars($api); ?></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <div>
                        <h4 class="text-md font-medium text-gray-900 dark:text-white mb-3">Login do Cliente (Opcional)</h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <input type="text" name="client_username" placeholder="Usuário do Cliente" class="input">
                            <input type="password" name="client_password" placeholder="Senha do Cliente" class="input">
                        </div>
                    </div>
                    
                    <div class="flex justify-end space-x-3">
                        <button type="button" onclick="Utils.closeModal('add-key-modal')" class="btn btn-secondary">Cancelar</button>
                        <button type="submit" name="add_key" class="btn btn-primary">Gerar e Salvar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Login Modal -->
    <div id="edit-login-modal" class="modal hidden">
        <div class="modal-backdrop"></div>
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="modal-content">
                <div class="flex justify-between items-center mb-6">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white">Alterar Login do Cliente</h3>
                    <button onclick="Utils.closeModal('edit-login-modal')" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
                
                <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                    Editando o login para: <strong id="current-username"></strong>
                </p>
                
                <form action="salvar.php" method="post" class="space-y-4">
                    <input type="hidden" id="edit-key-id" name="id">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <input type="text" id="edit-username" name="client_username" placeholder="Novo usuário" required class="input">
                        <input type="password" name="client_password" placeholder="Nova senha (deixe em branco para não alterar)" class="input">
                    </div>
                    <div class="flex justify-end space-x-3">
                        <button type="button" onclick="Utils.closeModal('edit-login-modal')" class="btn btn-secondary">Cancelar</button>
                        <button type="submit" name="update_client_login" class="btn btn-success">Salvar Login</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- New Key Success Modal -->
    <div id="new-key-modal" class="modal hidden">
        <div class="modal-backdrop"></div>
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="modal-content max-w-2xl w-full">
                <div class="flex justify-between items-center mb-6">
                    <div class="flex items-center">
                        <div class="h-8 w-8 bg-green-100 dark:bg-green-900 rounded-full flex items-center justify-center mr-3">
                            <svg class="h-5 w-5 text-green-600 dark:text-green-400" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                            </svg>
                        </div>
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white">Chave Criada com Sucesso!</h3>
                    </div>
                    <button onclick="Utils.closeModal('new-key-modal')" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
                
                <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                    Copie os dados abaixo e envie para o seu cliente.
                </p>
                
                <div id="new-key-data" class="mb-6"></div>
                
                <div class="flex justify-end space-x-3">
                    <button onclick="Utils.closeModal('new-key-modal')" class="btn btn-secondary">Fechar</button>
                    <button onclick="adminPanel.copyNewKeyData()" class="btn btn-primary">
                        <svg class="h-4 w-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                        </svg>
                        Copiar Tudo
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="public/js/theme.js"></script>
    <script src="public/js/utils.js"></script>
    <script src="public/js/admin.js"></script>
    
    <script>
        // Show new key modal if key was created
        <?php if ($nova_chave): ?>
            document.addEventListener('DOMContentLoaded', () => {
                adminPanel.showNewKeyModal(<?php echo json_encode($nova_chave); ?>);
            });
        <?php endif; ?>
    </script>
</body>
</html>