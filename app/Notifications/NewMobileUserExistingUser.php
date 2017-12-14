<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class NewMobileUserExistingUser extends Notification
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
            ->subject('New Employee Account')
            ->greeting('Greetings Existing User!')
            ->line('You are receiving this email because an employee account has been created for you 
            which enables you to use OdinLite Mobile App on behalf of ' . $this->comp . '.')
            ->line('Download OdinLite on your mobile or tablet:')
            ->line("<a href='https://play.google.com/store/apps/details?id=com.odinliteapp&pcampaignid=MKT-Other-global-all-co-prtnr-py-PartBadge-Mar2515-1'>
                <img alt='Get it on Google Play' src='https://play.google.com/intl/en_us/badges/images/generic/en_badge_web_generic.png' 
                style='height: auto; width: 240px;'/></a>")
            ->line("<img src='{{ asset(\"/images/Download_on_the_App_Store_Badge_US-UK_RGB_blk_092917.svg\") }}' alt='App Store Badge'/>")
            ->line('You can login to the mobile app using your current console user password.');

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
