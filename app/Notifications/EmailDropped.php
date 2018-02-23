<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class EmailDropped extends Notification
{
    use Queueable;

    public $appErrors;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct($appErrors)
    {
        $this->appErrors = $appErrors;

        //todo: use email to find company_id from users table and retrieve primary_contact
        //todo: perhaps also send throught the name of the company Yes.
//        $this->contact = User::find($this->comp->primary_contact);
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
            ->subject('Notification: auto-generated email to user dropped')
            ->greeting('Uh Oh!')
            ->line('An email did not arrive at the intended recipient:')
//            ->line('An email did not arrive at the intended recipient:')
            ->line('Please follow up on this issue!');
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
