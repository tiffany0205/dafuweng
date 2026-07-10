/**
 * Lucky Jump - App JS
 */

const API_BASE = '';

// ===== Auth =====
function getToken() { return localStorage.getItem('token'); }
function setToken(t) { localStorage.setItem('token', t); }
function clearToken() { localStorage.removeItem('token'); }
function isLoggedIn() { return !!getToken(); }

// ===== API =====
async function api(path, options = {}) {
    const headers = { 'Content-Type': 'application/json' };
    const token = getToken();
    if (token) headers['Authorization'] = 'Bearer ' + token;
    const res = await fetch(API_BASE + path, { ...options, headers });
    return res.json();
}

// ===== Toast =====
function showToast(msg, type = 'info') {
    const t = document.createElement('div');
    t.className = 'toast ' + type;
    t.textContent = msg;
    document.body.appendChild(t);
    setTimeout(() => t.remove(), 2500);
}

// ===== Render helpers =====
function cellIcon(type) {
    const icons = {
        start: '🏁', normal: '', usdt_001: '💵', php_50: '💴',
        vnd_10000: '💶', freeze: '❄️', battery: '🔋',
        vip_upgrade: '⭐', bomb: '💣'
    };
    return icons[type] || '';
}

// ===== Board Rendering =====
function renderBoard(cells, currentPos) {
    const grid = document.getElementById('boardGrid');
    if (!grid) return;

    // Render in snake pattern: 6 rows, 6 cols
    // Row 1 (pos 0-5): left to right, Row 2 (pos 6-11): right to left, etc.
    grid.innerHTML = '';
    for (let row = 0; row < 6; row++) {
        for (let col = 0; col < 6; col++) {
            let pos;
            if (row % 2 === 0) {
                pos = row * 6 + col;
            } else {
                pos = row * 6 + (5 - col);
            }
            const cell = cells.find(c => c.position == pos) || { cell_type: 'normal' };
            const div = document.createElement('div');
            div.className = 'board-cell ' + cell.cell_type;
            if (pos === currentPos) div.classList.add('current');
            div.innerHTML = `
                <span class="cell-num">${pos + 1}</span>
                <span class="cell-icon">${cellIcon(cell.cell_type)}</span>
            `;
            if (cell.cell_type !== 'normal') {
                div.title = cell.reward_desc || '';
            }
            grid.appendChild(div);
        }
    }
}

// ===== Leaderboard Rendering =====
function renderLeaderboard(list, myId) {
    const container = document.getElementById('lbList');
    if (!container) return;
    container.innerHTML = '';

    if (list.length === 0) {
        container.innerHTML = '<div class="empty-state"><div class="icon">🏆</div>暂无排行数据</div>';
        return;
    }

    list.forEach(item => {
        const div = document.createElement('div');
        div.className = 'lb-item';
        if (item.rank === 1) div.classList.add('top-1');
        if (item.rank === 2) div.classList.add('top-2');
        if (item.rank === 3) div.classList.add('top-3');
        if (item.id == myId) div.classList.add('is-me');

        const prizeHtml = item.prize
            ? `<span class="lb-prize">🎁 ${item.prize}</span>`
            : '';

        div.innerHTML = `
            <div class="lb-rank">${item.rank}</div>
            <div class="lb-info">
                <div class="lb-name">${esc(item.username)} ${item.vip_level > 0 ? '⭐'.repeat(item.vip_level) : ''}</div>
                <div class="lb-stats">${item.total_laps}圈 · 第${item.current_position}格 · ${item.total_cells}格</div>
            </div>
            ${prizeHtml}
        `;
        container.appendChild(div);
    });
}

function esc(s) {
    const d = document.createElement('div');
    d.textContent = s;
    return d.innerHTML;
}

