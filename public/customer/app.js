(function () {
    'use strict';

    const API = '/api/v1';
    const TOKEN_KEY = 'll_token';

    const app = document.getElementById('app');
    const nav = document.getElementById('nav');
    const toastEl = document.getElementById('toast');

    let pollTimer = null;
    let toastTimer = null;
    let services = [];

    // ---------- Pembantu kecil ----------

    const rupiah = (n) => 'Rp' + new Intl.NumberFormat('id-ID').format(Math.round(Number(n) || 0));

    // Semua teks dari server dibersihkan dulu sebelum masuk ke HTML
    const esc = (s) => String(s ?? '').replace(/[&<>"']/g, (c) => ({
        '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;',
    }[c]));

    const fmtDate = (iso) => {
        if (!iso) return '-';
        return new Date(iso).toLocaleString('id-ID', {
            timeZone: 'Asia/Jakarta', day: '2-digit', month: 'short', year: 'numeric',
            hour: '2-digit', minute: '2-digit',
        }) + ' WIB';
    };

    const qtyText = (q) => String(parseFloat(q));

    function toast(message, bad) {
        toastEl.textContent = message;
        toastEl.className = 'toast' + (bad ? ' bad' : '');
        toastEl.hidden = false;
        clearTimeout(toastTimer);
        toastTimer = setTimeout(() => { toastEl.hidden = true; }, 3500);
    }

    function loading() {
        app.innerHTML = '<p class="muted center">Memuat...</p>';
    }

    // ---------- Pemanggil API ----------

    async function api(path, options) {
        options = options || {};
        const headers = {
            'Accept': 'application/json',
            'ngrok-skip-browser-warning': 'true',
        };
        const token = localStorage.getItem(TOKEN_KEY);
        if (token) headers['Authorization'] = 'Bearer ' + token;
        if (options.body) headers['Content-Type'] = 'application/json';

        let res;
        try {
            res = await fetch(API + path, {
                method: options.method || 'GET',
                headers: headers,
                body: options.body ? JSON.stringify(options.body) : undefined,
            });
        } catch (e) {
            throw new Error('Tidak bisa terhubung ke server. Cek koneksi internet.');
        }

        let json = null;
        try { json = await res.json(); } catch (e) { /* bukan JSON */ }

        if (res.status === 401 && token) {
            localStorage.removeItem(TOKEN_KEY);
            location.hash = '#/login';
            throw new Error('Sesi berakhir, silakan masuk lagi.');
        }

        if (!res.ok) {
            let msg = (json && json.message) || 'Terjadi kesalahan (' + res.status + ').';
            if (json && json.errors) {
                const first = Object.values(json.errors)[0];
                if (Array.isArray(first) && first[0]) msg = first[0];
            }
            throw new Error(msg);
        }
        return json;
    }

    // Menghubungkan form: tombol dinonaktifkan selama proses, error tampil sebagai toast
    function bindForm(id, handler) {
        const form = document.getElementById(id);
        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            const btn = form.querySelector('button[type=submit]');
            const label = btn.textContent;
            btn.disabled = true;
            btn.textContent = 'Memproses...';
            try {
                await handler(Object.fromEntries(new FormData(form).entries()));
            } catch (err) {
                toast(err.message, true);
            }
            btn.disabled = false;
            btn.textContent = label;
        });
    }

    // ---------- Layar: masuk dan daftar ----------

    function viewLogin() {
        app.innerHTML = `
            <h1>Masuk</h1>
            <p class="muted">Pesan laundry dari HP, bayar dengan QRIS.</p>
            <form id="f" class="form card">
                <label>Email<input type="email" name="email" required autocomplete="email"></label>
                <label>Password<input type="password" name="password" required autocomplete="current-password"></label>
                <button class="btn" type="submit">Masuk</button>
            </form>
            <p class="center">Belum punya akun? <a href="#/register">Daftar</a></p>`;

        bindForm('f', async (d) => {
            const res = await api('/login', { method: 'POST', body: { email: d.email, password: d.password } });
            localStorage.setItem(TOKEN_KEY, res.data.token);
            location.hash = '#/services';
        });
    }

    function viewRegister() {
        app.innerHTML = `
            <h1>Daftar</h1>
            <p class="muted">Buat akun pelanggan baru.</p>
            <form id="f" class="form card">
                <label>Nama<input type="text" name="name" required autocomplete="name"></label>
                <label>Email<input type="email" name="email" required autocomplete="email"></label>
                <label>Password<input type="password" name="password" required minlength="8" autocomplete="new-password"></label>
                <label>Ulangi password<input type="password" name="password_confirmation" required autocomplete="new-password"></label>
                <button class="btn" type="submit">Daftar</button>
            </form>
            <p class="center">Sudah punya akun? <a href="#/login">Masuk</a></p>`;

        bindForm('f', async (d) => {
            const res = await api('/register', {
                method: 'POST',
                body: {
                    name: d.name,
                    email: d.email,
                    password: d.password,
                    password_confirmation: d.password_confirmation,
                },
            });
            localStorage.setItem(TOKEN_KEY, res.data.token);
            toast('Pendaftaran berhasil');
            location.hash = '#/services';
        });
    }

    // ---------- Layar: daftar layanan ----------

    async function viewServices() {
        loading();
        const res = await api('/services');
        services = res.data;
        app.innerHTML = '<h2>Layanan kami</h2>' + (services.length
            ? services.map((s) => `
                <div class="card">
                    <div class="row">
                        <strong>${esc(s.name)}</strong>
                        <strong>${rupiah(s.price)}</strong>
                    </div>
                    <div class="muted small">${esc(s.unit_label)} &middot; estimasi ${esc(s.estimated_hours)} jam</div>
                    ${s.description ? `<p class="small">${esc(s.description)}</p>` : ''}
                </div>`).join('')
            : '<p class="muted center">Belum ada layanan.</p>')
            + '<a class="btn" href="#/order">Buat pesanan</a>';
    }

    // ---------- Layar: buat pesanan ----------

    async function viewOrder() {
        loading();
        const res = await api('/services');
        services = res.data;
        if (!services.length) {
            app.innerHTML = '<p class="muted center">Belum ada layanan untuk dipesan.</p>';
            return;
        }

        app.innerHTML = `
            <h2>Buat pesanan</h2>
            <form id="f" class="form">
                <div class="card">
                    <h3>Pilih layanan</h3>
                    ${services.map((s) => `
                        <div class="row svc">
                            <div>
                                <strong>${esc(s.name)}</strong>
                                <div class="muted small">${rupiah(s.price)} ${esc(s.unit_label)}</div>
                            </div>
                            <input class="qty" type="number" name="q_${s.id}" min="0" max="999"
                                   step="${s.unit === 'item' ? '1' : '0.1'}" value="0" inputmode="decimal">
                        </div>`).join('')}
                    <div class="row total"><span>Estimasi total</span><strong id="est">Rp0</strong></div>
                    <p class="muted small">Total akhir dihitung ulang oleh server.</p>
                </div>

                <div class="card">
                    <h3>Pengambilan</h3>
                    <label class="radio"><input type="radio" name="fulfillment_type" value="drop_off" checked> Datang sendiri ke laundry</label>
                    <label class="radio"><input type="radio" name="fulfillment_type" value="pickup_delivery"> Antar-jemput</label>
                    <div id="pickup" hidden>
                        <label>Alamat<textarea name="address" rows="2" maxlength="500"></textarea></label>
                        <label>Jadwal jemput<input type="datetime-local" name="scheduled_at"></label>
                    </div>
                    <label>Catatan (opsional)<textarea name="notes" rows="2" maxlength="500"></textarea></label>
                </div>

                <button class="btn" type="submit">Buat pesanan</button>
            </form>`;

        const form = document.getElementById('f');
        const update = () => {
            let total = 0;
            services.forEach((s) => {
                const q = parseFloat(form.elements['q_' + s.id].value);
                if (q > 0) total += Number(s.price) * q;
            });
            document.getElementById('est').textContent = rupiah(total);
            document.getElementById('pickup').hidden = form.elements['fulfillment_type'].value !== 'pickup_delivery';
        };
        form.addEventListener('input', update);
        form.addEventListener('change', update);

        bindForm('f', async (d) => {
            const items = [];
            services.forEach((s) => {
                const q = parseFloat(d['q_' + s.id]);
                if (q > 0) items.push({ service_id: s.id, quantity: q });
            });
            if (!items.length) throw new Error('Pilih minimal satu layanan dan isi jumlahnya.');

            const body = { fulfillment_type: d.fulfillment_type, items: items };
            if (d.fulfillment_type === 'pickup_delivery') {
                if (!d.address || !d.scheduled_at) throw new Error('Isi alamat dan jadwal jemput.');
                body.address = d.address;
                body.scheduled_at = d.scheduled_at;
            }
            if (d.notes) body.notes = d.notes;

            const res = await api('/orders', { method: 'POST', body: body });
            toast('Pesanan berhasil dibuat');
            location.hash = '#/history/' + res.data.id;
        });
    }

    // ---------- Layar: riwayat pesanan ----------

    async function viewHistory() {
        loading();
        const res = await api('/orders?per_page=30');
        const list = res.data;

        app.innerHTML = '<h2>Riwayat pesanan</h2>' + (list.length
            ? list.map((o) => `
                <a class="card" href="#/history/${o.id}">
                    <div class="row">
                        <strong>${esc(o.order_number)}</strong>
                        <span class="badge s-${esc(o.status)}">${esc(o.status_label)}</span>
                    </div>
                    <div class="muted small">${fmtDate(o.ordered_at)}</div>
                    <div class="small">${(o.items || []).map((i) => esc(i.service_name)).join(', ')}</div>
                    <div class="row">
                        <strong>${rupiah(o.total)}</strong>
                        <span class="badge pay-${esc(o.payment_status)}">${esc(o.payment_status_label)}</span>
                    </div>
                </a>`).join('')
            : '<p class="muted center">Belum ada pesanan.</p><a class="btn" href="#/order">Buat pesanan pertama</a>');
    }

    // ---------- Layar: detail pesanan dan pembayaran ----------

    async function viewDetail(id) {
        loading();
        const res = await api('/orders/' + id);
        renderDetail(res.data);
    }

    function renderDetail(o) {
        const paid = o.payment_status === 'paid';
        const cancelled = o.status === 'cancelled';

        app.innerHTML = `
            <a href="#/history" class="back">&larr; Riwayat</a>
            <div class="card">
                <div class="row">
                    <strong>${esc(o.order_number)}</strong>
                    <span class="badge s-${esc(o.status)}">${esc(o.status_label)}</span>
                </div>
                <div class="muted small">${fmtDate(o.ordered_at)}</div>
                <div class="row">
                    <span class="small">${o.fulfillment_type === 'pickup_delivery' ? 'Antar-jemput' : 'Datang sendiri'}</span>
                    <span class="badge pay-${esc(o.payment_status)}">${esc(o.payment_status_label)}</span>
                </div>
                ${o.address ? `<p class="small">Alamat: ${esc(o.address)}</p>` : ''}
                ${o.scheduled_at ? `<p class="small">Jadwal: ${fmtDate(o.scheduled_at)}</p>` : ''}
                ${o.notes ? `<p class="small">Catatan: ${esc(o.notes)}</p>` : ''}
                <table class="items">
                    ${(o.items || []).map((i) => `
                        <tr>
                            <td>${esc(i.service_name)}<div class="muted small">${qtyText(i.quantity)} x ${rupiah(i.unit_price)}</div></td>
                            <td>${rupiah(i.subtotal)}</td>
                        </tr>`).join('')}
                </table>
                <div class="row total"><span>Total</span><strong>${rupiah(o.total)}</strong></div>
            </div>

            ${paid ? '<div class="ok">Pembayaran lunas. Terima kasih!</div>' : ''}
            <div id="paybox"></div>
            ${!paid && !cancelled ? '<button id="payBtn" class="btn" type="button">Bayar dengan QRIS</button>' : ''}
            ${o.can_cancel ? '<button id="cancelBtn" class="btn ghost" type="button">Batalkan pesanan</button>' : ''}`;

        const payBtn = document.getElementById('payBtn');
        if (payBtn) {
            payBtn.addEventListener('click', async () => {
                payBtn.disabled = true;
                payBtn.textContent = 'Membuat QR...';
                try {
                    const res = await api('/orders/' + o.id + '/pay', { method: 'POST' });
                    showQr(o, res.data);
                    payBtn.hidden = true;
                } catch (err) {
                    toast(err.message, true);
                    payBtn.disabled = false;
                    payBtn.textContent = 'Bayar dengan QRIS';
                }
            });
        }

        const cancelBtn = document.getElementById('cancelBtn');
        if (cancelBtn) {
            cancelBtn.addEventListener('click', async () => {
                if (!confirm('Batalkan pesanan ini?')) return;
                cancelBtn.disabled = true;
                try {
                    await api('/orders/' + o.id + '/cancel', { method: 'POST' });
                    toast('Pesanan dibatalkan');
                    viewDetail(o.id);
                } catch (err) {
                    toast(err.message, true);
                    cancelBtn.disabled = false;
                }
            });
        }
    }

    function showQr(o, p) {
        document.getElementById('paybox').innerHTML = `
            <div class="card center">
                <h3>Scan untuk membayar</h3>
                <img class="qr" src="${esc(p.qr_url)}" alt="QRIS pembayaran">
                <p><strong>${rupiah(p.amount)}</strong></p>
                <p class="muted small">Berlaku sampai ${esc(p.expiry_time)} WIB. Halaman ini otomatis berubah setelah pembayaran diterima.</p>
                <p class="sim">Mode simulasi (Midtrans Sandbox): bayar lewat simulator Midtrans, tidak ada uang sungguhan.</p>
            </div>`;

        // Cek status ke server tiap 4 detik. Status lunas hanya berubah lewat webhook Midtrans.
        clearInterval(pollTimer);
        pollTimer = setInterval(async () => {
            try {
                const r = await api('/orders/' + o.id + '/payment');
                const st = r.data.payment_status;
                if (st === 'paid') {
                    clearInterval(pollTimer);
                    toast('Pembayaran diterima. Terima kasih!');
                    viewDetail(o.id);
                } else if (st === 'expired' || st === 'failed') {
                    clearInterval(pollTimer);
                    toast('QR kedaluwarsa atau gagal. Silakan coba lagi.', true);
                    viewDetail(o.id);
                }
            } catch (e) { /* coba lagi pada putaran berikutnya */ }
        }, 4000);
    }

    // ---------- Layar: akun ----------

    async function viewAccount() {
        loading();
        const res = await api('/me');
        const u = res.data;
        app.innerHTML = `
            <h2>Akun saya</h2>
            <div class="card">
                <div class="muted small">Nama</div><div>${esc(u.name)}</div>
                <div class="muted small" style="margin-top:10px">Email</div><div>${esc(u.email)}</div>
            </div>
            <button id="logout" class="btn ghost" type="button">Keluar</button>`;

        document.getElementById('logout').addEventListener('click', async () => {
            try { await api('/logout', { method: 'POST' }); } catch (e) { /* tetap keluar */ }
            localStorage.removeItem(TOKEN_KEY);
            location.hash = '#/login';
        });
    }

    // ---------- Pengatur halaman (router) ----------

    function route() {
        clearInterval(pollTimer);

        const parts = (location.hash || '#/services').replace(/^#\//, '').split('/');
        const page = parts[0] || 'services';
        const loggedIn = !!localStorage.getItem(TOKEN_KEY);

        if (!loggedIn && page !== 'login' && page !== 'register') {
            location.hash = '#/login';
            return;
        }
        if (loggedIn && (page === 'login' || page === 'register')) {
            location.hash = '#/services';
            return;
        }

        nav.hidden = !loggedIn;
        nav.querySelectorAll('a').forEach((a) => {
            a.classList.toggle('active', a.dataset.page === page);
        });
        window.scrollTo(0, 0);

        let view;
        if (page === 'login') view = viewLogin();
        else if (page === 'register') view = viewRegister();
        else if (page === 'order') view = viewOrder();
        else if (page === 'history' && parts[1]) view = viewDetail(parts[1]);
        else if (page === 'history') view = viewHistory();
        else if (page === 'account') view = viewAccount();
        else view = viewServices();

        Promise.resolve(view).catch((err) => {
            app.innerHTML = `<div class="err">${esc(err.message)}</div>
                <button class="btn" type="button" onclick="location.reload()">Coba lagi</button>`;
        });
    }

    window.addEventListener('hashchange', route);
    route();
})();