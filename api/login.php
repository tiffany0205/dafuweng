<?php
/**
 * POST /api/login
 * Body: {"username":"...", "password":"..."}
 */
$input = getJsonInput();
$username = trim($input['username'] ?? '');
$password = $input['password'] ?? '';

if ($username === '' || $password === '') {
    jsonResponse(400, '请输入用户名和密码');
}

$db = getDB();
$stmt = $db->prepare("SELECT * FROM users WHERE username = ?");
$stmt->execute([$username]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user || !password_verify($password, $user['password_hash'])) {
    jsonResponse(400, '用户名或密码错误');
}

$token = generateToken();
$stmt = $db->prepare("INSERT INTO auth_tokens (user_id, token, expires_at) VALUES (?, ?, datetime('now','localtime','+7 days'))");
$stmt->execute([$user['id'], $token]);

jsonResponse(0, '登录成功', [
    'token' => $token,
    'user' => [
        'id' => $user['id'],
        'username' => $user['username'],
        'invite_code' => $user['invite_code'],
        'vip_level' => $user['vip_level'],
    ]
]);
