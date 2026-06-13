import './bootstrap';

const API_BASE = '/api/v1';

const api = axios.create({
    baseURL: API_BASE,
    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
});

const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
if (csrfToken) {
    axios.defaults.headers.common['X-CSRF-TOKEN'] = csrfToken;
}

api.interceptors.request.use(config => {
    const token = localStorage.getItem('jwt_token');
    if (token) config.headers.Authorization = `Bearer ${token}`;
    return config;
});

function formatCurrency(amount) {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency', currency: 'IDR', minimumFractionDigits: 0, maximumFractionDigits: 0
    }).format(amount);
}

function formatDate(dateStr) {
    return new Date(dateStr).toLocaleDateString('id-ID', {
        year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit'
    });
}

function toast(message, type = 'success') {
    const toast = document.getElementById('toast');
    if (!toast) return;
    const colors = { success: 'bg-green-600', error: 'bg-red-600', warning: 'bg-amber-500' };
    toast.className = `fixed bottom-4 right-4 z-50 px-4 py-3 rounded-lg text-white text-sm font-medium shadow-lg ${colors[type] || colors.success}`;
    toast.textContent = message;
    toast.classList.remove('hidden');
    setTimeout(() => toast.classList.add('hidden'), 4000);
}

function updateTokenStatus() {
    const el = document.getElementById('token-status');
    if (!el) return;
    const ssoAuthenticated = document.querySelector('meta[name="sso-authenticated"]')?.content === '1';
    if (ssoAuthenticated) {
        el.textContent = 'SSO Active';
        el.className = 'text-xs px-2 py-1 rounded-full bg-green-100 text-green-700';
        el.classList.remove('hidden');
    } else {
        el.textContent = 'SSO Inactive';
        el.className = 'text-xs px-2 py-1 rounded-full bg-gray-100 text-gray-500';
        el.classList.remove('hidden');
    }
}

