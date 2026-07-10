<?php
/**
 * 数据库连接 + 初始化
 */

define('DB_PATH', __DIR__ . '/data/activity.db');
define('APP_URL', 'http://localhost:8080');

function getDB(): PDO {
    static $db = null;
    if ($db === null) {
        $db = new PDO('sqlite:' . DB_PATH);
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $db->exec('PRAGMA journal_mode=WAL');
        $db->exec('PRAGMA busy_timeout=5000');
        $db->exec('PRAGMA foreign_keys=ON');
        initDB($db);
    }
    return $db;
}

function initDB(PDO $db): void {
    $db->exec("
        CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            username VARCHAR(50) UNIQUE NOT NULL,
            password_hash VARCHAR(255) NOT NULL,
            invite_code VARCHAR(10) UNIQUE NOT NULL,
            invited_by INTEGER DEFAULT NULL,
            vip_level INTEGER DEFAULT 0,
            created_at DATETIME DEFAULT (datetime('now','localtime'))
        );

        CREATE TABLE IF NOT EXISTS auth_tokens (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            token VARCHAR(64) UNIQUE NOT NULL,
            expires_at DATETIME NOT NULL,
            created_at DATETIME DEFAULT (datetime('now','localtime')),
            FOREIGN KEY (user_id) REFERENCES users(id)
        );

        CREATE TABLE IF NOT EXISTS check_in_records (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            check_date DATE NOT NULL,
            streak_day INTEGER DEFAULT 1,
            chances_awarded INTEGER DEFAULT 0,
            created_at DATETIME DEFAULT (datetime('now','localtime')),
            UNIQUE(user_id, check_date)
        );

        CREATE TABLE IF NOT EXISTS recharge_records (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            amount DECIMAL(18,2) NOT NULL,
            chances_awarded INTEGER DEFAULT 0,
            created_at DATETIME DEFAULT (datetime('now','localtime'))
        );

        CREATE TABLE IF NOT EXISTS lottery_chances (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            source VARCHAR(30) NOT NULL,
            source_id INTEGER DEFAULT NULL,
            used INTEGER DEFAULT 0,
            used_at DATETIME DEFAULT NULL,
            created_at DATETIME DEFAULT (datetime('now','localtime'))
        );

        CREATE TABLE IF NOT EXISTS board_cells (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            position INTEGER NOT NULL,
            cell_type VARCHAR(20) NOT NULL DEFAULT 'normal',
            reward_desc VARCHAR(100) DEFAULT NULL
        );

        CREATE TABLE IF NOT EXISTS user_progress (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER UNIQUE NOT NULL,
            current_position INTEGER DEFAULT 0,
            total_laps INTEGER DEFAULT 0,
            total_cells INTEGER DEFAULT 0,
            is_frozen INTEGER DEFAULT 0,
            frozen_at DATETIME DEFAULT NULL,
            last_move_at DATETIME DEFAULT NULL,
            created_at DATETIME DEFAULT (datetime('now','localtime'))
        );

        CREATE TABLE IF NOT EXISTS move_records (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            dice INTEGER NOT NULL,
            from_pos INTEGER NOT NULL,
            to_pos INTEGER NOT NULL,
            from_lap INTEGER DEFAULT 0,
            to_lap INTEGER DEFAULT 0,
            cell_type VARCHAR(20) DEFAULT 'normal',
            result TEXT DEFAULT NULL,
            created_at DATETIME DEFAULT (datetime('now','localtime'))
        );

        CREATE TABLE IF NOT EXISTS prizes (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            rank_start INTEGER NOT NULL,
            rank_end INTEGER NOT NULL,
            name VARCHAR(100) NOT NULL
        );

        CREATE TABLE IF NOT EXISTS winners (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            prize_id INTEGER NOT NULL,
            rank INTEGER NOT NULL,
            status VARCHAR(20) DEFAULT 'pending',
            created_at DATETIME DEFAULT (datetime('now','localtime'))
        );

        CREATE TABLE IF NOT EXISTS activity_config (
            config_key VARCHAR(50) PRIMARY KEY,
            config_value TEXT NOT NULL
        );
    ");

    // Seed board cells (36 cells, position 0-35, displayed as 1-36)
    $count = $db->query("SELECT COUNT(*) FROM board_cells")->fetchColumn();
    if ($count == 0) {
        $cells = array_fill(0, 36, ['type' => 'normal', 'desc' => '']);
        $cells[0]  = ['type' => 'start',       'desc' => '起点'];
        $cells[3]  = ['type' => 'usdt_001',    'desc' => '+0.01 USDT'];
        $cells[5]  = ['type' => 'php_50',      'desc' => '+50 PHP'];
        $cells[7]  = ['type' => 'vnd_10000',   'desc' => '+10000 VND'];
        $cells[9]  = ['type' => 'freeze',      'desc' => '冰冻！需消耗1次机会解冻'];
        $cells[12] = ['type' => 'battery',     'desc' => '电池！额外前进2格'];
        $cells[15] = ['type' => 'vip_upgrade', 'desc' => 'VIP等级+1'];
        $cells[18] = ['type' => 'bomb',        'desc' => '炸弹！回到起点'];
        $cells[21] = ['type' => 'usdt_001',    'desc' => '+0.01 USDT'];
        $cells[24] = ['type' => 'freeze',      'desc' => '冰冻！需消耗1次机会解冻'];
        $cells[27] = ['type' => 'php_50',      'desc' => '+50 PHP'];
        $cells[30] = ['type' => 'vnd_10000',   'desc' => '+10000 VND'];
        $cells[33] = ['type' => 'battery',     'desc' => '电池！额外前进2格'];
        $cells[35] = ['type' => 'vip_upgrade', 'desc' => 'VIP等级+1'];

        $stmt = $db->prepare("INSERT INTO board_cells (position, cell_type, reward_desc) VALUES (?, ?, ?)");
        foreach ($cells as $pos => $cell) {
            $stmt->execute([$pos, $cell['type'], $cell['desc']]);
        }
    }

    // Seed prizes
    $pCount = $db->query("SELECT COUNT(*) FROM prizes")->fetchColumn();
    if ($pCount == 0) {
        $prizes = [
            [1, 1, 'iPhone 16 Pro'],
            [2, 2, '500 USDT'],
            [3, 3, '400 USDT'],
            [4, 4, '300 USDT'],
            [5, 5, '200 USDT'],
            [6, 10, '100 USDT'],
        ];
        $stmt = $db->prepare("INSERT INTO prizes (rank_start, rank_end, name) VALUES (?, ?, ?)");
        foreach ($prizes as $p) {
            $stmt->execute($p);
        }
    }

    // Seed config
    $cCount = $db->query("SELECT COUNT(*) FROM activity_config")->fetchColumn();
    if ($cCount == 0) {
        $configs = [
            ['checkin_chances', '5'],
            ['streak_bonus_day', '7'],
            ['streak_bonus_chances', '10'],
            ['recharge_threshold', '10'],
            ['recharge_chances', '10'],
            ['invite_chances', '5'],
            ['friend_recharge_chances', '10'],
            ['unfreeze_cost', '1'],
            ['roll_cost', '1'],
            ['dice_min', '1'],
            ['dice_max', '6'],
        ];
        $stmt = $db->prepare("INSERT OR IGNORE INTO activity_config (config_key, config_value) VALUES (?, ?)");
        foreach ($configs as $c) {
            $stmt->execute($c);
        }
    }
}

function getConfig(string $key): string {
    $db = getDB();
    $stmt = $db->prepare("SELECT config_value FROM activity_config WHERE config_key = ?");
    $stmt->execute([$key]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ? $row['config_value'] : '';
}
