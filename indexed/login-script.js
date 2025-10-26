// معالجة نموذج تسجيل الدخول
const loginForm = document.getElementById('loginForm');
if (loginForm) {
    loginForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const email = document.getElementById('email').value;
        const password = document.getElementById('password').value;
        
        // التحقق من البيانات
        if (email && password) {
            // هنا يمكنك إضافة كود للتحقق من البيانات مع السيرفر
            console.log('تسجيل الدخول:', { email, password });
            alert('تم تسجيل الدخول بنجاح!\nالبريد الإلكتروني: ' + email);
            
            // يمكنك توجيه المستخدم لصفحة أخرى
            // window.location.href = 'index.html';
        } else {
            alert('الرجاء إدخال جميع البيانات');
        }
    });
}

// معالجة نموذج التسجيل
const signupForm = document.getElementById('signupForm');
if (signupForm) {
    signupForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const fullname = document.getElementById('fullname').value;
        const email = document.getElementById('signupEmail').value;
        const password = document.getElementById('signupPassword').value;
        const confirmPassword = document.getElementById('confirmPassword').value;
        
        // التحقق من تطابق كلمات المرور
        if (password !== confirmPassword) {
            alert('كلمات المرور غير متطابقة!');
            return;
        }
        
        // التحقق من طول كلمة المرور
        if (password.length < 6) {
            alert('كلمة المرور يجب أن تكون 6 أحرف على الأقل');
            return;
        }
        
        // التحقق من صيغة البريد الإلكتروني
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(email)) {
            alert('الرجاء إدخال بريد إلكتروني صحيح');
            return;
        }
        
        // التحقق من جميع الحقول
        if (fullname && email && password && confirmPassword) {
            // هنا يمكنك إضافة كود لإرسال البيانات للسيرفر
            console.log('إنشاء حساب:', { fullname, email, password });
            alert('تم إنشاء الحساب بنجاح!\nمرحباً ' + fullname);
            
            // توجيه المستخدم لصفحة تسجيل الدخول
            setTimeout(() => {
                window.location.href = 'login.html';
            }, 1500);
        } else {
            alert('الرجاء إدخال جميع البيانات');
        }
    });
}

// إنشاء نجوم إضافية متحركة
function createFloatingStars() {
    const starsContainer = document.querySelector('.stars');
    const starCount = 30;
    
    for (let i = 0; i < starCount; i++) {
        const star = document.createElement('div');
        const size = Math.random() * 3 + 1;
        const duration = Math.random() * 3 + 2;
        const delay = Math.random() * 3;
        
        star.style.cssText = `
            position: absolute;
            width: ${size}px;
            height: ${size}px;
            background: white;
            border-radius: 50%;
            top: ${Math.random() * 100}%;
            left: ${Math.random() * 100}%;
            animation: twinkle ${duration}s infinite ${delay}s;
            box-shadow: 0 0 ${size * 2}px rgba(255, 255, 255, 0.8);
        `;
        
        starsContainer.appendChild(star);
    }
}

// تأثير حركة النجوم مع الماوس
let mouseX = 0;
let mouseY = 0;

document.addEventListener('mousemove', function(e) {
    mouseX = e.clientX / window.innerWidth - 0.5;
    mouseY = e.clientY / window.innerHeight - 0.5;
});

function animateStars() {
    const stars = document.querySelector('.stars');
    if (stars) {
        const moveX = mouseX * 30;
        const moveY = mouseY * 30;
        stars.style.transform = `translate(${moveX}px, ${moveY}px)`;
    }
    requestAnimationFrame(animateStars);
}

// تشغيل التأثيرات عند تحميل الصفحة
window.addEventListener('load', function() {
    createFloatingStars();
    animateStars();
});

// تأثير على الإدخالات
const inputs = document.querySelectorAll('input');
inputs.forEach(input => {
    input.addEventListener('focus', function() {
        this.parentElement.style.transform = 'scale(1.02)';
        this.parentElement.style.transition = 'transform 0.3s ease';
    });
    
    input.addEventListener('blur', function() {
        this.parentElement.style.transform = 'scale(1)';
    });
});

// منع إرسال النموذج عند الضغط على Enter إلا إذا كان في زر Submit
document.addEventListener('keypress', function(e) {
    if (e.key === 'Enter' && e.target.type !== 'submit') {
        const form = e.target.closest('form');
        if (form) {
            const submitBtn = form.querySelector('button[type="submit"]');
            if (submitBtn) {
                submitBtn.click();
            }
        }
    }
});
