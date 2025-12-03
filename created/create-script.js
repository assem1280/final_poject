// State Management
let allProducts = [];
let selectedProducts = [];
let selectedBottle = null;

// Maximum fragrance volume per bottle size
const MAX_FRAGRANCE_PER_BOTTLE = {
    100: 50,  // 100ml bottle -> max 50ml fragrance
    50: 25,   // 50ml bottle -> max 25ml fragrance
    30: 15    // 30ml bottle -> max 15ml fragrance
};

// Brand & Gender Mappings
const brandMapping = {
    'Dior': 1,
    'Chanel': 2,
    'Tom Ford': 3,
    'Versace': 4,
    'Gucci': 5,
    'Prada': 6,
    'Armani': 7
};

const genderMapping = {
    'MAN': 1,
    'WOMAN': 2,
    'UNISEX': 3
};

// Price constant used on the create page (displayed as per-10ml price)
const PRICE_PER_10ML = 2.5;

// DOM Elements
const productsGrid = document.getElementById('productsGrid');
const searchInput = document.getElementById('searchInput');
const genderFilter = document.getElementById('genderFilter');
const brandFilter = document.getElementById('brandFilter');
const mlSection = document.getElementById('mlSection');
const selectedProductsContainer = document.getElementById('selectedProductsContainer');
const addToCartBtn = document.getElementById('addToCartBtn');

// Price Elements
const productsCostEl = document.getElementById('productsCost');
const bottleCostEl = document.getElementById('bottleCost');
const totalMLEl = document.getElementById('totalML');
const totalPriceEl = document.getElementById('totalPrice');

// Initialize
document.addEventListener('DOMContentLoaded', async () => {
    await loadAllProducts();
    setupEventListeners();
    setupBottleSelection();
});

// Load All Products (126 products)
async function loadAllProducts() {
    try {
        productsGrid.innerHTML = '<div class="loading-message">Loading products...</div>';
        
        // Fetch all products (no gender/brand filter)
        const response = await fetch('../perfdb/get_products.php');
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const text = await response.text();
        console.log('API Response:', text);
        
        let data;
        try {
            data = JSON.parse(text);
        } catch (e) {
            throw new Error('Invalid JSON response: ' + text);
        }
        
        if (data.success && data.products && data.products.length > 0) {
            allProducts = data.products;
            console.log(`Loaded ${allProducts.length} products`);
            displayProducts(allProducts);
        } else {
            console.error('API Response:', data);
            const errorMsg = data.error || data.message || 'No products found';
            productsGrid.innerHTML = `<div class="error-message">Error: ${errorMsg}</div>`;
        }
    } catch (error) {
        console.error('Error loading products:', error);
        productsGrid.innerHTML = '<div class="error-message">Failed to load products. Check console for details.</div>';
    }
}

// Display Products (shows first 15 initially, rest with search/filters)
function displayProducts(products) {
    if (!products || products.length === 0) {
        productsGrid.innerHTML = '<div class="no-products-message">لا توجد منتجات</div>';
        return;
    }

    // تنظيف البحث من الفراغات
    const searchTerm = searchInput.value.trim().toLowerCase();
    const genderValue = genderFilter.value;
    const brandValue = brandFilter.value;

    // تحقق إذا في فلتر فعال
    const hasActiveFilter = searchTerm || genderValue || brandValue;

    // اختر المنتجات للعرض
    const productsToShow = hasActiveFilter ? products : products.slice(0, 15);

    productsGrid.innerHTML = productsToShow.map(product => {
        const isSelected = selectedProducts.find(p => p.p_id === product.p_id);
        const isDisabled = selectedProducts.length >= 3 && !isSelected;
        const isOutOfStock = product.stock <= 0;

        return `
            <div class="product-card ${isSelected ? 'selected' : ''} ${isDisabled ? 'disabled' : ''} ${isOutOfStock ? 'out-of-stock' : ''}" 
                 data-product-id="${product.p_id}"
                 onclick="toggleProduct(${product.p_id})"
                 ${isOutOfStock ? 'style="opacity: 0.6; pointer-events: none;"' : ''}>
                <div class="product-name">${product.p_name}</div>
                <div class="product-brand">${product.brand_name}</div>
                <div class="product-gender">${product.gender_name}</div>
                <div class="product-price">$${PRICE_PER_10ML.toFixed(2)}/10ml</div>
                ${isOutOfStock ? '<div class="out-of-stock-text">Out of Stock</div>' : ''}
            </div>
        `;
    }).join('');

    // رسالة معلومات عند عرض أول 15 منتج بدون فلترة
    if (!hasActiveFilter && products.length > 15) {
        productsGrid.innerHTML += `
            <div class="info-message" style="grid-column: 1/-1; text-align: center; color: rgba(0, 0, 0, 0.7); padding: 15px; font-style: italic;">
                Showing 15 of ${products.length} products. Use search or filters to find more.
            </div>
        `;
    }
}


