<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
    <title>登录 - 幸运跳棋</title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
<div class="auth-page">
    <div class="logo">🎯</div>
    <h2>幸运跳棋 · Lucky Jump</h2>
    <form class="auth-form" id="loginForm">
        <input type="text" id="loginUsername" placeholder="用户名" required autocomplete="username">
        <input type="password" id="loginPassword" placeholder="密码" required autocomplete="current-password">
        <button type="submit" class="btn btn-primary btn-block">登 录</button>
        <div class="switch-link">
            还没有账号？<a href="/register">立即注册</a>
        </div>
    </form>
</div>
<script src="/assets/js/app.js"></script>
</body>
</html>
