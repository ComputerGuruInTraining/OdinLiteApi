<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class NewMobileUser extends Notification
{
    use Queueable;
    public $comp;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct($comp)
    {
        $this->comp = $comp;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->greeting('Welcome!')
            ->line('You are receiving this email because you have been registered to use ODIN Case Management Mobile App 
            on behalf of ' . $this->comp . '.')
            ->line('Download ODIN Case Management on your mobile or tablet:')
            ->line("<a href='https://play.google.com/store/apps/details?id=com.odinliteapp&pcampaignid=MKT-Other-global-all-co-prtnr-py-PartBadge-Mar2515-1'>
                <img alt='Get it on Google Play' src='https://play.google.com/intl/en_us/badges/images/generic/en_badge_web_generic.png'
                style='height: auto; width: 240px;'/></a>")

            ->line("<a href='https://itunes.apple.com/us/app/odinlite/id1290654035?mt=8''>
                <img alt='Find it on App Store' src='https://linkmaker.itunes.apple.com/assets/shared/badges/en-us/appstore-lrg.svg'
                style='height: auto; width: 210px; margin-left: 15px'/></a>")
            
            ->line('Please create a password to use with the account using our Password Reset Facility. This facility optimizes the security of our software suite by not 
              emailing passwords to users or sharing passwords with the person who registered the account.')
            ->line('It also enables you to change the password at your convenience. 
              Once you request a password reset, you will have 	
            24 hours to change the password.')
            ->action('Create Password', url('password/reset'));

    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        return [
            //
        ];
    }
}
