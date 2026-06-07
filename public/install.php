<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';

header('Content-Type: text/html; charset=utf-8');

try {
    $pdo = Database::pdo();
} catch (Throwable $e) {
    echo '<h1>Erro de conexão</h1><p>Configure <code>config/database.php</code> e importe o SQL.</p>';
    echo '<pre>' . htmlspecialchars($e->getMessage()) . '</pre>';
    exit;
}

$email = 'admin@onecheck.local';
$senha = 'admin123';
$hash = password_hash($senha, PASSWORD_DEFAULT);

$stmt = $pdo->prepare('SELECT id FROM usuarios WHERE email = ?');
$stmt->execute([$email]);

if ($stmt->fetch()) {
    $pdo->prepare(
        'UPDATE usuarios SET senha_hash = ?, ativo = 1, mfa_obrigatorio = 1 WHERE email = ?'
    )->execute([$hash, $email]);
    $msg = 'Senha do admin atualizada (MFA obrigatório ativado).';
} else {
    $pdo->prepare(
        'INSERT INTO usuarios (uuid, nome, email, senha_hash, perfil, mfa_obrigatorio)
         VALUES (UUID(), ?, ?, ?, ?, 1)'
    )->execute(['Administrador', $email, $hash, 'admin']);
    $msg = 'Usuário admin criado (configure MFA no primeiro login).';
}

echo '<h1>Instalação OK</h1>';
echo '<p>' . htmlspecialchars($msg) . '</p>';
echo '<ul>';
echo '<li>E-mail: <strong>' . htmlspecialchars($email) . '</strong></li>';
echo '<li>Senha: <strong>' . htmlspecialchars($senha) . '</strong></li>';
echo '</ul>';
echo '<p><a href="' . htmlspecialchars(base_url('public/login.php')) . '">Ir para o login</a></p>';
echo '<p>No primeiro login você será direcionado a configurar o MFA (Google Authenticator).</p>';
