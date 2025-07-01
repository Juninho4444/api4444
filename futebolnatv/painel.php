<?php
// painel.php - v19 - Correção final do JavaScript

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
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel de Controle</title>
    <style>
        :root {
            --cor-fundo: #f4f7f9; --cor-fundo-card: #ffffff; --cor-texto: #343a40;
            --cor-texto-suave: #6c757d; --cor-borda: #dee2e6; --cor-primaria: #0d6efd;
            --cor-sucesso: #198754; --cor-perigo: #dc3545; --cor-aviso: #ffc107;
            --cor-info: #0dcaf0; --cor-dark: #212529; --sombra: 0 4px 12px rgba(0,0,0,0.08);
        }
        html.dark-mode {
            --cor-fundo: #121212; --cor-fundo-card: #1e1e1e; --cor-texto: #e0e0e0;
            --cor-texto-suave: #9e9e9e; --cor-borda: #424242;
        }
        body { margin: 0; font-family: system-ui, -apple-system, sans-serif; background-color: var(--cor-fundo); color: var(--cor-texto); transition: background-color 0.2s, color 0.2s; }
        .wrapper { display: flex; }
        .sidebar { width: 240px; background-color: var(--cor-fundo-card); min-height: 100vh; box-shadow: var(--sombra); padding: 20px; box-sizing: border-box; }
        .sidebar h2 { font-size: 22px; color: var(--cor-primaria); border-bottom: 1px solid var(--cor-borda); padding-bottom: 10px; margin: 0 0 20px 0; }
        .sidebar-nav a { display: block; color: var(--cor-texto-suave); text-decoration: none; padding: 12px 15px; border-radius: 6px; margin-bottom: 5px; font-weight: 500; transition: background-color 0.2s, color 0.2s; }
        .sidebar-nav a:hover, .sidebar-nav a.active { background-color: var(--cor-primaria); color: #fff; }
        .main-content { flex-grow: 1; padding: 30px; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
        .header-left { font-weight: 500; }
        .header-right { display: flex; align-items: center; gap: 20px; }
        .card { background-color: var(--cor-fundo-card); padding: 25px; border-radius: 8px; box-shadow: var(--sombra); margin-bottom: 30px; }
        h1 { font-size: 28px; margin: 0; } h2 { font-size: 20px; border-bottom: 1px solid var(--cor-borda); padding-bottom: 10px; margin-top: 0; margin-bottom: 20px; } h3 { font-size: 16px; margin: 15px 0 10px 0; color: #555; }
        .btn { padding: 8px 12px; border: none; border-radius: 5px; cursor: pointer; color: white; text-decoration: none; display: inline-block; font-size: 13px; font-weight: 500; transition: all 0.2s; }
        .btn:hover { opacity: 0.9; transform: translateY(-1px); }
        .btn-add { background-color: var(--cor-primaria); }
        .btn-save { background-color: var(--cor-sucesso); }
        .btn-remove { background-color: var(--cor-perigo); }
        .btn-toggle { background-color: var(--cor-aviso); color: #212529; }
        .btn-renew { background-color: var(--cor-info); }
        .btn-copy { background-color: var(--cor-cinza); }
        .btn-edit-login { background-color: var(--cor-dark); }
        .mensagem { padding: 15px; margin-bottom: 20px; border-radius: 5px; text-align: center; font-weight: bold; }
        .mensagem.sucesso { background-color: #d1e7dd; color: #0f5132; border: 1px solid #badbcc; }
        .mensagem.erro { background-color: #f8d7da; color: #842029; border: 1px solid #f5c2c7; }
        .table-wrapper { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; font-size: 14px; }
        th, td { text-align: left; padding: 12px; border-bottom: 1px solid var(--cor-borda); vertical-align: middle; }
        th { background-color: var(--cor-fundo); font-weight: 600; white-space: nowrap; }
        td { background-color: var(--cor-fundo-card); }
        tbody tr:hover td { background-color: var(--cor-fundo); }
        td small { font-family: monospace; font-size: 12px; background: #e9ecef; padding: 3px 6px; border-radius: 4px; display: inline-block; max-width: 150px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; vertical-align: middle;}
        :root.dark-mode td small { background-color: #333; }
        .status-ativo { color: var(--cor-sucesso); font-weight: bold; }
        .status-inativo { color: var(--cor-perigo); font-weight: bold; }
        .status-expirado { color: var(--cor-cinza); font-weight: bold; }
        .form-inline { display: flex; gap: 10px; align-items: center; flex-wrap: wrap; }
        .form-add { padding-top: 10px; }
        input[type="text"], input[type="password"], input[type="date"] { padding: 10px; border: 1px solid var(--cor-borda); border-radius: 5px; font-family: inherit; font-size: 14px; background-color: var(--cor-fundo); color: var(--cor-texto); }
        .form-add input { flex: 1; min-width: 160px; }
        .permissions-group { display: flex; flex-direction: column; gap: 5px; }
        td input { width: 95%; }
        .actions-cell { width: auto; white-space: nowrap; }
        .actions-row { display: flex; align-items: center; gap: 5px; }
        .actions-row:not(:last-child) { margin-bottom: 5px; }
        .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.5); justify-content: center; align-items: center; }
        .modal-content { background-color: var(--cor-fundo-card); padding: 25px; border-radius: 8px; box-shadow: var(--sombra); width: 80%; max-width: 600px; }
        .modal-content h2 { margin-top: 0; }
        .modal-content .info-item { margin-bottom: 12px; font-size: 15px; }
        .modal-content .info-item strong { color: var(--cor-texto-suave); display: inline-block; width: 150px; }
        .modal-content .info-item span { background: var(--cor-fundo); padding: 4px 8px; border-radius: 4px; font-family: monospace; }
        #successModal h2 { color: var(--cor-sucesso); }
        .close-button { color: #aaa; float: right; font-size: 28px; font-weight: bold; cursor: pointer; }
        .close-button:hover { color: var(--cor-texto); }
        .theme-switch { display: flex; align-items: center; gap: 10px; color: var(--cor-texto); }
        .switch { position: relative; display: inline-block; width: 50px; height: 24px; }
        .switch input { opacity: 0; width: 0; height: 0; }
        .slider { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: #ccc; transition: .4s; border-radius: 24px; }
        .slider:before { position: absolute; content: ""; height: 18px; width: 18px; left: 3px; bottom: 3px; background-color: white; transition: .4s; border-radius: 50%; }
        input:checked + .slider { background-color: var(--cor-primaria); }
        input:checked + .slider:before { transform: translateX(26px); }

        @media screen and (max-width: 1200px) { .wrapper { flex-direction: column; } .sidebar { width: 100%; min-height: auto; } }
        @media screen and (max-width: 992px) {
            table, thead, tbody, th, td, tr { display: block; }
            thead tr { position: absolute; top: -9999px; left: -9999px; }
            tr { border: 1px solid var(--cor-borda); margin-bottom: 15px; border-radius: 5px; }
            td { border: none; border-bottom: 1px solid #eee; position: relative; padding-left: 45%; text-align: right; min-height: 48px; }
            td:before { position: absolute; top: 50%; left: 12px; transform: translateY(-50%); width: 40%; padding-right: 10px; white-space: nowrap; text-align: left; font-weight: bold; content: attr(data-label); }
            .actions-cell { padding-left: 12px; text-align: left; }
            .actions-cell:before { content: ""; }
            .actions-row { justify-content: flex-start; }
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
    <div class="wrapper">
        <aside class="sidebar">
            <h2>API Control</h2>
            <nav class="sidebar-nav">
                <a href="painel.php" class="active">Gerenciar Chaves</a>
                <a href="logs.php">Histórico de Acessos</a>
            </nav>
        </aside>
        <div class="main-content">
            <header class="header">
                <div class="header-left">
                    Olá, <strong><?php echo htmlspecialchars($_SESSION['username']); ?></strong>!
                </div>
                <div class="header-right">
                    <div class="theme-switch">
                        <span>☀️</span>
                        <label class="switch"><input type="checkbox" id="dark-mode-toggle"><span class="slider round"></span></label>
                        <span>🌙</span>
                    </div>
                    <a href="logout.php" class="btn btn-remove">Sair</a>
                </div>
            </header>
            <main>
                 <?php if ($mensagem): ?>
                    <div class="mensagem <?php echo htmlspecialchars($tipo_mensagem); ?>"><?php echo htmlspecialchars($mensagem); ?></div>
                <?php endif; ?>
                
                <div class="card">
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <h2>Chaves Existentes</h2>
                        <button class="btn btn-add" onclick="abrirModalAddKey()">+ Adicionar Nova Chave</button>
                    </div>
                    <div class="table-wrapper">
                        <table>
                            <thead>
                                <tr><th>Nome</th><th>Token</th><th>IP</th><th>Permissões</th><th>Status</th><th>Validade</th><th>Ações</th></tr>
                            </thead>
                            <tbody>
                                <?php foreach ($chaves as $chave): $permissoes_chave = json_decode($chave['permissions'] ?? '[]', true); ?>
                                    <tr>
                                        <form action="salvar.php" method="post">
                                            <input type="hidden" name="id" value="<?php echo $chave['id']; ?>">
                                            <td data-label="Nome"><input type="text" name="owner_name" value="<?php echo htmlspecialchars($chave['owner_name']); ?>"></td>
                                            <td data-label="Token"><small id="token-<?php echo $chave['id']; ?>"><?php echo htmlspecialchars($chave['token_key']); ?></small></td>
                                            <td data-label="IP"><input type="text" name="associated_ip" value="<?php echo htmlspecialchars($chave['associated_ip']); ?>"></td>
                                            <td data-label="Permissões">
                                                <div class="permissions-group">
                                                    <?php foreach ($apis_disponiveis as $api): ?>
                                                        <label><input type="checkbox" name="permissions[]" value="<?php echo $api; ?>" <?php echo in_array($api, $permissoes_chave) ? 'checked' : ''; ?>> <?php echo htmlspecialchars($api); ?></label>
                                                    <?php endforeach; ?>
                                                </div>
                                            </td>
                                            <td data-label="Status">
                                                <?php
                                                    if ($chave['status'] == 1) echo '<span class="status-ativo">Ativo</span>';
                                                    elseif ($chave['status'] == 0) echo '<span class="status-inativo">Inativo</span>';
                                                    else echo '<span class="status-expirado">Expirado</span>';
                                                ?>
                                            </td>
                                            <td data-label="Validade"><input type="date" name="expiration_date" value="<?php echo $chave['expiration_date'] ? date('Y-m-d', strtotime($chave['expiration_date'])) : ''; ?>" required></td>
                                            <td class="actions-cell" data-label="Ações">
                                                <div class="actions-row">
                                                    <button type="button" class="btn btn-copy" onclick="copiarToken('token-<?php echo $chave['id']; ?>', this)">Copiar</button>
                                                    <button type="submit" name="update_key" class="btn btn-save">Salvar</button>
                                                    <button type="submit" name="renew_key" class="btn btn-renew">Renovar</button>
                                                </div>
                                                <div class="actions-row">
                                                    <button type="submit" name="toggle_status" class="btn btn-toggle"><?php echo $chave['status'] ? 'Desativar' : 'Ativar'; ?></button>
                                                    <?php if(!empty($chave['client_username'])): ?>
                                                        <button type="button" class="btn btn-edit-login" onclick="abrirModalLogin('<?php echo $chave['id']; ?>', '<?php echo htmlspecialchars(addslashes($chave['client_username'])); ?>')">Editar Login</button>
                                                    <?php endif; ?>
                                                    <button type="submit" name="delete_key" class="btn btn-remove" onclick="return confirm('Tem certeza?');">Remover</button>
                                                </div>
                                            </td>
                                        </form>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <div class="card">
                    <h2>Alterar Senha do Administrador</h2>
                    <form action="salvar.php" method="post" class="form-add form-inline">
                        <input type="password" name="senha_antiga" placeholder="Senha Atual" required>
                        <input type="password" name="nova_senha" placeholder="Nova Senha" required>
                        <input type="password" name="confirmar_nova_senha" placeholder="Confirmar Nova Senha" required>
                        <button type="submit" name="change_password" class="btn btn-save">Alterar Senha</button>
                    </form>
                </div>
            </main>
        </div>
    </div>

    <div id="modalAddKey" class="modal">
        <div class="modal-content card">
             <span class="close-button" onclick="fecharModalAddKey()">&times;</span>
             <h2>Gerar Nova Chave e Login de Cliente</h2>
             <form action="salvar.php" method="post" class="form-add">
                <h3>Dados da Chave</h3>
                <div class="form-inline">
                    <input type="text" name="owner_name" placeholder="Nome do Cliente/App" required>
                    <input type="text" name="associated_ip" placeholder="IP Associado" required>
                    <input type="date" name="expiration_date" required>
                </div>
                <h3>Permissões da Chave</h3>
                <div class="form-inline" style="justify-content: flex-start; gap: 25px;">
                    <?php foreach ($apis_disponiveis as $api): ?>
                        <label><input type="checkbox" name="permissions[]" value="<?php echo $api; ?>"> <?php echo htmlspecialchars($api); ?></label>
                    <?php endforeach; ?>
                </div>
                <h3>Login do Cliente (Opcional)</h3>
                <div class="form-inline">
                    <input type="text" name="client_username" placeholder="Usuário do Cliente">
                    <input type="password" name="client_password" placeholder="Senha do Cliente">
                </div>
                <div style="text-align: right; margin-top: 20px;">
                    <button type="submit" name="add_key" class="btn btn-add">Gerar e Salvar Tudo</button>
                </div>
            </form>
        </div>
    </div>
    <div id="modalLogin" class="modal">
        <div class="modal-content card">
            <span class="close-button" onclick="fecharModalLogin()">&times;</span>
            <h2>Alterar Login do Cliente</h2>
            <p>Editando o login para: <b id="modalUsernameAtual"></b></p>
            <form action="salvar.php" method="post" class="form-add">
                <input type="hidden" id="modalKeyId" name="id">
                <div class="form-inline">
                    <input type="text" id="modalClientUsername" name="client_username" required>
                    <input type="password" name="client_password" placeholder="Nova senha (deixe em branco para não alterar)">
                </div>
                <div style="text-align: right; margin-top: 20px;"><button type="submit" name="update_client_login" class="btn btn-save">Salvar Login</button></div>
            </form>
        </div>
    </div>
    <div id="successModal" class="modal">
        <div class="modal-content card">
            <span class="close-button" onclick="document.getElementById('successModal').style.display='none'">&times;</span>
            <h2>✅ Chave Criada com Sucesso!</h2>
            <p>Copie os dados abaixo e envie para o seu cliente.</p>
            <div id="dadosNovaChave" style="line-height: 2;"></div>
            <div style="text-align: right; margin-top: 20px;"><button class="btn btn-copy" onclick="copiarDadosNovaChave(this)">Copiar Tudo</button></div>
        </div>
    </div>

<script>
    let dadosChaveRecemCriada = null;

    function copiarTexto(texto, botao) {
        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(texto).then(() => feedbackBotao(botao, 'Copiado!'));
        } else {
            const textArea = document.createElement("textarea");
            textArea.value = texto;
            textArea.style.position = "fixed"; textArea.style.left = "-9999px";
            document.body.appendChild(textArea);
            textArea.focus(); textArea.select();
            try { document.execCommand('copy'); feedbackBotao(botao, 'Copiado!'); } 
            catch (err) { alert('Falha ao copiar.'); }
            document.body.removeChild(textArea);
        }
    }

    function copiarToken(tokenId, botao) {
        const texto = document.getElementById(tokenId).innerText;
        copiarTexto(texto, botao);
    }

    function feedbackBotao(botao, mensagem) {
        const textoOriginal = botao.innerText;
        botao.innerText = mensagem;
        botao.disabled = true;
        setTimeout(() => {
            botao.innerText = textoOriginal;
            botao.disabled = false;
        }, 2000);
    }

    function abrirModalAddKey() {
        document.getElementById('modalAddKey').style.display = 'flex';
    }
    function fecharModalAddKey() {
        document.getElementById('modalAddKey').style.display = 'none';
    }

    function abrirModalLogin(keyId, currentUsername) {
        document.getElementById('modalKeyId').value = keyId;
        document.getElementById('modalUsernameAtual').innerText = currentUsername;
        document.getElementById('modalClientUsername').value = currentUsername;
        document.getElementById('modalLogin').style.display = 'flex';
    }

    function fecharModalLogin() {
        document.getElementById('modalLogin').style.display = 'none';
    }

    function abrirModalSucesso(dadosChave) {
        dadosChaveRecemCriada = dadosChave;
        const container = document.getElementById('dadosNovaChave');
        let html = '<div class="info-item"><strong>Nome:</strong> <span>' + (dadosChave.owner_name || '') + '</span></div>' +
                   '<div class="info-item"><strong>Token:</strong> <span>' + (dadosChave.token_key || '') + '</span></div>' +
                   '<div class="info-item"><strong>IP:</strong> <span>' + (dadosChave.associated_ip || '') + '</span></div>' +
                   '<div class="info-item"><strong>Validade:</strong> <span>' + (dadosChave.expiration_date || '') + '</span></div>';
        if (dadosChave.client_username) {
            html += '<hr style="margin: 15px 0;">' +
                    '<div class="info-item"><strong>Usuário Portal:</strong> <span>' + dadosChave.client_username + '</span></div>' +
                    '<div class="info-item"><strong>Senha Portal:</strong> <span>' + (dadosChave.client_password || '(sem alteração)') + '</span></div>';
        }
        container.innerHTML = html;
        document.getElementById('successModal').style.display = 'flex';
    }

    function copiarDadosNovaChave(botao) {
        if (!dadosChaveRecemCriada) return;
        let texto = "DADOS DE ACESSO À API\n" +
                    "--------------------------------\n" +
                    "Cliente: " + dadosChaveRecemCriada.owner_name + "\n" +
                    "Token: " + dadosChaveRecemCriada.token_key + "\n" +
                    "IP Autorizado: " + dadosChaveRecemCriada.associated_ip + "\n" +
                    "Validade: " + dadosChaveRecemCriada.expiration_date;
        if (dadosChaveRecemCriada.client_username && dadosChaveRecemCriada.client_password) {
            texto += "\n\n" +
                     "--------------------------------\n" +
                     "ACESSO AO PORTAL DO CLIENTE\n" +
                     "--------------------------------\n" +
                     "Usuário: " + dadosChaveRecemCriada.client_username + "\n" +
                     "Senha: " + dadosChaveRecemCriada.client_password;
        }
        copiarTexto(texto, botao);
    }

    window.onclick = function(event) {
        const modals = document.getElementsByClassName('modal');
        for (let i = 0; i < modals.length; i++) {
            if (event.target == modals[i]) {
                modals[i].style.display = "none";
            }
        }
    }
    
    document.addEventListener('DOMContentLoaded', function() {
        const toggle = document.getElementById('dark-mode-toggle');
        const htmlEl = document.documentElement;

        if (localStorage.getItem('theme') === 'dark') {
            toggle.checked = true;
        }

        toggle.addEventListener('click', () => {
            htmlEl.classList.toggle('dark-mode');
            localStorage.setItem('theme', htmlEl.classList.contains('dark-mode') ? 'dark' : 'light');
        });

        <?php if ($nova_chave): ?>
            abrirModalSucesso(<?php echo json_encode($nova_chave); ?>);
        <?php endif; ?>
    });
</script>
</body>
</html>