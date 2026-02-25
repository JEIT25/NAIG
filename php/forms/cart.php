<?php
session_start();
require_once __DIR__ . '/../includes/auth_check.php';
requireRole('consumer');

$basePath = getBasePath(__FILE__);
$pageTitle = 'My Cart';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Cart - FoodGrab</title>
    <link rel="stylesheet" href="<?php echo $basePath; ?>css/serve_asset.php?file=design-system.css">
    <link rel="stylesheet" href="<?php echo $basePath; ?>css/serve_asset.php?file=dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .cart-container { max-width: 900px; margin: 2rem auto; }
        .cart-grid { display: grid; grid-template-columns: 1fr 350px; gap: 2rem; align-items: start; }
        @media (max-width: 768px) { .cart-grid { grid-template-columns: 1fr; } }

        .cart-items-list { display: flex; flex-direction: column; gap: 1rem; }
        .cart-item { display: flex; align-items: center; background: var(--bg-card); padding: 1rem; border-radius: 12px; box-shadow: var(--shadow-sm); border: 1px solid var(--border-color); transition: transform 0.2s; }
        .cart-item:hover { transform: translateY(-2px); }
        .cart-icon { width: 50px; height: 50px; min-width: 50px; border-radius: 10px; display: flex; align-items: center; justify-content: center; background: #eef2ff; color: var(--primary-color); font-size: 1.25rem; }

        .cart-details { flex: 1; }
        .cart-details h3 { margin: 0 0 0.25rem 0; font-size: 1.1rem; font-weight: 700; color: var(--text-heading); }
        .cart-details .restaurant-name { margin: 0 0 0.5rem 0; font-size: 0.85rem; color: var(--text-muted); display: flex; align-items: center; gap: 0.25rem; }
        .cart-details .price { font-size: 1rem; font-weight: 600; color: var(--primary-color); }

        .cart-actions { display: flex; align-items: center; gap: 0.75rem; margin-left: 1rem; }
        .qty-control { display: flex; align-items: center; background: var(--bg-body); border-radius: 8px; padding: 2px; border: 1px solid var(--border-color); }
        .qty-btn { width: 32px; height: 32px; display: flex; align-items: center; justify-content: center; border: none; background: none; cursor: pointer; color: var(--text-heading); transition: all 0.2s; border-radius: 6px; }
        .qty-btn:hover { background: var(--bg-card); color: var(--primary-color); }
        .qty-display { width: 40px; text-align: center; font-weight: 600; font-size: 0.9rem; }

        .summary-card { background: var(--bg-card); padding: 1.5rem; border-radius: 12px; box-shadow: var(--shadow-sm); border: 1px solid var(--border-color); position: sticky; top: 100px; }
        .summary-row { display: flex; justify-content: space-between; margin-bottom: 1rem; font-size: 0.95rem; color: var(--text-muted); }
        .summary-total { display: flex; justify-content: space-between; margin-top: 1.5rem; padding-top: 1.5rem; border-top: 1px solid var(--border-color); font-size: 1.25rem; font-weight: 800; color: var(--text-heading); }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../includes/layout/navbar.php'; ?>
    <div class="dashboard-container">
        <div class="sidebar-overlay" id="sidebarOverlay" aria-hidden="true"></div>
        <?php $currentPage = 'cart';
include __DIR__ . '/../includes/layout/sidebar.php'; ?>

        <main class="dashboard-main">
            <div class="cart-container">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                    <div>
                        <h1 class="page-title" style="margin-bottom: 0.5rem;">Shopping Cart</h1>
                        <p class="page-subtitle">Review your items and checkout.</p>
                    </div>
                    <a href="order_food.php" class="btn-secondary"><i class="fa-solid fa-arrow-left"></i> Continue Shopping</a>
                </div>

                <div id="cartContent" style="display: none;">
                    <div class="cart-grid">
                        <div class="cart-items-list" id="cartItems">
                            <!-- Items will be loaded here -->
                        </div>

                        <div class="summary-card">
                            <h2 style="margin: 0 0 1.5rem 0; font-size: 1.25rem; font-weight: 700;">Order Summary</h2>
                            <div class="summary-row">
                                <span>Subtotal</span>
                                <span>₱<span id="subTotal">0.00</span></span>
                            </div>
                            <div class="summary-row">
                                <span>Delivery Fee</span>
                                <span>₱49.00</span> <!-- Fixed for now -->
                            </div>
                            <div class="summary-total">
                                <span>Total</span>
                                <span style="color: var(--primary-color);">₱<span id="totalPrice">0.00</span></span>
                            </div>
                            <button onclick="checkout()" class="btn-primary" style="width: 100%; margin-top: 1.5rem; padding: 1rem; font-size: 1rem; justify-content: center;">
                                Proceed to Checkout <i class="fa-solid fa-arrow-right"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <div id="emptyCart" class="empty-state" style="display: none; padding: 4rem 2rem;">
                    <div style="font-size: 4rem; color: var(--border-color); margin-bottom: 1.5rem;"><i class="fa-solid fa-cart-shopping"></i></div>
                    <h2 style="font-size: 1.5rem; font-weight: 700; color: var(--text-heading); margin-bottom: 0.5rem;">Your cart is empty</h2>
                    <p class="muted" style="margin-bottom: 2rem;">Looks like you haven't added anything to your cart yet.</p>
                    <a href="order_food.php" class="btn-primary" style="padding: 0.75rem 2rem;">Start Ordering</a>
                </div>
            </div>
        </main>
    </div>

    <script>
        const api = '../../php/database';

        function loadCart() {
            fetch(api + '/cart_get.php')
                .then(r => r.json())
                .then(data => {
                    if (!data.success || data.items.length === 0) {
                        document.getElementById('cartContent').style.display = 'none';
                        document.getElementById('emptyCart').style.display = 'block';
                        return;
                    }

                    document.getElementById('emptyCart').style.display = 'none';
                    document.getElementById('cartContent').style.display = 'block';

                    let html = '';
                    let subTotal = 0;

                    data.items.forEach(item => {
                        const price = parseFloat(item.price);
                        const itemTotal = price * item.quantity;
                        subTotal += itemTotal; // Using server provided item total logic usually but summing here for now

                        html += `<div class="cart-item">
                            <div class="cart-icon"><i class="fa-solid fa-utensils"></i></div>
                            <div class="cart-details">
                                <h3>${escapeHtml(item.name)}</h3>
                                <div class="restaurant-name"><i class="fa-solid fa-store"></i> ${escapeHtml(item.restaurant_name)}</div>
                                <div class="price">₱${price.toFixed(2)}</div>
                            </div>
                            <div class="cart-actions">
                                <div class="qty-control">
                                    <button onclick="updateQty(${item.id}, ${item.quantity - 1})" class="qty-btn" aria-label="Decrease quantity"><i class="fa-solid fa-minus"></i></button>
                                    <span class="qty-display">${item.quantity}</span>
                                    <button onclick="updateQty(${item.id}, ${item.quantity + 1})" class="qty-btn" aria-label="Increase quantity"><i class="fa-solid fa-plus"></i></button>
                                </div>
                                <button onclick="updateQty(${item.id}, 0)" class="btn-secondary" style="width: 38px; height: 38px; padding: 0; color: var(--error-color); border-color: var(--error-color);" aria-label="Remove item"><i class="fa-solid fa-trash"></i></button>
                            </div>
                        </div>`;
                    });

                    document.getElementById('cartItems').innerHTML = html;

                    // Update Summary
                    const deliveryFee = 49.00;
                    document.getElementById('subTotal').textContent = data.total.toFixed(2); // Use server total for subtotal
                    document.getElementById('totalPrice').textContent = (data.total + deliveryFee).toFixed(2);
                });
        }

        function updateQty(id, qty) {
            const fd = new FormData();
            fd.append('item_id', id);
            fd.append('quantity', qty);

            // Optimistic UI update could be done here, but sticking to simple fetch for reliability
            fetch(api + '/cart_update.php', { method: 'POST', body: fd })
                .then(r => r.json())
                .then(d => {
                    if (d.success) {
                        loadCart();
                        if (typeof window.updateNavCartBadge === 'function') window.updateNavCartBadge();
                    }
                    else alert(d.error);
                })
                .catch(e => {
                    console.error(e);
                    alert('Failed to update cart. Please try again.');
                });
        }

        function checkout() {
            window.location.href = 'checkout.php';
        }

        function escapeHtml(s) { const d = document.createElement('div'); d.textContent = s || ''; return d.innerHTML; }

        // Sidebar logic
        (function(){ var o=document.getElementById('sidebarOverlay'),t=document.getElementById('sidebarToggle'); if(t&&o){ t.addEventListener('click',function(){ document.body.classList.toggle('sidebar-open'); o.classList.toggle('is-open',document.body.classList.contains('sidebar-open')); }); o.addEventListener('click',function(){ document.body.classList.remove('sidebar-open'); o.classList.remove('is-open'); }); } })();

        loadCart();
    </script>
</body>
</html>
