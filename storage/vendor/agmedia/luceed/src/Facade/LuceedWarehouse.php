<?php


namespace Agmedia\Luceed\Facade;


use Agmedia\Luceed\Luceed;

class LuceedWarehouse
{
    
    /**
     * @return mixed
     */
    public static function all()
    {
        $luceed = new Luceed();
        
        return $luceed->getWarehouseList();
    }
    
    
    /**
     * @param string $uid
     *
     * @return mixed
     */
    public static function getById(string $id)
    {
        $luceed = new Luceed();
    
        return $luceed->getWarehouse($id);
    }
}