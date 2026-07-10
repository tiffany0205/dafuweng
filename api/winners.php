<?php
/**
 * GET /api/winners
 * 中奖名单（活动结束后管理员结算生成）
 */
$db = getDB();

$stmt = $db->query("
    SELECT w.rank, w.status, u.username, p.name AS prize_name
    FROM winners w
    JOIN users u ON u.id = w.user_id
    JOIN prizes p ON p.id = w.prize_id
    ORDER BY w.rank ASC
");
$winners = $stmt->fetchAll(PDO::FETCH_ASSOC);

// If no winners yet, show what it would look like based on current rankings
$wouldBe = [];
if (empty($winners)) {
    $rankings = $db->query("
        SELECT u.username, up.total_laps, up.current_position, up.total_cells
        FROM user_progress up
        JOIN users u ON u.id = up.user_id
        ORDER BY up.total_laps DESC, up.current_position DESC, up.last_move_at ASC
        LIMIT 10
    ")->fetchAll(PDO::FETCH_ASSOC);

    $prizes = $db->query("SELECT * FROM prizes ORDER BY rank_start")->fetchAll(PDO::FETCH_ASSOC);

    foreach ($rankings as $i => $row) {
        $rank = $i + 1;
        $prizeName = '';
        foreach ($prizes as $p) {
            if ($rank >= $p['rank_start'] && $rank <= $p['rank_end']) {
                $prizeName = $p['name'];
                break;
            }
        }
        $wouldBe[] = [
            'rank' => $rank,
            'username' => $row['username'],
            'prize_name' => $prizeName,
            'is_current' => true,
        ];
    }
}

jsonResponse(0, 'success', [
    'winners' => $winners,
    'current_top10' => $wouldBe,
]);
