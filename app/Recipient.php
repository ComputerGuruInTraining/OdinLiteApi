<?php
/**
 * Created by PhpStorm.
 * User: bernie
 * Date: 2/11/17
 * Time: 11:35 AM
 */

namespace App;

use Illuminate\Notifications\Notifiable;

abstract class Recipient
{

    use Notifiable;

    protected $email;

}