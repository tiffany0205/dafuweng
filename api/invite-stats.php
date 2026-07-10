<?php
/**
 * GET /api/invite-stats
 * 查询用户的邀请统计
 */
$user = requireAuth();
$db = getDB();
$userId = $user['id'];

// Invited count
$stmt = $db->prepare("SELECT COUNT(*) FROM users WHERE invited_by = ?");
$stmt->execute([$userId]);
$invitedCount = (int)$stmt->fetchColumn();

// Chances earned from invites
$stmt = $db->prepare("SELECT COUNT(*) FROM lottery_chances WHERE user_id = ? AND source = 'invite'");
$stmt->execute([$userId]);
$inviteChances = (int)$stmt->fetchColumn();

// Chances earned from friend recharge
$stmt = $db->prepare("SELECT COUNT(*) FROM lottery_chances WHERE user_id = ? AND source = 'friend_recharge'");
$stmt->execute([$userId]);
$friendRechargeChances = (int)$stmt->fetchColumn();

jsonResponse(0, 'success', [
    'invite_code' => $user['invite_code'],
    'invite_url' => APP_URL . '/register?code=' . $user['invite_code'],
    'invited_count' => $invitedCount,
    'invite_chances_earned' => $inviteChances,
    'friend_recharge_chances_earned' => $friendRechargeChances,
]);
