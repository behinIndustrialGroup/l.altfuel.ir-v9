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
    'attachment-file-types' => [
        'image/png', 'image/jpg', 'image/jpeg', 'application/pdf', 'application/x-zip-compressed',
        'application/octet-stream'
    ],
    'attachment-file-types-translate' => [
        'png', 'jpg', 'jpeg', 'pdf', 'zip', 'rar'
    ], 
    'max-attach-file-size' => 2048, //KB

    
];