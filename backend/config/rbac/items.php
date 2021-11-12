<?php
return [
    'viewIndex' => [
        'type' => 2,
        'description' => 'View the main page',
    ],
    'root' => [
        'type' => 1,
        'ruleName' => 'userRole',
        'children' => [
            'manager',
            'manageUsers',
            'manageEmployees',
            'editUser',
            'reportMoneyTotal',
            'reportCash',
            'pupilChangePast',
            'moneyCorrection',
            'deleteTeachers',
            'adminSchedule',
        ],
    ],
    'manager' => [
        'type' => 1,
        'ruleName' => 'userRole',
        'children' => [
            'parents',
        ],
    ],
    'parents' => [
        'type' => 1,
        'ruleName' => 'userRole',
        'children' => [
            'pupil',
        ],
    ],
    'pupil' => [
        'type' => 1,
        'ruleName' => 'userRole',
        'children' => [
            'viewIndex',
            'editOwnProfile',
            'viewMySchedule',
        ],
    ],
    'teacher' => [
        'type' => 1,
        'ruleName' => 'userRole',
        'children' => [
            'viewIndex',
            'manageSchedule',
            'viewMissed',
            'viewGroups',
        ],
    ],
    'registrator' => [
        'type' => 1,
        'children' => [
            'manageUsers',
            'editUser',
            'contractManagement',
            'welcomeLessons',
        ],
    ],
    'cashier' => [
        'type' => 1,
        'children' => [
            'manageUsers',
            'editUser',
            'moneyManagement',
            'reportDebt',
        ],
    ],
    'scheduler' => [
        'type' => 1,
        'children' => [
            'manageSchedule',
            'viewGroups',
            'reportDebt',
            'viewMissed',
            'callMissed',
        ],
    ],
    'accountant' => [
        'type' => 1,
        'children' => [
            'viewSalary',
            'reportMoney',
        ],
    ],
    'support' => [
        'type' => 1,
        'children' => [
            'manageFeedbacks',
            'manageOrders',
            'manageReviews',
        ],
    ],
    'groupManager' => [
        'type' => 1,
        'children' => [
            'manageTeachers',
            'manageSubjectCategories',
            'manageSubjects',
            'viewGroups',
            'manageGroups',
            'viewNotes',
            'reportGroupMovement',
            'manageGiftCardTypes',
            'viewMissed',
            'callMissed',
        ],
    ],
    'moneyMover' => [
        'type' => 1,
        'children' => [
            'moveMoney',
        ]
    ],
    'content' => [
        'type' => 1,
        'children' => [
            'managePages',
            'manageMenu',
            'manageWidgetHtml',
            'manageHighSchools',
            'manageSubjectCategories',
            'manageSubjects',
            'manageTeachers',
            'manageQuiz',
            'managePromotions',
            'manageBlog',
            'manageNews',
        ],
    ],
    'manageEmployees' => [
        'type' => 2,
        'description' => 'Manage employees',
    ],
    'editOwnProfile' => [
        'type' => 2,
        'ruleName' => 'isOwnProfile',
        'children' => [
            'editUser',
        ],
    ],

    /* REGISTRATOR */
    'contractManagement' => [
        'type' => 2,
        'description' => 'Manage contracts',
    ],
    'welcomeLessons' => [
        'type' => 2,
        'description' => 'Move pupils from welcome lessons to groups',
    ],

    /* CASHIER */
    'manageUsers' => [
        'type' => 2,
        'description' => 'Manage users',
    ],
    'editUser' => [
        'type' => 2,
        'description' => 'Edit user profile',
    ],
    'moneyManagement' => [
        'type' => 2,
        'description' => 'Manage money',
    ],

    /* SCHEDULER */
    'manageSchedule' => [
        'type' => 2,
        'description' => 'Manage schedule',
    ],
    'adminSchedule' => [
        'type' => 2,
        'description' => 'Admin schedule, change statuses after',
    ],
    'viewMissed' => [
        'type' => 2,
        'description' => 'View missed pupils report',
    ],
    'callMissed' => [
        'type' => 2,
        'description' => 'Call pupils that missed lessons',
    ],

    /* ACCOUNTANT */
    'viewSalary' => [
        'type' => 2,
        'description' => 'View teacher salary',
    ],
    'moneyCorrection' => [
        'type' => 2,
        'description' => 'Correct money movements manually',
    ],
    'moveMoney' => [
        'type' => 2,
        'description' => 'Move money from finished group to new one',
    ],

    /* GROUP MANAGER */
    'manageSubjectCategories' => [
        'type' => 2,
        'description' => 'Manage subject categories',
    ],
    'manageSubjects' => [
        'type' => 2,
        'description' => 'Manage subjects',
    ],
    'manageTeachers' => [
        'type' => 2,
        'description' => 'Manage teachers',
    ],
    'viewGroups' => [
        'type' => 2,
        'description' => 'View pupil groups',
    ],
    'manageGroups' => [
        'type' => 2,
        'description' => 'Manage pupil groups',
    ],
    'manageEvents' => [
        'type' => 2,
        'description' => 'Manage events',
    ],
    'reportGroupMovement' => [
        'type' => 2,
        'description' => 'Get group pupils movement report',
    ],
    'reportDebt' => [
        'type' => 2,
        'description' => 'Get group pupils debt report',
    ],
    'reportMoney' => [
        'type' => 2,
        'description' => 'Get money totals report for single group',
    ],
    'reportMoneyTotal' => [
        'type' => 2,
        'description' => 'Get money totals report for all groups',
    ],
    'reportCash' => [
        'type' => 2,
        'description' => 'Get money cash report for a day',
    ],
    'manageGiftCardTypes' => [
        'type' => 2,
        'description' => 'Manage prepaid gift card types',
    ],

    // Root only!
    'pupilChangePast' => [
        'type' => 2,
        'description' => 'Edit pupils in groups in past dates',
    ],
    'deleteTeachers' => [
        'type' => 2,
        'description' => 'Delete teachers',
    ],
    'sendPush' => [
        'type' => 2,
        'description' => 'Send custom push-messages',
    ],

    /* SUPPORT */
    'manageFeedbacks' => [
        'type' => 2,
        'description' => 'Manage feedbacks',
    ],
    'manageOrders' => [
        'type' => 2,
        'description' => 'Manage orders',
    ],
    'manageReviews' => [
        'type' => 2,
        'description' => 'Manage reviews',
    ],

    /* CONTENT MANAGER */
    'managePages' => [
        'type' => 2,
        'description' => 'Manage pages',
    ],
    'manageMenu' => [
        'type' => 2,
        'description' => 'Manage menus',
    ],
    'manageWidgetHtml' => [
        'type' => 2,
        'description' => 'Manage blocks',
    ],
    'manageBanner' => [
        'type' => 2,
        'description' => 'Manage banner contents',
    ],
    'manageHighSchools' => [
        'type' => 2,
        'description' => 'Manage high schools',
    ],
    'manageQuiz' => [
        'type' => 2,
        'description' => 'Manage quizes',
    ],
    'managePromotions' => [
        'type' => 2,
        'description' => 'Manage promotions',
    ],
    'manageBlog' => [
        'type' => 2,
        'description' => 'Manage blog',
    ],
    'manageNews' => [
        'type' => 2,
        'description' => 'Manage news',
    ],

    'viewMySchedule' => [
        'type' => 2,
        'description' => 'View my schedule',
        'ruleName' => 'canViewSchedule',
        'children' => [
            'viewSchedule',
        ],
    ],
    'viewSchedule' => [
        'type' => 2,
        'description' => 'View schedule',
    ],
    'viewNotes' => [
        'type' => 2,
        'description' => 'View group notes history',
    ],
];
