<?php
/**
 * POST /api/register
 * Body: {"username":"...", "password":"...", "invite_code":"..."}
 */
$input = getJsonInput();
$username = trim($input['username'] ?? '');
$password = $input['password'] ?? '';
$inviteCode = trim($input['invite_code'] ?? '');

if (strlen($username) < 3 || strlen($username) > 20) {
    jsonResponse(400, '用户名长度3-20位');
}
if (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
    jsonResponse(400, '用户名只能包含字母、数字、下划线');
}
if (strlen($password) < 6) {
    jsonResponse(400, '密码至少6位');
}

$db = getDB();

// Check duplicate
$stmt = $db->prepare("SELECT id FROM users WHERE username = ?");
$stmt->execute([$username]);
if ($stmt->fetch()) {
    jsonResponse(400, '用户名已被注册');
}

// Handle invite code
$invitedBy = null;
if ($inviteCode !== '') {
    $stmt = $db->prepare("SELECT id FROM users WHERE invite_code = ?");
    $stmt->execute([$inviteCode]);
    $inviter = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($inviter) {
        $invitedBy = $inviter['id'];
    } else {
        jsonResponse(400, '邀请码无效');
    }
}

// Generate unique invite code
do {
    $code = generateInviteCode();
    $stmt = $db->prepare("SELECT id FROM users WHERE invite_code = ?");
    $stmt->execute([$code]);
} while ($stmt->fetch());

$passwordHash = password_hash($password, PASSWORD_BCRYPT);

$db->beginTransaction();
try {
    $stmt = $db->prepare("INSERT INTO users (username, password_hash, invite_code, invited_by) VALUES (?, ?, ?, ?)");
    $stmt->execute([$username, $passwordHash, $code, $invitedBy]);
    $userId = $db->lastInsertId();

    // Create user_progress
    $stmt = $db->prepare("INSERT INTO user_progress (user_id) VALUES (?)");
    $stmt->execute([$userId]);

    // Reward inviter: 5 chances
    if ($invitedBy) {
        $stmt = $db->prepare("INSERT INTO lottery_chances (user_id, source, source_id) VALUES (?, 'invite', ?)");
        for ($i = 0; $i < 5; $i++) {
            $stmt->execute([$invitedBy, $userId]);
        }
    }

    // Generate token
    $token = generateToken();
    $stmt = $db->prepare("INSERT INTO auth_tokens (user_id, token, expires_at) VALUES (?, ?, datetime('now','localtime','+7 days'))");
    $stmt->execute([$userId, $token]);

    $db->commit();

    jsonResponse(0, '注册成功', [
        'token' => $token,
        'user' => [
            'id' => $userId,
            'username' => $username,
            'invite_code' => $code,
            'vip_level' => 0,
        ]
    ]);
} catch (Exception $e) {
    $db->rollBack();
    jsonResponse(500, '注册失败，请重试');
}
