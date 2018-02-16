<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class RegisterCompany extends Notification
{
    use Queueable;  
    
    public $compId;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct($compId)
    {
        $this->compId = $compId;
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
            ->line('To complete the registration of your ODIN Case Management Console account, please activate the account.')
            ->action('Activate', url('activate/'.$this->compId));
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
