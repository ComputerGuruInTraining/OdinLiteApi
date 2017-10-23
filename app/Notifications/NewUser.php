<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class NewUser extends Notification
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
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->greeting('Welcome!')
            ->line('You are receiving this email because you have been added as a new user for Odin Lite Management Console on behalf of '. $this->compName.'.')
            ->line('Please create a password to use with the account using our Password Reset Facility. This facility optimizes the security of our software suite by not 
              emailing passwords to users or sharing passwords with the person who registered the account.')
            ->line('It also enables you to change the password at your convenience. 
              Once you request a password reset, you will have 	
            24 hours to change the password.')
            ->action('Initiate Reset Password Process', url('password/reset'))
            ->line('If you do not believe the account should have been created for you, please consult with Management of '. $this->compName.'.');
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        return [
            //
        ];
    }
}
