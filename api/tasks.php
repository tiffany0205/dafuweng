<?php
/**
 * GET /api/tasks
 * 返回任务列表 + 各任务完成状态
 */
$user = requireAuth();
$db = getDB();
$userId = $user['id'];
$today = date('Y-m-d');

// 1. Check-in status
$stmt = $db->prepare("SELECT id FROM check_in_records WHERE user_id = ? AND check_date = ?");
$stmt->execute([$userId, $today]);
$checkedIn = (bool)$stmt->fetch();

// Current streak
$stmt = $db->prepare("SELECT streak_day, chances_awarded FROM check_in_records WHERE user_id = ? ORDER BY check_date DESC LIMIT 1");
$stmt->execute([$userId]);
$lastCheckin = $stmt->fetch(PDO::FETCH_ASSOC);

$streakDay = 0;
$lastCheckinDate = null;
if ($lastCheckin) {
    $streakDay = $lastCheckin['streak_day'];
}

// Next streak bonus
$streakBonusDay = (int)getConfig('streak_bonus_day');
$daysToBonus = $streakBonusDay - ($streakDay % $streakBonusDay);

// 2. Recharge - total recharged
$stmt = $db->prepare("SELECT COALESCE(SUM(amount), 0) FROM recharge_records WHERE user_id = ?");
$stmt->execute([$userId]);
$totalRecharged = (float)$stmt->fetchColumn();

$threshold = (int)getConfig('recharge_threshold');
$chancesPerUnit = (int)getConfig('recharge_chances');

// 3. Invite stats
$stmt = $db->prepare("SELECT COUNT(*) FROM users WHERE invited_by = ?");
$stmt->execute([$userId]);
$invitedCount = (int)$stmt->fetchColumn();

$inviteChancesPer = (int)getConfig('invite_chances');

// 4. Friend recharge
$stmt = $db->prepare("SELECT COUNT(*) FROM lottery_chances WHERE user_id = ? AND source = 'friend_recharge'");
$stmt->execute([$userId]);
$friendRechargeChances = (int)$stmt->fetchColumn();

// 5. Available chances
$stmt = $db->prepare("SELECT COUNT(*) FROM lottery_chances WHERE user_id = ? AND used = 0");
$stmt->execute([$userId]);
$availableChances = (int)$stmt->fetchColumn();

// 6. Total earned chances by source
$stmt = $db->prepare("
    SELECT source, COUNT(*) as cnt FROM lottery_chances
    WHERE user_id = ? GROUP BY source
");
$stmt->execute([$userId]);
$chancesBySource = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $chancesBySource[$row['source']] = (int)$row['cnt'];
}

jsonResponse(0, 'success', [
    'available_chances' => $availableChances,
    'chances_by_source' => $chancesBySource,
    'tasks' => [
        [
            'key' => 'checkin',
            'name' => '每日签到',
            'description' => "签到得{$inviteChancesPer}次机会，连续{$streakBonusDay}天得10次",
            'completed_today' => $checkedIn,
            'streak_day' => $streakDay,
            'next_bonus_in_days' => $daysToBonus,
        ],
        [
            'key' => 'recharge',
            'name' => '充值任务',
            'description' => "每充值{$threshold} USDT 送 {$chancesPerUnit} 次抽奖机会",
            'total_recharged' => $totalRecharged,
            'chances_earned' => $chancesBySource['recharge'] ?? 0,
        ],
        [
            'key' => 'invite',
            'name' => '邀请好友',
            'description' => "每邀请1位好友注册 送 {$inviteChancesPer} 次抽奖机会",
            'invited_count' => $invitedCount,
            'chances_earned' => $chancesBySource['invite'] ?? 0,
        ],
        [
            'key' => 'friend_recharge',
            'name' => '好友首充奖励',
            'description' => '好友首次充值达标 你获得10次抽奖机会',
            'chances_earned' => $chancesBySource['friend_recharge'] ?? 0,
        ],
    ],
]);
