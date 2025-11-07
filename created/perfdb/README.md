# 🌟 دليل إعداد قاعدة البيانات - Perfume Database Guide

## 📋 الملفات المتوفرة

1. **connect.php** - ملف الاتصال بقاعدة البيانات
2. **admin-panel.php** - لوحة التحكم الإدارية (واجهة رسومية)
3. **setup-database.sql** - ملف SQL لإنشاء الجداول والبيانات
4. **api-get-perfumes.php** - API للحصول على البيانات بصيغة JSON

---

## 🚀 خطوات التشغيل

### 1️⃣ تشغيل XAMPP
- افتح XAMPP Control Panel
- شغّل **Apache** و **MySQL**

### 2️⃣ إنشاء قاعدة البيانات
#### طريقة 1: عبر phpMyAdmin
1. افتح المتصفح واذهب إلى: http://localhost/phpmyadmin
2. اضغط على "Import" أو "استيراد"
3. اختر ملف `setup-database.sql`
4. اضغط "Go" أو "تنفيذ"

#### طريقة 2: عبر SQL Tab
1. افتح phpMyAdmin
2. اضغط على "SQL" في الأعلى
3. انسخ محتويات ملف `setup-database.sql` والصقها
4. اضغط "Go"

### 3️⃣ فتح لوحة التحكم
افتح المتصفح واذهب إلى:
```
http://localhost/pefumeppp/created/perfdb.db/admin-panel.php
```

---

## 🎯 استخدام لوحة التحكم

### ✨ المميزات المتوفرة:

#### 📊 الإحصائيات
- عرض إجمالي عدد العطور
- عدد العطور الرجالية
- عدد العطور النسائية
- عدد العطور للجنسين

#### ➕ إضافة عطر جديد
املأ الحقول التالية:
- **اسم العطر**: مثل "Blue de Chanel"
- **العلامة التجارية**: اختر من القائمة
  - CHANEL
  - DIOR
  - TOM FORD
  - VERSACE
  - HERMÈS
  - GUCCI
  - PACO RABANNE
- **الفئة**: 
  - man (رجالي)
  - woman (نسائي)
  - unisex (للجنسين)
- **السعر**: بالدولار مثل 120.00
- **اسم الصورة**: مثل perfume1.jpg

#### ✏️ تعديل عطر
- اضغط على زر "تعديل" بجانب العطر المراد تعديله
- ستظهر نافذة منبثقة
- عدّل البيانات واضغط "حفظ التعديلات"

#### ❌ حذف عطر
- اضغط على زر "حذف" بجانب العطر
- أكّد عملية الحذف

#### 📋 عرض جميع العطور
- جدول يعرض جميع العطور مع تفاصيلها
- فلترة حسب الفئة (من خلال الألوان)
- ترتيب حسب الأحدث

---

## 🔌 استخدام API

### جلب جميع العطور:
```
http://localhost/pefumeppp/created/perfdb.db/api-get-perfumes.php
```

### جلب عطور حسب الفئة:
```
http://localhost/pefumeppp/created/perfdb.db/api-get-perfumes.php?category=woman
http://localhost/pefumeppp/created/perfdb.db/api-get-perfumes.php?category=man
http://localhost/pefumeppp/created/perfdb.db/api-get-perfumes.php?category=unisex
```

### جلب عطور حسب العلامة التجارية:
```
http://localhost/pefumeppp/created/perfdb.db/api-get-perfumes.php?brand=chanel
http://localhost/pefumeppp/created/perfdb.db/api-get-perfumes.php?brand=dior
```

### جلب عطور حسب الفئة والعلامة مع تحديد العدد:
```
http://localhost/pefumeppp/created/perfdb.db/api-get-perfumes.php?category=woman&brand=chanel&limit=6
```

### مثال على الناتج (JSON):
```json
{
    "success": true,
    "count": 6,
    "data": [
        {
            "id": 1,
            "name": "Chanel No. 5",
            "brand": "chanel",
            "category": "woman",
            "price": 120.0,
            "image": "perfume1.jpg"
        },
        ...
    ]
}
```

---

## 🔧 إعدادات الاتصال (connect.php)

```php
$host = '127.0.0.1';    // عنوان السيرفر
$user = 'root';          // اسم المستخدم
$pass = '';              // كلمة المرور (فارغة افتراضياً)
$db   = 'perfume-db';    // اسم قاعدة البيانات
$port = 3306;            // المنفذ
```

---

## 📱 التصميم المتجاوب (Responsive)

اللوحة متجاوبة تماماً وتعمل على:
- 💻 أجهزة الكمبيوتر
- 📱 الهواتف المحمولة
- 📱 الأجهزة اللوحية

---

## 🎨 الألوان والتصميم

- **اللون الذهبي**: #D4AF37 (اللون الرئيسي)
- **خلفية داكنة**: #0a0a0a
- **تأثيرات**: Hover effects, Gradients, Shadows
- **تصميم عصري**: Modern UI with smooth animations

---

## 🛠️ استكشاف الأخطاء

### ❌ خطأ في الاتصال:
```
Connection failed: ...
```
**الحل**: تأكد من:
- تشغيل MySQL في XAMPP
- صحة بيانات الاتصال في connect.php
- وجود قاعدة البيانات perfume-db

### ❌ الصفحة فارغة:
**الحل**: 
- تأكد من تشغيل Apache
- تحقق من المسار الصحيح في المتصفح
- افحص أخطاء PHP (php_error.log)

### ❌ لا توجد بيانات في الجدول:
**الحل**:
- قم بتشغيل ملف setup-database.sql
- أو أضف البيانات يدوياً عبر لوحة التحكم

---

## 📞 الدعم

إذا واجهت أي مشكلة:
1. تحقق من تشغيل XAMPP (Apache + MySQL)
2. تحقق من وجود قاعدة البيانات
3. تحقق من صلاحيات الملفات
4. تحقق من أخطاء PHP في console المتصفح

---

## ✅ اكتمل التحضير!

الآن يمكنك:
- ✅ إدارة العطور بسهولة
- ✅ إضافة وتعديل وحذف
- ✅ عرض إحصائيات
- ✅ استخدام API للحصول على البيانات

---

**تم بحمد الله 🌟**
