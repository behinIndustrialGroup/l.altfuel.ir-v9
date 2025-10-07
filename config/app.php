<?php

use Registration\RegistrationServiceProvider;

return [
    'version' => 'v=9.1.25',

    'agencies' => [
        'high-pressure' => [
            'name' => 'high-pressure',
            'fa_name' => 'مراکز خدمات فنی',
            'table' => 'marakez1'
            // 'paymnet' and 'extra-payments' => from ConfigServiceProvider.php
        ],
        'hidro' => [
            'name' => 'hidro',
            'fa_name' => 'آزمایشگاه هیدرواستاتیک',
            'table' => 'hidro'
            // 'paymnet' and 'extra-payments' => from ConfigServiceProvider.php
        ],
        'low-pressure' => [
            'name' => 'low-pressure',
            'fa_name' => 'مراکز خدمات کم فشار',
            'table' => 'kamfeshar'
            // 'paymnet' and 'extra-payments' => from ConfigServiceProvider.php
        ]
    ],

    'report' => [
        'call' => [
            'max_unanswer_number' => 10,
            'extensions' => [
                101 => ['name' => ''],
                102 => ['name' => 'خانم بابایی'],
                103 => ['name' => 'خانم سیدی'],
                104 => ['name' => 'آقای کابلی'],
                105 => ['name' => 'آقای شهاب'],
                106 => ['name' => ''],
                107 => ['name' => ''],
                108 => ['name' => 'آقای گل گواهی'],
                109 => ['name' => ''],
                110 => ['name' => ''],
                111 => ['name' => 'خانم شناسنده'],
                112 => ['name' => ''],
                113 => ['name' => 'آقای خودرو'],
                114 => ['name' => 'آقای محمدی'],
                115 => ['name' => 'خانم شهریاری'],
                116 => ['name' => 'خانم شهیدی'],
                117 => ['name' => ''],
                118 => ['name' => 'آقای جوهری'],
                119 => ['name' => ''],
                120 => ['name' => 'خانم احمدی'],
                121 => ['name' => ''],
                122 => ['name' => 'آقای شادمان'],
            ]
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Application Name
    |--------------------------------------------------------------------------
    |
    | This value is the name of your application. This value is used when the
    | framework needs to place the application's name in a notification or
    | any other location as required by the application or its packages.
    |
    */

    'name' => env('APP_NAME', 'Laravel'),

    /*
    |--------------------------------------------------------------------------
    | Application Environment
    |--------------------------------------------------------------------------
    |
    | This value determines the "environment" your application is currently
    | running in. This may determine how you prefer to configure various
    | services the application utilizes. Set this in your ".env" file.
    |
    */

    'env' => env('APP_ENV', 'production'),

    /*
    |--------------------------------------------------------------------------
    | Application Debug Mode
    |--------------------------------------------------------------------------
    |
    | When your application is in debug mode, detailed error messages with
    | stack traces will be shown on every error that occurs within your
    | application. If disabled, a simple generic error page is shown.
    |
    */

    'debug' => (bool) env('APP_DEBUG', false),

    /*
    |--------------------------------------------------------------------------
    | Application URL
    |--------------------------------------------------------------------------
    |
    | This URL is used by the console to properly generate URLs when using
    | the Artisan command line tool. You should set this to the root of
    | your application so that it is used when running Artisan tasks.
    |
    */

    'url' => env('APP_URL', 'http://localhost'),

    'asset_url' => env('ASSET_URL', null),

    /*
    |--------------------------------------------------------------------------
    | Application Timezone
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default timezone for your application, which
    | will be used by the PHP date and date-time functions. We have gone
    | ahead and set this to a sensible default for you out of the box.
    |
    */

    'timezone' => 'Asia/Tehran',

    /*
    |--------------------------------------------------------------------------
    | Application Locale Configuration
    |--------------------------------------------------------------------------
    |
    | The application locale determines the default locale that will be used
    | by the translation service provider. You are free to set this value
    | to any of the locales which will be supported by the application.
    |
    */

    'locale' => 'fa',

    /*
    |--------------------------------------------------------------------------
    | Application Fallback Locale
    |--------------------------------------------------------------------------
    |
    | The fallback locale determines the locale to use when the current one
    | is not available. You may change the value to correspond to any of
    | the language folders that are provided through your application.
    |
    */

    'fallback_locale' => 'fa',

    /*
    |--------------------------------------------------------------------------
    | Faker Locale
    |--------------------------------------------------------------------------
    |
    | This locale will be used by the Faker PHP library when generating fake
    | data for your database seeds. For example, this will be used to get
    | localized telephone numbers, street address information and more.
    |
    */

    'faker_locale' => 'en_US',

    /*
    |--------------------------------------------------------------------------
    | Encryption Key
    |--------------------------------------------------------------------------
    |
    | This key is used by the Illuminate encrypter service and should be set
    | to a random, 32 character string, otherwise these encrypted strings
    | will not be safe. Please do this before deploying an application!
    |
    */

    'key' => env('APP_KEY'),

    'cipher' => 'AES-256-CBC',

    /*
    |--------------------------------------------------------------------------
    | Autoloaded Service Providers
    |--------------------------------------------------------------------------
    |
    | The service providers listed here will be automatically loaded on the
    | request to your application. Feel free to add your own services to
    | this array to grant expanded functionality to your applications.
    |
    */

    'providers' => [

        /*
         * Laravel Framework Service Providers...
         */
        Illuminate\Auth\AuthServiceProvider::class,
        Illuminate\Broadcasting\BroadcastServiceProvider::class,
        Illuminate\Bus\BusServiceProvider::class,
        Illuminate\Cache\CacheServiceProvider::class,
        Illuminate\Foundation\Providers\ConsoleSupportServiceProvider::class,
        Illuminate\Cookie\CookieServiceProvider::class,
        Illuminate\Database\DatabaseServiceProvider::class,
        Illuminate\Encryption\EncryptionServiceProvider::class,
        Illuminate\Filesystem\FilesystemServiceProvider::class,
        Illuminate\Foundation\Providers\FoundationServiceProvider::class,
        Illuminate\Hashing\HashServiceProvider::class,
        Illuminate\Mail\MailServiceProvider::class,
        Illuminate\Notifications\NotificationServiceProvider::class,
        Illuminate\Pagination\PaginationServiceProvider::class,
        Illuminate\Pipeline\PipelineServiceProvider::class,
        Illuminate\Queue\QueueServiceProvider::class,
        Illuminate\Redis\RedisServiceProvider::class,
        Illuminate\Auth\Passwords\PasswordResetServiceProvider::class,
        Illuminate\Session\SessionServiceProvider::class,
        Illuminate\Translation\TranslationServiceProvider::class,
        Illuminate\Validation\ValidationServiceProvider::class,
        Illuminate\View\ViewServiceProvider::class,

        /*
         * Package Service Providers...
         */
        App\Providers\ColumnFilterServiceProvider::class,
        Livewire\LivewireServiceProvider::class,
        App\Providers\FinanceServiceProvider::class,
        App\Providers\ConfigServiceProvider::class,
        // BinshopsBlog\BinshopsBlogServiceProvider::class,
        Mkhodroo\AltfuelTicket\AltfuelTicketServiceProvider::class,
        \Mkhodroo\UserRoles\UserRolesServiceProvider::class,
        \Mkhodroo\Voip\VoipServiceProvider::class,
        \Mkhodroo\SmsTemplate\SmsTemplateProvider::class,
        Mkhodroo\DateConvertor\DateConvertorProvider::class,
        Mkhodroo\PMReport\PMReportProvider::class,
        Mkhodroo\HelpSupportRobot\HelpSupportRobotProvider::class,
        \Hidro\Registration\HidroRegProvider::class,
        Mkhodroo\AgencyInfo\AgencyInfoProvider::class,
        Mkhodroo\Cities\CityProvider::class,
        \UserProfile\UserProfileProvider::class,
        \IrngvPoll\IrngvPollProvider::class,
        \ChatGpt\ChatGptProvider::class,
        \AiAssistant\AiAssistantProvider::class,
        BehinTest\BehinTestProvider::class,
        \TodoList\TodoListProvider::class,
        \TelegramBot\TelegramBotProvider::class,
        BaleBot\BaleBotProvider::class,
        BehinLogging\ServiceProvider::class,
        BehinProcessMaker\BehinProcessMakerProvider::class,
        MyAgencyInfo\PackageServiceProvider::class,
        \FileService\FileServiceProvider::class,
        \QrCodeScanner\QrCodeScannerProvider::class,
        \ExcelReader\ExcelReaderServiceProvider::class,
        \Behin\Complaint\ComplaintProvider::class,
        Behin\Hamayesh\BehinHamayeshServiceProvider::class,
        RegistrationServiceProvider::class,
        \TelegramTicket\TelegramTicketProvider::class,

        // \Mkhodroo\CaseInsensitiveTranslate\CaseInsensitiveTranslateProvider::class,
        // MKhodroo\UserRoles\UserRolesServiceProvider::class,
        // Mkhodroo\ShahabTicketSystem\ShahabTicketServiceProvider::class,
        /*
         * Application Service Providers...
         */
        App\Providers\AppServiceProvider::class,
        App\Providers\AuthServiceProvider::class,
        // App\Providers\BroadcastServiceProvider::class,
        App\Providers\EventServiceProvider::class,
        App\Providers\RouteServiceProvider::class,


    ],

    /*
    |--------------------------------------------------------------------------
    | Class Aliases
    |--------------------------------------------------------------------------
    |
    | This array of class aliases will be registered when this application
    | is started. However, feel free to register as many as you wish as
    | the aliases are "lazy" loaded so they don't hinder performance.
    |
    */

    'aliases' => [

        'App' => Illuminate\Support\Facades\App::class,
        'Arr' => Illuminate\Support\Arr::class,
        'Artisan' => Illuminate\Support\Facades\Artisan::class,
        'Auth' => Illuminate\Support\Facades\Auth::class,
        'Blade' => Illuminate\Support\Facades\Blade::class,
        'Broadcast' => Illuminate\Support\Facades\Broadcast::class,
        'Bus' => Illuminate\Support\Facades\Bus::class,
        'Cache' => Illuminate\Support\Facades\Cache::class,
        'Config' => Illuminate\Support\Facades\Config::class,
        'Cookie' => Illuminate\Support\Facades\Cookie::class,
        'Crypt' => Illuminate\Support\Facades\Crypt::class,
        'DB' => Illuminate\Support\Facades\DB::class,
        'Eloquent' => Illuminate\Database\Eloquent\Model::class,
        'Event' => Illuminate\Support\Facades\Event::class,
        'File' => Illuminate\Support\Facades\File::class,
        'Gate' => Illuminate\Support\Facades\Gate::class,
        'Hash' => Illuminate\Support\Facades\Hash::class,
        'Http' => Illuminate\Support\Facades\Http::class,
        'Lang' => Illuminate\Support\Facades\Lang::class,
        'Log' => Illuminate\Support\Facades\Log::class,
        'Mail' => Illuminate\Support\Facades\Mail::class,
        'Notification' => Illuminate\Support\Facades\Notification::class,
        'Password' => Illuminate\Support\Facades\Password::class,
        'Queue' => Illuminate\Support\Facades\Queue::class,
        'Redirect' => Illuminate\Support\Facades\Redirect::class,
        // 'Redis' => Illuminate\Support\Facades\Redis::class,
        'Request' => Illuminate\Support\Facades\Request::class,
        'Response' => Illuminate\Support\Facades\Response::class,
        'Route' => Illuminate\Support\Facades\Route::class,
        'Schema' => Illuminate\Support\Facades\Schema::class,
        'Session' => Illuminate\Support\Facades\Session::class,
        'Storage' => Illuminate\Support\Facades\Storage::class,
        'Str' => Illuminate\Support\Str::class,
        'URL' => Illuminate\Support\Facades\URL::class,
        'Validator' => Illuminate\Support\Facades\Validator::class,
        'View' => Illuminate\Support\Facades\View::class,
        'Verta' => Hekmatinasser\Verta\Verta::class,

    ],

];
