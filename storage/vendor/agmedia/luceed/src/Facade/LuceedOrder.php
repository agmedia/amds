<?php


namespace Agmedia\Luceed\Facade;


use Agmedia\Luceed\Luceed;

class LuceedOrder extends Facade
{

    /**
     * @param string $statuses
     * @param string $date
     *
     * @return false|mixed
     */
    public static function get(string $statuses, string $date = null)
    {
        $luceed = new Luceed();

        if ($date) {
            $statuses .= '/' . $date;
        }

        return $luceed->getOrders($statuses);
    }

}