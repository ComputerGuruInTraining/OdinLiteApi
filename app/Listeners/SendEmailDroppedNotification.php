<?php

namespace App\Listeners;

use App\Events\EmailDropped;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

/*notification imports*/
use App\Recipients\DynamicRecipient;
use App\Notifications\EmailDropped as EmailAlert;
use Config;

class SendEmailDroppedNotification
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
     * @param  EmailDropped  $event
     * @return void
     */
    public function handle(EmailDropped $event)
    {
        $appErrors = $event->appErrors;

        $odinTeam = new DynamicRecipient(Config::get('constants.COMPANY_EMAIL2'));
        $odinTeam->notify(new EmailAlert($appErrors));
    }
}
