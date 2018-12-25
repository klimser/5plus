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
    'cashier' => [
        'type' => 1,
        'children' => [
            'manageUsers',
            'editUser',
            'moneyManagement',
        ],
    ],
    'scheduler' => [
        'type' => 1,
        'children' => [
            'manageSchedule',
            'viewGroups',
        ],
    ],
    'accountant' => [
        'type' => 1,
        'children' => [
            'viewSalary',
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
            'reportGroupMovement',
        ],
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

    /* ACCOUNTANT */
    'viewSalary' => [
        'type' => 2,
        'description' => 'View teacher salary',
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
];
