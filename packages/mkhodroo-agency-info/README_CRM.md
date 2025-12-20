# ارسال مراکز به CRM

## روت:
```
GET /agency-info/send-to-crm
```

## عملکرد:
1. خواندن اطلاعات مراکز از جدول `agency_info`
2. تقسیم داده‌ها به chunk های 10 تایی
3. برای هر chunk:
   - پردازش هر مرکز
   - جستجوی Contact بر اساس شماره موبایل
   - ایجاد Contact جدید اگر وجود نداشت
   - ایجاد Service Center با lookup به Contact
   - نمایش پیشرفت به صورت زنده

## ویژگی‌های پردازش:
- **Chunk Processing**: داده‌ها 10 تا 10 تا پردازش می‌شوند
- **Live Progress**: پیشرفت به صورت زنده نمایش داده می‌شود
- **Error Handling**: خطاها نمایش داده می‌شوند اما پردازش متوقف نمی‌شود
- **Delay**: بین هر رکورد 0.2 ثانیه و بین هر chunk 1 ثانیه استراحت
- **Logging**: تمام فعالیت‌ها در لاگ ثبت می‌شوند

## فیلدهای مورد نیاز در CRM:

### Entity: `rhs_servicecenter`
- `rhs_row` - نوع مشتری (customer_type)
- `rhs_fullname` - نام (firstname)
- `rhs_lastname` - نام خانوادگی (lastname)
- `rhs_yearofreceivingthecode` - سال دریافت کد (recieving_code_year)
- `rhs_nationalcode` - کد ملی (national_id)
- `rhs_servicecenterid` - کد نمایندگی (agency_code)
- `rhs_address` - آدرس (address)
- `rhs_guildnumber` - شماره صنفی (guild_number)
- `rhs_mobile` - موبایل (mobile)
- `rhs_phone` - تلفن (phone)
- `rhs_dateofissue` - تاریخ صدور (issued_date)
- `rhs_expirydate` - تاریخ انقضا (exp_date)
- `rhs_description` - توضیحات (description)
- `rhs_province` - استان (province)
- `rhs_city` - شهر (city)
- `rhs_postalcode` - کد پستی (postal_code)
- `statecode` - وضعیت فعال/غیرفعال (enable) - 0=فعال, 1=غیرفعال
- `rhs_location` - موقعیت (location)
- `rhs_contact` - Lookup به Contact

### Entity: `contacts` (استاندارد)
- `firstname` - نام
- `lastname` - نام خانوادگی
- `fullname` - نام کامل
- `mobilephone` - موبایل
- `telephone1` - تلفن
- `address1_line1` - آدرس

## فیلدهای ارسالی از دیتابیس:
customer_type → rhs_row
firstname → rhs_fullname  
lastname → rhs_lastname
recieving_code_year → rhs_yearofreceivingthecode
national_id → rhs_nationalcode
agency_code → rhs_servicecenterid
address → rhs_address
guild_number → rhs_guildnumber
mobile → rhs_mobile
phone → rhs_phone
issued_date → rhs_dateofissue
exp_date → rhs_expirydate
description → rhs_description
province → rhs_province
city → rhs_city
postal_code → rhs_postalcode
enable → statecode (0=فعال, 1=غیرفعال)
location → rhs_location

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