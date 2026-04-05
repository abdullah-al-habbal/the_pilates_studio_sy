<?php

// filePath: lang/ar/dashboard.php

return [
    'navigation' => [
        'groups' => [
            'bookings' => 'الحجوزات',
        ],
        'bookings' => 'الحجوزات',
    ],
    'resources' => [
        'bookings' => [
            'singular' => 'حجز',
            'plural' => 'الحجوزات',
            'navigation' => [
                'label' => 'الحجوزات',
                'icon' => 'heroicon-o-credit-card',
                'group' => 'الحجوزات',
            ],
            'sections' => [
                'information' => 'معلومات الحجز',
                'information_desc' => 'تفاصيل حزمة المستخدم',
                'quick_stats' => 'الإحصائيات السريعة',
                'system' => 'معلومات النظام',
            ],
            'fields' => [
                'id' => 'رقم التعريف',
                'user' => 'المستخدم',
                'user_id' => 'المستخدم',
                'package' => 'الحزمة',
                'package_id' => 'الحزمة',
                'total_credits' => 'إجمالي الاعتمادات',
                'remaining_credits' => 'الاعتمادات المتبقية',
                'credits_used' => 'الاعتمادات المستخدمة',
                'credits_usage' => 'استخدام الاعتمادات',
                'credits_remaining' => 'يمتلك اعتمادات',
                'status' => 'الحالة',
                'is_expired' => 'منتهي الصلاحية',
                'is_active' => 'نشط',
                'expires_at' => 'تاريخ انتهاء الصلاحية',
                'created_at' => 'تاريخ الإنشاء',
                'updated_at' => 'تاريخ التحديث',
                'deleted_at' => 'تاريخ الحذف',
                'booking_information' => 'معلومات الحجز',
                'quick_stats' => 'الإحصائيات السريعة',
                'system_info' => 'معلومات النظام',
            ],
            'placeholders' => [
                'expires_at' => 'لم يتم تعيين تاريخ انتهاء صلاحية',
                'no_expiry' => 'بدون انتهاء صلاحية',
                'not_deleted' => 'لم تحذف',
            ],
            'statuses' => [
                'active' => 'نشط',
                'exhausted' => 'مستنزف',
                'expired' => 'منتهي الصلاحية',
                'cancelled' => 'ملغى',
            ],
            'empty_state' => [
                'heading' => 'لا توجد حجوزات حتى الآن',
                'description' => 'أنشئ حجزًا لتعيين حزمة ائتمان لمستخدم.',
            ],
            'messages' => [
                'no_bookings' => 'لم توجد حجوزات',
                'booking_details' => 'تفاصيل وسجلات الحجز',
                'credits_remaining' => 'اعتماد واحد متبقي|:count اعتمادات متبقية',
                'credits_used' => 'اعتماد واحد مستخدم|:count اعتمادات مستخدمة',
                'expires_on' => 'ينتهي في :date',
                'created_on' => 'تم الإنشاء في :date',
            ],
            'actions' => [
                'create' => 'إنشاء حجز',
                'edit' => 'تحرير الحجز',
                'view' => 'عرض الحجز',
                'delete' => 'حذف الحجز',
                'restore' => 'استعادة الحجز',
                'force_delete' => 'حذف نهائي',
            ],
        ],
        'booking_sessions' => [
            'singular' => 'جلسة حجز',
            'plural' => 'جلسات الحجز',
            'fields' => [
                'id' => 'رقم التعريف',
                'booking_id' => 'الحجز',
                'class_session' => 'جلسة الدرس',
                'class_session_id' => 'جلسة الدرس',
                'status' => 'الحالة',
                'cancelled_at' => 'تاريخ الإلغاء',
                'created_at' => 'تاريخ الإنشاء',
            ],
            'statuses' => [
                'reserved' => 'محجوز',
                'cancelled' => 'ملغى',
                'attended' => 'حضر',
                'no_show' => 'غياب',
            ],
            'actions' => [
                'create' => 'إنشاء جلسة',
                'edit' => 'تحرير الجلسة',
                'delete' => 'حذف الجلسة',
                'attach' => 'إرفاق جلسة',
                'detach' => 'فصل جلسة',
            ],
            'messages' => [
                'no_sessions' => 'لم توجد جلسات',
                'session_details' => 'تفاصيل الجلسة والمعلومات',
            ],
        ],
        'classes' => [
            'statuses' => [
                'active' => 'نشط',
                'inactive' => 'غير نشط',
                'archived' => 'مؤرشف',
            ],
            'actions' => [
                'mark_completed' => 'تعليم كمكتمل',
                'mark_cancelled' => 'إلغاء الحصة',
            ],
            'notifications' => [
                'completed' => 'تم تعليم الحصة كمكتملة.',
                'cancelled' => 'تم إلغاء الحصة.',
            ],
        ],
        'class_sessions' => [
            'statuses' => [
                'scheduled' => 'مجدولة',
                'completed' => 'مكتملة',
                'cancelled' => 'ملغاة',
            ],
        ],
    ],
    'widgets' => [
        'stats_overview' => [
            'active_users' => 'المستخدمون النشطون',
            'active_users_description' => 'مستخدمون لديهم حجوزات نشطة',
            'active_bookings' => 'الحجوزات النشطة',
            'active_bookings_description' => 'حجوزات غير منتهية الصلاحية',
            'credits_sold' => 'الاعتمادات المباعة',
            'credits_sold_description' => 'إجمالي جميع الحزم',
            'credits_consumed' => 'الاعتمادات المستهلكة',
            'credits_consumed_usage' => ':percentage% مستخدم',
            'attendance_rate' => 'معدل الحضور',
            'attendance_rate_description' => 'آخر ٣٠ يوماً',
            'no_shows' => 'حالات الغياب',
            'no_shows_description' => ':trend% مقارنة بالشهر الماضي',
            'fill_rate' => 'معدل الإشغال',
            'fill_rate_description' => 'متوسط إشغال الدروس',
            'upcoming_full_sessions' => 'الجلسات القادمة المكتملة',
            'upcoming_full_sessions_description' => 'دروس محجوزة بالكامل',
        ],
        'attendance_trend' => [
            'heading' => 'اتجاه الحضور اليومي (آخر ٣٠ يوماً)',
            'attended_sessions' => 'الجلسات التي تم حضورها',
        ],
        'top_instructors' => [
            'heading' => 'المدربون الأفضل أداءً',
            'table_heading' => 'أفضل ٥ مدربين حسب الحضور',
            'instructor' => 'المدرب',
            'attended_sessions' => 'الجلسات التي تم حضورها',
        ],
        'category_performance' => [
            'heading' => 'الأقسام الأكثر حضوراً',
            'attended_sessions' => 'الجلسات التي تم حضورها',
        ],
    ],
];
