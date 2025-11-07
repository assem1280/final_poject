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
        card.style.cursor = 'pointer';
        // Use correct image path from database
        const imagePath = perfume.image_url ? `../images/${perfume.image_url}` : '../images/perfumes/default-perfume.jpg';
        card.innerHTML = `
            <div class="perfume-image-container">
                <img src="${imagePath}" alt="${perfume.name}">
            </div>
            <h3 class="perfume-name">${perfume.name}</h3>
            <p class="perfume-price">$${perfume.price}</p>
            <button class="add-to-cart-btn" data-product-id="${perfume.id}">
                <svg class="cart-icon" viewBox="0 0 24 24" width="16" height="16">
                    <path d="M7 18c-1.1 0-1.99.9-1.99 2S5.9 22 7 22s2-.9 2-2-.9-2-2-2zM1 2v2h2l3.6 7.59-1.35 2.45c-.16.28-.25.61-.25.96 0 1.1.9 2 2 2h12v-2H7.42c-.14 0-.25-.11-.25-.25l.03-.12.9-1.63h7.45c.75 0 1.41-.41 1.75-1.03l3.58-6.49c.08-.14.12-.31.12-.48 0-.55-.45-1-1-1H5.21l-.94-2H1zm16 16c-1.1 0-1.99.9-1.99 2s.89 2 1.99 2 2-.9 2-2-.9-2-2-2z" fill="currentColor"/>
                </svg>
            </button>
        `;
        
        // عند الضغط على الكارد (غير الزر)، انتقل لصفحة التفاصيل
        card.addEventListener('click', function(e) {
            // إذا ماكان الضغط على زر السلة
            if (!e.target.closest('.add-to-cart-btn')) {
                const perfumeName = perfume.name;
                console.log('Navigating to perfume:', perfumeName);
                window.location.href = `../perfdb/perfume-details.php?id=${perfume.id}`;
            }
        });
        
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

    // Add to Cart functionality - using PHP
    document.addEventListener('click', async function(e) {
        const btn = e.target.closest('.add-to-cart-btn');
        if (btn) {
            const productId = btn.getAttribute('data-product-id');
            const perfumeCard = btn.closest('.perfume-card');
            const perfumeName = perfumeCard.querySelector('.perfume-name').textContent;
            
            // Add animation effect
            btn.style.transform = 'scale(0.95)';
            setTimeout(() => { btn.style.transform = ''; }, 150);
            
            try {
                const response = await fetch('../perfdb/add_to_cart.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ 
                        product_id: parseInt(productId), 
                        quantity: 1 
                    })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    alert(`Added ${perfumeName} to cart!`);
                } else {
                    alert('Error: ' + (data.error || 'Failed to add to cart'));
                }
            } catch (error) {
                console.error('Error adding to cart:', error);
                alert('Failed to add to cart. Please try again.');
            }
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
                    id: p.p_id,
                    name: p.p_name,
                    price: p.price,
                    image_url: p.image_url,
                    description: p.description,
                    stock: p.stock,
                    brand: p.brand_name,
                    gender: p.gender_name
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