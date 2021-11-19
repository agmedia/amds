<?php


namespace Agmedia\Models;


use Illuminate\Database\Eloquent\Model;

class Location extends Model
{
    
    /**
     * @var string
     */
    protected $table = 'location';
    
    /**
     * @var string
     */
    protected $primaryKey = 'location_id';

    /**
     * @var bool
     */
    public $timestamps = false;
    
    /**
     * @var array
     */
    protected $guarded = [
        'location_id'
    ];
}