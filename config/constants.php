<?php
/**
 * Created by PhpStorm.
 * User: bernie
 * Date: 14/2/18
 * Time: 12:09 PM
 */


return [

    /*****company details******/
    'COMPANY_EMAIL' => 'hello@odincasemanagement.com',
    'COMPANY_EMAIL2' => 'admin@odinlite.net',
    'COMPANY_NAME' => 'ODIN Case Management',
    'CONSOLE_NAME' => 'ODIN Case Management Console',
    'MOBILE_APP_NAME' => 'ODIN Case Management Mobile App',
    'COMPANY_NICKNAME' => 'ODIN',
    'TEAM' => 'ODIN Team',

    /****Active Campaign config****/
    'ACTIVE_API_KEY' => '7b60b3914bd330fe2f29461d391133847685111e2f6910dec5aa16627ea83bf82c1ecfb9',
    'ACTIVE_URL' => 'https://odin.api-us1.com/admin/api.php?',
    'TRIAL_TAG' => 'Started Trial - 90 Days',//todo, change to 30 days once beta testing complete
//    'PAID_CUSTOMER_TAG' => 'paid customer',//todo: archive soon.
    'PAID_MONTHLY_TAG' => 'Started Subscription - Paid Monthly',
    'PAID_YEARLY_TAG' => 'Started Subscription - Paid Yearly',
    'REMOVE_TAG_REQUEST' => 'api_action=contact_tag_remove&api_output=json&api_key=',
    'ADD_TAG_REQUEST' => 'api_action=contact_tag_add&api_output=json&api_key=',

    /*****Stripe Plan Ids with amounts in  USD*******/

    /*test monthly*/
    'TEST_PLAN_M1' => 'plan_CXk0vEO3OII8ZU',

    /*test yearly*/
    'TEST_PLAN_Y1' => 'plan_CXk0hfbFwLLvVd',

    /*monthly*/
    'PLAN_M1' => 'plan_CXjmOc1d4APdQ1',//$29/mth
    'PLAN_M2' => 'plan_CXjnfBY2GonO8J',//$59/mth
    'PLAN_M3' => 'plan_CXjnJMfqW8hTnH',//$99/mth
    'PLAN_M4' => '',

    /*yearly*/
    'PLAN_Y1' => 'plan_CXjqEb6lGsQXcU',//$228/yr
    'PLAN_Y2' => 'plan_CXjqkgBvAAikFU',//$468/yr
    'PLAN_Y3' => 'plan_CXjrylVJoFY2It',//$828/yr
    'PLAN_Y4' => '',



    /*****error msgs******/

//    'ERROR_UPDATE' => 'Unexpected error updating details',


];