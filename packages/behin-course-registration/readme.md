# پکیج ثبت‌نام دوره‌های آموزشی

این پکیج یک فرم ساده برای ثبت‌نام و پرداخت هزینه دوره‌های آموزشی فراهم می‌کند. برای فعال‌سازی مراحل زیر را انجام دهید:

1. اضافه کردن سرویس‌پروایدر به فایل `config/app.php`:
   ```php
   use CourseRegistration\CourseRegistrationServiceProvider;

   return [
       // ...
       'providers' => [
           // ...
           CourseRegistrationServiceProvider::class,
       ],
   ];
   ```
2. اجرای دستورات زیر جهت پابلیش تنظیمات و اجرای مایگریشن‌ها:
   ```bash
   php artisan vendor:publish --tag=course-registration
   php artisan migrate
   ```
3. دسترسی به فرم ثبت‌نام از آدرس `/courses/register` و صفحه نتیجه پرداخت از `/courses/verify` خواهد بود.

تنظیمات دوره‌ها و شماره‌های دریافت‌کننده پیامک در فایل تنظیمات `config/course-registration.php` قرار دارد.
