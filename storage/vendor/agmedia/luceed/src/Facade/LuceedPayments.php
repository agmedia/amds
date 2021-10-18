<?php


namespace Agmedia\Luceed\Facade;


use Agmedia\Luceed\Luceed;

class LuceedPayments
{
    
    /**
     * @return mixed
     */
    public static function all()
    {
        $luceed = new Luceed();
        
        return $luceed->getPayments();
    }

}