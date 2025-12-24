/* Cart widget script
   - Injects a cart button top-right and a modal showing cart contents
   - Fetches cart data from /pefumeppp/perfdb/get_cart.php
*/
(function(){
    const baseApi = '/pefumeppp/perfdb';

    // Small helper
    function escapeHtml(s){ return String(s).replace(/[&<>\"]/g, function(c){ return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[c]; }); }

    // Check if user is logged in and show dashboard link
    async function checkDashboardAccess(dashboardLink) {
        try {
            const userInfo = await getUserInfo();
            if (userInfo.loggedIn && dashboardLink) {
                // Set the correct dashboard URL based on user role
                const dashboardUrls = {
                    'customer': '/pefumeppp/customer/dashboard.php',
                    'employee': '/pefumeppp/employee/dashboard.php',
                    'admin': '/pefumeppp/admin/dashboard.php'
                };
                const role = userInfo.role || 'customer';
                dashboardLink.href = dashboardUrls[role] || '/pefumeppp/customer/dashboard.php';
                dashboardLink.style.display = 'inline-block';
            } else if (dashboardLink) {
                dashboardLink.style.display = 'none';
            }
        } catch (e) {
            console.warn('Dashboard check failed', e);
        }
    }

    // Check if user is logged in
    async function isUserLoggedIn() {
        try {
            const res = await fetch(baseApi + '/get_cart.php', { credentials: 'same-origin' });
            const data = await res.json();
            return data && data.user_logged_in === true;
        } catch (e) {
            return false;
        }
    }

    // Get user information including role
    async function getUserInfo() {
        try {
            const res = await fetch(baseApi + '/get_user_info.php', { credentials: 'same-origin' });
            const data = await res.json();
            if (data && data.success) {
                return {
                    loggedIn: true,
                    role: data.role,
                    firstName: data.first_name
                };
            }
            return { loggedIn: false, role: null };
        } catch (e) {
            // Fallback to cart check
            const loggedIn = await isUserLoggedIn();
            return { loggedIn, role: loggedIn ? 'customer' : null };
        }
    }

    // Handle checkout button click
    async function handleCheckoutClick(e) {
        e.preventDefault();
        
        const loggedIn = await isUserLoggedIn();
        
        if (!loggedIn) {
            // Redirect to login directly (no alert)
            // Store current page in session so we can return after login
            sessionStorage.setItem('returnAfterLogin', window.location.href);
            window.location.href = '/pefumeppp/docs/login.html';
            return;
        }
        
        // User is logged in, proceed with checkout API call
        try {
            const res = await fetch(baseApi + '/checkout.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ payment_method: 'cash' }),
                credentials: 'same-origin'
            });
            
            const data = await res.json();
            
            if (data.success) {
                showToast('Order created successfully! Order ID: ' + data.order_id);
                // Redirect to dashboard to see the completed order
                setTimeout(() => {
                    window.location.href = '/pefumeppp/customer/dashboard.php';
                }, 1500);
            } else if (data.redirect) {
                // Server returned a redirect URL (user not logged in)
                window.location.href = data.redirect;
            } else {
                showToast('Error: ' + (data.error || 'Failed to create order'), true);
            }
        } catch (err) {
            console.error('Checkout error', err);
            showToast('Processing error: ' + err.message, true);
        }
    }

    // Centralized fetch + render used by both full widget and nav-inline modal
    async function fetchAndRender(targetModal, badgeEl) {
        try {
            const res = await fetch(baseApi + '/get_cart.php', { credentials: 'same-origin' });
            const data = await res.json();
            if (!data) return;

            const count = data.item_count || (data.session_cart ? (Object.keys(data.session_cart).length || 0) + (data.session_custom ? Object.keys(data.session_custom).length || 0 : 0) : 0);
            if (badgeEl) badgeEl.textContent = count;

            const cartItems = data.cart_items || [];
            const customItems = data.custom_items || [];

            const itemsContainer = targetModal.querySelector('.items');
            const totalEl = targetModal.querySelector('.total');

            if ((!cartItems || cartItems.length === 0) && (!customItems || customItems.length === 0)) {
                itemsContainer.innerHTML = '<div class="empty">Cart is empty</div>';
                totalEl.textContent = 'Total: $0.00';
                return;
            }

            const itemNodes = [];
            cartItems.forEach(it => {
                const img = it.image_url ? ('/pefumeppp/images/' + it.image_url) : '/pefumeppp/images/perfumes/default-perfume.jpg';
                itemNodes.push(`
                    <div class="cart-item">
                        <img src="${img}" alt="${escapeHtml(it.name)}">
                        <div class="meta">
                            <div class="name">${escapeHtml(it.name)}</div>
                            <div class="qty">Quantity: ${it.quantity} — $${Number(it.price).toFixed(2)} each</div>
                        </div>
                    </div>
                `);
            });

            customItems.forEach(ci => {
                const img = '/pefumeppp/images/Untitled_design-removebg-preview.png';
                itemNodes.push(`
                    <div class="cart-item">
                        <img src="${img}" alt="Custom mix">
                        <div class="meta">
                            <div class="name">Custom Mix - ${escapeHtml(ci.bottle_design || 'Custom')}</div>
                            <div class="qty">Price: $${Number(ci.price).toFixed(2)}</div>
                        </div>
                    </div>
                `);
            });

            // Add remove buttons to each item: include identifiers for server-side removal
            // We will render items with data attributes so client can call remove endpoint
            // Rebuild itemNodes with removable markup
            const rebuilt = [];
            cartItems.forEach(it => {
                const img = it.image_url ? ('/pefumeppp/images/' + it.image_url) : '/pefumeppp/images/perfumes/default-perfume.jpg';
                // prefer cart_item_id when available for logged-in users, otherwise use product_id for session
                let identifier = it.cart_item_id ? `data-cart-item-id="${it.cart_item_id}"` : `data-product-id="${it.product_id}"`;
                // Add volume data attribute for session cart items
                if (!it.cart_item_id && it.volume_ml) {
                    identifier += ` data-volume="${it.volume_ml}"`;
                }
                rebuilt.push(`
                    <div class="cart-item" ${identifier}>
                        <img src="${img}" alt="${escapeHtml(it.name)}">
                        <div class="meta">
                            <div class="name">${escapeHtml(it.name)}${it.volume_ml ? ' (' + it.volume_ml + 'ml)' : ''}</div>
                            <div class="qty">Quantity: ${it.quantity} — $${Number(it.price).toFixed(2)} each</div>
                        </div>
                        <button class="remove-item" title="Remove" aria-label="Remove">✕</button>
                    </div>
                `);
            });

            customItems.forEach((ci, idx) => {
                // Use bottle image if available, otherwise fallback to default
                let img = ci.bottle_image || '/pefumeppp/images/Untitled_design-removebg-preview.png';
                
                // If no image and we have bottle_size, construct the image path
                if (!ci.bottle_image && ci.bottle_size) {
                    const bottleImages = {
                        100: '/pefumeppp/images/photo_٢٠٢٥-١١-٢٤_١٥-٠٨-٤١ (3).jpg',
                        50: '/pefumeppp/images/photo_٢٠٢٥-١١-٢٤_١٥-٠٨-٤١.jpg',
                        30: '/pefumeppp/images/photo_٢٠٢٥-١١-٢٤_١٥-٠٨-٤١ (2).jpg'
                    };
                    img = bottleImages[ci.bottle_size] || img;
                }
                
                // prefer custom_cart_id for logged-in users, otherwise use session index
                const identifier = ci.custom_cart_id ? `data-custom-cart-id="${ci.custom_cart_id}"` : `data-session-custom-index="${idx}"`;
                rebuilt.push(`
                    <div class="cart-item" ${identifier}>
                        <img src="${img}" alt="Bottle - ${ci.bottle_size || ''}ml">
                        <div class="meta">
                            <div class="name">Custom Mix - ${ci.bottle_size ? escapeHtml(ci.bottle_size + 'ml') : escapeHtml(ci.bottle_design || 'Custom')}</div>
                            <div class="qty">Price: $${Number(ci.price).toFixed(2)}</div>
                        </div>
                        <button class="remove-item" title="Remove" aria-label="Remove">✕</button>
                    </div>
                `);
            });

            itemsContainer.innerHTML = rebuilt.join('');

            // Use delegated handler on items container to reliably catch remove clicks
            itemsContainer.removeEventListener && itemsContainer.removeEventListener('click', itemsContainer._removeHandler);
            itemsContainer._removeHandler = async function(ev) {
                const btn = ev.target.closest && ev.target.closest('.remove-item');
                if (!btn) return;
                ev.stopPropagation();
                const parent = btn.closest('.cart-item');
                if (!parent) return;

                const cartItemId = parent.getAttribute('data-cart-item-id');
                const productId = parent.getAttribute('data-product-id');
                const volume = parent.getAttribute('data-volume');
                const customCartId = parent.getAttribute('data-custom-cart-id');
                const sessionCustomIndex = parent.getAttribute('data-session-custom-index');

                const payload = {};
                if (cartItemId) payload.cart_item_id = parseInt(cartItemId);
                else if (productId) {
                    payload.product_id = parseInt(productId);
                    if (volume) payload.volume = parseInt(volume);
                }
                if (customCartId) payload.custom_cart_id = parseInt(customCartId);
                if (sessionCustomIndex !== null && sessionCustomIndex !== undefined) payload.session_custom_index = parseInt(sessionCustomIndex);

                try {
                    btn.disabled = true;
                    // debug log
                    console.log('Removing item payload:', payload);
                    const resp = await fetch(baseApi + '/remove_from_cart.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(payload),
                        credentials: 'same-origin'
                    });
                    const jr = await resp.json();
                    if (jr.success) {
                    showToast('Item removed from cart');    // تحديث المحتوى داخل المودال
    await fetchAndRender(targetModal, badgeEl);

    // تحديث رقم البادج الرئيسي (في الزر الثابت أو الزر العلوي)
    const globalBadge = document.querySelector('#site-cart-widget .badge, .nav-cart-btn .badge');
    if (globalBadge && badgeEl && badgeEl.textContent) {
        globalBadge.textContent = badgeEl.textContent;
    }
}
 else {
                        showToast('Failed to remove item: ' + (jr.error || jr.message || ''), true);
                    }
                } catch (err) {
                    console.error('Remove error', err);
                    alert('Failed to remove item. Please try again later.');
                } finally {
                    btn.disabled = false;
                }
            };
            itemsContainer.addEventListener('click', itemsContainer._removeHandler);

            //
            totalEl.textContent = 'Total: $' + (Number(data.total || 0)).toFixed(2);
        } catch (err) {
            console.error('Cart refresh error', err);
        }
    }

    // Build a full fixed widget placed at top-right (used on pages without placeholder)
    function createWidget(){
        if (document.getElementById('site-cart-widget')) return;

        const wrap = document.createElement('div');
        wrap.id = 'site-cart-widget';

        wrap.innerHTML = `
            <div class="cart-button" role="button" title="View Cart">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                    <path d="M3 7h18l-2 11a1 1 0 0 1-1 .8H6a1 1 0 0 1-1-.8L3 7z" stroke-linejoin="round"></path>
                    <path d="M8 7l4-4 4 4" stroke-linecap="round" stroke-linejoin="round"></path>
                </svg>
                <span class="badge">0</span>
            </div>
            <div class="cart-modal" inert>
                <header>
                    <strong>Shopping Cart</strong>
                    <button class="close" aria-label="Close">✕</button>
                </header>
                <div class="items"></div>
                <div class="cart-footer">
                    <div class="total">Total: $0.00</div>
                    <button class="checkout-btn">Complete Order</button>
                    <a href="/pefumeppp/customer/dashboard.php" class="dashboard-link" style="display:none;">📊 Dashboard</a>
                </div>
            </div>
        `;

        document.body.appendChild(wrap);

        const btn = wrap.querySelector('.cart-button');
        const badge = wrap.querySelector('.badge');
        const modal = wrap.querySelector('.cart-modal');
        const closeBtn = wrap.querySelector('.close');
        const checkoutBtn = wrap.querySelector('.checkout-btn');
        const dashboardLink = wrap.querySelector('.dashboard-link');

        btn.addEventListener('click', async function(e){
            modal.classList.toggle('open');
            if (modal.classList.contains('open')) {
                await fetchAndRender(modal, badge);
                modal.removeAttribute('inert');
                modal.setAttribute('aria-hidden','false');
                // Show dashboard link if user is logged in
                checkDashboardAccess(dashboardLink);
            } else {
                modal.setAttribute('inert', '');
                modal.setAttribute('aria-hidden','true');
            }
        });

        closeBtn.addEventListener('click', function(){ 
            modal.classList.remove('open');
            modal.setAttribute('inert', '');
            modal.setAttribute('aria-hidden','true');
        });
        
        checkoutBtn.addEventListener('click', handleCheckoutClick);

        // initial refresh and periodic update (update badge even when modal closed)
        fetchAndRender(modal, badge);
        setInterval(() => fetchAndRender(modal, badge), 30000);
    }

    // Positioning helper for nav modal: place it below/near the button
    function positionModalNear(targetEl, modalEl) {
        if (!targetEl || !modalEl) return;
        const rect = targetEl.getBoundingClientRect();
        const modalWidth = Math.min(360, window.innerWidth - 20);
        modalEl.style.position = 'absolute';
        modalEl.style.width = modalWidth + 'px';
        modalEl.style.maxWidth = 'calc(100% - 40px)';
        // place below the button, align right edges
        const top = rect.bottom + window.scrollY + 8;
        const left = Math.max(10, rect.right + window.scrollX - modalWidth);
        modalEl.style.top = top + 'px';
        modalEl.style.left = left + 'px';
        modalEl.style.zIndex = 9999;
    }

    // Initialize widget or nav-inline trigger
    function initWidget() {
        // Check for placeholders - support both nav-cart-placeholder and topbar-cart-placeholder
        const placeholder = document.getElementById('nav-cart-placeholder');
        const topbarPlaceholder = document.getElementById('topbar-cart-placeholder');
        const mobileCartPlaceholder = document.getElementById('mobile-cart-placeholder');
        
        // Create cart button for mobile (next to search on phone screens)
        if (mobileCartPlaceholder) {
            const mobileBtn = document.createElement('button');
            mobileBtn.type = 'button';
            mobileBtn.className = 'cart-button mobile-cart-button';
            mobileBtn.title = 'View Cart';
            mobileBtn.innerHTML = `
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M3 7h18l-2 11a1 1 0 0 1-1 .8H6a1 1 0 0 1-1-.8L3 7z" stroke-linejoin="round"></path>
                    <path d="M8 7l4-4 4 4" stroke-linecap="round" stroke-linejoin="round"></path>
                </svg>
                <span class="badge mobile-badge">0</span>
            `;
            mobileCartPlaceholder.appendChild(mobileBtn);
            
            // Create modal for mobile cart
            const mobileModal = document.createElement('div');
            mobileModal.className = 'cart-modal mobile-cart-modal';
            mobileModal.setAttribute('inert','');
            mobileModal.style.display = 'none';
            mobileModal.innerHTML = `
                <header>
                    <strong>Shopping Cart</strong>
                    <button class="close" aria-label="Close">✕</button>
                </header>
                <div class="items"></div>
                <div class="cart-footer">
                    <div class="total">Total: $0.00</div>
                    <button class="checkout-btn">Complete Order</button>
                    <a href="/pefumeppp/customer/dashboard.php" class="dashboard-link" style="display:none;">📊 Dashboard</a>
                </div>
            `;
            document.body.appendChild(mobileModal);
            
            const mobileBadge = mobileBtn.querySelector('.badge');
            const mobileCloseBtn = mobileModal.querySelector('.close');
            const mobileCheckoutBtn = mobileModal.querySelector('.checkout-btn');
            const mobileDashboardLink = mobileModal.querySelector('.dashboard-link');
            
            const openMobileModal = async () => {
                mobileModal.classList.add('open');
                mobileModal.style.display = 'flex';
                mobileModal.removeAttribute('inert');
                await fetchAndRender(mobileModal, mobileBadge);
                checkDashboardAccess(mobileDashboardLink);
            };
            
            mobileBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                if (mobileModal.classList.contains('open')) {
                    mobileModal.classList.remove('open');
                    mobileModal.style.display = 'none';
                    mobileModal.setAttribute('inert','');
                } else {
                    openMobileModal();
                }
            });
            
            mobileCloseBtn.addEventListener('click', () => {
                mobileModal.classList.remove('open');
                mobileModal.style.display = 'none';
                mobileModal.setAttribute('inert','');
            });
            
            mobileCheckoutBtn.addEventListener('click', handleCheckoutClick);
            
            // Initial badge update
            (async () => {
                try {
                    const res = await fetch(baseApi + '/get_cart.php', { credentials: 'same-origin' });
                    const d = await res.json();
                    const count = d.cart ? d.cart.reduce((s,i)=>s+(i.quantity||1),0) : 0;
                    mobileBadge.textContent = count;
                } catch(e){}
            })();
            
            // Refresh badge periodically
            setInterval(async () => {
                try {
                    const res = await fetch(baseApi + '/get_cart.php', { credentials: 'same-origin' });
                    const d = await res.json();
                    const count = d.cart ? d.cart.reduce((s,i)=>s+(i.quantity||1),0) : 0;
                    mobileBadge.textContent = count;
                } catch(e){}
            }, 30000);
        }
        
        // Create cart button for topbar if placeholder exists
        if (topbarPlaceholder) {
            const topbarBtn = document.createElement('button');
            topbarBtn.type = 'button';
            topbarBtn.className = 'nav-cart-btn topbar-cart-btn';
            topbarBtn.title = 'View Cart';
            topbarBtn.innerHTML = `
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M3 7h18l-2 11a1 1 0 0 1-1 .8H6a1 1 0 0 1-1-.8L3 7z" stroke-linejoin="round"></path>
                    <path d="M8 7l4-4 4 4" stroke-linecap="round" stroke-linejoin="round"></path>
                </svg>
                <span class="badge topbar-badge">0</span>
            `;
            topbarPlaceholder.appendChild(topbarBtn);
            
            // Create modal for topbar cart
            const topbarModal = document.createElement('div');
            topbarModal.className = 'cart-modal topbar-cart-modal';
            topbarModal.setAttribute('inert','');
            topbarModal.style.display = 'none';
            topbarModal.innerHTML = `
                <header>
                    <strong>Shopping Cart</strong>
                    <button class="close" aria-label="Close">✕</button>
                </header>
                <div class="items"></div>
                <div class="cart-footer">
                    <div class="total">Total: $0.00</div>
                    <button class="checkout-btn">Complete Order</button>
                    <a href="/pefumeppp/customer/dashboard.php" class="dashboard-link" style="display:none;">📊 Dashboard</a>
                </div>
            `;
            document.body.appendChild(topbarModal);
            
            const topbarBadge = topbarBtn.querySelector('.badge');
            const topbarCloseBtn = topbarModal.querySelector('.close');
            const topbarCheckoutBtn = topbarModal.querySelector('.checkout-btn');
            const topbarDashboardLink = topbarModal.querySelector('.dashboard-link');
            
            const openTopbarModal = async () => {
                topbarModal.classList.add('open');
                topbarModal.style.display = 'flex';
                topbarModal.removeAttribute('inert');
                await fetchAndRender(topbarModal, topbarBadge);
                checkDashboardAccess(topbarDashboardLink);
            };
            
            topbarBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                if (topbarModal.classList.contains('open')) {
                    topbarModal.classList.remove('open');
                    topbarModal.style.display = 'none';
                    topbarModal.setAttribute('inert','');
                } else {
                    openTopbarModal();
                }
            });
            
            topbarCloseBtn.addEventListener('click', () => {
                topbarModal.classList.remove('open');
                topbarModal.style.display = 'none';
                topbarModal.setAttribute('inert','');
            });
            
            topbarCheckoutBtn.addEventListener('click', handleCheckoutClick);
            
            // Initial badge update
            (async () => {
                try {
                    const res = await fetch(baseApi + '/get_cart.php', { credentials: 'same-origin' });
                    const d = await res.json();
                    const count = d.cart ? d.cart.reduce((s,i)=>s+(i.quantity||1),0) : 0;
                    topbarBadge.textContent = count;
                } catch(e){}
            })();
        }
        
        if (placeholder) {
            // create small trigger inside placeholder
            const smallBtn = document.createElement('button');
            smallBtn.type = 'button';
            smallBtn.className = 'nav-cart-btn';
            smallBtn.title = 'View Cart';
            smallBtn.innerHTML = `
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.2">
                    <path d="M3 7h18l-2 11a1 1 0 0 1-1 .8H6a1 1 0 0 1-1-.8L3 7z" stroke-linejoin="round"></path>
                    <path d="M8 7l4-4 4 4" stroke-linecap="round" stroke-linejoin="round"></path>
                </svg>
                <span class="badge">0</span>
            `;
            placeholder.appendChild(smallBtn);

            // create modal element (hidden) appended to body
            const navModal = document.createElement('div');
            navModal.className = 'cart-modal nav-inline-modal';
            navModal.setAttribute('inert','');
            navModal.style.display = 'none';
            navModal.innerHTML = `
                <header>
                    <strong>Shopping Cart</strong>
                    <button class="close" aria-label="Close">✕</button>
                </header>
                <div class="items"></div>
                <div class="cart-footer">
                    <div class="total">Total: $0.00</div>
                    <button class="checkout-btn">Complete Order</button>
                    <a href="/pefumeppp/customer/dashboard.php" class="dashboard-link" style="display:none;">📊 Dashboard</a>
                </div>
            `;
            document.body.appendChild(navModal);

            const badge = smallBtn.querySelector('.badge');
            const closeBtn = navModal.querySelector('.close');
            const checkoutBtn = navModal.querySelector('.checkout-btn');
            const dashboardLink = navModal.querySelector('.dashboard-link');

            // toggle and populate
            const openNavModal = async () => {
                navModal.classList.add('open');
                navModal.style.display = 'flex';
                navModal.removeAttribute('inert');
                navModal.setAttribute('aria-hidden','false');
                // position near button
                positionModalNear(smallBtn, navModal);
                await fetchAndRender(navModal, badge);
                // Show dashboard link if user is logged in
                checkDashboardAccess(dashboardLink);
                // attach outside click handler
                document.addEventListener('click', outsideClickHandler);
                document.addEventListener('keydown', escKeyHandler);
            };

            const closeNavModal = () => {
                navModal.classList.remove('open');
                navModal.style.display = 'none';
                navModal.setAttribute('inert', '');
                navModal.setAttribute('aria-hidden','true');
                document.removeEventListener('click', outsideClickHandler);
                document.removeEventListener('keydown', escKeyHandler);
            };

            smallBtn.addEventListener('click', async function(e){
                e.stopPropagation();
                if (navModal.classList.contains('open')) closeNavModal();
                else await openNavModal();
            });
            closeBtn.addEventListener('click', function(e){ e.stopPropagation(); closeNavModal(); });
            
            checkoutBtn.addEventListener('click', handleCheckoutClick);            // outside click closes the modal
            function outsideClickHandler(ev) {
                if (!navModal.classList.contains('open')) return;
                if (navModal.contains(ev.target) || smallBtn.contains(ev.target)) return;
                closeNavModal();
            }

            function escKeyHandler(ev) {
                if (ev.key === 'Escape' && navModal.classList.contains('open')) closeNavModal();
            }

            // reposition on scroll/resize when open
            window.addEventListener('resize', () => { if (navModal.classList.contains('open')) positionModalNear(smallBtn, navModal); });
            window.addEventListener('scroll', () => { if (navModal.classList.contains('open')) positionModalNear(smallBtn, navModal); });

            // refresh badge periodically
            setInterval(() => fetchAndRender(navModal, badge), 30000);
            // also do an initial quick refresh for badge
            fetchAndRender(navModal, badge);
        } else {
            if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', createWidget);
            else createWidget();
        }
    }

    initWidget();

    // Toast helper (visible feedback)
    function showToast(message, isError){
        let t = document.querySelector('.cart-toast');
        if (!t) {
            t = document.createElement('div');
            t.className = 'cart-toast';
            document.body.appendChild(t);
        }
        t.innerHTML = message;
        if (isError) t.style.background = 'rgba(180,40,40,0.95)'; else t.style.background = 'rgba(0,0,0,0.85)';
        t.classList.add('show');
        clearTimeout(t._hideTimer);
        t._hideTimer = setTimeout(() => { t.classList.remove('show'); }, 3000);
    }

