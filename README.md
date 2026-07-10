# 幸运跳棋 · Lucky Jump

## 活动简介

用户通过完成任务（签到、充值、邀请好友）获取抽奖机会，消耗机会在 36 格棋盘上掷骰子前进。棋盘上分布着奖励和陷阱，排行榜实时竞争，活动结束后前 10 名赢取奖品。

---

## 部署方式

### 方式一：PHP 内置服务器（快速启动）

```bash
cd 项目目录
php -S 0.0.0.0:8080 index.php
```

浏览器打开 `http://你的IP:8080` 即可。

### 方式二：Nginx

```nginx
server {
    listen 80;
    server_name 你的域名;
    root /path/to/dafuweng;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
```

### 方式三：Apache

把整个文件夹放到 Apache 的 `DocumentRoot` 下，确保 `.htaccess` 开启 URL 重写：

```
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ index.php [QSA,L]
```

---

## 数据库说明

`activity.db` 文件存放在 `data/` 目录下，**首次访问时自动创建**，无需手动导入。包含以下内容：

- 所有业务表结构自动建表
- 36 格棋盘数据自动填充
- 奖品配置自动填充
- 活动参数自动填充

> `activity.db` 未提交到 Git，每个环境首次运行自动生成。

---

## 访问地址

| 页面 | 地址 | 说明 |
|------|------|------|
| 注册 | `/register` | 注册账号，可填邀请码 |
| 登录 | `/login` | 登录后进入首页 |
| 首页 | `/` | 任务面板 + 机会余额 + 快捷入口 |
| 棋盘 | `/board` | 36 格棋盘游戏 |
| 排行榜 | `/leaderboard` | 实时排名 + 奖品展示 |

---

## 功能说明

### 1. 账号系统

- 注册需要用户名 + 密码，邀请码选填
- 注册后自动生成唯一邀请码
- 使用邀请码注册，邀请人与被邀请人建立绑定

### 2. 任务系统

| 任务 | 规则 | 奖励 |
|------|------|------|
| 每日签到 | 每天 1 次，不可补签 | 5 次抽奖机会 |
| 连续签到 | 每连续签到满 7 天 | 当天得 10 次机会 |
| 充值 | 每充值 10 USDT | 10 次机会 |
| 邀请好友 | 好友通过你的邀请码注册 | 5 次机会 |
| 好友首充 | 被邀请好友首次充值 ≥10U | 10 次机会（仅首次） |

> 断签后重新从第 1 天开始计算。

### 3. 棋盘游戏（36 格）

消耗 1 次机会掷骰子（1-6 随机），落到不同格子触发不同效果：

| 格子类型 | 效果 |
|----------|------|
| 🏁 起点 | 经过/到达无特殊效果 |
| 💰 奖励格 | 0.01 USDT / 50 PHP / 10000 VND |
| ⭐ VIP 升级 | VIP 等级 +1 |
| 🔋 电池 | 额外前进 2 格（可连锁触发） |
| ❄️ 冰冻 | 无法掷骰子，需消耗 1 次机会解冻 |
| 💣 炸弹 | 回到起点，圈数不变 |

### 4. 排行榜

- 按 圈数 > 格子数 > 到达时间 排序
- 展示前 20 名
- 每个名次旁标注对应奖品

### 5. 活动奖品

| 排名 | 奖品 |
|------|------|
| 第 1 名 | iPhone 16 Pro |
| 第 2 名 | 500 USDT |
| 第 3 名 | 400 USDT |
| 第 4 名 | 300 USDT |
| 第 5 名 | 200 USDT |
| 第 6-10 名 | 100 USDT |

---

## 项目结构

```
├── index.php              # 入口路由
├── config.php             # 数据库 + 种子数据
├── includes/
│   ├── helpers.php        # 工具函数 + 路由
│   └── auth.php           # Token 认证
├── api/                   # 后端 API
│   ├── register.php       # 注册
│   ├── login.php          # 登录
│   ├── user.php           # 用户信息
│   ├── tasks.php          # 任务列表
│   ├── checkin.php        # 签到
│   ├── recharge.php       # 充值
│   ├── invite-stats.php   # 邀请统计
│   ├── chances.php        # 机会明细
│   ├── board.php          # 棋盘状态
│   ├── board-roll.php     # 掷骰子
│   ├── board-unfreeze.php # 解冻
│   ├── leaderboard.php    # 排行榜
│   ├── prizes.php         # 奖品
│   └── winners.php        # 中奖名单
├── pages/                 # 前端页面
│   ├── login.php
│   ├── register.php
│   ├── home.php
│   ├── board.php
│   └── leaderboard.php
├── assets/
│   ├── css/style.css
│   └── js/app.js
└── data/                  # SQLite 数据库（自动创建）
```

---

## 常见问题

**Q: 怎么修改奖品或任务奖励？**

编辑 `config.php` 中的种子数据，或直接修改 `activity_config` 表。

**Q: 活动结束怎么结算排名？**

调用排行榜 API 获取前 10 名，然后向 `winners` 表写入中奖记录。

**Q: 怎么重置活动？**

删除 `data/activity.db` 文件，下次访问自动重建。
