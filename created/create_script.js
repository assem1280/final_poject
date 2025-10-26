
// البحث عن العطور
const searchInput = document.getElementById('searchInput');
const searchBtn = document.querySelector('.search-btn');
const scentCards = document.querySelectorAll('.scent-card');

// قائمة أسماء العطور
const scentNames = {
    'jasmine': 'Jasmine',
    'lavender': 'Lavender',
    'amber': 'Amber',
    'sandalwood': 'Sandalwood',
    'ocean': 'Ocean',
    'rose': 'Rose',
    'cherry': 'Cherry',
    'aqua': 'Aqua',
    'violet': 'Violet',
    'musk': 'Musk',
    'oud': 'Oud',
    'vanilla': 'Vanilla',
    'blue': 'Blue',
    'gold': 'Gold'
};

// وظيفة البحث
function searchScents() {
    const searchTerm = searchInput.value.toLowerCase().trim();
    
    scentCards.forEach(card => {
        const scentName = card.getAttribute('data-scent').toLowerCase();
        const fullName = scentNames[scentName] ? scentNames[scentName].toLowerCase() : scentName;
        
        if (searchTerm === '' || fullName.includes(searchTerm) || scentName.includes(searchTerm)) {
            card.style.display = 'flex';
            card.style.animation = 'fadeIn 0.3s ease';
        } else {
            card.style.display = 'none';
        }
    });
}

// عند الضغط على زر البحث
searchBtn.addEventListener('click', searchScents);

// عند الكتابة في حقل البحث
searchInput.addEventListener('input', searchScents);

// عند الضغط على Enter
searchInput.addEventListener('keypress', function(e) {
    if (e.key === 'Enter') {
        searchScents();
    }
});

// تحديث قيم الـ Sliders
const sliders = document.querySelectorAll('.scent-slider');
const totalGramsElement = document.getElementById('totalGrams');

sliders.forEach(slider => {
    const sliderItem = slider.closest('.slider-item');
    const valueDisplay = sliderItem.querySelector('.slider-value');
    
    // تحديث القيمة عند تحريك الـ slider
    slider.addEventListener('input', function() {
        valueDisplay.textContent = this.value;
        updateTotalGrams();
    });
});

// حساب المجموع الكلي
function updateTotalGrams() {
    let total = 0;
    sliders.forEach(slider => {
        total += parseInt(slider.value);
    });
    totalGramsElement.textContent = total;
}

// تحديد العطور (Checkboxes)
const checkboxes = document.querySelectorAll('.scent-check');

checkboxes.forEach(checkbox => {
    checkbox.addEventListener('change', function() {
        const card = this.closest('.scent-card');
        if (this.checked) {
            card.style.borderColor = '#d4af37';
            card.style.boxShadow = '0 5px 20px rgba(212, 175, 55, 0.5)';
        } else {
            card.style.borderColor = 'rgba(212, 175, 55, 0.2)';
            card.style.boxShadow = 'none';
        }
    });
});

// تحديد الزجاجة
const bottleOptions = document.querySelectorAll('.bottle-option');

bottleOptions.forEach(bottle => {
    bottle.addEventListener('click', function() {
        // إزالة التحديد من جميع الزجاجات
        bottleOptions.forEach(b => {
            b.querySelector('.bottle-circle').style.borderColor = 'rgba(212, 175, 55, 0.3)';
            b.querySelector('.bottle-circle').style.boxShadow = 'none';
        });
        
        // تحديد الزجاجة المختارة
        const circle = this.querySelector('.bottle-circle');
        circle.style.borderColor = '#d4af37';
        circle.style.boxShadow = '0 0 20px rgba(212, 175, 55, 0.6)';
    });
});

// إضافة animation للبطاقات
const style = document.createElement('style');
style.textContent = `
    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: scale(0.9);
        }
        to {
            opacity: 1;
            transform: scale(1);
        }
    }
`;
document.head.appendChild(style);

// زر إضافة للسلة
const addCartBtn = document.querySelector('.add-cart-btn');

addCartBtn.addEventListener('click', function() {
    // التحقق من اختيار العطور
    const selectedScents = Array.from(checkboxes).filter(cb => cb.checked);
    
    if (selectedScents.length === 0) {
        alert('Please select at least one scent!');
        return;
    }
    
    // رسالة نجاح
    this.textContent = '✓ ADDED TO CART';
    this.style.background = 'linear-gradient(135deg, #4CAF50 0%, #45a049 100%)';
    
    setTimeout(() => {
        this.innerHTML = `
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="9" cy="21" r="1"/>
                <circle cx="20" cy="21" r="1"/>
                <path d="M1 1h4l2.68 13.39a2 2 0 002 1.61h9.72a2 2 0 002-1.61L23 6H6"/>
            </svg>
            ADD TO CART - CUSTOM PERFUME
        `;
        this.style.background = 'linear-gradient(135deg, #d4af37 0%, #c49f27 100%)';
    }, 2000);
});

console.log('Craft Your Perfume - Script Loaded ✨');
