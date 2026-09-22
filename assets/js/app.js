async function api(path, data) {
    const res = await fetch('/ajax/' + path + '.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data || {}),
    });
    return res.json();
}

function notify(msg, type) {
    const box = document.createElement('div');
    box.className = 'mb-4 rounded-md px-4 py-2 text-sm flex items-center justify-between ' +
        (type === 'ok'
            ? 'bg-green-50 text-green-700 dark:bg-green-950 dark:text-green-300'
            : 'bg-red-50 text-red-700 dark:bg-red-950 dark:text-red-300');
    box.innerHTML = '<span></span><button type="button" class="ml-3 opacity-60 hover:opacity-100">✕</button>';
    box.querySelector('span').textContent = msg;
    box.querySelector('button').onclick = () => box.remove();
    const main = document.querySelector('main');
    if (main) main.prepend(box);
}

function flashReload(msg, url) {
    try { sessionStorage.setItem('flash', msg); } catch (e) {}
    window.location.href = url || window.location.href;
}

document.addEventListener('DOMContentLoaded', () => {
    try {
        const msg = sessionStorage.getItem('flash');
        if (msg) {
            sessionStorage.removeItem('flash');
            notify(msg, 'ok');
        }
    } catch (e) {}
});

function filterColumn(input) {
    const table = document.querySelector(input.dataset.table);
    const col = parseInt(input.dataset.filterCol, 10);
    const q = input.value.toLowerCase();
    table.querySelectorAll('tbody tr').forEach((tr) => {
        const cell = tr.children[col];
        if (!cell) return;
        tr.style.display = cell.textContent.toLowerCase().includes(q) ? '' : 'none';
    });
}

async function confirmDelete(area, id, msg) {
    if (!confirm(msg)) return;
    const res = await api(area + '/delete', { id });
    if (res.ok) {
        flashReload(msg);
    } else {
        notify(res.error || 'Error', 'err');
    }
}

function toggleTheme() {
    const dark = document.documentElement.classList.toggle('dark');
    document.cookie = 'theme=' + (dark ? 'dark' : 'light') + ';path=/;max-age=31536000';
}

// Cuenta atrás de la invitación pública (bloque "countdown").
document.addEventListener('DOMContentLoaded', () => {
    const el = document.querySelector('[data-countdown]');
    if (!el) return;
    const target = new Date(el.dataset.countdown.replace(' ', 'T')).getTime();
    const box = document.getElementById('countdown-box');
    function tick() {
        const diff = target - Date.now();
        if (diff <= 0) { box.textContent = '¡Hoy!'; return; }
        const d = Math.floor(diff / 86400000);
        const h = Math.floor((diff % 86400000) / 3600000);
        const m = Math.floor((diff % 3600000) / 60000);
        box.textContent = d + 'd ' + h + 'h ' + m + 'm';
    }
    tick();
    setInterval(tick, 60000);
});
