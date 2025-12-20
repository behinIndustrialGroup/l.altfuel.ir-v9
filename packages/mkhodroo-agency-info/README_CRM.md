# ارسال مراکز به CRM

## روت:
```
GET /agency-info/send-to-crm
```

## عملکرد:
1. خواندن اطلاعات مراکز از جدول `agency_info`
2. برای هر مرکز:
   - جستجوی Contact بر اساس شماره موبایل
   - ایجاد Contact جدید اگر وجود نداشت
   - ایجاد Service Center با lookup به Contact

## فیلدهای مورد نیاز در CRM:

### Entity: `rhs_servicecenter`
- `rhs_name` - نام مرکز
- `rhs_agency_code` - کد نمایندگی
- `rhs_mobile` - موبایل
- `rhs_phone` - تلفن
- `rhs_address` - آدرس
- `rhs_national_id` - کد ملی
- `rhs_province` - استان
- `rhs_city` - شهر
- `rhs_contact` - Lookup به Contact

### Entity: `contacts` (استاندارد)
- `firstname` - نام
- `lastname` - نام خانوادگی
- `fullname` - نام کامل
- `mobilephone` - موبایل
- `telephone1` - تلفن
- `address1_line1` - آدرس

## استفاده:
```
https://your-domain.com/agency-info/send-to-crm
```

## پاسخ نمونه:
```json
{
  "success": true,
  "message": "ارسال کامل شد. موفق: 45، خطا: 3",
  "total": 48,
  "success_count": 45,
  "error_count": 3,
  "results": [...]
}
```