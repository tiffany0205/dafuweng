<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
    <title>排行榜 - 幸运跳棋</title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>

<div class="header">
    <h1>🏆 排行榜</h1>
    <div class="nav-links">
        <a href="/">首页</a>
        <a href="/board">棋盘</a>
        <a href="/leaderboard" class="active">排行</a>
        <a href="#" id="logoutBtn">退出</a>
    </div>
</div>

<!-- My Rank -->
<div id="myRankDisplay" style="text-align:center;padding:12px;font-size:14px;color:var(--text-muted)">
    登录后查看你的排名
</div>

<!-- Prize Info -->
<div class="card" id="myPrizeInfo" style="text-align:center;font-size:14px"></div>

<!-- Prize Grid -->
<div class="card">
    <div class="section-title">🎁 奖品设置</div>
    <div class="prizes-grid" id="prizesGrid"></div>
</div>

<!-- Leaderboard -->
<div class="section-title" style="padding:12px 16px 4px">📊 实时排名 (Top 20)</div>
<div class="lb-list" id="lbList"></div>

<script src="/assets/js/app.js"></script>
</body>
</html>
