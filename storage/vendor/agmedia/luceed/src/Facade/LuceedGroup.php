<?php


namespace Agmedia\Luceed\Facade;


use Agmedia\Luceed\Luceed;

class LuceedGroup
{
    
    /**
     * @return mixed
     */
    public static function all()
    {
        $luceed = new Luceed();
        
        return $luceed->getGroupList();
    }
    
    
    /**
     * @return mixed
     */
    public static function aditions()
    {
        $luceed = new Luceed();
    
        return $luceed->getGroupAdditionsList();
    }
    
    
    /**
     * @param string $uid
     *
     * @return mixed
     */
    public static function getByUid(string $uid)
    {
        $luceed = new Luceed();
    
        return $luceed->getGroup($uid);
    }
}