// ===== Page: Login =====
function initLogin() {
    const form = document.getElementById('loginForm');
    if (!form) return;
    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        const username = document.getElementById('loginUsername').value.trim();
        const password = document.getElementById('loginPassword').value;
        const btn = form.querySelector('button');

        btn.disabled = true;
        btn.textContent = '登录中...';
        const res = await api('/api/login', {
            method: 'POST',
            body: JSON.stringify({ username, password })
        });
        if (res.code === 0) {
            setToken(res.data.token);
            window.location.href = '/';
        } else {
            showToast(res.msg, 'error');
            btn.disabled = false;
            btn.textContent = '登录';
        }
    });
}

// ===== Page: Register =====
function initRegister() {
    const form = document.getElementById('registerForm');
    if (!form) return;

    // Pre-fill invite code from URL
    const params = new URLSearchParams(window.location.search);
    const code = params.get('code');
    if (code) {
        const input = document.getElementById('regInviteCode');
        if (input) input.value = code;
    }

    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        const username = document.getElementById('regUsername').value.trim();
        const password = document.getElementById('regPassword').value;
        const inviteCode = document.getElementById('regInviteCode').value.trim();
        const btn = form.querySelector('button');

        btn.disabled = true;
        btn.textContent = '注册中...';
        const res = await api('/api/register', {
            method: 'POST',
            body: JSON.stringify({ username, password, invite_code: inviteCode })
        });
        if (res.code === 0) {
            setToken(res.data.token);
            showToast('注册成功！', 'success');
            setTimeout(() => { window.location.href = '/'; }, 500);
        } else {
            showToast(res.msg, 'error');
            btn.disabled = false;
            btn.textContent = '注册';
        }
    });
}

// ===== Page: Home =====
async function initHome() {
    if (!isLoggedIn()) {
        window.location.href = '/login';
        return;
    }

    // Load user info
    const userRes = await api('/api/user');
    if (userRes.code !== 0) {
        clearToken();
        window.location.href = '/login';
        return;
    }

    const user = userRes.data;
    document.getElementById('homeUsername').textContent = user.username;
    document.getElementById('homeVip').textContent = '⭐'.repeat(user.vip_level) || 'VIP 0';
    document.getElementById('homeChances').textContent = user.chances;
    document.getElementById('homeRank').textContent = '#' + user.rank;
    document.getElementById('homeLaps').textContent = user.progress ? user.progress.total_laps : 0;
    document.getElementById('homePosition').textContent = user.progress ? user.progress.current_position + 1 : 1;

    // Load tasks
    const tasksRes = await api('/api/tasks');
    if (tasksRes.code === 0) {
        renderTasks(tasksRes.data);
    }
}

function renderTasks(data) {
    const container = document.getElementById('taskList');
    if (!container) return;

    const tasks = data.tasks;
    let html = '';

    // Check-in task
    const ci = tasks.find(t => t.key === 'checkin');
    html += `
    <div class="task-item">
        <div class="task-icon checkin">📅</div>
        <div class="task-body">
            <div class="task-name">每日签到</div>
            <div class="task-desc">${ci.completed_today ? '✅ 今日已签到 · 连续' + ci.streak_day + '天' : ci.streak_day > 0 ? '连续签到' + ci.streak_day + '天 · 再签' + ci.next_bonus_in_days + '天得10次' : '签到得5次机会，连续7天得10次'}</div>
        </div>
        <div class="task-action">
            ${ci.completed_today
                ? '<span style="color:var(--success);font-size:13px">已签到</span>'
                : '<button class="btn btn-primary btn-sm" onclick="doCheckin()">签到</button>'}
        </div>
    </div>`;

    // Recharge task
    const rc = tasks.find(t => t.key === 'recharge');
    html += `
    <div class="task-item">
        <div class="task-icon recharge">💳</div>
        <div class="task-body">
            <div class="task-name">充值任务</div>
            <div class="task-desc">
                已充值 ${rc.total_recharged} USDT · 每10U得10次
                <div style="background:var(--border);border-radius:4px;height:4px;margin-top:4px">
                    <div style="background:var(--primary);height:100%;border-radius:4px;width:${Math.min(100, (rc.total_recharged % 10) / 10 * 100)}%"></div>
                </div>
            </div>
        </div>
        <div class="task-action">
            <button class="btn btn-primary btn-sm" onclick="doRecharge()">充值</button>
        </div>
    </div>`;

    // Invite task
    const iv = tasks.find(t => t.key === 'invite');
    html += `
    <div class="task-item">
        <div class="task-icon invite">👥</div>
        <div class="task-body">
            <div class="task-name">邀请好友</div>
            <div class="task-desc">已邀请 ${iv.invited_count} 人 · 每邀请1人得5次</div>
        </div>
        <div class="task-action">
            <button class="btn btn-warning btn-sm" onclick="doInvite()">邀请</button>
        </div>
    </div>`;

    // Friend recharge
    const fr = tasks.find(t => t.key === 'friend_recharge');
    html += `
    <div class="task-item">
        <div class="task-icon friend">🎁</div>
        <div class="task-body">
            <div class="task-name">好友首充奖励</div>
            <div class="task-desc">好友首次充值达标得10次 · 已获得 ${fr.chances_earned} 次</div>
        </div>
    </div>`;

    container.innerHTML = html;
}

