# MANON | مانون — Premium Abaya Store

**متجر عبايات مانون الفاخرة — بدون قاعدة بيانات**

[![PHP](https://img.shields.io/badge/PHP-7.4+-blue)](https://php.net)
[![No Database](https://img.shields.io/badge/Database-None%20Required-green)](.)
[![Bootstrap](https://img.shields.io/badge/Bootstrap-5.3-purple)](https://getbootstrap.com)

---

## 🎯 المميزات

- ✅ **بدون قاعدة بيانات** — يعمل بملفات JSON فقط
- ✅ **تخزين JSON آمن** — قفل الملفات لمنع التلف
- ✅ **لوحة تحكم كاملة** — إدارة المنتجات والطلبات والفئات
- ✅ **نظام واتساب** — إرسال الطلبات مباشرة عبر واتساب
- ✅ **عجلة الحظ** — أكواد خصم تُولَّد تلقائياً
- ✅ **سلة تسوق** — تعتمد على الجلسات
- ✅ **متجاوب تماماً** — يعمل على الجوال والتابلت والكمبيوتر
- ✅ **تصميم فاخر** — Bootstrap 5 RTL مع CSS مخصص

---

## 📦 متطلبات التشغيل

| المطلب | الحد الأدنى |
|--------|------------|
| PHP    | 7.4+       |
| PHP Extensions | `fileinfo` (لرفع الصور) |
| خادم ويب | Apache / Nginx / أي PHP server |
| **قاعدة بيانات** | **غير مطلوبة** |

---

## 🚀 التثبيت — 3 خطوات فقط

### الخطوة 1: رفع الملفات
```
ارفع جميع ملفات المشروع إلى مجلد الاستضافة (public_html أو htdocs)
```

### الخطوة 2: ضبط الصلاحيات
```bash
# على Linux/Apache
chmod 755 data/
chmod 755 uploads/products/
chmod 755 uploads/categories/
chmod 755 uploads/special-orders/
```

> على Windows (XAMPP/WAMP) لا حاجة لتغيير الصلاحيات عادةً.

### الخطوة 3: فتح الموقع
افتح `setup.php` للتحقق من أن كل شيء يعمل، ثم احذفه.

**انتهى! الموقع جاهز تماماً.**

---

## 🔑 بيانات دخول المشرف

| الحقل | القيمة |
|-------|--------|
| البريد الإلكتروني | `admin@manon.com` |
| كلمة المرور | `admin123456` |
| رابط لوحة التحكم | `/admin/login.php` |

> ⚠️ **مهم:** غير كلمة المرور فور تسجيل الدخول من **إعدادات الموقع** في لوحة التحكم.

---

## 📁 هيكل المشروع

```
MANON/
├── admin/               # لوحة التحكم
│   ├── login.php
│   ├── dashboard.php
│   ├── products.php
│   ├── product-add.php
│   ├── product-edit.php
│   ├── categories.php
│   ├── colors.php
│   ├── orders.php
│   ├── order-details.php
│   ├── reviews.php
│   ├── discount-codes.php
│   ├── special-orders.php
│   ├── settings.php
│   └── customers.php
│
├── assets/              # CSS, JS, صور
│   ├── css/style.css
│   ├── js/main.js
│   └── images/
│
├── data/                # ملفات البيانات (محمية من الوصول المباشر)
│   ├── products.json
│   ├── categories.json
│   ├── colors.json
│   ├── orders.json
│   ├── reviews.json
│   ├── special_orders.json
│   ├── discount_codes.json
│   ├── settings.json
│   └── admin.json
│
├── includes/            # ملفات PHP المشتركة
│   ├── config.php
│   ├── json-storage.php  ← مكتبة JSON CRUD
│   ├── functions.php
│   ├── header.php
│   └── footer.php
│
├── uploads/             # الصور المرفوعة
│   ├── products/
│   ├── categories/
│   └── special-orders/
│
├── api/                 # نقاط AJAX
│   ├── cart.php
│   ├── reviews.php
│   ├── special-order.php
│   └── wheel.php
│
├── index.php            # الصفحة الرئيسية
├── shop.php             # متجر العبايات
├── product.php          # تفاصيل المنتج
├── cart.php             # سلة التسوق
├── checkout.php         # إتمام الطلب
├── categories.php       # تصنيفات العبايات
├── about.php            # عن مانون
├── contact.php          # تواصل معنا
├── setup.php            # فحص التثبيت (احذفه بعد التثبيت)
└── .htaccess
```

---

## 🔒 الأمان

- **ملفات JSON محمية** بـ `.htaccess` — لا يمكن الوصول إليها مباشرة من المتصفح
- **تشفير كلمة المرور** — bcrypt عبر `password_hash()` / `password_verify()`
- **حماية CSRF** — توكن في جميع النماذج
- **XSS Prevention** — `htmlspecialchars()` على جميع المخرجات
- **رفع الصور** — التحقق من MIME type والحجم وإعادة التسمية عشوائياً
- **قفل الملفات** — `flock()` لمنع تلف بيانات JSON عند الطلبات المتزامنة

---

## 📱 واتساب

رقم الواتساب: **+20 12 73572887**

عند تأكيد الطلب، يُولَّد رسالة واتساب كاملة تشمل:
- رقم الطلب (مثل: `MANON-2026-0001`)
- بيانات العميل
- قائمة المنتجات مع الألوان والمقاسات
- الإجمالي وطريقة الدفع

---

## ⚙️ تغيير إعدادات المتجر

من **لوحة التحكم > الإعدادات** يمكنك تغيير:
- اسم المتجر، الشعار، البريد الإلكتروني
- رقم الواتساب
- العملة
- تفعيل/تعطيل عجلة الحظ
- رسالة الإعلان في الشريط العلوي

---

## 📊 نظام التخزين JSON

| الملف | الوصف |
|-------|-------|
| `products.json` | جميع منتجات العبايات |
| `categories.json` | تصنيفات المنتجات |
| `colors.json` | الألوان المتاحة |
| `orders.json` | طلبات العملاء |
| `reviews.json` | تقييمات العملاء |
| `special_orders.json` | طلبات التفصيل الخاص |
| `discount_codes.json` | أكواد الخصم |
| `settings.json` | إعدادات الموقع |
| `admin.json` | بيانات حساب المشرف |

---

## 🛠️ تغيير كلمة مرور المشرف

من **لوحة التحكم > الإعدادات > تغيير كلمة المرور**.

أو يدوياً في `data/admin.json`:
```json
{
    "email": "admin@manon.com",
    "password_hash": "$2y$10$...(hash جديد)...",
    "name": "MANON Admin",
    "role": "admin"
}
```

لتوليد hash جديد:
```php
echo password_hash('كلمة_المرور_الجديدة', PASSWORD_BCRYPT);
```

---

## 📞 الدعم

- واتساب: **+20 12 73572887**
- البريد الإلكتروني: **contact@manon-abaya.com**

---

*MANON | مانون — عبايتك فخامة تليق بك* 🤍
