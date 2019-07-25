<?php
/**
 * Created by PhpStorm.
 * User: bernie
 * Date: 25/6/19
 * Time: 10:11 AM
 */

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;


class ShiftResume extends Model
{
    use SoftDeletes;

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = ['deleted_at'];
}