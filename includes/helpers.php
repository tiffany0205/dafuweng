<?php
/**
 * 工具函数
 */

function jsonResponse(int $code, string $msg, $data = null): void {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['code' => $code, 'msg' => $msg, 'data' => $data], JSON_UNESCAPED_UNICODE);
    exit;
}

function getJsonInput(): array {
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

function generateInviteCode(int $len = 8): string {
    $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $code = '';
    for ($i = 0; $i < $len; $i++) {
        $code .= $chars[random_int(0, strlen($chars) - 1)];
    }
    return $code;
}

function generateToken(int $len = 64): string {
    return bin2hex(random_bytes($len / 2));
}

function getCellDisplay(int $position): int {
    return $position + 1; // 0-based to 1-based for display
}

function dispatchRoute(string $method, string $path): void {
    $routes = [
        // Pages
        'GET /'                  => 'pages/home.php',
        'GET /login'             => 'pages/login.php',
        'GET /register'          => 'pages/register.php',
        'GET /board'             => 'pages/board.php',
        'GET /leaderboard'       => 'pages/leaderboard.php',

        // API - Auth
        'POST /api/login'        => 'api/login.php',
        'POST /api/register'     => 'api/register.php',
        'POST /api/logout'       => 'api/logout.php',
        'GET /api/user'          => 'api/user.php',

        // API - Tasks
        'GET /api/tasks'         => 'api/tasks.php',
        'POST /api/checkin'      => 'api/checkin.php',
        'POST /api/recharge'     => 'api/recharge.php',
        'GET /api/invite-stats'  => 'api/invite-stats.php',

        // API - Chances
        'GET /api/chances'       => 'api/chances.php',

        // API - Board
        'GET /api/board'         => 'api/board.php',
        'POST /api/board/roll'   => 'api/board-roll.php',
        'POST /api/board/unfreeze' => 'api/board-unfreeze.php',

        // API - Leaderboard & Prizes
        'GET /api/leaderboard'   => 'api/leaderboard.php',
        'GET /api/prizes'        => 'api/prizes.php',
        'GET /api/winners'       => 'api/winners.php',
    ];

    $root = dirname(__DIR__); // project root
    $key = $method . ' ' . $path;
    if (isset($routes[$key])) {
        require $root . '/' . $routes[$key];
    } else {
        // Serve static assets
        if (preg_match('#^/assets/(.+)$#', $path, $m)) {
            $file = $root . '/assets/' . $m[1];
            if (file_exists($file)) {
                $ext = pathinfo($file, PATHINFO_EXTENSION);
                $mimes = ['css' => 'text/css', 'js' => 'application/javascript', 'png' => 'image/png', 'svg' => 'image/svg+xml'];
                header('Content-Type: ' . ($mimes[$ext] ?? 'application/octet-stream'));
                readfile($file);
                return;
            }
        }
        http_response_code(404);
        jsonResponse(404, 'Not Found');
    }
}
