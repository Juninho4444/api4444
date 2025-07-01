<?php
// client_dashboard.php - v6 - Final Completo com Logs dinâmicos e Layout

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
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel do Cliente</title>
    <style>
        :root {
            --cor-primaria: #007bff; --cor-sucesso: #28a745; --cor-perigo: #dc3545;
            --cor-cinza: #6c757d; --cor-fundo: #f4f7f9; --cor-texto: #343a40;
            --cor-borda: #dee2e6; --cor-branca: #fff; --sombra: 0 4px 6px rgba(0,0,0,0.05);
        }
        html.dark-mode {
            --cor-fundo: #121212; --cor-fundo-card: #1e1e1e; --cor-texto: #e0e0e0;
            --cor-texto-suave: #9e9e9e; --cor-borda: #424242;
        }
        body { margin: 0; font-family: system-ui, -apple-system, sans-serif; background-color: var(--cor-fundo); color: var(--cor-texto); transition: background-color 0.2s, color 0.2s; }
        .container { max-width: 800px; margin: 20px auto; padding: 10px; }
        .header { display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center; margin-bottom: 25px; }
        h1, h2, h3 { color: var(--cor-texto); }
        h1 { font-size: 22px; }
        h2 { font-size: 20px; padding-bottom: 10px; margin-bottom: 20px; border-bottom: 1px solid var(--cor-borda); }
        h3 { font-size: 18px; margin-top: 25px; }
        .card { background-color: var(--cor-fundo-card); padding: 25px; border-radius: 8px; box-shadow: var(--sombra); margin-bottom: 30px; }
        .btn { padding: 10px 15px; border: none; border-radius: 5px; cursor: pointer; color: white; text-decoration: none; font-size: 14px; font-weight: 500; transition: all 0.2s; }
        .btn:hover { opacity: 0.9; }
        .btn-save { background-color: var(--cor-sucesso); }
        .btn-copy { background-color: var(--cor-cinza); }
        .info-grid { display: grid; grid-template-columns: 180px 1fr; gap: 15px 20px; align-items: center; }
        .info-grid strong { font-weight: 600; color: var(--cor-texto-suave); }
        .info-grid span, .info-grid small { font-size: 15px; word-break: break-all; }
        .info-grid small { font-family: monospace; background: var(--cor-fundo); padding: 5px 8px; border-radius: 4px; }
        .form-inline { display: flex; gap: 10px; align-items: center; margin-top: 10px; }
        input[type="text"] { flex-grow: 1; padding: 10px; border: 1px solid var(--cor-borda); border-radius: 5px; font-size: 14px; background-color: var(--cor-fundo-card); color: var(--cor-texto); }
        .mensagem { padding: 15px; margin-bottom: 20px; border-radius: 5px; text-align: center; font-weight: bold; }
        .mensagem.sucesso { background-color: #d1e7dd; color: #0f5132; border: 1px solid #badbcc; }
        .mensagem.erro { background-color: #f8d7da; color: #842029; border: 1px solid #f5c2c7; }
        .log-list { max-height: 400px; overflow-y: auto; background: var(--cor-fundo); padding: 10px; border-radius: 5px; margin-top: 15px; font-family: monospace; font-size: 13px;}
        .log-item { margin-bottom: 8px; padding-bottom: 8px; border-bottom: 1px solid var(--cor-borda); }
        .log-item.sucesso { color: var(--cor-sucesso); }
        .log-item.falha { color: var(--cor-perigo); }
        .log-controls { display: flex; gap: 10px; align-items: center; margin-bottom: 15px; flex-wrap: wrap; }
        .log-controls .btn { background-color: var(--cor-cinza); padding: 6px 12px; font-size: 13px; }
        .log-controls .btn.active { background-color: var(--cor-primaria); font-weight: bold; }
        .load-more-container { text-align: center; margin-top: 20px; }
        .status-ativo { color: var(--cor-sucesso); font-weight: bold; }
        .status-inativo { color: var(--cor-texto-suave); font-weight: bold; }
        .switch { position: relative; display: inline-block; width: 50px; height: 24px; }
        .switch input { opacity: 0; width: 0; height: 0; }
        .slider { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: #ccc; transition: .4s; border-radius: 24px; }
        .slider:before { position: absolute; content: ""; height: 18px; width: 18px; left: 3px; bottom: 3px; background-color: white; transition: .4s; border-radius: 50%; }
        input:checked + .slider { background-color: var(--cor-primaria); }
        input:checked + .slider:before { transform: translateX(26px); }

        @media screen and (max-width: 600px) {
            h1 { font-size: 20px; }
            .header { flex-direction: column; gap: 15px; }
            .info-grid { grid-template-columns: 1fr; }
            .info-grid strong { margin-top: 10px; }
            .form-inline { flex-direction: column; align-items: stretch; }
        }
    </style>
     <script>
        (function() {
            const theme = localStorage.getItem('theme') || 'light';
            if (theme === 'dark') {
                document.documentElement.classList.add('dark-mode');
            }
        })();
    </script>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Painel de Acesso - <?php echo htmlspecialchars($chave['owner_name']); ?></h1>
            <div class="form-inline">
                <div class="theme-switch">
                    <span>☀️</span>
                    <label class="switch">
                        <input type="checkbox" id="dark-mode-toggle">
                        <span class="slider round"></span>
                    </label>
                    <span>🌙</span>
                </div>
                <a href="logout.php" class="btn" style="background-color:#6c757d;">Sair</a>
            </div>
        </div>

        <?php if ($mensagem): ?>
            <div class="mensagem <?php echo htmlspecialchars($tipo_mensagem); ?>"><?php echo htmlspecialchars($mensagem); ?></div>
        <?php endif; ?>

        <div class="card">
            <h2>Detalhes da sua Chave de API</h2>
            <div class="info-grid">
                <strong>Sua Chave (Token):</strong>
                <div class="form-inline" style="margin: 0; justify-content: space-between;">
                    <small id="client-token"><?php echo htmlspecialchars($chave['token_key']); ?></small>
                    <button class="btn btn-copy" style="padding: 5px 10px;" onclick="copiarToken('client-token', this)">Copiar</button>
                </div>
                <strong>Status:</strong>
                <span><?php 
                    $is_expired = !empty($chave['expiration_date']) && new DateTime() > new DateTime($chave['expiration_date']);
                    if ($chave['status'] == 1 && !$is_expired) echo '<span class="status-ativo">Ativo</span>';
                    else echo '<span class="status-inativo">Inativo/Expirado</span>';
                ?></span>
                <strong>Válido até:</strong>
                <span><?php echo $chave['expiration_date'] ? date('d/m/Y', strtotime($chave['expiration_date'])) : 'Não expira'; ?></span>
                <strong>IP Autorizado:</strong>
                <span><?php echo htmlspecialchars($chave['associated_ip']); ?></span>
            </div>
            
            <hr style="margin: 30px 0 25px 0; border: 0; border-top: 1px solid #eee;">
            
            <h3>Alterar IP Autorizado</h3>
            <p style="font-size: 14px; color: var(--cor-texto-suave); margin-top: -15px;">Se o seu servidor mudar de IP, você pode atualizá-lo abaixo.</p>
            <form action="salvar_cliente.php" method="post" class="form-inline">
                <input type="text" name="associated_ip" value="<?php echo htmlspecialchars($chave['associated_ip']); ?>" required>
                <button type="submit" name="update_ip" class="btn btn-save">Salvar Novo IP</button>
            </form>
        </div>

        <div class="card">
            <h2>Seu Histórico de Acessos Recentes</h2>
            <div class="log-controls">
                <span>Filtrar por:</span>
                <button id="btn-todos" class="btn active" onclick="definirFiltro('todos')">Todos</button>
                <button id="btn-sucesso" class="btn" onclick="definirFiltro('sucesso')">Sucesso</button>
                <button id="btn-falha" class="btn" onclick="definirFiltro('falha')">Falhas</button>
            </div>
            <div id="log-list-container" class="log-list"></div>
            <div class="load-more-container">
                <button id="load-more-btn" class="btn btn-add" onclick="carregarMaisLogs()">Carregar Mais 10</button>
            </div>
        </div>
    </div>
<script>
    let filtroAtual = 'todos';
    let limiteAtual = 10;

    async function carregarLogs(status, limit) {
        const logContainer = document.getElementById('log-list-container');
        const loadMoreBtn = document.getElementById('load-more-btn');
        if(limit === 10) { 
            logContainer.innerHTML = '<p>Carregando logs...</p>';
        }
        loadMoreBtn.disabled = true;

        try {
            const response = await fetch(`get_client_logs.php?status=${status}&limit=${limit}`);
            const logs = await response.json();

            if (logs.erro) {
                logContainer.innerHTML = `<p style="color: red;">${logs.erro}</p>`;
                return;
            }
            if (limit === 10 && logs.length === 0) {
                logContainer.innerHTML = '<p>Nenhum registro encontrado para este filtro.</p>';
                loadMoreBtn.style.display = 'none';
                return;
            }
            
            let html = '';
            logs.forEach(log => {
                const statusClass = log.status === 'Sucesso' ? 'sucesso' : 'falha';
                const dataFormatada = new Date(log.access_time).toLocaleString('pt-BR');
                html += `<div class="log-item ${statusClass}">`;
                html += `[${dataFormatada}] - <b>${log.status}</b><br>`;
                html += `IP: ${log.ip_address} | API: ${log.api_called}`;
                html += `</div>`;
            });
            logContainer.innerHTML = html;
            loadMoreBtn.style.display = logs.length < limit ? 'none' : 'block';

        } catch (error) {
            logContainer.innerHTML = '<p>Erro ao carregar os logs. Tente novamente.</p>';
        } finally {
            loadMoreBtn.disabled = false;
        }
    }

    function definirFiltro(novoStatus) {
        filtroAtual = novoStatus;
        limiteAtual = 10; 
        carregarLogs(filtroAtual, limiteAtual);
        
        document.querySelectorAll('.log-controls .btn').forEach(btn => btn.classList.remove('active'));
        document.getElementById(`btn-${novoStatus}`).classList.add('active');
    }

    function carregarMaisLogs() {
        limiteAtual += 10;
        carregarLogs(filtroAtual, limiteAtual);
    }
    
    function copiarToken(tokenId, botao) {
        const textoParaCopiar = document.getElementById(tokenId).innerText;
        navigator.clipboard.writeText(textoParaCopiar).then(() => {
            const textoOriginal = botao.innerText;
            botao.innerText = 'Copiado!';
            botao.style.backgroundColor = 'var(--cor-sucesso)';
            setTimeout(() => {
                botao.innerText = textoOriginal;
                botao.style.backgroundColor = '';
            }, 2000);
        });
    }

    document.addEventListener('DOMContentLoaded', () => {
        carregarLogs(filtroAtual, limiteAtual);

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