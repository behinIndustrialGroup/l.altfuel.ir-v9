# سیستم پرداخت آنلاین بدهی‌ها

## نحوه کار

### 1. مشاهده بدهی‌ها
کاربر می‌تواند بدهی‌های مراکز خود را از طریق مسیر زیر مشاهده کند:
```
/agency-info/user-centers/{serviceCenterId}/debts
```

### 2. شروع پرداخت
- کاربر روی دکمه "پرداخت بدهی" کلیک می‌کند
- سیستم اطلاعات بدهی را از CRM دریافت می‌کند
- بررسی می‌شود که بدهی قبلاً پرداخت نشده باشد
- اطلاعات پرداخت در Cache ذخیره می‌شود (15 دقیقه)
- کاربر به درگاه پرداخت زیبال هدایت می‌شود

### 3. تایید پرداخت
- پس از پرداخت، کاربر به صفحه تایید برمی‌گردد
- سیستم پرداخت را با درگاه زیبال تایید می‌کند
- در صورت موفقیت، اطلاعات زیر در CRM ثبت می‌شود:
  - `rhs_paymentid`: کد رهگیری پرداخت
  - `rhs_debtpaymentdate`: تاریخ پرداخت

### 4. نمایش نتیجه
- صفحه نتیجه پرداخت با جزئیات کامل نمایش داده می‌شود
- شامل: کد رهگیری، مبلغ، عنوان بدهی، تاریخ پرداخت

## Routes

```php
// نمایش بدهی‌های یک مرکز
GET /agency-info/user-centers/{serviceCenterId}/debts

// شروع پرداخت بدهی
POST /agency-info/user-centers/debt/{debtId}/pay

// تایید پرداخت (callback از درگاه)
GET /agency-info/user-centers/verify-debt-payment
```

## فیلدهای CRM

### جدول: rhs_debtinformations
- `rhs_debtinformationid`: شناسه بدهی (GUID)
- `rhs_name`: عنوان بدهی
- `rhs_amountowed`: مبلغ بدهی (ریال)
- `rhs_debtpaymentdate`: تاریخ پرداخت
- `rhs_paymentid`: کد رهگیری پرداخت
- `_rhs_servicecentercode_value`: شناسه مرکز (lookup)

## نکات مهم

1. **امنیت**: اطلاعات پرداخت در Cache با TTL 15 دقیقه ذخیره می‌شود
2. **GUID Format**: GUID ها باید بدون براکت و lowercase باشند
3. **OData Filter**: در فیلترهای OData، GUID بدون کوتیشن استفاده می‌شود
4. **تایید دوگانه**: هم درگاه پرداخت و هم CRM تایید می‌شوند
5. **خطاها**: در صورت خطا در ثبت CRM، پیام مناسب به کاربر نمایش داده می‌شود

## پیکربندی

اطمینان حاصل کنید که تنظیمات زیبال در `config/zibal.php` به درستی تنظیم شده است:

```php
return [
    'merchant_id' => env('ZIBAL_MERCHANT_ID'),
    'pay_url' => env('ZIBAL_PAY_URL', 'https://gateway.zibal.ir/start/'),
    // ...
];
```

## مثال استفاده

```php
// در view بدهی‌ها
<form action="{{ route('agencyInfo.userCenters.payDebt', ['debtId' => $debt['rhs_debtinformationid']]) }}" method="POST">
    @csrf
    <button type="submit" class="btn btn-success">پرداخت بدهی</button>
</form>
```
