<?php
require_once 'painel_config.php';
session_start();

if (isset($_SESSION['autenticado']) && $_SESSION['autenticado'] === true) {
    $destination = ($_SESSION['role'] === 'admin') ? 'painel.php' : 'client_dashboard.php';
    header("Location: $destination");
    exit;
}

$erro = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username_digitado = $_POST['username'] ?? '';
    $senha_digitada = $_POST['senha'] ?? '';
    $db = conectar_db();
    
    $stmt_admin = $db->prepare("SELECT * FROM admin_users WHERE username = ?");
    $stmt_admin->execute([$username_digitado]);
    $admin_user = $stmt_admin->fetch();

    if ($admin_user && password_verify($senha_digitada, $admin_user['password_hash'])) {
        $_SESSION['autenticado'] = true;
        $_SESSION['role'] = 'admin';
        $_SESSION['username'] = $admin_user['username'];
        header('Location: painel.php');
        exit;
    }
    
    $stmt_client = $db->prepare("SELECT * FROM chaves WHERE client_username = ?");
    $stmt_client->execute([$username_digitado]);
    $client_user = $stmt_client->fetch();

    if ($client_user && !empty($client_user['client_password_hash']) && password_verify($senha_digitada, $client_user['client_password_hash'])) {
        $_SESSION['autenticado'] = true;
        $_SESSION['role'] = 'client';
        $_SESSION['username'] = $client_user['client_username'];
        $_SESSION['key_id'] = $client_user['id'];
        header('Location: client_dashboard.php');
        exit;
    }
    $erro = "Usuário ou senha incorretos!";
}
?>
<!DOCTYPE html>
<html lang="pt-br" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - API Control</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    animation: {
                        'fade-in': 'fadeIn 0.5s ease-out',
                        'slide-up': 'slideUp 0.5s ease-out'
                    }
                }
            }
        }
    </script>
    <style>
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        @keyframes slideUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body class="h-full bg-gradient-to-br from-blue-50 to-indigo-100 dark:from-gray-900 dark:to-gray-800">
    <div class="min-h-full flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full space-y-8 animate-slide-up">
            <div class="text-center">
                <div class="mx-auto h-16 w-16 bg-blue-600 rounded-full flex items-center justify-center mb-4">
                    <svg class="h-8 w-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                    </svg>
                </div>
                <h2 class="text-3xl font-bold text-gray-900 dark:text-white">API Control</h2>
                <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">Faça login para acessar o painel</p>
            </div>
            
            <div class="bg-white dark:bg-gray-800 py-8 px-6 shadow-xl rounded-lg border dark:border-gray-700">
                <form class="space-y-6" action="login.php" method="post">
                    <?php if (!empty($erro)): ?>
                        <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 text-red-700 dark:text-red-400 px-4 py-3 rounded-md text-sm animate-fade-in">
                            <div class="flex items-center">
                                <svg class="h-4 w-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                                </svg>
                                <?php echo htmlspecialchars($erro); ?>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <div>
                        <label for="username" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Usuário
                        </label>
                        <input 
                            id="username" 
                            name="username" 
                            type="text" 
                            required 
                            class="block w-full px-3 py-3 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm placeholder-gray-400 dark:placeholder-gray-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                            placeholder="Digite seu usuário"
                            autocomplete="username"
                        >
                    </div>
                    
                    <div>
                        <label for="senha" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Senha
                        </label>
                        <input 
                            id="senha" 
                            name="senha" 
                            type="password" 
                            required 
                            class="block w-full px-3 py-3 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm placeholder-gray-400 dark:placeholder-gray-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                            placeholder="Digite sua senha"
                            autocomplete="current-password"
                        >
                    </div>
                    
                    <div>
                        <button 
                            type="submit" 
                            class="group relative w-full flex justify-center py-3 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-all duration-200 transform hover:scale-105"
                        >
                            <span class="absolute left-0 inset-y-0 flex items-center pl-3">
                                <svg class="h-5 w-5 text-blue-500 group-hover:text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"></path>
                                </svg>
                            </span>
                            Entrar
                        </button>
                    </div>
                </form>
            </div>
            
            <!-- Theme toggle -->
            <div class="flex justify-center">
                <button 
                    id="theme-toggle-btn" 
                    class="p-2 rounded-full bg-white dark:bg-gray-800 shadow-md border dark:border-gray-700 hover:shadow-lg transition-all duration-200"
                    onclick="toggleTheme()"
                >
                    <svg id="theme-icon-light" class="h-5 w-5 text-gray-600 dark:hidden" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 2a1 1 0 011 1v1a1 1 0 11-2 0V3a1 1 0 011-1zm4 8a4 4 0 11-8 0 4 4 0 018 0zm-.464 4.95l.707.707a1 1 0 001.414-1.414l-.707-.707a1 1 0 00-1.414 1.414zm2.12-10.607a1 1 0 010 1.414l-.706.707a1 1 0 11-1.414-1.414l.707-.707a1 1 0 011.414 0zM17 11a1 1 0 100-2h-1a1 1 0 100 2h1zm-7 4a1 1 0 011 1v1a1 1 0 11-2 0v-1a1 1 0 011-1zM5.05 6.464A1 1 0 106.465 5.05l-.708-.707a1 1 0 00-1.414 1.414l.707.707zm1.414 8.486l-.707.707a1 1 0 01-1.414-1.414l.707-.707a1 1 0 011.414 1.414zM4 11a1 1 0 100-2H3a1 1 0 000 2h1z" clip-rule="evenodd"></path>
                    </svg>
                    <svg id="theme-icon-dark" class="h-5 w-5 text-gray-400 hidden dark:block" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M17.293 13.293A8 8 0 016.707 2.707a8.001 8.001 0 1010.586 10.586z"></path>
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <script>
        // Theme management
        function toggleTheme() {
            const html = document.documentElement;
            const isDark = html.classList.contains('dark');
            
            if (isDark) {
                html.classList.remove('dark');
                localStorage.setItem('theme', 'light');
            } else {
                html.classList.add('dark');
                localStorage.setItem('theme', 'dark');
            }
        }

        // Initialize theme
        document.addEventListener('DOMContentLoaded', () => {
            const theme = localStorage.getItem('theme') || 'light';
            if (theme === 'dark') {
                document.documentElement.classList.add('dark');
            }
        });
    </script>
</body>
</html>