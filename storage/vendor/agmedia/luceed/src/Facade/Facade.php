<?php


namespace Agmedia\Luceed\Facade;


use Agmedia\Luceed\Luceed;

class Facade
{
    
    /**
     * @var Luceed
     */
    protected static $luceed;
    
    
    /**
     * Facade constructor.
     *
     * @param Luceed $luceed
     */
    public function __construct()
    {
        self::$luceed = new Luceed();
    }
}