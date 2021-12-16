<?php


namespace Agmedia\Models;


use Illuminate\Database\Eloquent\Model;

class Coupon extends Model
{
    
    /**
     * @var string
     */
    protected $table = 'coupon';
    
    /**
     * @var string
     */
    protected $primaryKey = 'coupon_id';
    
    /**
     * @var array
     */
    protected $guarded = [
        'coupon_id'
    ];
}