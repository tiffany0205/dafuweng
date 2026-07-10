<?php
/**
 * GET /api/user
 */
$user = requireAuth();
$db = getDB();

// Progress
$stmt = $db->prepare("SELECT * FROM user_progress WHERE user_id = ?");
$stmt->execute([$user['id']]);
$progress = $stmt->fetch(PDO::FETCH_ASSOC);

// Available chances
$stmt = $db->prepare("SELECT COUNT(*) FROM lottery_chances WHERE user_id = ? AND used = 0");
$stmt->execute([$user['id']]);
$chances = (int)$stmt->fetchColumn();

// Rank
$stmt = $db->query("
    SELECT user_id FROM user_progress
    ORDER BY total_laps DESC, current_position DESC, last_move_at ASC
");
$rankings = $stmt->fetchAll(PDO::FETCH_COLUMN);
$rank = array_search($user['id'], $rankings);
$rank = $rank !== false ? $rank + 1 : count($rankings) + 1;

jsonResponse(0, 'success', [
    'id' => $user['id'],
    'username' => $user['username'],
    'invite_code' => $user['invite_code'],
    'vip_level' => $user['vip_level'],
    'chances' => $chances,
    'progress' => $progress ? [
        'current_position' => $progress['current_position'],
        'total_laps' => $progress['total_laps'],
        'total_cells' => $progress['total_cells'],
        'is_frozen' => (bool)$progress['is_frozen'],
    ] : null,
    'rank' => $rank,
]);
