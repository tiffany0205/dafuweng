<?php
/**
 * POST /api/recharge
 * Body: {"amount": 25}
 * 每10 USDT = 10次抽奖机会
 */
$user = requireAuth();
$input = getJsonInput();
$amount = floatval($input['amount'] ?? 0);

if ($amount < (int)getConfig('recharge_threshold')) {
    jsonResponse(400, '充值金额至少 ' . getConfig('recharge_threshold') . ' USDT');
}

$threshold = (int)getConfig('recharge_threshold'); // 10
$chancesPerUnit = (int)getConfig('recharge_chances'); // 10
$units = intdiv((int)$amount, $threshold);
$chancesAwarded = $units * $chancesPerUnit;

$db = getDB();
$userId = $user['id'];

$db->beginTransaction();
try {
    // Record recharge
    $stmt = $db->prepare("INSERT INTO recharge_records (user_id, amount, chances_awarded) VALUES (?, ?, ?)");
    $stmt->execute([$userId, $amount, $chancesAwarded]);
    $recordId = $db->lastInsertId();

    // Award chances to user
    $stmt = $db->prepare("INSERT INTO lottery_chances (user_id, source, source_id) VALUES (?, 'recharge', ?)");
    for ($i = 0; $i < $chancesAwarded; $i++) {
        $stmt->execute([$userId, $recordId]);
    }

    // Friend recharge bonus: if this user was invited, reward inviter (first recharge only)
    $invitedBy = $user['invited_by'];
    if ($invitedBy) {
        // Check if this is the user's first recharge
        $stmt = $db->prepare("SELECT COUNT(*) FROM recharge_records WHERE user_id = ?");
        $stmt->execute([$userId]);
        $rechargeCount = $stmt->fetchColumn();

        if ($rechargeCount == 1) {
            // First recharge - give inviter 10 chances
            $friendChances = (int)getConfig('friend_recharge_chances');
            $stmt = $db->prepare("INSERT INTO lottery_chances (user_id, source, source_id) VALUES (?, 'friend_recharge', ?)");
            for ($i = 0; $i < $friendChances; $i++) {
                $stmt->execute([$invitedBy, $recordId]);
            }
        }
    }

    $db->commit();

    jsonResponse(0, "充值成功！获得 {$chancesAwarded} 次抽奖机会", [
        'amount' => $amount,
        'chances_awarded' => $chancesAwarded,
    ]);
} catch (Exception $e) {
    $db->rollBack();
    jsonResponse(500, '充值失败');
}
