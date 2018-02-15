<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class ChangePW extends Notification
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
            ->subject('Password Change Security Notification')

            ->greeting('Hello!')

            ->line('You are receiving this email because your password for ODIN Case Management Mobile App for '.$this->compName.' has 
            recently been changed.')

            ->line('If you made this change, you may delete this message and no further action is required.')

            ->line('If you did not change your password, please be advised that your password has been changed using the 
            ODIN Case Management Mobile App Change Password Facility and we recommend that you reset your password to protect your security. 
            The Forgot Password Facility in the app can be used for this purpose.');
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
