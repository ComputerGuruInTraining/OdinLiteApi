<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;
use Config;

class ChangeEmailNew extends Notification
{
    use Queueable;
    public $compName;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct($compName)
    {
        $this->compName = $compName;
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
            ->subject('Email Added Courtesy Notification')

            ->greeting('Hello!')

            ->line('Just a courtesy notification to advise you that this email address is now your login email 
            for '.Config::get('constants.CONSOLE_NAME').' used by '.$this->compName. '. This is due to your login email address 
            recently being updated.')

            ->line('If you do not approve of this change, you, or an authenticated user, can revert your login details 
            back to your previous email address via the console.');
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
