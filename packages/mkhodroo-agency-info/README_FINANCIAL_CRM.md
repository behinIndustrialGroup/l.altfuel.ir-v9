# ارسال اطلاعات مالی مراکز به CRM

## روت‌های موجود:

### 1. ارسال اطلاعات مالی
```
GET /agency-info/send-financial-to-crm
```

### 2. تست اطلاعات مالی
```
GET /agency-info/test-financial-crm
```

## عملکرد:
1. خواندن مراکزی که در CRM موجود هستند (دارای CRM Service Center ID)
2. برای هر مرکز:
   - دریافت اطلاعات مالی (membership، irngv، debt، plate_reader و...)
   - ایجاد رکورد جداگانه برای هر پرداخت در جدول مالی CRM
   - اتصال به Service Center از طریق lookup
3. نمایش پیشرفت و آمار کامل

## فیلدهای مالی پشتیبانی شده:
- **Memberships**: membership_96 تا membership_04
- **IRNGV**: irngv، irngv_fee
- **Debts**: debt1، debt2
- **Fees**: lock_fee، plate_reader

هر پرداخت شامل 3 فیلد است:
- مبلغ (amount)
- تاریخ پرداخت (pay_date)
- کد پیگیری (ref_id)

## فیلدهای CRM:
- `rhs_name` - نام پرداخت (کلید)
- `rhs_amount` - مبلغ
- `rhs_paymentdate` - تاریخ پرداخت
- `rhs_trackingcode` - کد پیگیری
- `rhs_year` - سال (برای membership ها)
- Lookup field - اتصال به Service Center

## نقشه‌برداری فیلدها:
```
Database → CRM
membership_96 → rhs_name
membership_96_pay_date → rhs_paymentdate
membership_96_ref_id → rhs_trackingcode
membership_96 (amount) → rhs_amount
```

## تشخیص خودکار جدول و فیلد Lookup:
سیستم به صورت خودکار جداول و فیلدهای مختلف را تست می‌کند:

### جداول تست شده:
- `rhs_financialinformationcenters`
- `rhs_financialinformationcenter`
- `new_financialinformation`
- `rhs_paymentinfo`
- `rhs_payment`

### فیلدهای Lookup تست شده:
- `rhs_servicecenter@odata.bind`
- `new_servicecenter@odata.bind`
- `rhs_servicecenterlookup@odata.bind`
- `rhs_servicecenterid@odata.bind`
- `_rhs_servicecenter_value@odata.bind`

## پیش‌نیازها:
- مراکز باید قبلاً در CRM ایجاد شده باشند (دارای `crm_service_center_id`)
- اطلاعات مالی در دیتابیس وجود داشته باشند

## استفاده:

### ارسال اطلاعات مالی:
```
https://your-domain.com/agency-info/send-financial-to-crm
```

### تست عملکرد:
```
https://your-domain.com/agency-info/test-financial-crm
```

## نکات مهم:
- این کنترلر فقط اطلاعات پرداخت‌ها را ارسال می‌کند
- فیلد `fin_green` جزو اطلاعات اصلی مراکز است و در کنترلر اصلی ارسال می‌شود
- برای ایجاد مراکز جدید از کنترلر `SendToCrmController` استفاده کنید
- سیستم به صورت خودکار نام صحیح جدول و فیلد lookup را تشخیص می‌دهد

## عیب‌یابی:
1. ابتدا از روت تست استفاده کنید تا مشکلات احتمالی شناسایی شوند
2. بررسی کنید که مراکز در CRM ایجاد شده باشند
3. اطمینان حاصل کنید که اطلاعات مالی در دیتابیس وجود دارند
4. لاگ‌های Laravel را برای جزئیات خطاها بررسی کنید