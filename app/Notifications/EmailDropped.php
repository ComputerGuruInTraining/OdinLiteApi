<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use App\User as User;
use App\Company as Company;

class EmailDropped extends Notification
{
    use Queueable;

    public $appErrors;
    public $company;
    public $user;
    public $primaryContact;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct($appErrors)
    {
        $this->appErrors = $appErrors;

        $this->user = User::withTrashed()
            ->where('email', '=', $appErrors->recipient)
            ->select('id', 'first_name', 'last_name')
            ->first();//could be null

        if($this->user != null) {
            $this->company = Company::where('id', '=', $this->user->company_id)->select('name', 'primary_contact')->first();

            if($this->company != null) {
                $this->primaryContact = User::withTrashed()
                    ->where('id', '=', $this->company->primary_contact)
                    ->select('first_name', 'last_name')->first();
            }
        }
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
        if(($this->user != null)&&($this->primaryContact != null)&&($this->company != null)) {
            if ($this->appErrors->description != null) {
                return (new MailMessage)
                    ->subject('Notification: auto-generated email to user dropped')
                    ->greeting('Uh Oh!')
                    ->line('An email did not arrive at the intended recipient:')
                    ->line('Subject of Email: ' . $this->appErrors->description)
                    ->line('Recipient: ' . $this->user->first_name . ' ' . $this->user->last_name)
                    ->line('Email address of recipient: ' . $this->appErrors->recipient)
                    ->line('Company of Recipient: ' . $this->company->name)
                    ->line('Company Contact: ' . $this->primaryContact->first_name . ' ' . $this->primaryContact->last_name)
                    ->line('Please follow up on this issue!');
            } else {
                return (new MailMessage)
                    ->subject('Notification: auto-generated email to user dropped')
                    ->greeting('Uh Oh!')
                    ->line('An email did not arrive at the intended recipient:')
                    ->line('Recipient: ' . $this->user->first_name . ' ' . $this->user->last_name)
                    ->line('Email address of recipient: ' . $this->appErrors->recipient)
                    ->line('Company of Recipient: ' . $this->company->name)
                    ->line('Company Contact: ' . $this->primaryContact->first_name . ' ' . $this->primaryContact->last_name)
                    ->line('Please follow up on this issue!');
            }
        }else{
            return (new MailMessage)
                ->subject('Notification: auto-generated email to user dropped')
                ->greeting('Uh Oh!')
                ->line('An email did not arrive at the intended recipient:')
                ->line('Email address of recipient: ' . $this->appErrors->recipient)
                ->line('Unfortunately, we were unable to match the email address to a user.')
                ->line('Please follow up on this issue!');

        }
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
