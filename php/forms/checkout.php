<?php
session_start();
require_once __DIR__ . '/../includes/auth_check.php';
requireRole('consumer');

$basePath = getBasePath(__FILE__);
$pageTitle = 'Checkout';
$user = $_SESSION['user'];
$delivery_address = trim(
    ($user['purok'] ?? '') . ', ' .
    ($user['barangay'] ?? '') . ', ' .
    ($user['city'] ?? '') . ', ' .
    ($user['province'] ?? '') . ' ' .
    ($user['zipCode'] ?? '') . ', ' .
    ($user['country'] ?? '')
);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - FoodGrab</title>
    <link rel="stylesheet" href="<?php echo $basePath; ?>css/serve_asset.php?file=design-system.css">
    <link rel="stylesheet" href="<?php echo $basePath; ?>css/serve_asset.php?file=dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .checkout-grid { display: grid; grid-template-columns: 1fr 380px; gap: 2rem; max-width: 900px; margin: 0 auto; align-items: start; }
        @media (max-width: 768px) { .checkout-grid { grid-template-columns: 1fr; } }

        .checkout-items { display: flex; flex-direction: column; gap: 0.75rem; }
        .checkout-item { display: flex; align-items: center; gap: 1rem; background: var(--bg-card); padding: 0.75rem 1rem; border-radius: 10px; border: 1px solid var(--border-color); }
        .checkout-item-info { flex: 1; }
        .checkout-item-info h4 { margin: 0 0 0.15rem; font-size: 0.95rem; font-weight: 600; color: var(--text-heading); }
        .checkout-item-info .ci-meta { font-size: 0.8rem; color: var(--text-muted); }
        .checkout-item-price { font-weight: 700; color: var(--primary-color); white-space: nowrap; }

        .checkout-form-card { background: var(--bg-card); padding: 1.5rem; border-radius: 12px; box-shadow: var(--shadow-sm); border: 1px solid var(--border-color); position: sticky; top: 100px; }
        .form-group { margin-bottom: 1rem; }
        .form-group label { display: block; font-size: 0.8rem; font-weight: 600; color: var(--text-muted); margin-bottom: 0.3rem; text-transform: uppercase; letter-spacing: 0.5px; }
        .form-group textarea, .form-group select, .form-group input { width: 100%; padding: 0.6rem 0.75rem; border: 1px solid var(--border-color); border-radius: 6px; font-size: 0.9rem; background: var(--bg-body); color: var(--text-heading); }
        .form-group textarea:focus, .form-group input:focus { outline: none; border-color: var(--primary-color); box-shadow: 0 0 0 3px rgba(37,99,235,0.1); }

        .summary-row { display: flex; justify-content: space-between; font-size: 0.9rem; color: var(--text-muted); margin-bottom: 0.5rem; }
        .summary-total { display: flex; justify-content: space-between; padding-top: 0.75rem; margin-top: 0.75rem; border-top: 1px solid var(--border-color); font-size: 1.15rem; font-weight: 800; color: var(--text-heading); }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../includes/layout/navbar.php'; ?>
    <div class="dashboard-container">
        <div class="sidebar-overlay" id="sidebarOverlay" aria-hidden="true"></div>
        <?php $currentPage = 'cart';
