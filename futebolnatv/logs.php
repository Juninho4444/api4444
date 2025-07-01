<?php
// logs.php - v4 - Layout Final e Completo
require_once 'painel_config.php';
session_start();

if (!isset($_SESSION['autenticado']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logs da API</title>
    <style>
        :root {
            --cor-fundo: #f4f7f9; --cor-fundo-card: #ffffff; --cor-texto: #343a40;
            --cor-texto-suave: #6c757d; --cor-borda: #dee2e6; --cor-primaria: #0d6efd;
            --cor-sucesso: #198754; --cor-perigo: #dc3545; --sombra: 0 4px 12px rgba(0,0,0,0.08);
        }
        :root.dark-mode {
            --cor-fundo: #121212; --cor-fundo-card: #1e1e1e; --cor-texto: #e0e0e0;
            --cor-texto-suave: #9e9e9e; --cor-borda: #424242;
        }
        body { margin: 0; font-family: system-ui, -apple-system, sans-serif; background-color: var(--cor-fundo); color: var(--cor-texto); transition: background-color 0.2s, color 0.2s; }
        .wrapper { display: flex; }
        .sidebar { width: 240px; background-color: var(--cor-fundo-card); min-height: 100vh; box-shadow: var(--sombra); padding: 20px; box-sizing: border-box; }
        .sidebar h2 { font-size: 22px; color: var(--cor-primaria); border-bottom: 1px solid var(--cor-borda); padding-bottom: 10px; margin: 0 0 20px 0; }
        .sidebar-nav a { display: block; color: var(--cor-texto-suave); text-decoration: none; padding: 12px 15px; border-radius: 6px; margin-bottom: 5px; font-weight: 500; transition: background-color 0.2s, color 0.2s; }
        .sidebar-nav a:hover { background-color: rgba(13, 110, 253, 0.1); color: var(--cor-primaria); }
        .sidebar-nav a.active { background-color: var(--cor-primaria); color: #fff; }
        .main-content { flex-grow: 1; padding: 30px; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
        h1 { font-size: 28px; margin: 0; }
        .card { background-color: var(--cor-fundo-card); padding: 25px; border-radius: 8px; box-shadow: var(--sombra); margin-bottom: 30px; }
        .btn { padding: 10px 15px; border: none; border-radius: 5px; cursor: pointer; text-decoration: none; color: white; background-color: var(--cor-primaria); font-weight: 500; }
        .form-inline { display: flex; gap: 15px; align-items: center; flex-wrap: wrap; }
        input[type="text"] { padding: 10px; border: 1px solid var(--cor-borda); border-radius: 5px; font-size: 14px; background-color: var(--cor-fundo-card); color: var(--cor-texto); }
        .logs-container { display: grid; grid-template-columns: 1fr 1fr; gap: 30px; }
        .log-column h2 { font-size: 20px; border-bottom: 1px solid var(--cor-borda); padding-bottom: 10px; margin-top: 0; }
        .log-list { height: 60vh; overflow-y: auto; background: var(--cor-fundo); padding: 15px; border: 1px solid var(--cor-borda); border-radius: 5px; }
        .log-item { margin-bottom: 10px; padding-bottom: 10px; border-bottom: 1px solid var(--cor-borda); font-family: monospace; font-size: 13px; line-height: 1.5; }
        .log-item span { display: block; }
        .log-item strong { color: var(--cor-texto); }
        .log-item small { background: #e9ecef; padding: 2px 4px; border-radius: 3px; }
        :root.dark-mode .log-item small { background-color: #333; }
        .log-status-sucesso { color: var(--cor-sucesso); }
        .log-status-falha { color: var(--cor-perigo); font-weight: bold; }
        .switch { position: relative; display: inline-block; width: 50px; height: 24px; }
        .switch input { opacity: 0; width: 0; height: 0; }
        .slider { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: #ccc; transition: .4s; border-radius: 24px; }
        .slider:before { position: absolute; content: ""; height: 18px; width: 18px; left: 3px; bottom: 3px; background-color: white; transition: .4s; border-radius: 50%; }
        input:checked + .slider { background-color: var(--cor-primaria); }
        input:checked + .slider:before { transform: translateX(26px); }

        @media screen and (max-width: 992px) {
            .wrapper { flex-direction: column; }
            .sidebar { width: 100%; min-height: auto; }
            .main-content { padding: 15px; }
            .logs-container { grid-template-columns: 1fr; }
        }
    </style>
     <script>
        (function() {
            const theme = localStorage.getItem('theme') || 'light';
            if (theme === 'dark') document.documentElement.classList.add('dark-mode');
        })();
    </script>
</head>
<body>
    <div class="wrapper">
        <aside class="sidebar">
            <h2>API Control</h2>
            <nav class="sidebar-nav">
                <a href="painel.php">Gerenciar Chaves</a>
                <a href="logs.php" class="active">Histórico de Acessos</a>
            </nav>
        </aside>
        <div class="main-content">
            <header class="header">
                 <a href="painel.php" class="btn" style="background-color: var(--cor-cinza)">← Voltar ao Painel</a>
                 <div class="form-inline">
                    <span>☀️</span>
                    <label class="switch">
                        <input type="checkbox" id="dark-mode-toggle">
                        <span class="slider round"></span>
                    </label>
                    <span>🌙</span>
                 </div>
            </header>
            <main>
                <h1>Histórico de Acessos da API</h1>
                <div class="card">
                    <h2>Filtrar Logs</h2>
                    <div class="form-inline" id="filter-form">
                        <input type="text" id="filter_ip" placeholder="Filtrar por IP...">
                        <input type="text" id="filter_token" placeholder="Filtrar por Token...">
                        <button id="filter-btn" class="btn">Filtrar</button>
                    </div>
                </div>

                <div class="logs-container">
                    <div class="log-column">
                        <h2>Acessos Bem-Sucedidos (Últimos 30)</h2>
                        <div id="logs-sucesso" class="log-list"><p>Carregando...</p></div>
                    </div>
                    <div class="log-column">
                        <h2>Acessos Negados (Últimos 30)</h2>
                        <div id="logs-falha" class="log-list"><p>Carregando...</p></div>
                    </div>
                </div>
            </main>
        </div>
    </div>
<script>
    async function carregarLogs() {
        const ip = document.getElementById('filter_ip').value;
        const token = document.getElementById('filter_token').value;
        const successContainer = document.getElementById('logs-sucesso');
        const failContainer = document.getElementById('logs-falha');
        
        successContainer.innerHTML = '<p>Carregando...</p>';
        failContainer.innerHTML = '<p>Carregando...</p>';

        try {
            const response = await fetch(`get_admin_logs.php?ip=${ip}&token=${token}`);
            const data = await response.json();

            renderLogs(successContainer, data.sucesso || []);
            renderLogs(failContainer, data.falha || []);

        } catch (error) {
            successContainer.innerHTML = '<p>Erro ao carregar logs.</p>';
            failContainer.innerHTML = '<p>Erro ao carregar logs.</p>';
            console.error("Erro ao buscar logs:", error);
        }
    }

    function renderLogs(container, logs) {
        if (logs.length === 0) {
            container.innerHTML = '<p>Nenhum registro encontrado.</p>';
            return;
        }
        let html = '';
        logs.forEach(log => {
            const statusClass = log.status === 'Sucesso' ? 'log-status-sucesso' : 'log-status-falha';
            const dataFormatada = new Date(log.access_time).toLocaleString('pt-BR');
            let ownerInfo = log.owner_name ? `<strong>Cliente:</strong> ${log.owner_name}` : `<strong>Token Tentado:</strong> <small>${log.token_attempted || 'N/A'}</small>`;

            html += `<div class="log-item">
                <span>[${dataFormatada}] - <strong class="${statusClass}">${log.status}</strong></span>
                <span>${ownerInfo}</span>
                <span><strong>IP:</strong> ${log.ip_address} | <strong>API:</strong> ${log.api_called}</span>
            </div>`;
        });
        container.innerHTML = html;
    }

    document.getElementById('filter-btn').addEventListener('click', carregarLogs);
    document.addEventListener('DOMContentLoaded', function() {
        carregarLogs(); // Carga inicial

        const toggle = document.getElementById('dark-mode-toggle');
        const htmlEl = document.documentElement;

        if (localStorage.getItem('theme') === 'dark') {
            toggle.checked = true;
        }

        toggle.addEventListener('click', () => {
            htmlEl.classList.toggle('dark-mode');
            localStorage.setItem('theme', htmlEl.classList.contains('dark-mode') ? 'dark' : 'light');
        });
    });
</script>
</body>
</html>