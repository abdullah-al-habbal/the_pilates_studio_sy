<?php

// filePath: lang/ar/dashboard.php

return [
    'navigation' => [
        'groups' => [
            'bookings' => 'الحجوزات',
            'store' => 'المتجر',
            'operations' => 'العمليات',
        ],
        'bookings' => 'الحجوزات',
        'scheduler' => 'الجدول الزمني',
        'reports' => 'التقارير',
    ],
    'pages' => [
        'scheduler' => [
            'title' => 'جدول الحضور',
            'attendance' => 'إدارة الحضور',
            'empty' => [
                'title' => 'لا توجد جلسات اليوم',
                'description' => 'لا توجد حصص مجدولة لهذا اليوم.',
            ],
            'session_full' => 'ممتلئة',
            'session_full_notice' => 'وصلت هذه الجلسة إلى الطاقة الاستيعابية الكاملة.',
            'credits_remaining' => 'رصيد متبقٍ',
            'no_credits' => 'لا يوجد رصيد',
            'class' => 'الحصة',
            'instructor' => 'المدرب',
            'no_instructor' => 'لا يوجد مدرب',
            'time' => 'الوقت',
            'attendance_summary' => 'الحضور',
            'modal' => [
                'heading' => 'الحضور: :class (:date)',
                'confirmed_attendees' => 'الحضور المؤكدون',
                'no_reservations' => 'لا توجد حجوزات لهذه الجلسة بعد.',
                'attended' => 'حضر',
                'missed' => 'غاب',
                'add_walkin' => 'إضافة حاضر بدون حجز',
                'search_member' => 'البحث عن عضو',
                'select_member' => 'اختر عضواً...',
                'attend_now' => 'تحضير الآن',
                'note' => 'ملاحظة: "تحضير الآن" سيقوم تلقائياً بإنشاء اشتراك جلسة واحدة وتسجيل المستخدم كحاضر.',
                'close' => 'إغلاق',
                'existing_member' => 'عضو موجود',
                'new_member' => 'عضو جديد',
            ],
            'notifications' => [
                'attendance_updated' => 'تم تحديث الحضور',
                'walkin_added' => 'تم إضافة الحاضر بدون حجز',
            ],
            'actions' => [
                'refresh' => 'تحديث البيانات',
                'today' => 'اليوم',
            ],
        ],
        'reports' => [
            'title' => 'تقارير العمل',
            'filters' => [
                'all_time' => 'كل الوقت',
                'yearly' => 'سنوي',
                'monthly' => 'شهري',
                'daily' => 'يومي',
                'custom' => 'مخصص',
                'select_date' => 'التاريخ',
                'select_month' => 'الشهر',
                'select_year' => 'السنة',
                'start_date' => 'من',
                'end_date' => 'إلى',
                'custom_hint' => 'اختر نطاق تاريخ لتحميل التقرير.',
            ],
            'stats' => [
                'total_revenue' => 'إجمالي الإيرادات',
                'booking_revenue' => 'إيرادات الحجوزات',
                'store_revenue' => 'إيرادات المتجر',
                'total_bookings' => 'إجمالي الحجوزات',
                'total_merchandise_orders' => 'إجمالي طلبات المنتجات',
            ],
            'popular_classes' => [
                'heading' => 'الحصص الشائعة',
                'attendees' => ':count حاضر',
                'sessions' => ':count جلسة',
                'avg' => 'المتوسط: :count لكل جلسة',
                'empty' => 'لا توجد بيانات لهذه الفترة.',
            ],
            'top_merchandise' => [
                'heading' => 'المنتجات الأكثر مبيعاً',
                'sold' => 'تم بيع :count',
                'empty' => 'لا توجد مبيعات لهذه الفترة.',
            ],
        ],
    ],
    'resources' => [
        'center_merchandises' => [
            'singular' => 'منتج',
            'plural' => 'المنتجات',
            'sections' => [
                'information' => 'معلومات المنتج',
                'pricing' => 'التسعير والمخزون',
                'gallery' => 'معرض الصور',
                'details' => 'التفاصيل',
            ],
            'fields' => [
                'name' => 'الاسم',
                'description' => 'الوصف',
                'price' => 'السعر',
                'stock_quantity' => 'الكمية المتوفرة',
                'category' => 'الفئة',
                'image' => 'صورة',
                'is_primary' => 'صورة رئيسية',
                'created_at' => 'تاريخ الإنشاء',
            ],
            'placeholders' => [
                'no_description' => 'لا يوجد وصف.',
            ],
            'helpers' => [
                'stock_min' => 'الحد الأدنى :min (المخزون الحالي). قلّل المخزون عبر الطلبات.',
            ],
            'labels' => [
                'primary' => 'رئيسية',
            ],
            'actions' => [
                'add_image' => 'إضافة صورة',
            ],
            'empty_state' => [
                'heading' => 'لا توجد منتجات بعد',
                'description' => 'أنشئ منتجك الأول للبدء.',
            ],
        ],

        'merchandise_categories' => [
            'singular' => 'فئة',
            'plural' => 'فئات المنتجات',
            'sections' => [
                'details' => 'تفاصيل الفئة',
            ],
            'fields' => [
                'name' => 'الاسم',
                'merchandises_count' => 'المنتجات',
                'created_at' => 'تاريخ الإنشاء',
            ],
            'empty_state' => [
                'heading' => 'لا توجد فئات بعد',
            ],
        ],

        'merchandise_orders' => [
            'singular' => 'طلب',
            'plural' => 'طلبات المنتجات',
            'sections' => [
                'order_details' => 'تفاصيل الطلب',
                'customer' => 'العميل',
            ],
            'fields' => [
                'merchandise' => 'المنتج',
                'quantity' => 'الكمية',
                'total_price' => 'الإجمالي (ل.س)',
                'customer' => 'العميل',
                'phone' => 'الهاتف',
                'ordered_at' => 'تاريخ الطلب',
            ],
            'placeholders' => [
                'walk_in' => 'عميل بدون حجز',
            ],
            'empty_state' => [
                'heading' => 'لا توجد طلبات بعد',
            ],
            'actions' => [
                'create_customer' => 'إنشاء عميل جديد',
            ],
            'helpers' => [
                'max_stock' => 'الحد الأقصى المتاح: :max وحدة',
            ],
        ],

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
                'create_customer' => 'إنشاء عميل جديد',
                'create_package' => 'إنشاء حزمة جديدة',
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
                'missed' => 'غائب',
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
        'users' => [
            'fields' => [
                'password' => 'كلمة المرور',
            ],
            'helpers' => [
                'password_default' => 'اتركه فارغاً لتعيين كلمة المرور الافتراضية 12345678',
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
            'missed' => 'حالات الغياب',
            'missed_description' => ':trend% مقارنة بالشهر الماضي',
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