document.addEventListener('DOMContentLoaded', () => {
    updateTokenStatus();

    const path = window.location.pathname;

    // ── Dashboard ──
    if (path === '/') {
        api.get('/stats').then(res => {
            const d = res.data.data;
            document.getElementById('stat-total-winners').textContent = d.total_winners;
            document.getElementById('stat-total-invoices').textContent = d.total_invoices;
            document.getElementById('stat-total-revenue').textContent = formatCurrency(d.total_revenue);
            document.getElementById('stat-pending-invoices').textContent = d.pending_invoices;
        }).catch(() => {});

        api.get('/winners').then(res => {
            const winners = res.data.data || [];
            const tbody = document.getElementById('recent-winners-table');
            if (winners.length === 0) {
                tbody.innerHTML = '<tr><td colspan="6" class="px-6 py-8 text-center text-gray-400">No winners found</td></tr>';
                return;
            }
            tbody.innerHTML = winners.slice(0, 5).map(w => {
                const statusBadge = w.invoice?.status === 'paid'
                    ? '<span class="px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-700">Paid</span>'
                    : '<span class="px-2 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-700">Pending</span>';
                return `<tr class="hover:bg-gray-50">
                    <td class="px-6 py-4">
                        <div class="text-sm font-medium text-gray-900">${w.user?.name || '—'}</div>
                        <div class="text-xs text-gray-400">${w.user?.email || ''}</div>
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-700">${w.auction_item?.name || '—'}</td>
                    <td class="px-6 py-4 text-sm font-medium text-gray-900">${formatCurrency(w.winning_bid)}</td>
                    <td class="px-6 py-4 text-sm text-gray-700">${w.invoice?.invoice_number || '—'}</td>
                    <td class="px-6 py-4">${statusBadge}</td>
                    <td class="px-6 py-4 text-sm text-gray-500">${formatDate(w.won_at)}</td>
                </tr>`;
            }).join('');
        }).catch(() => {
            document.getElementById('recent-winners-table').innerHTML = '<tr><td colspan="6" class="px-6 py-8 text-center text-red-400">Failed to load data</td></tr>';
        });
    }

    // ── Winners List ──
    if (path === '/winners') {
        api.get('/winners').then(res => {
            const winners = res.data.data || [];
            const tbody = document.getElementById('winners-table');
            if (winners.length === 0) {
                tbody.innerHTML = '<tr><td colspan="8" class="px-6 py-8 text-center text-gray-400">No winners found</td></tr>';
                return;
            }
            tbody.innerHTML = winners.map(w => {
                const statusBadge = w.invoice?.status === 'paid'
                    ? '<span class="px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-700">Paid</span>'
                    : '<span class="px-2 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-700">Pending</span>';
                return `<tr class="hover:bg-gray-50">
                    <td class="px-6 py-4 text-sm font-mono text-gray-500">${w.id}</td>
                    <td class="px-6 py-4">
                        <div class="text-sm font-medium text-gray-900">${w.user?.name || '—'}</div>
                        <div class="text-xs text-gray-400">${w.user?.email || ''}</div>
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-700">${w.auction_item?.name || '—'}</td>
                    <td class="px-6 py-4 text-sm font-medium text-gray-900">${formatCurrency(w.winning_bid)}</td>
                    <td class="px-6 py-4 text-sm font-mono text-gray-700">${w.invoice?.invoice_number || '—'}</td>
                    <td class="px-6 py-4">${statusBadge}</td>
                    <td class="px-6 py-4 text-sm text-gray-500">${formatDate(w.won_at)}</td>
                    <td class="px-6 py-4">
                        <a href="/winners/${w.id}" class="text-sm font-medium text-indigo-600 hover:text-indigo-800">Detail</a>
                    </td>
                </tr>`;
            }).join('');
        }).catch(() => {
            document.getElementById('winners-table').innerHTML = '<tr><td colspan="8" class="px-6 py-8 text-center text-red-400">Failed to load data</td></tr>';
        });
    }

    // ── Winner Detail ──
    const detailMatch = path.match(/^\/winners\/(\d+)$/);
    if (detailMatch) {
        const id = detailMatch[1];
        api.get(`/winners/${id}`).then(res => {
            const w = res.data.data;
            if (!w) {
                document.getElementById('winner-detail').classList.add('hidden');
                document.getElementById('winner-error').classList.remove('hidden');
                return;
            }
            const statusBadge = w.invoice?.status === 'paid'
                ? '<span class="px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-700">Paid</span>'
                : '<span class="px-3 py-1 rounded-full text-sm font-medium bg-amber-100 text-amber-700">Pending</span>';
            document.getElementById('winner-detail').innerHTML = `
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Winner Info -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                        <h2 class="text-lg font-semibold text-gray-900 mb-4">Winner Information</h2>
                        <dl class="space-y-3">
                            <div class="flex justify-between">
                                <dt class="text-sm text-gray-500">ID</dt>
                                <dd class="text-sm font-mono font-medium text-gray-900">${w.id}</dd>
                            </div>
                            <div class="flex justify-between">
                                <dt class="text-sm text-gray-500">Winning Bid</dt>
                                <dd class="text-sm font-bold text-gray-900">${formatCurrency(w.winning_bid)}</dd>
                            </div>
                            <div class="flex justify-between">
                                <dt class="text-sm text-gray-500">Won At</dt>
                                <dd class="text-sm text-gray-900">${formatDate(w.won_at)}</dd>
                            </div>
                            <div class="flex justify-between">
                                <dt class="text-sm text-gray-500">Created At</dt>
                                <dd class="text-sm text-gray-900">${formatDate(w.created_at)}</dd>
                            </div>
                        </dl>
                    </div>

                    <!-- User Info -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                        <h2 class="text-lg font-semibold text-gray-900 mb-4">User</h2>
                        <dl class="space-y-3">
                            <div class="flex justify-between">
                                <dt class="text-sm text-gray-500">Name</dt>
                                <dd class="text-sm font-medium text-gray-900">${w.user?.name || '—'}</dd>
                            </div>
                            <div class="flex justify-between">
                                <dt class="text-sm text-gray-500">Email</dt>
                                <dd class="text-sm text-gray-900">${w.user?.email || '—'}</dd>
                            </div>
                            <div class="flex justify-between">
                                <dt class="text-sm text-gray-500">Role</dt>
                                <dd class="text-sm text-gray-900">${w.user?.role || '—'}</dd>
                            </div>
                        </dl>
                    </div>

                    <!-- Auction Item Info -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                        <h2 class="text-lg font-semibold text-gray-900 mb-4">Auction Item</h2>
                        <dl class="space-y-3">
                            <div class="flex justify-between">
                                <dt class="text-sm text-gray-500">Name</dt>
                                <dd class="text-sm font-medium text-gray-900">${w.auction_item?.name || '—'}</dd>
                            </div>
                            <div class="flex justify-between">
                                <dt class="text-sm text-gray-500">Description</dt>
                                <dd class="text-sm text-gray-900">${w.auction_item?.description || '—'}</dd>
                            </div>
                            <div class="flex justify-between">
                                <dt class="text-sm text-gray-500">Final Price</dt>
                                <dd class="text-sm font-medium text-gray-900">${formatCurrency(w.auction_item?.final_price || 0)}</dd>
                            </div>
                            <div class="flex justify-between">
                                <dt class="text-sm text-gray-500">Status</dt>
                                <dd class="text-sm text-gray-900">${w.auction_item?.status || '—'}</dd>
                            </div>
                        </dl>
                    </div>

                    <!-- Invoice Info -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                        <h2 class="text-lg font-semibold text-gray-900 mb-4">Invoice</h2>
                        <dl class="space-y-3">
                            <div class="flex justify-between">
                                <dt class="text-sm text-gray-500">Invoice Number</dt>
                                <dd class="text-sm font-mono font-medium text-gray-900">${w.invoice?.invoice_number || '—'}</dd>
                            </div>
                            <div class="flex justify-between">
                                <dt class="text-sm text-gray-500">Amount</dt>
                                <dd class="text-sm font-bold text-gray-900">${formatCurrency(w.invoice?.amount || 0)}</dd>
                            </div>
                            <div class="flex justify-between">
                                <dt class="text-sm text-gray-500">Status</dt>
                                <dd class="text-sm">${w.invoice ? statusBadge : '—'}</dd>
                            </div>
                            <div class="flex justify-between">
                                <dt class="text-sm text-gray-500">Receipt Number</dt>
                                <dd class="text-sm font-mono text-gray-900">${w.invoice?.receipt_number || '—'}</dd>
                            </div>
                        </dl>
                    </div>
                </div>
            `;
        }).catch(err => {
            document.getElementById('winner-detail').classList.add('hidden');
            document.getElementById('winner-error').classList.remove('hidden');
            if (err.response?.status === 404) {
                document.getElementById('error-message').textContent = 'Winner not found.';
            }
        });
    }

    // ── Token Page ──
    if (path === '/token') {
        const input = document.getElementById('jwt-input');
        const saved = localStorage.getItem('jwt_token');
        if (saved) input.value = saved;

        document.getElementById('token-form').addEventListener('submit', e => {
            e.preventDefault();
            const val = input.value.trim();
            if (!val) {
                showTokenMsg('Please enter a token.', 'text-amber-600');
                return;
            }
            localStorage.setItem('jwt_token', val);
            updateTokenStatus();
            showTokenMsg('Token saved successfully!', 'text-green-600');
            toast('Token saved');
        });

        document.getElementById('clear-token-btn').addEventListener('click', () => {
            localStorage.removeItem('jwt_token');
            input.value = '';
            updateTokenStatus();
            showTokenMsg('Token cleared.', 'text-red-600');
            toast('Token cleared', 'warning');
        });

        function showTokenMsg(msg, cls) {
            const el = document.getElementById('token-status-msg');
            el.textContent = msg;
            el.className = `text-sm font-medium ${cls}`;
            el.classList.remove('hidden');
        }
    }

    // ── Checkout Page ──
    if (path === '/checkout') {
        const ssoAuthenticated = document.querySelector('meta[name="sso-authenticated"]')?.content === '1';
        const tokenAlert = document.getElementById('token-alert');
        const submitBtn = document.getElementById('submit-btn');

        if (!ssoAuthenticated) {
            tokenAlert.className = 'p-4 rounded-lg text-sm bg-amber-50 border border-amber-200 text-amber-700 flex items-center gap-2';
            tokenAlert.innerHTML = `<svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/></svg>
                Session SSO tidak aktif. Silakan login kembali sebelum memproses checkout.`;
            tokenAlert.classList.remove('hidden');
            submitBtn.disabled = true;
        }

        // Load auction items
        api.get('/auction-items').then(res => {
            const items = res.data.data || [];
            const sel = document.getElementById('auction_item_id');
            if (items.length === 0) {
                sel.innerHTML = '<option value="">No available items</option>';
            } else {
                sel.innerHTML = '<option value="">Select an auction item...</option>'
                    + items.map(i => `<option value="${i.id}" data-price="${i.final_price}">${i.name} — ${formatCurrency(i.final_price)}</option>`).join('');
            }
        }).catch(() => {
            document.getElementById('auction_item_id').innerHTML = '<option value="">Failed to load items</option>';
        });

        // Load users
        api.get('/users').then(res => {
            const users = res.data.data || [];
            const sel = document.getElementById('user_id');
            sel.innerHTML = '<option value="">Select a user...</option>'
                + users.map(u => `<option value="${u.id}">${u.name} (${u.email})</option>`).join('');
        }).catch(() => {
            document.getElementById('user_id').innerHTML = '<option value="">Failed to load users</option>';
        });

        // Bid preview
        document.getElementById('winning_bid').addEventListener('input', function () {
            const val = this.value;
            document.getElementById('bid-preview').textContent = val ? formatCurrency(val) : '—';
        });

        // Auto-fill winning bid from item selection
        document.getElementById('auction_item_id').addEventListener('change', function () {
            const opt = this.options[this.selectedIndex];
            if (opt && opt.dataset.price) {
                document.getElementById('winning_bid').value = opt.dataset.price;
                document.getElementById('winning_bid').dispatchEvent(new Event('input'));
            }
        });

        // Submit
        document.getElementById('checkout-form').addEventListener('submit', function (e) {
            e.preventDefault();
            submitBtn.disabled = true;
            submitBtn.textContent = 'Processing...';

            const data = {
                auction_item_id: parseInt(document.getElementById('auction_item_id').value),
                user_id: parseInt(document.getElementById('user_id').value),
                winning_bid: parseFloat(document.getElementById('winning_bid').value),
            };

            axios.post('/checkout', data, {
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                },
            }).then(res => {
                toast('Checkout successful!');
                submitBtn.textContent = 'Redirecting...';
                setTimeout(() => {
                    window.location.href = `/winners/${res.data.data.id}`;
                }, 1000);
            }).catch(err => {
                submitBtn.disabled = false;
                submitBtn.textContent = 'Process Checkout';

                const resp = err.response;
                if (resp?.status === 401) {
                    toast('Session SSO tidak valid. Silakan login kembali.', 'error');
                } else if (resp?.status === 419) {
                    toast('Session telah kedaluwarsa. Muat ulang halaman dan login kembali.', 'error');
                } else if (resp?.status === 422) {
                    const errors = resp.data?.errors?.errors || {};
                    const msgs = Object.values(errors).flat().join(', ');
                    toast(msgs || 'Validation error', 'error');
                } else if (resp?.status === 400) {
                    toast(resp.data?.message || 'Item already checked out', 'error');
                } else {
                    toast(resp?.data?.message || 'System error', 'error');
                }
            });
        });
    }
});
