<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Config;

class genericErrorNotification extends Notification
{
    use Queueable;
    private $errorCode;
    private $comp;
    private $contact;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct($errorCode, $comp, $contact)
    {
        $this->errorCode = $errorCode;
        $this->comp = $comp;
        $this->contact = $contact;
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
        //usage: if the original subscription was cancelled and the new subscription and subsequently resume subscription failed. and didn't revert to original primary contact
        $epcns = Config::get('constants.EDIT_PRIMARY_CONTACT_NEW_SUBSCRIPTION');
        //usage: subscription wasn't transferred and then didn't revert back to original primary contact. original subscription is still intact.
        $epccs = Config::get('constants.EDIT_PRIMARY_CONTACT_CANCEL_SUBSCRIPTION');

        $greeting = "Greetings!";

        $msgBody = 'Description should be provided, otherwise error notification needs completion.';

        if($this->errorCode  == $epccs) {

            $greeting = 'Important! ODIN Case Management Subscription Error - Billing Incorrect User!';

            $msgBody = 'An attempt was made to edit the primary contact and the primary contact was changed 
            but the current subscription was not transferred to the new primary contact. Please follow up on this matter 
            as the old primary contact will continue to be billed.';

        }elseif($this->errorCode == $epcns){

            $greeting = 'Important! ODIN Case Management Subscription Error - Subscription Cancelled!';

            $msgBody = 'An attempt was made to edit the primary contact and the primary contact was changed 
            but the transfer of subscription failed. Please follow up on this matter as the subscription has been inadvertently cancelled.';

        }

        return (new MailMessage)
            ->subject('ERROR Notification')
            ->greeting($greeting)
            ->line('Error Code: '.$this->errorCode)
            ->line($msgBody)
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