async function doCheckin() {
    const res = await api('/api/checkin', { method: 'POST' });
    if (res.code === 0) {
        showToast(res.msg, 'success');
        setTimeout(() => initHome(), 500);
    } else {
        showToast(res.msg, 'error');
    }
}

async function doRecharge() {
    const amount = prompt('请输入充值金额 (USDT)，至少10 USDT：', '10');
    if (!amount) return;
    const res = await api('/api/recharge', {
        method: 'POST',
        body: JSON.stringify({ amount: parseFloat(amount) })
    });
    if (res.code === 0) {
        showToast(res.msg, 'success');
        setTimeout(() => initHome(), 500);
    } else {
        showToast(res.msg, 'error');
    }
}

async function doInvite() {
    const res = await api('/api/invite-stats');
    if (res.code === 0) {
        const url = res.data.invite_url;
        try {
            await navigator.clipboard.writeText(url);
            showToast('邀请链接已复制！发送给好友注册即可', 'success');
        } catch {
            const input = prompt('复制此链接发送给好友：', url);
        }
    }
}

// ===== Page: Board =====
let boardState = null;

async function initBoard() {
    if (!isLoggedIn()) { window.location.href = '/login'; return; }

    const res = await api('/api/board');
    if (res.code !== 0) { showToast(res.msg, 'error'); return; }

    boardState = res.data;
    renderBoard(boardState.cells, boardState.progress.current_position);

    document.getElementById('boardChances').textContent = boardState.chances;
    document.getElementById('boardLaps').textContent = boardState.progress.total_laps;
    document.getElementById('boardPosition').textContent = boardState.progress.current_position + 1;
    document.getElementById('boardCells').textContent = boardState.progress.total_cells;

    // Frozen state
    const frozenArea = document.getElementById('frozenArea');
    const rollBtn = document.getElementById('rollBtn');
    if (boardState.progress.is_frozen) {
        frozenArea.style.display = 'block';
        rollBtn.style.display = 'none';
    } else {
        frozenArea.style.display = 'none';
        rollBtn.style.display = 'inline-block';
    }
}

async function doRoll() {
    const diceEl = document.getElementById('diceDisplay');
    const rollBtn = document.getElementById('rollBtn');

    rollBtn.disabled = true;
    diceEl.classList.add('rolling');

    const res = await api('/api/board/roll', { method: 'POST' });

    setTimeout(async () => {
        diceEl.classList.remove('rolling');

        if (res.code === 0) {
            const data = res.data;
            diceEl.textContent = data.dice;
            showRollResult(data);
            // Refresh board
            setTimeout(() => initBoard(), 300);
        } else {
            diceEl.textContent = '?';
            showToast(res.msg, 'error');
        }
        rollBtn.disabled = false;
    }, 500);
}

