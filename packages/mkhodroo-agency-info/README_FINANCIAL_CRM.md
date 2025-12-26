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
   - ایجاد رکورد جداگانه برای هر پرداخت در جدول `rhs_financialinformationcenter`
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

## فیلدهای CRM (`rhs_financialinformationcenter`):
- `rhs_name` - نام پرداخت (کلید)
- `rhs_amount` - مبلغ
- `rhs_paymentdate` - تاریخ پرداخت
- `rhs_servicecenter` - Lookup به Service Center
- `rhs_trackingcode` - کد پیگیری
- `rhs_year` - سال (برای membership ها)

## نقشه‌برداری فیلدها:
```
Database → CRM
membership_96 → rhs_name
membership_96_pay_date → rhs_paymentdate
membership_96_ref_id → rhs_trackingcode
membership_96 (amount) → rhs_amount
```

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