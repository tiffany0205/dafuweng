<?php
/**
 * POST /api/checkin
 * 每日签到，含连续签到逻辑
 */
$user = requireAuth();
$db = getDB();
$userId = $user['id'];
$today = date('Y-m-d');

// Check if already checked in today
$stmt = $db->prepare("SELECT id FROM check_in_records WHERE user_id = ? AND check_date = ?");
$stmt->execute([$userId, $today]);
if ($stmt->fetch()) {
    jsonResponse(400, '今日已签到');
}

// Calculate streak
$yesterday = date('Y-m-d', strtotime('-1 day'));
$stmt = $db->prepare("SELECT streak_day FROM check_in_records WHERE user_id = ? ORDER BY check_date DESC LIMIT 1");
$stmt->execute([$userId]);
$lastRecord = $stmt->fetch(PDO::FETCH_ASSOC);

if ($lastRecord) {
    $stmt = $db->prepare("SELECT check_date FROM check_in_records WHERE user_id = ? ORDER BY check_date DESC LIMIT 1");
    $stmt->execute([$userId]);
    $lastDate = $stmt->fetchColumn();

    if ($lastDate === $yesterday) {
        $streakDay = $lastRecord['streak_day'] + 1;
    } else {
        $streakDay = 1; // Broken streak
    }
} else {
    $streakDay = 1; // First ever check-in
}

// Calculate chances: 5 normal, 10 every 7th day
$streakBonusDay = (int)getConfig('streak_bonus_day'); // 7
$streakBonusChances = (int)getConfig('streak_bonus_chances'); // 10
$normalChances = (int)getConfig('checkin_chances'); // 5

$chancesAwarded = ($streakDay % $streakBonusDay === 0) ? $streakBonusChances : $normalChances;

$db->beginTransaction();
try {
    // Record check-in
    $stmt = $db->prepare("INSERT INTO check_in_records (user_id, check_date, streak_day, chances_awarded) VALUES (?, ?, ?, ?)");
    $stmt->execute([$userId, $today, $streakDay, $chancesAwarded]);

    // Award chances
    $stmt = $db->prepare("INSERT INTO lottery_chances (user_id, source, source_id) VALUES (?, 'checkin', ?)");
    $recordId = $db->lastInsertId();
    for ($i = 0; $i < $chancesAwarded; $i++) {
        $stmt->execute([$userId, $recordId]);
    }

    $db->commit();

    $msg = $chancesAwarded === 10
        ? "连续签到第{$streakDay}天！获得 {$chancesAwarded} 次抽奖机会"
        : "签到成功！获得 {$chancesAwarded} 次抽奖机会";

    jsonResponse(0, $msg, [
        'streak_day' => $streakDay,
        'chances_awarded' => $chancesAwarded,
    ]);
} catch (Exception $e) {
    $db->rollBack();
    jsonResponse(500, '签到失败');
}
