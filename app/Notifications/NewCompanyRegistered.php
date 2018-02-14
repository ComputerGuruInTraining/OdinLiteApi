<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use App\User as User;

class NewCompanyRegistered extends Notification
{
    use Queueable;
    public $comp;
    public $contact;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct($comp)
    {
        $this->comp = $comp;

        $this->contact = User::find($this->comp->primary_contact);

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
            ->subject('Registration Notification: '.$this->comp->name.'')
            ->greeting('Hello!')
            ->line('A new company has registered.')
            ->line('Company Name: ' .$this->comp->name.'')
            ->line('Primary Contact: ' .$this->contact->first_name. ' ' .$this->contact->last_name. '')
            ->line('Email: ' .$this->contact->email. '.')
            ->line('You will be alerted once the company has activated their account. If you do not receive an 
            alert titled Activation Notification: '.$this->comp->name.' within the next few days, 
            the company may not have received the activation email 
            or may not have acted upon it yet for some reason.');

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
