<?php
/**
 * Created by PhpStorm.
 * User: bernie
 * Date: 2/11/17
 * Time: 11:35 AM
 */

namespace App;

use Illuminate\Notifications\Notifiable;


class DynamicRecipient extends Recipient
{
    use Notifiable;

    public function __construct($email)
    {
        $this->email = $email;
    }

}