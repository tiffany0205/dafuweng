<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
    <title>注册 - 幸运跳棋</title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
<div class="auth-page">
    <div class="logo">🎯</div>
    <h2>注册账号</h2>
    <form class="auth-form" id="registerForm">
        <input type="text" id="regUsername" placeholder="用户名 (3-20位字母数字)" required autocomplete="username">
        <input type="password" id="regPassword" placeholder="密码 (至少6位)" required autocomplete="new-password">
        <input type="text" id="regInviteCode" placeholder="邀请码 (选填)">
        <button type="submit" class="btn btn-primary btn-block">注 册</button>
        <div class="switch-link">
            已有账号？<a href="/login">立即登录</a>
        </div>
    </form>
</div>
<script src="/assets/js/app.js"></script>
</body>
</html>
