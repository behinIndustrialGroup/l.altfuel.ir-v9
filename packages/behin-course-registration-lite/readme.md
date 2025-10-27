# پکیج ثبت‌نام کارگاه‌های آموزشی (Lite)

این پکیج یک فرم ساده برای ثبت‌نام و پرداخت هزینه کارگاه‌های آموزشی با امکان شخصی‌سازی نام مسیرها و جدول پایگاه‌داده فراهم می‌کند. برای فعال‌سازی مراحل زیر را انجام دهید:

1. اضافه کردن سرویس‌پروایدر به فایل `config/app.php`:
   ```php
   use CourseRegistrationLite\CourseRegistrationLiteServiceProvider;

   return [
       // ...
       'providers' => [
           // ...
           CourseRegistrationLiteServiceProvider::class,
       ],
   ];
   ```
2. اجرای دستورات زیر جهت پابلیش تنظیمات و اجرای مایگریشن‌ها:
   ```bash
   php artisan vendor:publish --tag=course-registration-lite
   php artisan migrate
   ```
3. پس از پابلیش تنظیمات می‌توانید پیشوند مسیرها را از طریق فایل `config/course-registration-lite.php` تغییر دهید. به صورت پیش‌فرض آدرس فرم `/workshops/register` و صفحه نتیجه پرداخت `/workshops/verify` خواهد بود و مسیرهای مدیریتی تحت `/admin/workshop-registrations` قرار می‌گیرند.

تنظیمات دوره‌ها و شماره‌های دریافت‌کننده پیامک در فایل تنظیمات `config/course-registration-lite.php` قرار دارد.
