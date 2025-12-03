// معالجة نموذج تسجيل الدخول
const loginForm = document.getElementById('loginForm');
if (loginForm) {
    loginForm.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const email = document.getElementById('email').value;
        const password = document.getElementById('password').value;
        
        if (email && password) {
            try {
                const response = await fetch('../perfdb/login_api.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ email, password })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    alert('مرحباً ' + data.user.first_name + '!');
                    window.location.href = data.redirect;
                } else {
                    alert('خطأ: ' + data.error);
                }
            } catch (error) {
                console.error('خطأ:', error);
                alert('حدث خطأ في الاتصال بالسيرفر');
            }
        } else {
            alert('الرجاء إدخال جميع البيانات');
        }
    });
}

// معالجة نموذج التسجيل
const signupForm = document.getElementById('signupForm');
if (signupForm) {
    signupForm.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const firstName = document.getElementById('firstName').value;
        const lastName = document.getElementById('lastName').value;
        const email = document.getElementById('signupEmail').value;
        const password = document.getElementById('signupPassword').value;
        const confirmPassword = document.getElementById('confirmPassword').value;
        const phone = document.getElementById('signupPhone').value;
        const address = document.getElementById('signupAddress').value;
        
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
        if (firstName && lastName && email && password && confirmPassword && phone && address) {
            console.log('Form validation passed. Sending:');
            console.log('Phone:', phone);
            console.log('Address:', address);
            
            try {
                const response = await fetch('../perfdb/signup_api.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        first_name: firstName,
                        last_name: lastName,
                        email: email,
                        password: password,
                        phone: phone,
                        address: address,
                        role: 'customer'
                    })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    alert('تم إنشاء الحساب بنجاً!\nمرحباً ' + firstName + '، يتم إعادة توجيهك لإتمام طلبك');
                    
                    // Check if cart has items - if yes, proceed directly to checkout
                    if (data.auto_login === true) {
                        // Store flag to indicate user just signed up
                        sessionStorage.setItem('justSignedUp', 'true');
                        
                        // Redirect to checkout complete page which will auto-complete the order
                        setTimeout(() => {
                            window.location.href = 'checkout-complete.html';
                        }, 1000);
                    } else {
                        // Fallback to login page
                        setTimeout(() => {
                            location.replace('login.html');
                        }, 1500);
                    }
                } else {
                    alert('خطأ: ' + data.error);
                }
            } catch (error) {
                console.error('خطأ:', error);
                alert('حدث خطأ في الاتصال بالسيرفر');
            }
        } else {
            console.log('Form validation FAILED:');
            console.log('firstName:', firstName, '| isEmpty:', !firstName);
            console.log('lastName:', lastName, '| isEmpty:', !lastName);
            console.log('email:', email, '| isEmpty:', !email);
            console.log('password:', password, '| isEmpty:', !password);
            console.log('confirmPassword:', confirmPassword, '| isEmpty:', !confirmPassword);
            console.log('phone:', phone, '| isEmpty:', !phone);
            console.log('address:', address, '| isEmpty:', !address);
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

    const starsContainer = document.getElementById("stars");

    // عدد النجوم: يمكنك تغييره بين 100 - 500
    const STAR_COUNT = 200;

    // Only add stars if container exists
    if (starsContainer) {
        for (let i = 0; i < STAR_COUNT; i++) {
            const star = document.createElement("span");

            // مكان النجمة العشوائي
            star.style.top = Math.random() * 100 + "vh";
            star.style.left = Math.random() * 100 + "vw";

            // تأخير الوميض العشوائي
            star.style.animationDelay = (Math.random() * 3) + "s";

            // حجم النجوم مختلف شوي ليعطي واقعية
            const size = Math.random() * 2 + 1; 
            star.style.width = size + "px";
            star.style.height = size + "px";

            starsContainer.appendChild(star);
        }
    }


