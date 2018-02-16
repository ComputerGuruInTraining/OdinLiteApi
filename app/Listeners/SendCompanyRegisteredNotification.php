<?php

namespace App\Listeners;

use App\Events\CompanyRegistered;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

/*notification imports*/
use App\Recipients\DynamicRecipient;
use App\Notifications\NewCompanyRegistered;
use Config;


class SendCompanyRegisteredNotification
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  CompanyRegistered  $event
     * @return void
     */
    public function handle(CompanyRegistered $event)
    {
        $company = $event->company;

        $odinTeam = new DynamicRecipient(Config::get('constants.COMPANY_EMAIL2'));
        $odinTeam->notify(new NewCompanyRegistered($company));

        $odinEmail = new DynamicRecipient(Config::get('constants.COMPANY_EMAIL'));
        $odinEmail->notify(new NewCompanyRegistered($company));

    }
}
