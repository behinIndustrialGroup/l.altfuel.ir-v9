# راهنمای استفاده از صفحه تیکت‌های CRM

## توضیحات
این صفحه جدید برای مشاهده تیکت‌ها از CRM ساخته شده است. برخلاف صفحه معمولی تیکت‌ها که از دیتابیس محلی اطلاعات را می‌خواند، این صفحه مستقیماً از CRM با استفاده از Contact ID تیکت‌ها را دریافت می‌کند.

## مسیرها (Routes)

### 1. لیست تیکت‌ها
- **URL**: `/crm/tickets/list`
- **Method**: GET
- **پارامتر**: `contact_id` (اختیاری)
- **مثال**: `/crm/tickets/list?contact_id=12345678-1234-1234-1234-123456789012`

### 2. نمایش جزئیات تیکت
- **URL**: `/admin/crm/tickets/show`
- **Method**: POST (AJAX)
- **پارامتر**: `ticket_id` (الزامی)
- **Route Name**: `ATRoutes.crm.show`

### 3. افزودن کامنت به تیکت
- **URL**: `/admin/crm/tickets/add-comment`
- **Method**: POST (AJAX)
- **پارامترها**: 
  - `ticket_id` (الزامی)
  - `text` (الزامی)
- **Route Name**: `ATRoutes.crm.addComment`

## نحوه استفاده

### برای کاربران عادی:
1. به صفحه لیست تیکت‌های CRM بروید (`/admin/crm/tickets/list`)
2. اگر `crm_contact_id` در جدول users برای شما ثبت شده باشد، تیکت‌های شما خودکار نمایش داده می‌شود
3. برای مشاهده جزئیات هر تیکت، روی دکمه "مشاهده" کلیک کنید

### برای کارشناسان (با دسترسی Ticket-Actors):
1. به صفحه لیست تیکت‌های CRM بروید
2. شناسه مخاطب (Contact ID) را در فرم وارد کنید
3. روی دکمه "جستجو" کلیک کنید
4. لیست تیکت‌های مربوط به آن مخاطب نمایش داده می‌شود
5. برای مشاهده جزئیات هر تیکت، روی دکمه "مشاهده" کلیک کنید

## ویژگی‌ها

- ✅ دریافت خودکار Contact ID از جدول users (ستون `crm_contact_id`)
- ✅ دریافت تیکت‌ها از CRM بر اساس Contact ID
- ✅ نمایش جزئیات کامل تیکت شامل:
  - اطلاعات مخاطب (نام، شماره همراه، ایمیل)
  - عنوان و وضعیت تیکت
  - دسته‌بندی
  - تاریخ ایجاد و بروزرسانی
  - نوع تبدیل و امتیاز
- ✅ نمایش کامنت‌های تیکت
- ✅ افزودن کامنت جدید به تیکت در CRM
- ✅ تشخیص خودکار صاحب تیکت (is_owner)
- ✅ نمایش لحظه‌ای کامنت جدید بدون نیاز به رفرش صفحه
- ✅ رابط کاربری مشابه صفحه تیکت معمولی
- ✅ دسترسی متفاوت برای کاربران عادی و کارشناسان

## فایل‌های ایجاد شده

1. **Controller**: `packages/altfuel-ticket/src/Controllers/ShowCrmTicketController.php`
2. **View لیست**: `packages/altfuel-ticket/src/Views/crm-list.blade.php`
3. **View جزئیات**: `packages/altfuel-ticket/src/Views/crm-show.blade.php`
4. **Routes**: اضافه شده به `packages/altfuel-ticket/src/routes.php`

## نکات مهم

- این صفحه نیاز به CRM Client دارد که باید در پروژه نصب و پیکربندی شده باشد
- Contact ID باید یک GUID معتبر باشد
- تمام خطاها در Log ثبت می‌شوند

## رفع مشکلات احتمالی

### خطای 405 Method Not Allowed
- مطمئن شوید که از متد درست استفاده می‌کنید (GET برای لیست، POST برای نمایش)
- Cache route را پاک کنید: `php artisan route:clear`

### تیکت‌ها نمایش داده نمی‌شوند
- مطمئن شوید که `crm_contact_id` در جدول users برای کاربر ثبت شده است
- Contact ID را به درستی وارد کنید (باید یک GUID معتبر باشد)
- اتصال به CRM را بررسی کنید
- Log فایل‌ها را برای خطاهای احتمالی چک کنید

### نحوه ثبت Contact ID برای کاربر
برای ثبت Contact ID در جدول users:

```sql
UPDATE users SET crm_contact_id = '12345678-1234-1234-1234-123456789012' WHERE id = 1;
```

یا از طریق کد:

```php
$user = User::find($userId);
$user->crm_contact_id = '12345678-1234-1234-1234-123456789012';
$user->save();
```

## دستورات مفید

```bash
# پاک کردن cache route
php artisan route:clear

# مشاهده لیست route ها
php artisan route:list | grep crm

# پاک کردن تمام cache ها
php artisan optimize:clear
```
