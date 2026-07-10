<?php
/**
 * 认证中间件
 */

function getAuthUser(): ?array {
    $header = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    if (!preg_match('/^Bearer\s+(.+)$/i', $header, $m)) {
        return null;
    }
    $token = $m[1];

    $db = getDB();
    $stmt = $db->prepare("
        SELECT u.* FROM auth_tokens t
        JOIN users u ON u.id = t.user_id
        WHERE t.token = ? AND t.expires_at > datetime('now','localtime')
    ");
    $stmt->execute([$token]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    return $user ?: null;
}

function requireAuth(): array {
    $user = getAuthUser();
    if (!$user) {
        jsonResponse(401, '请先登录');
    }
    return $user;
}
