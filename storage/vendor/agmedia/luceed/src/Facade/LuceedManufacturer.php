<?php


namespace Agmedia\Luceed\Facade;


use Agmedia\Luceed\Luceed;

class LuceedManufacturer
{
    
    /**
     * @return mixed
     */
    public static function all()
    {
        $luceed = new Luceed();
        
        return $luceed->getManufacturerList();
    }
    
    
    /**
     * @param string $uid
     *
     * @return mixed
     */
    public static function getByUid(string $uid)
    {
        $luceed = new Luceed();
    
        return $luceed->getManufacturer($uid);
    }
}