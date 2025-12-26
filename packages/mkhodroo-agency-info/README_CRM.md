# ارسال مراکز به CRM

## روت‌های موجود:

### 1. ارسال مراکز به CRM
```
GET /agency-info/send-to-crm
```

### 2. ارسال اطلاعات مالی به CRM
```
GET /agency-info/send-financial-to-crm
```

### 3. آمار مراکز در CRM
```
GET /agency-info/crm-stats
```

### 4. تست و Debug
```
GET /agency-info/debug-crm
GET /agency-info/test-minimal
```

## عملکرد ارسال اطلاعات مالی:
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

## عملکرد ارسال:
1. خواندن اطلاعات مراکز از جدول `agency_info`
2. **بررسی مراکز موجود**: چک کردن اینکه آیا مرکز قبلاً در CRM ایجاد شده است
3. **رد کردن مراکز تکراری**: مراکزی که CRM ID دارند رد می‌شوند
4. تقسیم داده‌های جدید به chunk های 10 تایی
5. برای هر chunk:
   - پردازش هر مرکز جدید
   - جستجوی Contact بر اساس شماره موبایل
   - ایجاد Contact جدید اگر وجود نداشت
   - ایجاد Service Center با lookup به Contact
   - **ذخیره CRM Service Center ID در دیتابیس** (key: `crm_service_center_id`)
   - نمایش پیشرفت به صورت زنده

## ویژگی‌های پردازش:
- **Duplicate Prevention**: جلوگیری از ایجاد مراکز تکراری در CRM
- **Chunk Processing**: داده‌ها 10 تا 10 تا پردازش می‌شوند
- **Live Progress**: پیشرفت به صورت زنده نمایش داده می‌شود
- **Error Handling**: خطاها نمایش داده می‌شوند اما پردازش متوقف نمی‌شود
- **CRM ID Storage**: آیدی Service Center در CRM به دیتابیس محلی ذخیره می‌شود
- **Statistics**: نمایش آمار کامل (موفق، رد شده، خطا)
- **Delay**: بین هر رکورد 0.2 ثانیه و بین هر chunk 1 ثانیه استراحت
- **Logging**: تمام فعالیت‌ها در لاگ ثبت می‌شوند

## فیلدهای مورد نیاز در CRM:

### Entity: `rhs_servicecenter`
- `rhs_name` - نام کامل مرکز (name)
- `rhs_row` - نوع مشتری (customer_type)
- `rhs_fullname` - نام (firstname)
- `rhs_lastname` - نام خانوادگی (lastname)
- `rhs_yearofreceivingthecode` - سال دریافت کد (recieving_code_year)
- `rhs_nationalcode` - کد ملی (national_id)
- `rhs_centercode` - کد نمایندگی (agency_code) - نوع Text
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
- `statuscode` - وضعیت مالی (fin_green) - 1=ok, 2=not ok
- `rhs_location` - موقعیت (location)
- `rhs_contact` - Lookup به Contact

**نکته مهم:** فیلد `rhs_centercode` باید از نوع Single Line of Text باشد، نه Guid.

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
agency_code → rhs_centercode
address → rhs_address
guild_number → rhs_guildnumber
mobile → rhs_mobile
phone → rhs_phone
issued_date → rhs_dateofissue (فرمت ISO 8601)
exp_date → rhs_expirydate (فرمت ISO 8601)
description → rhs_description
province → rhs_province
city → rhs_city
postal_code → rhs_postalcode
enable → statecode (0=فعال, 1=غیرفعال)
fin_green → statuscode (1=ok, 2=not ok)
location → rhs_location
name → rhs_name (ترکیب firstname + lastname)

## ذخیره CRM Service Center ID:
پس از ایجاد موفق هر Service Center در CRM، آیدی آن (`rhs_servicecenterid`) در جدول `agency_info` ذخیره می‌شود:

```sql
INSERT INTO agency_info (parent_id, key, value, created_at, updated_at) 
VALUES (parent_id, 'crm_service_center_id', 'service_center_guid', NOW(), NOW())
```

- **Key**: `crm_service_center_id`
- **Value**: GUID آیدی Service Center در CRM
- **Parent ID**: آیدی مرکز در سیستم محلی

اگر رکورد قبلاً وجود داشته باشد، به‌روزرسانی می‌شود.

## استفاده:

### بررسی آمار مراکز:
```
https://your-domain.com/agency-info/crm-stats
```
نمایش آمار کامل از مراکز موجود و غیرموجود در CRM

### ارسال مراکز جدید:
```
https://your-domain.com/agency-info/send-to-crm
```
ارسال فقط مراکزی که قبلاً در CRM ایجاد نشده‌اند

### تست عملکرد:
```
https://your-domain.com/agency-info/test-minimal
```
تست کامل ایجاد و بررسی یک Service Center

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