// Toggle Product Selection
function toggleProduct(productId) {
    console.log('Toggle product:', productId, 'Current selected:', selectedProducts.length);
    
    const product = allProducts.find(p => p.p_id === productId);
    if (!product) return;
    
    // Check if product is out of stock
    if (product.stock <= 0) {
        alert('⚠️ This product is out of stock!');
        return;
    }
    
    const existingIndex = selectedProducts.findIndex(p => p.p_id === productId);
    
    if (existingIndex >= 0) {
        // Deselect product
        console.log('Deselecting product');
        selectedProducts.splice(existingIndex, 1);
        updateDisplay();
    } else {
        // Check max limit BEFORE adding
        if (selectedProducts.length >= 3) {
            console.log('MAX REACHED! Cannot add more products');
            alert('⚠️ Maximum 3 products allowed for mixing!');
            return; // Don't add the product
        }
        
        // Add product
        console.log('Adding product');
        selectedProducts.push({
            ...product,
            ml: 0
        });
        updateDisplay();
    }
}

// Update Display
function updateDisplay() {
    // Update products grid
    displayProducts(filterProducts());
    
    // Update selected products on the right side
    displaySelectedProducts();
    
    // Calculate and update price
    calculatePrice();
}

// Display Selected Products with ML Inputs (Right Column)
function displaySelectedProducts() {
    if (selectedProducts.length === 0) {
        selectedProductsContainer.innerHTML = '<div class="empty-state">No products selected yet</div>';
        return;
    }
    
    selectedProductsContainer.innerHTML = selectedProducts.map(product => `
        <div class="selected-product-item">
            <div class="product-info">
                <h3>${product.p_name}</h3>
                <p>${product.brand_name} - ${product.gender_name}</p>
            </div>
            <div class="ml-input-group">
                <label>ML:</label>
                <input type="number" 
                       class="ml-input" 
                       min="0" 
                       max="${selectedBottle ? (MAX_FRAGRANCE_PER_BOTTLE[selectedBottle.size] || selectedBottle.size) : 50}"
                       value="${product.ml || 0}"
                       onchange="updateProductML(${product.p_id}, this.value)"
                       placeholder="0">
            </div>
            <button class="remove-product-btn" onclick="toggleProduct(${product.p_id})">✕ Remove</button>
        </div>
    `).join('');
}

// Update Product ML
function updateProductML(productId, ml) {
    const product = selectedProducts.find(p => p.p_id === productId);
    if (!product) return;
    
    ml = parseInt(ml) || 0;
    
    // Check bottle capacity
    if (selectedBottle) {
        const totalML = selectedProducts.reduce((sum, p) => sum + (p.p_id === productId ? ml : (p.ml || 0)), 0);
        const maxFragrance = MAX_FRAGRANCE_PER_BOTTLE[selectedBottle.size] || selectedBottle.size;
        
        if (totalML > maxFragrance) {
            alert(`The maximum limit for an excellent experience is ${maxFragrance}ml to avoid affecting the clothes and the fragrance quality.`);
            return;
        }
    }
    
    product.ml = ml;
    calculatePrice();
}

// Calculate Price
function calculatePrice() {
    // use shared PRICE_PER_10ML constant defined at top of the file
    
    // Calculate total ML
    const totalML = selectedProducts.reduce((sum, p) => sum + (p.ml || 0), 0);
    
    // Calculate products cost: (total_ml / 10) * 2.5
    const productsCost = (totalML / 10) * PRICE_PER_10ML;
    
    // Bottle cost
    const bottleCost = selectedBottle ? selectedBottle.price : 0;
    
    // Total
    const total = productsCost + bottleCost;
    
    // Update UI
    productsCostEl.textContent = `$${productsCost.toFixed(2)}`;
    bottleCostEl.textContent = `$${bottleCost.toFixed(2)}`;
    totalMLEl.textContent = `${totalML}ml`;
    totalPriceEl.textContent = `$${total.toFixed(2)}`;
}

// Setup Bottle Selection
function setupBottleSelection() {
    const bottleOptions = document.querySelectorAll('.bottle-option');
    
    bottleOptions.forEach(option => {
        option.addEventListener('click', function() {
            // Remove previous selection
            bottleOptions.forEach(opt => opt.classList.remove('selected'));
            
            // Add selection
            this.classList.add('selected');
            
            // Update selected bottle
            selectedBottle = {
                id: parseInt(this.dataset.bottleId),
                size: parseInt(this.dataset.size),
                price: parseFloat(this.dataset.price)
            };
            
            // Recalculate price
            calculatePrice();
            
            // Update ML input max values
            displaySelectedProducts();
        });
    });
    
    // Select first bottle by default
    if (bottleOptions.length > 0) {
        bottleOptions[0].click();
    }
}

