<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
    <title>幸运跳棋 · Lucky Jump</title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>

<div class="header">
    <h1>🎯 幸运跳棋</h1>
    <div class="nav-links">
        <a href="/">首页</a>
        <a href="/board">棋盘</a>
        <a href="/leaderboard">排行</a>
        <a href="#" id="logoutBtn">退出</a>
    </div>
</div>

<!-- User Bar -->
<div class="user-bar">
    <div class="user-avatar" id="homeAvatar">👤</div>
    <div class="user-info">
        <div class="name" id="homeUsername">---</div>
        <div class="meta" id="homeVip">VIP 0</div>
    </div>
    <div class="user-rank" id="homeRank">#--</div>
</div>

<!-- Chances Badge -->
<div class="chances-badge">
    <div>
        <div class="label">可用抽奖机会</div>
        <div class="count" id="homeChances">0</div>
    </div>
    <div style="text-align:right">
        <div class="label">圈数 / 格子</div>
        <div style="font-size:18px;font-weight:700"><span id="homeLaps">0</span>圈 · 第<span id="homePosition">1</span>格</div>
    </div>
</div>

<!-- Quick Actions -->
<div style="padding:0 16px;display:flex;gap:10px">
    <a href="/board" class="btn btn-primary" style="flex:1">🎲 进入棋盘</a>
    <a href="/leaderboard" class="btn btn-warning" style="flex:1">🏆 排行榜</a>
</div>

<!-- Task List -->
<div class="card" style="margin-top:12px">
    <div class="section-title">📋 任务列表</div>
    <div id="taskList"></div>
</div>

<script src="/assets/js/app.js"></script>
</body>
</html>
