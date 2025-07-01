<?php
require_once 'painel_config.php';
session_start();

if (!isset($_SESSION['autenticado']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-br" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logs da API - API Control</title>
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
                        <a href="painel.php" class="sidebar-nav-item sidebar-nav-item-inactive">
                            <svg class="mr-3 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-6 6c-3 0-5.5-1.5-5.5-4m0 0c0 2.5 2.5 4 5.5 4 3 0 5.5-1.5 5.5-4m-10 4H9m1.5-10H9m4.5 4H9"></path>
                            </svg>
                            Gerenciar Chaves
                        </a>
                        <a href="logs.php" class="sidebar-nav-item sidebar-nav-item-active">
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
                            <a href="painel.php" class="btn btn-secondary mr-4">
                                <svg class="h-4 w-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                                </svg>
                                Voltar ao Painel
                            </a>
                            <h2 class="text-lg font-medium text-gray-900 dark:text-white">Histórico de Acessos da API</h2>
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
                        </div>
                    </div>
                </div>
            </header>

            <!-- Main content area -->
            <main class="flex-1 overflow-y-auto">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                    <!-- Filters -->
                    <div class="card mb-8">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Filtrar Logs</h3>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <input type="text" id="filter-ip" placeholder="Filtrar por IP..." class="input">
                            <input type="text" id="filter-token" placeholder="Filtrar por Token..." class="input">
                            <button id="filter-logs-btn" class="btn btn-primary">
                                <svg class="h-4 w-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.414A1 1 0 013 6.707V4z"></path>
                                </svg>
                                Filtrar
                            </button>
                        </div>
                    </div>

                    <!-- Logs grid -->
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                        <!-- Success logs -->
                        <div class="card">
                            <div class="flex items-center mb-4">
                                <div class="h-8 w-8 bg-green-100 dark:bg-green-900 rounded-full flex items-center justify-center mr-3">
                                    <svg class="h-5 w-5 text-green-600 dark:text-green-400" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                    </svg>
                                </div>
                                <h3 class="text-lg font-medium text-gray-900 dark:text-white">Acessos Bem-Sucedidos</h3>
                                <span class="ml-2 text-sm text-gray-500 dark:text-gray-400">(Últimos 30)</span>
                            </div>
                            <div id="logs-success" class="space-y-2 max-h-96 overflow-y-auto border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                                <div class="animate-pulse">Carregando...</div>
                            </div>
                        </div>

                        <!-- Failure logs -->
                        <div class="card">
                            <div class="flex items-center mb-4">
                                <div class="h-8 w-8 bg-red-100 dark:bg-red-900 rounded-full flex items-center justify-center mr-3">
                                    <svg class="h-5 w-5 text-red-600 dark:text-red-400" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                                    </svg>
                                </div>
                                <h3 class="text-lg font-medium text-gray-900 dark:text-white">Acessos Negados</h3>
                                <span class="ml-2 text-sm text-gray-500 dark:text-gray-400">(Últimos 30)</span>
                            </div>
                            <div id="logs-failure" class="space-y-2 max-h-96 overflow-y-auto border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                                <div class="animate-pulse">Carregando...</div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="public/js/theme.js"></script>
    <script src="public/js/utils.js"></script>
    <script src="public/js/admin.js"></script>
</body>
</html>