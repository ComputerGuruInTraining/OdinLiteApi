<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Stripe, Mailgun, SparkPost and others. This file provides a sane
    | default location for this type of information, allowing packages
    | to have a conventional place to find your various credentials.
    |
    */

    'mailgun' => [
        'domain' => 'odinlite.net',
        'secret' => 'key-523c91a9a95f4f039f270ee75a5bf9b6',
    ],

    'ses' => [
        'key' => env('SES_KEY'),
        'secret' => env('SES_SECRET'),
        'region' => 'us-east-1',
    ],

    'sparkpost' => [
        'secret' => env('SPARKPOST_SECRET'),
    ],

    'stripe' => [
        'model' => App\User::class,

        //test data
//        'key' => 'pk_test_u5hJw0nEAL2kgix2Za91d3cV',
//        'secret' => 'sk_test_WmGxgDGLP3M9MRJ2oWLJCxlc'

        //live data
        'key' => 'pk_live_oQQ02SuVrn0UHTOnYIKsizcV',
        'secret' => 'sk_live_To9x3WX6NSHvrto3FpH760V1',
    ],

];
