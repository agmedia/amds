<?php


namespace Agmedia\Luceed\Facade;


use Agmedia\Luceed\Luceed;

class LuceedProduct extends Facade
{

    /**
     * @return mixed
     */
    public static function all()
    {
        $luceed = new Luceed();

        return $luceed->getProductsList();
    }


    /**
     * @return mixed
     */
    public static function shortList()
    {
        $luceed = new Luceed();

        return $luceed->getProductsShortList();
    }
    
    
    /**
     * @param string $id
     *
     * @return mixed
     */
    public static function getById(string $id)
    {
        $luceed = new Luceed();
        
        return $luceed->getProduct($id);
    }
    
    
    /**
     * @param $group_id
     *
     * @return mixed
     */
    public static function getByGroup($group_id)
    {
        $luceed = new Luceed();
        
        return $luceed->getGroupProducts($group_id);
    }
    
    
    /**
     * @param $manufacturer_id
     *
     * @return mixed
     */
    public static function getByManufacturer($manufacturer_id)
    {
        $luceed = new Luceed();
        
        return $luceed->getManufacturerProducts($manufacturer_id);
    }
    
    
    /**
     * @return mixed
     */
    public static function getActions()
    {
        $luceed = new Luceed();
        
        return $luceed->getProductsActions();
    }


    /**
     * @param null|string|array $units
     *
     * @return mixed
     */
    public static function stock($units, $product)
    {
        $luceed = new Luceed();

        return $luceed->getStock($units, $product);
    }


    /**
     * @param null|string|array $units
     *
     * @return mixed
     */
    public static function getWarehouseStock(array $units = null)
    {
        $luceed = new Luceed();

        return $luceed->getWarehouseStock($units);
    }
    
    
    /**
     * @param string $id
     *
     * @return mixed
     */
    public static function getSuplierStock(string $id = '')
    {
        $luceed = new Luceed();
        
        return $luceed->getSuplierStock($id);
    }
    
    
    /**
     * @param string $id
     *
     * @return mixed
     */
    public static function getImage(string $uid)
    {
        $luceed = new Luceed();
        
        return $luceed->getProductImage($uid);
    }
    
    
    /*******************************************************************************
    *                                Copyright : AGmedia                           *
    *                              email: filip@agmedia.hr                         *
    *******************************************************************************/
    // HELPERS
    
    /**
     * @param $group_id
     *
     * @return mixed
     */
    public static function getByCategory($group_id)
    {
        return self::getByGroup($group_id);
    }
}