// Filter Products
function filterProducts() {
    let filtered = allProducts;
    
    // Search filter
    const searchTerm = (searchInput && searchInput.value) ? searchInput.value.toLowerCase() : '';
    if (searchTerm) {
        filtered = filtered.filter(product => {
            const name = (product.p_name || '').toString().toLowerCase();
            const brand = (product.brand_name || '').toString().toLowerCase();
            const gender = (product.gender_name || '').toString().toLowerCase();
            return name.includes(searchTerm) || brand.includes(searchTerm) || gender.includes(searchTerm);
        });
    }
    
    // Gender filter
    const genderValue = genderFilter.value;
    if (genderValue) {
        filtered = filtered.filter(product => product.gender_id == genderValue);
    }
    
    // Brand filter
    const brandValue = brandFilter.value;
    if (brandValue) {
        filtered = filtered.filter(product => product.brand_id == brandValue);
    }
    
    return filtered;
}

// Setup Event Listeners
function setupEventListeners() {
    // Search input
    searchInput.addEventListener('input', () => {
        displayProducts(filterProducts());
    });
    
    // Gender filter
    genderFilter.addEventListener('change', () => {
        displayProducts(filterProducts());
    });
    
    // Brand filter
    brandFilter.addEventListener('change', () => {
        displayProducts(filterProducts());
    });
    
    // Add to Cart button
    addToCartBtn.addEventListener('click', addToCart);
}

// Add to Cart
async function addToCart() {
    // Validation
    if (selectedProducts.length === 0) {
        alert('Please select at least 1 product!');
        return;
    }
    
    if (selectedProducts.length > 3) {
        alert('Maximum 3 products allowed!');
        return;
    }
    
    const totalML = selectedProducts.reduce((sum, p) => sum + (p.ml || 0), 0);
    if (totalML === 0) {
        alert('Please enter ML for at least one product!');
        return;
    }
    
    if (!selectedBottle) {
        alert('Please select a bottle!');
        return;
    }
    
    const maxFragrance = MAX_FRAGRANCE_PER_BOTTLE[selectedBottle.size] || selectedBottle.size;
    if (totalML > maxFragrance) {
        alert(`The maximum limit for an excellent experience is ${maxFragrance}ml to avoid affecting the clothes and the fragrance quality.`);
        return;
    }
    
    // Prepare data
    const bottleImages = {
        100: '../images/photo_٢٠٢٥-١١-٢٤_١٥-٠٨-٤١ (3).jpg',
        50: '../images/photo_٢٠٢٥-١١-٢٤_١٥-٠٨-٤١.jpg',
        30: '../images/photo_٢٠٢٥-١١-٢٤_١٥-٠٨-٤١ (2).jpg'
    };
    
    const cartData = {
        products: selectedProducts.map(p => ({
            product_id: p.p_id,
            ml: p.ml || 0
        })),
        bottle_size: selectedBottle.size,
        bottle_design_id: selectedBottle.id,
        bottle_image: bottleImages[selectedBottle.size] || '/pefumeppp/images/Untitled_design-removebg-preview.png',
        total_ml: totalML
    };
    
    try {
        addToCartBtn.disabled = true;
        addToCartBtn.textContent = 'Adding...';
        
        const response = await fetch('../perfdb/add_custom_mix.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(cartData)
        });
        
        const result = await response.json();
        
        if (result.success) {
            alert('Custom mix added to cart successfully!');
            
            // Reset form
            selectedProducts = [];
            updateDisplay();
            // refresh global cart widget/badge if available
            if (window.refreshCartBadgeAndModal) {
                try { window.refreshCartBadgeAndModal(); } catch(e) { console.warn('refreshCart failed', e); }
            }
                // also attempt the lightweight badge-only refresh and dispatch a custom event
                if (window.refreshCartBadges) { try { window.refreshCartBadges(); } catch(e) { console.warn('refreshCartBadges failed', e); } }
                try { window.dispatchEvent(new Event('cart:updated')); } catch(e) { /* ignore */ }
        } else {
            alert('Error: ' + (result.message || result.error || 'Unknown error'));
        }
    } catch (error) {
        console.error('Error adding to cart:', error);
        // Arabic translation: "Failed to add to cart. Please try again."
        alert('فشل الإضافة إلى السلة. الرجاء المحاولة مرة أخرى.');
    } finally {
        addToCartBtn.disabled = false;
        addToCartBtn.innerHTML = `
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="9" cy="21" r="1"/>
                <circle cx="20" cy="21" r="1"/>
                <path d="M1 1h4l2.68 13.39a2 2 0 002 1.61h9.72a2 2 0 002-1.61L23 6H6"/>
            </svg>
            ADD TO CART - CUSTOM MIX
        `;
    }
}
