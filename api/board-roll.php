<?php
/**
 * POST /api/board/roll
 * 掷骰子走棋盘
 */
$user = requireAuth();
$db = getDB();
$userId = $user['id'];

// Check frozen
$stmt = $db->prepare("SELECT * FROM user_progress WHERE user_id = ?");
$stmt->execute([$userId]);
$progress = $stmt->fetch(PDO::FETCH_ASSOC);

if ($progress['is_frozen']) {
    jsonResponse(400, '你被冰冻了！请先消耗1次抽奖机会解冻');
}

// Check available chances
$stmt = $db->prepare("SELECT COUNT(*) FROM lottery_chances WHERE user_id = ? AND used = 0");
$stmt->execute([$userId]);
$available = (int)$stmt->fetchColumn();

if ($available < 1) {
    jsonResponse(400, '抽奖机会不足，请先完成任务获取机会');
}

// Load cell data
$cells = $db->query("SELECT * FROM board_cells ORDER BY position")->fetchAll(PDO::FETCH_ASSOC);
$cellMap = [];
foreach ($cells as $c) {
    $cellMap[$c['position']] = $c;
}

$diceMin = (int)getConfig('dice_min');
$diceMax = (int)getConfig('dice_max');
$dice = random_int($diceMin, $diceMax);

$currentPos = (int)$progress['current_position'];
$currentLap = (int)$progress['total_laps'];
$totalCells = (int)$progress['total_cells'];

// Calculate movement
$newPos = $currentPos + $dice;
$newLap = $currentLap;

// Handle crossing the start (position 35 -> position 0+)
if ($newPos > 35) {
    $newLap += intdiv($newPos, 36);
    $newPos = $newPos % 36;
}

$moved = ($newLap - $currentLap) * 36 + ($newPos - $currentPos);
$totalCells += $moved;

// Process cell effects
$effects = [];
$processedCells = [];
$maxChain = 5; // Prevent infinite loops
$chainCount = 0;

$db->beginTransaction();
try {
    // Consume 1 chance
    $stmt = $db->prepare("UPDATE lottery_chances SET used = 1, used_at = datetime('now','localtime') WHERE id = (SELECT id FROM lottery_chances WHERE user_id = ? AND used = 0 LIMIT 1)");
    $stmt->execute([$userId]);

    // Process landing cell (and any battery chains)
    $processPos = $newPos;
    $processLap = $newLap;
    $frozenActive = false;

    while ($chainCount < $maxChain) {
        $cell = $cellMap[$processPos];
        $cellType = $cell['cell_type'];
        $processedCells[] = ['position' => $processPos, 'type' => $cellType, 'desc' => $cell['reward_desc']];

        switch ($cellType) {
            case 'battery':
                $effects[] = ['type' => 'battery', 'msg' => '🔋 电池！额外前进2格'];
                $oldPos = $processPos;
                $processPos += 2;
                if ($processPos > 35) {
                    $processLap += intdiv($processPos, 36);
                    $processPos = $processPos % 36;
                }
                $extraMoved = ($processLap - $newLap) * 36 + ($processPos - $oldPos);
                $totalCells += ($extraMoved - 2); // Already counted 2 in the dice move? no—we haven't saved yet
                // Actually let me simplify: battery moves 2 more cells
                $totalCells += 2;
                $chainCount++;
                continue 2; // Process the new position

            case 'freeze':
                $frozenActive = true;
                $effects[] = ['type' => 'freeze', 'msg' => '❄️ 被冰冻了！需要消耗1次抽奖机会解冻'];
                break;

            case 'bomb':
                $effects[] = ['type' => 'bomb', 'msg' => '💣 炸弹！回到起点'];
                $processPos = 0;
                // lap stays the same
                break;

            case 'vip_upgrade':
                $stmt2 = $db->prepare("UPDATE users SET vip_level = vip_level + 1 WHERE id = ?");
                $stmt2->execute([$userId]);
                $effects[] = ['type' => 'vip_upgrade', 'msg' => '⭐ VIP等级+1'];
                break;

            case 'usdt_001':
                $effects[] = ['type' => 'reward', 'msg' => '💰 获得 0.01 USDT'];
                break;

            case 'php_50':
                $effects[] = ['type' => 'reward', 'msg' => '💰 获得 50 PHP'];
                break;

            case 'vnd_10000':
                $effects[] = ['type' => 'reward', 'msg' => '💰 获得 10000 VND'];
                break;

            default:
                // normal or start - nothing special
                if ($cellType === 'start') {
                    $effects[] = ['type' => 'normal', 'msg' => '📍 经过起点'];
                }
                break;
        }
        break; // Exit loop unless continue 2 (battery)
    }

    $finalPos = $processPos;
    $finalLap = $processLap;

    // Update progress
    $stmt = $db->prepare("UPDATE user_progress SET current_position = ?, total_laps = ?, total_cells = ?, is_frozen = ?, frozen_at = ?, last_move_at = datetime('now','localtime') WHERE user_id = ?");
    $stmt->execute([
        $finalPos,
        $finalLap,
        $totalCells,
        $frozenActive ? 1 : 0,
        $frozenActive ? date('Y-m-d H:i:s') : null,
        $userId
    ]);

    // Record move
    $stmt = $db->prepare("INSERT INTO move_records (user_id, dice, from_pos, to_pos, from_lap, to_lap, cell_type, result) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        $userId,
        $dice,
        $currentPos,
        $finalPos,
        $currentLap,
        $finalLap,
        $cellMap[$finalPos]['cell_type'],
        json_encode($effects, JSON_UNESCAPED_UNICODE)
    ]);

    $db->commit();

    // Get updated user
    $stmt = $db->prepare("SELECT vip_level FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $updatedUser = $stmt->fetch(PDO::FETCH_ASSOC);

    jsonResponse(0, "掷出了 {$dice} 点！", [
        'dice' => $dice,
        'from' => ['position' => $currentPos, 'lap' => $currentLap],
        'to' => ['position' => $finalPos, 'lap' => $finalLap],
        'total_cells' => $totalCells,
        'is_frozen' => $frozenActive,
        'vip_level' => $updatedUser['vip_level'],
        'effects' => $effects,
        'cells_triggered' => $processedCells,
    ]);
} catch (Exception $e) {
    $db->rollBack();
    jsonResponse(500, '操作失败: ' . $e->getMessage());
}
