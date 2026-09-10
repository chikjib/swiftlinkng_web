<?php

return [
    /*
     * These category titles count toward the ₦500 welcome target
     * and the ₦50,000 active-user target.
     */
    'qualifying_categories' => [
        'Airtime',
        'Data',
        'Cable',
        'Electricity',
        'Exam',
        'BulkSMS'
    ],

    'data_category' => 'Data',

    /*
     * Disposable inbox providers commonly used to manufacture referral
     * accounts. Add domains here without changing registration code.
     */
    'blocked_email_domains' => [
        '10minutemail.com',
        'dispostable.com',
        'emailondeck.com',
        'fakeinbox.com',
        'getnada.com',
        'guerrillamail.com',
        'maildrop.cc',
        'mailinator.com',
        'mintemail.com',
        'mohmal.com',
        'sharklasers.com',
        'temp-mail.org',
        'tempmail.com',
        'throwawaymail.com',
        'trashmail.com',
        'yopmail.com',
    ],

    'welcome' => [
        'minimum_spend' => 500,
        'referrer_reward' => 50,
        'friend_reward' => 50
    ],

    /*
     * Percentage commission remains controlled through the products
     * JSON stored against Subcategory ID 23.
     */
    'data_commission' => [
        'subcategory_id' => 23
    ],
    
    'order_history' => [
        'subcategory_id' => 23,
    ],
    
    'program_starts_at' => env(
        'REFERRAL_PROGRAM_START_AT',
        '2026-08-01T15:08:27+01:00'
    ),

    /*
     * The referred user becomes active after ₦50,000 in cumulative
     * successful qualifying purchases.
     */
    'active' => [
        'minimum_spend' => 50000,
        'referrer_reward' => 400
    ],

    /*
     * Keys are cumulative active-user totals.
     *
     * Silver: 5
     * Diamond: 5 + 10 = 15
     * Gold: 5 + 10 + 20 = 35
     */
    'milestones' => [
        5 => [
            'name' => 'Silver',
            'reward' => 2000
        ],

        15 => [
            'name' => 'Diamond',
            'reward' => 4000
        ],

        35 => [
            'name' => 'Gold',
            'reward' => 32000
        ]
    ]
];
