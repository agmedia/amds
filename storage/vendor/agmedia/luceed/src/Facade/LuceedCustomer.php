<?php


namespace Agmedia\Luceed\Facade;


class LuceedCustomer extends Facade
{
    
    /**
     * @return mixed
     */
    public static function all()
    {
        return self::$luceed->getCustomers();
    }
    
    
    /**
     * @param string $partner_uid
     *
     * @return mixed
     */
    public static function getByUid(string $partner_uid)
    {
        return self::$luceed->getCustomer($partner_uid, 'sifra');
    }
    
    
    /**
     * @param string $email
     *
     * @return mixed
     */
    public static function getByEmail(string $email)
    {
        return self::$luceed->getCustomer($email, 'email');
    }
    
    
    /**
     * @param string $oib
     *
     * @return mixed
     */
    public static function getByOib(string $oib)
    {
        return self::$luceed->getCustomer($oib, 'oib');
    }
    
    
    /**
     * @param string $name
     *
     * @return mixed
     */
    public static function getByName(string $name)
    {
        return self::$luceed->getCustomer($name, 'naziv');
    }
    
    
    /**
     * @param array $body
     *
     * @return mixed
     */
    public static function create(array $body)
    {
        return self::$luceed->createCustomer($body);
    }
    
    
    /**
     * @param string $partner_uid
     * @param array  $body
     *
     * @return mixed
     */
    public static function update(string $partner_uid, array $body)
    {
        return self::$luceed->updateCustomer($partner_uid, $body);
    }
}