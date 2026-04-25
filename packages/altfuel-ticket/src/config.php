<?php 


return [
    'route-prefix' => 'admin/',
    'ticket-uploads-folder' => '/ticket-uploads',
    'status' => [
        'new' => 'جدید',
        'opened' => 'بازشده',
        'answered' => 'پاسخ داده شده',
        'closed' => 'بسته شده'
    ],
    'conversion_types' => [
        'yaraneh' => 'یارانه‌ای',
        'azad' => 'آزاد',
    ],
    'filter_agents' => [
        ['id' => 14, 'name' => 'کابلی'],
        ['id' => 15, 'name' => 'گل گواهی'],
        ['id' => 18, 'name' => 'شناسنده'],
        ['id' => 25, 'name' => 'شهیدی'],
        ['id' => 28, 'name' => 'بابائی'],
        ['id' => 37, 'name' => 'احمدی'],
        ['id' => 39, 'name' => 'سیدی'],
        ['id' => 42, 'name' => 'شهاب'],
        ['id' => 41, 'name' => 'شهریاری'],
        ['id' => 40, 'name' => 'شادمان'],
        ['id' => 1365, 'name' => 'آهنگران'],
        ['id' => 1427, 'name' => 'حاجیوند'],
        ['id' => 2428, 'name' => 'لک'],
        ['id' => 2823, 'name' => 'رضایی'],
    ],
    'attachment-file-types' => [
        'image/png', 'image/jpg', 'image/jpeg', 'application/pdf', 'application/x-zip-compressed',
        'application/octet-stream'
    ],
    'attachment-file-types-translate' => [
        'png', 'jpg', 'jpeg', 'pdf', 'zip', 'rar'
    ], 
    'max-attach-file-size' => 2048, //KB

    
];