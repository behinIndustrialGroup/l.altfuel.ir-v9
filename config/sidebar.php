<?php

return [
    'menu' =>[

        'dashboard' => [
            'fa_name' => 'داشبرد',
            'submenu' => [
                'dashboard' => [ 'fa_name' => 'داشبرد', 'route-name' => '', 'route-url' => 'admin' ],
            ]
        ],
        'cases' => [
            'fa_name' => 'کارپوشه',
            'submenu' => [
                'new-case' => [ 'fa_name' => 'فرایند جدید', 'route-name' => 'MkhodrooProcessMaker.forms.start', 'route-url' => '' ],
                'inbox' => [ 'fa_name' => 'انجام نشده ها', 'route-name' => 'MkhodrooProcessMaker.forms.todo', 'route-url' => '' ],
                'done' => [ 'fa_name' => 'انجام شده ها', 'route-name' => 'MkhodrooProcessMaker.forms.done', 'route-url' => '' ],
                'draft' => [ 'fa_name' => 'پیش نویس', 'route-name' => 'MkhodrooProcessMaker.forms.draft', 'route-url' => '' ]
            ]
        ],
        'content' => [
            'fa_name' => 'محتوا',
            'submenu' => [
                'dashboard' => [ 'fa_name' => 'محتوا', 'route-name' => 'binshopsblog.admin.index', 'route-url' => '' ],
            ]
        ],
        'agencies' => [
            'fa_name' => 'مراکز',
            'submenu' => [
                'dashboard' => [ 'fa_name' => 'همه', 'route-name' => 'agencyInfo.listForm', 'route-url' => '' ],
            ]
        ],
        'users' => [
            'fa_name' => 'کاربران',
            'submenu' => [
                'dashboard' => [ 'fa_name' => 'همه', 'route-name' => '', 'route-url' => 'user/all' ],
                'role' => [ 'fa_name' => 'نقش ها', 'route-name' => 'role.listForm', 'route-url' => '' ],
                'method' => [ 'fa_name' => 'متد ها', 'route-name' => 'method.list', 'route-url' => '' ],
            ]
        ],
        'cities' => [
            'fa_name' => 'شهر و استان',
            'submenu' => [
                'show' => [ 'fa_name' => 'نمایش لیست', 'route-name' => 'city.index' ]
            ]
        ],
        'reports' => [
            'fa_name' => 'گزارش',
            'submenu' => [
                'issues' => [ 'fa_name' => 'تیکت', 'route-name' => 'ATRoutes.report.summary', 'route-url' => 'admin/report/ticket' ],
                'call' => [ 'fa_name' => 'تماس', 'route-name' => 'voip.getCallReport', 'route-url' => '' ],
                'callVoices' => [ 'fa_name' => 'صوت تماس ها', 'route-name' => 'voip.recordedList', 'route-url' => '' ],
                'guild' => [ 'fa_name' => 'پروانه کسب', 'route-name' => '', 'route-url' => 'admin/report/license' ],
                'irngv' => [ 'fa_name' => 'نظرسنجی irngv', 'route-name' => 'report.irngv.poll', 'route-url' => '' ],
            ]
        ],
        'asign-ins' => [
            'fa_name' => 'تخصیص بازرس',
            'submenu' => [
                'new' => [ 'fa_name' => 'درخواست جدید', 'route-name' => '', 'route-url' => 'admin/ins/asign/ins/form' ],
                'list' => [ 'fa_name' => 'لیست درخواست ها', 'route-name' => '', 'route-url' => 'admin/ins/show/all' ],
            ]
        ],
        'ins-requests' => [
            'fa_name' => 'لیست درخواست ها',
            'submenu' => [
                'list' => [ 'fa_name' => 'همه', 'route-name' => '', 'route-url' => 'admin/request/asign/ins/show/all' ],
            ]
        ],
        'irngv-poll' => [
            'fa_name' => 'اطلاعات دریافتی از irngv',
            'submenu' => [
                'irngv' => [ 'fa_name' => 'اطلاعات دریافتی', 'route-name' => 'admin.irngv.show.list', 'route-url' => '' ],
                'poll' => [ 'fa_name' => 'اطلاعات نظرسنجی', 'route-name' => 'admin.irngv.show.answers', 'route-url' => '' ],
            ]
        ],
        'tickets' => [
            'fa_name' => 'تیکت پشتیبانی',
            'submenu' => [
                'create' => [ 'fa_name' => 'ایجاد', 'route-name' => 'ATRoutes.index', 'route-url' => '' ],
                'show' => [ 'fa_name' => 'مشاهده', 'route-name' => 'ATRoutes.show.listForm', 'route-url' => '' ],
            ]
        ],

        'course-registrations' => [
            'fa_name' => 'ثبت‌نام دوره‌ها',
            'submenu' => [
                'list' => [ 'fa_name' => 'لیست ثبت‌نامی‌ها', 'route-name' => 'admin.course-registrations.index', 'route-url' => '' ],
            ],
        ],

    ]
];
