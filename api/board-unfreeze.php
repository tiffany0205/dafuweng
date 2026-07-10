<?php
/**
 * POST /api/board/unfreeze
 * 消耗1次抽奖机会解冻
 */
$user = requireAuth();
$db = getDB();
$userId = $user['id'];

$stmt = $db->prepare("SELECT * FROM user_progress WHERE user_id = ?");
$stmt->execute([$userId]);
$progress = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$progress['is_frozen']) {
    jsonResponse(400, '你没有被冰冻');
}

// Check available chances
$stmt = $db->prepare("SELECT COUNT(*) FROM lottery_chances WHERE user_id = ? AND used = 0");
$stmt->execute([$userId]);
$available = (int)$stmt->fetchColumn();

if ($available < 1) {
    jsonResponse(400, '抽奖机会不足，无法解冻');
}

$db->beginTransaction();
try {
    // Consume 1 chance
    $stmt = $db->prepare("UPDATE lottery_chances SET used = 1, used_at = datetime('now','localtime'), source = 'unfreeze' WHERE id = (SELECT id FROM lottery_chances WHERE user_id = ? AND used = 0 LIMIT 1)");
    $stmt->execute([$userId]);

    // Unfreeze
    $stmt = $db->prepare("UPDATE user_progress SET is_frozen = 0, frozen_at = NULL WHERE user_id = ?");
    $stmt->execute([$userId]);

    $db->commit();

    jsonResponse(0, '解冻成功！可以继续掷骰子了', [
        'current_position' => $progress['current_position'],
        'total_laps' => $progress['total_laps'],
    ]);
} catch (Exception $e) {
    $db->rollBack();
    jsonResponse(500, '解冻失败');
}
