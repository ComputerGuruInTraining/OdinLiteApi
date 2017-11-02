<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class ChangeEmailOld extends Notification
{
    use Queueable;
    public $compName;
    public $emailNew;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct($compName, $emailNew)
    {
        $this->compName = $compName;

        $this->emailNew = $emailNew;
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
            ->subject('Email Change Courtesy Notification')

            ->greeting('Hello!')

            ->line('Just a courtesy notification to advise you that this email address is no longer your login email for
            OdinLite Mobile App for '.$this->compName. '. This is due to your email address 
            recently being updated in the Management Console to '.$this->emailNew.'.')

            ->line('If you are not happy about this change or you do not believe this should have occurred, 
            please see a user of the management console to continue using this email address.');
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
