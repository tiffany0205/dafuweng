<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
    <title>棋盘 - 幸运跳棋</title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>

<div class="header">
    <h1>🎯 棋盘游戏</h1>
    <div class="nav-links">
        <a href="/">首页</a>
        <a href="/board" class="active">棋盘</a>
        <a href="/leaderboard">排行</a>
        <a href="#" id="logoutBtn">退出</a>
    </div>
</div>

<div class="board-container">
    <!-- Stats -->
    <div class="board-header">
        <div class="board-stat">
            <div class="value" id="boardChances">0</div>
            <div class="label">剩余机会</div>
        </div>
        <div class="board-stat">
            <div class="value" id="boardLaps">0</div>
            <div class="label">圈数</div>
        </div>
        <div class="board-stat">
            <div class="value" id="boardPosition">1</div>
            <div class="label">当前格子</div>
        </div>
        <div class="board-stat">
            <div class="value" id="boardCells">0</div>
            <div class="label">累计格子</div>
        </div>
    </div>

    <!-- Board Grid -->
    <div class="board-grid" id="boardGrid"></div>

    <!-- Frozen state -->
    <div class="frozen-overlay" id="frozenArea" style="display:none">
        <p>❄️ 你被冰冻了！无法掷骰子</p>
        <button class="btn btn-warning btn-block mt-8" onclick="doUnfreeze()">消耗1次机会 · 解冻</button>
    </div>

    <!-- Dice -->
    <div class="dice-area" id="rollArea">
        <div class="dice-display" id="diceDisplay">?</div>
        <button class="btn btn-primary btn-block" id="rollBtn" onclick="doRoll()" style="font-size:18px;padding:14px">
            🎲 掷骰子（消耗1次机会）
        </button>
    </div>
</div>

<script src="/assets/js/app.js"></script>
</body>
</html>
