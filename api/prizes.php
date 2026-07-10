<?php
/**
 * GET /api/prizes
 * 奖品列表 + 当前排名对应奖品
 */
$db = getDB();

$prizes = $db->query("SELECT * FROM prizes ORDER BY rank_start")->fetchAll(PDO::FETCH_ASSOC);

// If user is logged in, show their rank
$myRank = null;
$myPrize = null;
$user = getAuthUser();
if ($user) {
    $stmt = $db->query("
        SELECT user_id FROM user_progress
        ORDER BY total_laps DESC, current_position DESC, last_move_at ASC
    ");
    $rankings = $stmt->fetchAll(PDO::FETCH_COLUMN);
    $idx = array_search($user['id'], $rankings);
    if ($idx !== false) {
        $myRank = $idx + 1;
        foreach ($prizes as $p) {
            if ($myRank >= $p['rank_start'] && $myRank <= $p['rank_end']) {
                $myPrize = $p['name'];
                break;
            }
        }
    }
}

jsonResponse(0, 'success', [
    'prizes' => $prizes,
    'my_rank' => $myRank,
    'my_prize' => $myPrize,
]);