function showRollResult(data) {
    const effectsHtml = data.effects.map(e => `<div class="effect">${e.msg}</div>`).join('');

    const overlay = document.createElement('div');
    overlay.className = 'modal-overlay';
    overlay.innerHTML = `
        <div class="modal">
            <div class="modal-icon">🎲</div>
            <h3>掷出了 ${data.dice} 点！</h3>
            <p class="text-muted">从第 ${data.from.position + 1} 格 → 第 ${data.to.position + 1} 格 (${data.to.lap}圈)</p>
            ${effectsHtml ? '<div class="modal-effects">' + effectsHtml + '</div>' : ''}
            ${data.is_frozen ? '<p style="color:#38bdf8;margin-top:8px">❄️ 你被冰冻了！需要解冻后才能继续</p>' : ''}
            <button class="btn btn-primary btn-block" onclick="this.closest('.modal-overlay').remove()">知道了</button>
        </div>
    `;
    document.body.appendChild(overlay);
    overlay.addEventListener('click', (e) => { if (e.target === overlay) overlay.remove(); });
}

async function doUnfreeze() {
    const res = await api('/api/board/unfreeze', { method: 'POST' });
    if (res.code === 0) {
        showToast('解冻成功！可以继续掷骰子了', 'success');
        setTimeout(() => initBoard(), 300);
    } else {
        showToast(res.msg, 'error');
    }
}

// ===== Page: Leaderboard =====
async function initLeaderboard() {
    // Load leaderboard
    const res = await api('/api/leaderboard');
    if (res.code === 0) {
        renderLeaderboard(res.data.list);
    }

    // Load prizes
    const prizesRes = await api('/api/prizes');
    if (prizesRes.code === 0) {
        renderPrizes(prizesRes.data);
    }

    // My rank (from user API if logged in)
    if (isLoggedIn()) {
        const userRes = await api('/api/user');
        if (userRes.code === 0) {
            document.getElementById('myRankDisplay').textContent = '我的排名: #' + userRes.data.rank;
        }
    }
}

function renderPrizes(data) {
    const container = document.getElementById('prizesGrid');
    if (!container) return;

    let html = '';
    const rankClasses = ['', 'rank-1', 'rank-1', 'rank-1', 'rank-1', 'rank-1'];
    data.prizes.forEach((p, i) => {
        const rankLabel = p.rank_start === p.rank_end
            ? `第${p.rank_start}名`
            : `第${p.rank_start}-${p.rank_end}名`;
        html += `
        <div class="prize-card">
            <div class="prize-rank">${i === 0 ? '🥇' : i === 1 ? '🥈' : i === 2 ? '🥉' : '🏅'} ${rankLabel}</div>
            <div class="prize-name">${p.name}</div>
        </div>`;
    });
    container.innerHTML = html;

    if (data.my_rank) {
        const el = document.getElementById('myPrizeInfo');
        if (el) {
            el.innerHTML = data.my_prize
                ? `你当前排名 <strong>#${data.my_rank}</strong>，如果现在结束将获得 <strong style="color:var(--gold)">${data.my_prize}</strong>`
                : `你当前排名 <strong>#${data.my_rank}</strong>，暂未进入获奖范围（需前10名）`;
        }
    }
}

// ===== Init =====
document.addEventListener('DOMContentLoaded', () => {
    const path = window.location.pathname;

    if (path === '/login') initLogin();
    else if (path === '/register') initRegister();
    else if (path === '/board') initBoard();
    else if (path === '/leaderboard') initLeaderboard();
    else initHome();

    // Logout handler
    const logoutBtn = document.getElementById('logoutBtn');
    if (logoutBtn) {
        logoutBtn.addEventListener('click', async (e) => {
            e.preventDefault();
            await api('/api/logout', { method: 'POST' });
            clearToken();
            window.location.href = '/login';
        });
    }
});
