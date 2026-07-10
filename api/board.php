<?php
/**
 * GET /api/board
 * 获取棋盘状态 + 格子信息
 */
$user = requireAuth();
$db = getDB();
$userId = $user['id'];

// User progress
$stmt = $db->prepare("SELECT * FROM user_progress WHERE user_id = ?");
$stmt->execute([$userId]);
$progress = $stmt->fetch(PDO::FETCH_ASSOC);

// Available chances
$stmt = $db->prepare("SELECT COUNT(*) FROM lottery_chances WHERE user_id = ? AND used = 0");
$stmt->execute([$userId]);
$chances = (int)$stmt->fetchColumn();

// All cells
$cells = $db->query("SELECT * FROM board_cells ORDER BY position")->fetchAll(PDO::FETCH_ASSOC);

// Recent moves (last 5)
$stmt = $db->prepare("
    SELECT dice, from_pos, to_pos, from_lap, to_lap, cell_type, result, created_at
    FROM move_records
    WHERE user_id = ?
    ORDER BY created_at DESC LIMIT 5
");
$stmt->execute([$userId]);
$recentMoves = $stmt->fetchAll(PDO::FETCH_ASSOC);

jsonResponse(0, 'success', [
    'chances' => $chances,
    'progress' => [
        'current_position' => (int)$progress['current_position'],
        'total_laps' => (int)$progress['total_laps'],
        'total_cells' => (int)$progress['total_cells'],
        'is_frozen' => (bool)$progress['is_frozen'],
    ],
    'cells' => $cells,
    'recent_moves' => $recentMoves,
]);
