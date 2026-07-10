<?php
/**
 * GET /api/leaderboard
 * 实时排行榜 Top 20，每个名次带上对应奖品
 */
$db = getDB();

$rankings = $db->query("
    SELECT u.id, u.username, u.vip_level,
           up.current_position, up.total_laps, up.total_cells,
           up.last_move_at, up.is_frozen
    FROM user_progress up
    JOIN users u ON u.id = up.user_id
    ORDER BY up.total_laps DESC, up.current_position DESC, up.last_move_at ASC
    LIMIT 20
")->fetchAll(PDO::FETCH_ASSOC);

// Load prizes
$prizes = $db->query("SELECT * FROM prizes ORDER BY rank_start")->fetchAll(PDO::FETCH_ASSOC);

// Map rank to prize
function getPrizeForRank(int $rank, array $prizes): ?string {
    foreach ($prizes as $p) {
        if ($rank >= $p['rank_start'] && $rank <= $p['rank_end']) {
            return $p['name'];
        }
    }
    return null;
}

$list = [];
foreach ($rankings as $i => $row) {
    $rank = $i + 1;
    $list[] = [
        'rank' => $rank,
        'username' => $row['username'],
        'vip_level' => $row['vip_level'],
        'current_position' => getCellDisplay($row['current_position']),
        'total_laps' => $row['total_laps'],
        'total_cells' => $row['total_cells'],
        'is_frozen' => (bool)$row['is_frozen'],
        'prize' => getPrizeForRank($rank, $prizes),
        'last_move_at' => $row['last_move_at'],
    ];
}

jsonResponse(0, 'success', [
    'list' => $list,
    'prizes' => $prizes,
]);
