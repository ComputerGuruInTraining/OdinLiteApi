<?php

namespace App\Providers;

use Illuminate\Support\Facades\Event;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array
     */
    protected $listen = [
        //eg
//        'App\Events\SomeEvent' => [
//            'App\Listeners\EventListener',
//        ],

        'App\Events\CompanyRegistered' => [
            'App\Listeners\SendCompanyRegisteredNotification',
        ],
        'App\Events\EmailDropped' => [
            'App\Listeners\SendEmailDroppedNotification',
        ],
    ];

    /**
     * Register any events for your application.
     *
     * @return void
     */
    public function boot()
    {
        parent::boot();

        //
    }
}
