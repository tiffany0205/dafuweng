<?php
/**
 * GET /api/chances
 * 查询用户的抽奖机会明细
 */
$user = requireAuth();
$db = getDB();
$userId = $user['id'];

$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 20;
$offset = ($page - 1) * $perPage;

// Total counts
$stmt = $db->prepare("SELECT COUNT(*) FROM lottery_chances WHERE user_id = ? AND used = 0");
$stmt->execute([$userId]);
$available = (int)$stmt->fetchColumn();

$stmt = $db->prepare("SELECT COUNT(*) FROM lottery_chances WHERE user_id = ? AND used = 1");
$stmt->execute([$userId]);
$used = (int)$stmt->fetchColumn();

// Detail list
$stmt = $db->prepare("
    SELECT id, source, used, created_at, used_at
    FROM lottery_chances
    WHERE user_id = ?
    ORDER BY created_at DESC
    LIMIT ? OFFSET ?
");
$stmt->execute([$userId, $perPage, $offset]);
$list = $stmt->fetchAll(PDO::FETCH_ASSOC);

$sourceNames = [
    'checkin' => '签到',
    'recharge' => '充值',
    'invite' => '邀请好友',
    'friend_recharge' => '好友首充',
    'unfreeze' => '解冻消耗',
    'roll' => '掷骰子消耗',
];

foreach ($list as &$item) {
    $item['source_name'] = $sourceNames[$item['source']] ?? $item['source'];
    $item['used'] = (bool)$item['used'];
}

jsonResponse(0, 'success', [
    'available' => $available,
    'used' => $used,
    'list' => $list,
    'page' => $page,
]);
