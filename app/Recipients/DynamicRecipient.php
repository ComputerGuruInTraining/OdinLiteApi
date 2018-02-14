<?php
/**
 * Created by PhpStorm.
 * User: bernie
 * Date: 2/11/17
 * Time: 11:35 AM
 */

namespace App\Recipients;

class DynamicRecipient extends Recipient{

    public function __construct($email)
    {
        $this->email = $email;
    }

}