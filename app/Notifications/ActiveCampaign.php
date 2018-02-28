<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class ActiveCampaign extends Notification
{
    use Queueable;

    public $feature;
    public $result;
    public $contact;
    public $comp;
    public $msg;
    public $failedmsg;
    public $attempting;
    public $succeeded;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct($msg, $result, $contact, $comp, $feature, $failedmsg = null, $attempting = null, $succeeded = null)
    {
        $this->msg = $msg;
        $this->feature = $feature;
        $this->result = $result;
        $this->contact = $contact;
        $this->comp = $comp;
        $this->failedmsg = $failedmsg;
        $this->attempting = $attempting;
        $this->succeeded = $succeeded;
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
                    ->subject($this->result.' - Active Campaign - '.$this->msg)
                    ->greeting($this->result)
                    ->line('An active campaign event was triggered by '.$this->feature.'.')
                    ->line($this->succeeded)
                    ->line($this->failedmsg)
                    ->line($this->attempting)
                    ->line('Company Name: ' .$this->comp->name.'')
                    ->line('Primary Contact: ' .$this->contact->first_name. ' ' .$this->contact->last_name. '')
                    ->line('Email: ' .$this->contact->email)
                    ->line('Kind Regards,');
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
