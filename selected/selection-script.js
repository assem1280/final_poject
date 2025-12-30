document.addEventListener('DOMContentLoaded', function() {
    const companyButtons = document.querySelectorAll('.company-btn');
    const perfumesGrid = document.querySelector('.perfumes-grid');
    const tabButtons = document.querySelectorAll('.tab-btn');
    const companiesGrid = document.querySelector('.companies-grid');

    // Brand mapping (brand name to brand_id)
    const brandMapping = {
        'chanel': 1,
        'dior': 2,
        'tomford': 3,
        'versace': 4,
        'hermes': 5,
        'gucci': 6,
        'pacorabanne': 7
    };

    // Gender mapping (category name to gender_id)
    const genderMapping = {
        'man': 1,
        'woman': 2,
        'unisex': 3
    };

    // Utility to create a perfume card element
    function createPerfumeCard(perfume) {
        const card = document.createElement('div');
        card.className = 'perfume-card';
        const isOutOfStock = perfume.stock <= 0;
        if (isOutOfStock) {
            card.classList.add('out-of-stock');
        }
        card.style.cursor = 'pointer';
        // Use correct image path from database
        const imagePath = perfume.image_url ? `../images/${perfume.image_url}` : '../images/perfumes/default-perfume.jpg';
        card.innerHTML = `
            <div class="perfume-image-container">
                <img src="${imagePath}" alt="${perfume.p_name}">
            </div>
            <h3 class="perfume-name">${perfume.p_name}</h3>
            <p class="perfume-price">$${perfume.price}</p>
            ${isOutOfStock ? '<div class="out-of-stock-text">Out of Stock</div>' : ''}
            <div class="card-actions">
                <button class="view-details-btn" data-product-id="${perfume.p_id}">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                        <circle cx="12" cy="12" r="3"></circle>
                    </svg>
                    View Details
                </button>
                <button class="add-to-cart-btn" data-product-id="${perfume.p_id}" data-stock="${perfume.stock}">
                    <svg class="cart-icon" viewBox="0 0 24 24" width="16" height="16">
                        <path d="M7 18c-1.1 0-1.99.9-1.99 2S5.9 22 7 22s2-.9 2-2-.9-2-2-2zM1 2v2h2l3.6 7.59-1.35 2.45c-.16.28-.25.61-.25.96 0 1.1.9 2 2 2h12v-2H7.42c-.14 0-.25-.11-.25-.25l.03-.12.9-1.63h7.45c.75 0 1.41-.41 1.75-1.03l3.58-6.49c.08-.14.12-.31.12-.48 0-.55-.45-1-1-1H5.21l-.94-2H1zm16 16c-1.1 0-1.99.9-1.99 2s.89 2 1.99 2 2-.9 2-2-.9-2-2-2z" fill="currentColor"/>
                    </svg>
                </button>
            </div>
        `;
        
        // Add click handler for View Details button
        const viewDetailsBtn = card.querySelector('.view-details-btn');
        if (viewDetailsBtn) {
            viewDetailsBtn.addEventListener('click', function(e) {
                e.stopPropagation();
                window.location.href = `../perfdb/product-details.php?id=${perfume.p_id}`;
            });
        }
        
        return card;
    }

    // Render perfumes into the grid
    function renderPerfumes(list) {
        perfumesGrid.innerHTML = '';
        list.forEach(p => perfumesGrid.appendChild(createPerfumeCard(p)));
    }

    // Replace existing simple buttons with icons (if any exist statically)
    document.querySelectorAll('.add-to-cart-btn').forEach(button => {
        if (!button.innerHTML.trim()) {
            button.innerHTML = `
                <svg class="cart-icon" viewBox="0 0 24 24" width="16" height="16">
                    <path d="M7 18c-1.1 0-1.99.9-1.99 2S5.9 22 7 22s2-.9 2-2-.9-2-2-2zM1 2v2h2l3.6 7.59-1.35 2.45c-.16.28-.25.61-.25.96 0 1.1.9 2 2 2h12v-2H7.42c-.14 0-.25-.11-.25-.25l.03-.12.9-1.63h7.45c.75 0 1.41-.41 1.75-1.03l3.58-6.49c.08-.14.12-.31.12-.48 0-.55-.45-1-1-1H5.21l-.94-2H1zm16 16c-1.1 0-1.99.9-1.99 2s.89 2 1.99 2 2-.9 2-2-.9-2-2-2z" fill="currentColor"/>
                </svg>
            `;
        }
    });

    // Create a modal for size selection
    function createSizeModal() {
        const modal = document.createElement('div');
        modal.id = 'size-selection-modal';
        modal.style.cssText = `
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            justify-content: center;
            align-items: center;
        `;
        
        modal.innerHTML = `
            <div style="background-color: #ffffff; padding: 30px; border-radius: 12px; text-align: center; box-shadow: 0 4px 20px rgba(0,0,0,0.3); max-width: 400px;">
                <h2 style="margin: 0 0 20px 0; color: #000000; font-size: 20px;">Select Size</h2>
                <p style="color: #666666; margin-bottom: 20px;">Choose your preferred volume:</p>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 12px;">
                    <button class="size-choice-btn" data-size="50" style="padding: 12px; background: #ffffff; border: 2px solid #000000; color: #000000; border-radius: 8px; cursor: pointer; font-weight: 600; transition: all 0.3s ease;">50ml</button>
                    <button class="size-choice-btn" data-size="100" style="padding: 12px; background: #ffffff; border: 2px solid #000000; color: #000000; border-radius: 8px; cursor: pointer; font-weight: 600; transition: all 0.3s ease;">100ml</button>
                </div>
                <div style="margin-bottom:12px; text-align:left;">
                    <label for="quantity-input" style="display:block; margin-bottom:6px; color:#222; font-weight:600;">Quantity</label>
                    <input id="quantity-input" type="number" min="1" value="1" style="width:100%; padding:8px; border:1px solid #ccc; border-radius:6px;" />
                    <div id="stock-info" style="margin-top:8px; color:#555; font-size:13px;"></div>
                </div>
                <div style="display:flex; gap:12px; justify-content:center;">
                    <button id="confirm-add-btn" style="padding: 12px 18px; background: #27ae60; border: none; color: #fff; border-radius:8px; cursor:pointer; font-weight:600;">Add to Cart</button>
                    <button id="cancel-size-btn" style="padding: 12px 18px; background: #f0f0f0; border: 2px solid #cccccc; color: #000000; border-radius: 8px; cursor: pointer; font-weight: 600; transition: all 0.3s ease;">Cancel</button>
                </div>
            </div>
        `;
        
        document.body.appendChild(modal);
        return modal;
    }

    const sizeModal = createSizeModal();
    let pendingCartData = null;

    // Add to Cart functionality - with size selection
    document.addEventListener('click', async function(e) {
        const btn = e.target.closest('.add-to-cart-btn');
        if (btn) {
            const perfumeCard = btn.closest('.perfume-card');
            if (perfumeCard.classList.contains('out-of-stock')) {
                alert('⚠️ This product is out of stock!');
                return;
            }
            const productId = btn.getAttribute('data-product-id');
            const perfumeName = perfumeCard.querySelector('.perfume-name').textContent;
            
            // Store the data for when size is selected (include stock)
            const stockAvailable = parseInt(btn.getAttribute('data-stock')) || 0;
            pendingCartData = {
                productId: parseInt(productId),
                perfumeName: perfumeName,
                stock: stockAvailable,
                selectedSize: null
            };
            
            // Show size selection modal
            sizeModal.style.display = 'flex';
            
            // Add animation effect
            btn.style.transform = 'scale(0.95)';
            setTimeout(() => { btn.style.transform = ''; }, 150);
        }
        
        // Handle size selection buttons: mark chosen size
        const sizeBtn = e.target.closest('.size-choice-btn');
        if (sizeBtn && pendingCartData) {
            // remove active style from other size buttons
            const allSizeBtns = sizeModal.querySelectorAll('.size-choice-btn');
            allSizeBtns.forEach(b => b.style.boxShadow = 'none');
            sizeBtn.style.boxShadow = '0 0 0 3px rgba(102,119,234,0.15)';
            pendingCartData.selectedSize = parseInt(sizeBtn.getAttribute('data-size'));
            // update stock-info (already shown) - no further action here
        }

        // Update stock display and quantity max when modal is opened (set earlier)

        // Confirm add button: validate quantity vs stock and send request
        if (e.target.id === 'confirm-add-btn' && pendingCartData) {
            const qtyInput = document.getElementById('quantity-input');
            let qty = parseInt(qtyInput.value) || 1;
            if (qty <= 0) qty = 1;

            if (qty > pendingCartData.stock) {
                alert('That quantity is not available.');
                return;
            }

            if (!pendingCartData.selectedSize) {
                alert('Please select a size first.');
                return;
            }

            // Send add to cart request
            try {
                const response = await fetch('../perfdb/add_to_cart.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        product_id: pendingCartData.productId,
                        quantity: qty,
                        size: pendingCartData.selectedSize
                    }),
                    credentials: 'same-origin'
                });

                const data = await response.json();
                if (data.success) {
                    alert(`Added ${pendingCartData.perfumeName} (${pendingCartData.selectedSize}ml) x${qty} to cart!`);
                    if (window.refreshCartBadges) try { window.refreshCartBadges(); } catch(e) { console.warn('refreshCartBadges failed', e); }
                    try { window.dispatchEvent(new Event('cart:updated')); } catch(e) { /* ignore */ }
                    sizeModal.style.display = 'none';
                } else {
                    alert('Error: ' + (data.error || 'Failed to add to cart'));
                }

                pendingCartData = null;
            } catch (error) {
                console.error('Error adding to cart:', error);
                alert('Failed to add to cart. Please try again.');
                pendingCartData = null;
            }
        }

        // Handle cancel button
        if (e.target.id === 'cancel-size-btn') {
            sizeModal.style.display = 'none';
            pendingCartData = null;
        }
    });

    // Initially hide the perfumes grid
    perfumesGrid.style.display = 'none';
    companiesGrid.style.display = 'grid';

    // Tab buttons behavior (show brands grid)
    tabButtons.forEach(button => {
        button.addEventListener('click', function() {
            tabButtons.forEach(btn => { btn.classList.remove('active'); btn.removeAttribute('data-selected'); });
            this.classList.add('active');
            this.setAttribute('data-selected', 'true');
            companiesGrid.style.display = 'grid';
            perfumesGrid.style.display = 'none';
        });
    });

    // Company buttons behavior: show only perfumes for selected brand and current category
    companyButtons.forEach(button => {
        button.addEventListener('click', function() {
            const selectedCategory = document.querySelector('.tab-btn[data-selected="true"]').getAttribute('data-category');
            const selectedBrand = this.getAttribute('data-brand');
            // Show perfumes grid and hide companies
            perfumesGrid.style.display = 'grid';
            companiesGrid.style.display = 'none';
            // Load perfumes filtered
            loadPerfumes(selectedCategory, selectedBrand);
        });
    });

    // loadPerfumes: fetch from database via PHP
    async function loadPerfumes(category, brand) {
        try {
            const genderId = genderMapping[category] || '';
            const brandId = brand ? brandMapping[brand] || '' : '';
            
            const url = `../perfdb/get_products.php?gender_id=${genderId}&brand_id=${brandId}`;
            
            const response = await fetch(url);
            const data = await response.json();
            
            if (data.success && data.products) {
                // Map products to expected format with correct field names from database
                const products = data.products.map(p => ({
                    p_id: p.p_id,
                    p_name: p.p_name,
                    price: p.price,
                    image_url: p.image_url,
                    description: p.description,
                    stock: p.stock,
                    brand_name: p.brand_name,
                    gender_name: p.gender_name
                }));
                
                renderPerfumes(products);
            } else {
                console.error('Failed to load products:', data.error);
                perfumesGrid.innerHTML = '<p style="text-align:center; color:#fff;">No products found</p>';
            }
        } catch (error) {
            console.error('Error loading perfumes:', error);
            perfumesGrid.innerHTML = '<p style="text-align:center; color:#fff;">Error loading products</p>';
        }
    }

    // Optional: if you want to show all women's perfumes by default (with Blue de Chanel first)
    // you can uncomment the following lines to auto-show when page loads and women tab is active
    const activeTab = document.querySelector('.tab-btn.active');
    if (activeTab && activeTab.getAttribute('data-category') === 'woman') {
        // keep companies grid visible until a company is clicked. Do nothing here.
    }

    // Navigation history and back button removed per user request
});