// ✅ دالة عامة لتحديث السلة بعد إضافة منتج
window.refreshCartBadgeAndModal = async function() {
    try {
        const modal = document.querySelector('#site-cart-widget .cart-modal, .nav-inline-modal');
        const badge = document.querySelector('#site-cart-widget .badge, .nav-cart-btn .badge');
        if (modal && badge) {
            await fetchAndRender(modal, badge);
        }
    } catch (err) {
        console.error('Cart refresh failed:', err);
    }
};
// call the global refresh explicitly and close the IIFE
window.refreshCartBadgeAndModal();
})();

// Backwards-compatible badge-only refresher and event listener
// Keeps badge number in sync even if modal isn't present
;(function(){
    async function refreshBadgesOnly(){
        // local copy of baseApi because this IIFE may run separately from the outer one
        const baseApi = '/pefumeppp/perfdb';
        try {
            const res = await fetch(baseApi + '/get_cart.php', { credentials: 'same-origin' });
            const data = await res.json();
            let count = 0;
            if (data) {
                if (typeof data.item_count === 'number' && data.item_count >= 0) {
                    count = data.item_count;
                } else {
                    const sc = data.session_cart ? Object.keys(data.session_cart).length : 0;
                    const scc = data.session_custom ? Object.keys(data.session_custom).length : 0;
                    count = sc + scc;
                }
            }
            // update known badge selectors
            const badgeSelectors = ['#site-cart-widget .badge', '.nav-cart-btn .badge', '.cart-button .badge', '.cart-badge'];
            badgeSelectors.forEach(sel => {
                document.querySelectorAll(sel).forEach(el => { el.textContent = count; });
            });
            console.log('Cart badges refreshed to', count);
        } catch (err) {
            console.warn('refreshBadgesOnly failed', err);
        }
    }

    // expose short name
    window.refreshCartBadges = refreshBadgesOnly;

    // listen for custom event so other scripts can fire `window.dispatchEvent(new Event('cart:updated'))`
    window.addEventListener('cart:updated', function(){ refreshBadgesOnly(); });

    // also poll briefly after page load to ensure badges reflect session state
    setTimeout(refreshBadgesOnly, 500);
})();
