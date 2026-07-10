<?php
/**
 * 幸运跳棋 - 入口路由
 *
 * 启动方式: php -S localhost:8080 index.php
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/auth.php';

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'];

// Normalize path
$uri = rtrim($uri, '/') ?: '/';

dispatchRoute($method, $uri);
