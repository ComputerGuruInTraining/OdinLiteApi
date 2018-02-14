<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;
use Config;

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
            ->subject('Email Removed Courtesy Notification')

            ->greeting('Hello!')

            ->line('Just a courtesy notification to advise you that this email address is no longer your login email 
            for '.Config::get('constants.CONSOLE_NAME').' used by '.$this->compName. '. This is due to your login email address
            recently being changed to '.$this->emailNew.'.')

            ->line('If you do not approve of this change, you, or an authenticated user, can revert your login details 
            back so that you can continue using this preferred email address.');

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
