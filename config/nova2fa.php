<?php

use Partybussen\Nova2fa\Models\User2fa;

return [
    /**
     * Disable or enable middleware.
     */
    'enabled' => env('NOVA_2FA_ENABLED', true),

    'models' => [
        /**
         * Change this variable to path to user model.
         */
        'user'    => 'App\User',

        /**
         * Change this if you need a custom connector
         */
        'user2fa' => User2fa::class,
    ],
    'tables' => [
        /**
         * Table in which users are stored.
         */
        'user' => 'users',
    ],

    'recovery_codes' => [
        'enabled' => env('NOVA_2FA_ENABLE_RECOVERY', true),

        /**
         * Number of recovery codes that will be generated.
         */
        'count'             => 5,

        /**
         * Number of blocks in each recovery code.
         */
        'blocks'            => 2,

        /**
         * Number of characters in each block in recovery code.
         */
        'chars_in_block'    => 5,

        /**
         * The following algorithms are currently supported:
         *  - PASSWORD_DEFAULT
         *  - PASSWORD_BCRYPT
         *  - PASSWORD_ARGON2I // available from php 7.2
         */
        'hashing_algorithm' => PASSWORD_BCRYPT,
    ],

    /**
     * Optional field on the User model that indicates if 2fa is required for the user. The field should be a boolean.
     */
    'requires_2fa_attribute' => null,

    'google_qr_code_url' => env('NOVA_2FA_GOOGLE_QR_URL', 'https://quickchart.io/'),

    'google_qr_code_query' => env('NOVA_2FA_GOOGLE_QR_QUERY', 'chs=200x200&chld=M|0&cht=qr&chl='),

];