include __DIR__ . '/../includes/layout/sidebar.php'; ?>

        <main class="dashboard-main">
            <div style="max-width: 900px; margin: 0 auto;">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 1.5rem;">
                    <div>
                        <h1 class="page-title" style="margin-bottom: 0.25rem;">Checkout</h1>
                        <p class="page-subtitle">Review your order and confirm.</p>
                    </div>
                    <a href="cart.php" class="btn-secondary"><i class="fa-solid fa-arrow-left"></i> Back to Cart</a>
                </div>

                <div id="checkoutContent" style="display: none;">
                    <div class="checkout-grid">
                        <!-- Left: Items -->
                        <div>
                            <h3 style="margin: 0 0 0.75rem; font-size: 1rem; color: var(--text-heading);">Order Items</h3>
                            <div class="checkout-items" id="checkoutItems"></div>
                        </div>

                        <!-- Right: Form + Summary -->
                        <div class="checkout-form-card">
                            <form id="checkoutForm">
                                <div class="form-group">
                                    <label>Delivery Address</label>
                                    <textarea name="address" rows="3" required placeholder="Enter complete address..."><?php echo htmlspecialchars($delivery_address); ?></textarea>
                                </div>
                                <div class="form-group">
                                    <label>Notes (Optional)</label>
                                    <input type="text" name="notes" placeholder="e.g. No onions, extra sauce">
                                </div>

                                <h3 style="margin: 1rem 0 0.75rem; font-size: 1rem; color: var(--text-heading);">Summary</h3>
                                <div class="summary-row"><span>Subtotal</span><span>₱<span id="subTotal">0.00</span></span></div>
                                <div class="summary-row"><span>Delivery Fee</span><span>₱49.00</span></div>
                                <div class="summary-total"><span>Total</span><span style="color: var(--primary-color);">₱<span id="totalPrice">0.00</span></span></div>

                                <button type="submit" class="btn-primary" id="placeOrderBtn" style="width: 100%; margin-top: 1.25rem; padding: 0.85rem; font-size: 1rem; justify-content: center;">
                                    <i class="fa-solid fa-check" style="margin-right: 0.4rem;"></i> Place Order
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <div id="emptyState" style="display: none; text-align: center; padding: 4rem 2rem;">
                    <div style="font-size: 3rem; color: var(--border-color); margin-bottom: 1rem;"><i class="fa-solid fa-cart-shopping"></i></div>
                    <h2 style="margin-bottom: 0.5rem;">Your cart is empty</h2>
                    <p class="muted" style="margin-bottom: 1.5rem;">Add items before checking out.</p>
                    <a href="order_food.php" class="btn-primary" style="padding: 0.75rem 2rem;">Start Ordering</a>
                </div>
            </div>
        </main>
        <?php include __DIR__ . '/../includes/layout/footer.php'; ?>
    </div>

    <!-- Message Modal -->
    <div id="messageModal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.4); z-index:1100; align-items:center; justify-content:center;">
        <div style="background:white; padding:1.5rem; border-radius:12px; width:90%; max-width:350px; text-align:center; box-shadow:0 10px 25px rgba(0,0,0,0.1);">
            <div id="msgIconContainer" style="width:50px; height:50px; border-radius:50%; display:flex; align-items:center; justify-content:center; margin:0 auto 1rem;">
                <i id="msgIcon" class="fa-solid" style="font-size:1.5rem;"></i>
            </div>
            <h3 id="msgTitle" style="margin-bottom:0.25rem; font-size: 1.1rem;"></h3>
            <p id="msgBody" style="color:var(--text-muted); margin-bottom:1.5rem; font-size: 0.9rem;"></p>
            <button onclick="document.getElementById('messageModal').style.display='none'" class="btn-primary" style="width:100%; padding: 0.5rem; justify-content:center;">Okay</button>
        </div>
    </div>

    <script>
        const api = '../../php/database';

        function escapeHtml(s) { const d = document.createElement('div'); d.textContent = s || ''; return d.innerHTML; }

        function showMessageModal(type, title, message) {
            const modal = document.getElementById('messageModal');
            const iconContainer = document.getElementById('msgIconContainer');
            const icon = document.getElementById('msgIcon');
            document.getElementById('msgTitle').textContent = title;
            document.getElementById('msgBody').textContent = message;
            if (type === 'success') {
                iconContainer.style.background = '#dcfce7'; icon.className = 'fa-solid fa-check'; icon.style.color = '#16a34a'; document.getElementById('msgTitle').style.color = '#16a34a';
            } else {
                iconContainer.style.background = '#fee2e2'; icon.className = 'fa-solid fa-xmark'; icon.style.color = '#dc2626'; document.getElementById('msgTitle').style.color = '#dc2626';
            }
            modal.style.display = 'flex';
        }

        function loadCheckout() {
            fetch(api + '/cart_get.php')
                .then(r => r.json())
                .then(data => {
                    if (!data.success || data.items.length === 0) {
                        document.getElementById('checkoutContent').style.display = 'none';
                        document.getElementById('emptyState').style.display = 'block';
                        return;
                    }

                    document.getElementById('emptyState').style.display = 'none';
                    document.getElementById('checkoutContent').style.display = 'block';

                    let html = '';
                    data.items.forEach(item => {
                        const price = parseFloat(item.price);
                        html += `<div class="checkout-item">
                            <div class="checkout-item-info">
                                <h4>${escapeHtml(item.name)}</h4>
                                <div class="ci-meta"><i class="fa-solid fa-store" style="margin-right:0.2rem;"></i> ${escapeHtml(item.restaurant_name)} · Qty: ${item.quantity}</div>
                            </div>
                            <div class="checkout-item-price">₱${(price * item.quantity).toFixed(2)}</div>
                        </div>`;
                    });
                    document.getElementById('checkoutItems').innerHTML = html;

                    const deliveryFee = 49.00;
                    document.getElementById('subTotal').textContent = data.total.toFixed(2);
                    document.getElementById('totalPrice').textContent = (data.total + deliveryFee).toFixed(2);
                });
        }

        document.getElementById('checkoutForm').onsubmit = function(e) {
            e.preventDefault();
            const fd = new FormData(this);
            fd.append('from_cart', '1');

            const btn = document.getElementById('placeOrderBtn');
            btn.disabled = true;
            btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin" style="margin-right:0.4rem;"></i> Placing Order...';

            fetch(api + '/place_order.php', { method: 'POST', body: fd })
                .then(r => r.json())
                .then(d => {
                    if (d.success) {
                        showMessageModal('success', 'Order Placed!', 'Your order has been placed successfully. Check Order History to track it.');
                        document.getElementById('messageModal').querySelector('button').onclick = function() {
                            window.location.href = 'order_history.php';
                        };
                        if (typeof window.updateNavCartBadge === 'function') window.updateNavCartBadge();
                    } else {
                        showMessageModal('error', 'Order Failed', d.error || 'Could not place order.');
                        btn.disabled = false;
                        btn.innerHTML = '<i class="fa-solid fa-check" style="margin-right:0.4rem;"></i> Place Order';
                    }
                })
                .catch(() => {
                    showMessageModal('error', 'Error', 'Network error occurred.');
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fa-solid fa-check" style="margin-right:0.4rem;"></i> Place Order';
                });
        };

        // Sidebar logic
        (function(){ var o=document.getElementById('sidebarOverlay'),t=document.getElementById('sidebarToggle'); if(t&&o){ t.addEventListener('click',function(){ document.body.classList.toggle('sidebar-open'); o.classList.toggle('is-open',document.body.classList.contains('sidebar-open')); }); o.addEventListener('click',function(){ document.body.classList.remove('sidebar-open'); o.classList.remove('is-open'); }); } })();

        loadCheckout();
    </script>
</body>
</html>
