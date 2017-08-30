<?php

namespace App\Mail;

use App\User as User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class NewMobileUser extends Mailable
{
    use Queueable, SerializesModels;

    public $user;
    
    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(User $user)
    {
     $this->user = $user;   
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        //!important! Replaced by a notification
        return $this->view('emails/new_mobile_user_email');
    }
}
