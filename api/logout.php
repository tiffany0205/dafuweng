<?php
/**
 * POST /api/logout
 */
$user = requireAuth();
$db = getDB();
$header = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
preg_match('/^Bearer\s+(.+)$/i', $header, $m);
$token = $m[1] ?? '';
$stmt = $db->prepare("DELETE FROM auth_tokens WHERE token = ?");
$stmt->execute([$token]);
jsonResponse(0, '已退出');
