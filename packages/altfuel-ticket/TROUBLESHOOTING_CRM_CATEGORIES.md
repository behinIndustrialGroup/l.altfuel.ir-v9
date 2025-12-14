# عیب‌یابی مشکلات دسته‌بندی CRM

## مراحل عیب‌یابی

### 1. بررسی ساختار دسته‌بندی‌ها در CRM
برای بررسی ساختار دسته‌بندی‌ها در CRM، از URL زیر استفاده کنید:

```
GET /crm/tickets/debug-category-structure
```

این endpoint اطلاعات زیر را برمی‌گرداند:
- تمام دسته‌بندی‌ها
- دسته‌بندی‌های والد
- نمونه جستجوی زیردسته‌ها

### 2. بررسی لاگ‌ها
لاگ‌های مربوط به دسته‌بندی‌ها در فایل‌های لاگ Laravel قابل مشاهده است:

```bash
tail -f storage/logs/laravel.log | grep -i "category\|parent"
```

### 3. مشکلات رایج

#### مشکل: دسته‌بندی‌های والد نمایش داده نمی‌شوند
**علت احتمالی:** 
- عدم وجود دسته‌بندی در CRM
- مشکل در اتصال به CRM
- ساختار نادرست entity در CRM

**راه حل:**
1. از debug endpoint استفاده کنید
2. لاگ‌ها را بررسی کنید
3. دسترسی‌های CRM را چک کنید

#### مشکل: زیردسته‌ها لود نمی‌شوند
**علت احتمالی:**
- فیلتر نادرست در OData query
- GUID نادرست parent_id
- عدم وجود رابطه parent-child در CRM

**راه حل:**
1. Console browser را باز کنید (F12)
2. خطاهای JavaScript را بررسی کنید
3. Network tab را چک کنید تا ببینید آیا درخواست ارسال می‌شود
4. Response را بررسی کنید

### 4. تست دستی

#### تست دریافت دسته‌بندی‌های والد:
```javascript
// در console browser
$.get('/crm/tickets/debug-category-structure')
  .done(function(data) { console.log('Categories:', data); })
  .fail(function(xhr) { console.error('Error:', xhr); });
```

#### تست دریافت زیردسته‌ها:
```javascript
// جایگزین PARENT_ID_HERE با ID واقعی
$.get('/crm/tickets/child-categories?parent_id=PARENT_ID_HERE')
  .done(function(data) { console.log('Child categories:', data); })
  .fail(function(xhr) { console.error('Error:', xhr); });
```

### 5. بررسی ساختار CRM

در CRM باید موارد زیر وجود داشته باشد:

1. **Entity:** `new_ticketcategory`
2. **فیلدهای مورد نیاز:**
   - `new_ticketcategoryid` (Primary Key)
   - `new_name` (نام دسته‌بندی)
   - `new_parent_id` (رابطه به والد)

3. **رابطه Parent-Child:** 
   - دسته‌بندی‌های والد: `new_parent_id` خالی یا null
   - زیردسته‌ها: `new_parent_id` شامل ID والد

### 6. نکات مهم

- همیشه GUID ها را از براکت‌ها و فاصله‌ها پاک کنید
- از لاگ‌ها برای debug استفاده کنید
- ساختار response های CRM را بررسی کنید
- دسترسی‌های کاربر به CRM را چک کنید