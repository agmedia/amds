<?php

namespace Agmedia\Luceed;

/**
 * Class LuceedEndPoints
 * @package Agmedia\Luceed
 */
class LuceedEndPoints
{

    /**
     * @return string[]
     */
    private static function local(): array
    {
        return [
            'group_list'        => 'grupeartikala_lista.json',
            'manufacturer_list' => 'robnemarke_lista.json',
            'warehouse_list'    => 'skladista_lista.json',
            'product_list'      => 'artikli_lista_full.json',
            'product_actions'   => 'akcije.json',
            'product_image'     => 'product_image.json',
            'manufacturer_uid'  => 'partner_single.json',
            'customer_email'    => 'customer_email.url',
            'customer_create'   => 'customer_create.url',
            'order_create'      => 'order_create.url',
            'orders_get'        => 'orders_get.json',
            'stock_get'         => 'stock_skladista.json',
            'ind_stock_get'     => 'ind_stock_get.url',
            'raspis'            => 'raspis.url',
            'mjesta'            => 'mjesta.json',
            'vrste_placanja'    => 'vrste_placanja.json',
            //
            'stock_skladista'   => 'stock_skladista.json',
            'stock_dobavljaca'  => 'stock_dobavljaca.json',
            'stock_dobavljac'   => 'stock_dobavljac.json',
            //
            'product_0730222201' => 'artikl_0730222201.json',
            'product_0730161301' => 'artikl_0730161301.json',
            'product_9001002044' => 'artikl_9001002044.json',
        ];
    }


    /**
     * @return string[]
     */
    private static function production(): array
    {
        return [
            'group_list'        => 'grupeartikala/lista',
            'manufacturer_list' => 'robnemarke/lista',
            'warehouse_list'    => 'skladista/lista',
            'product_list'      => 'artikli/naziv//',
            'product'           => 'artikli/sifra/',
            'product_actions'   => 'akcije/lista',
            'product_image'     => 'artikli/dokumenti/',
            'manufacturer_uid'  => 'partneri/uid/',
            'customer_email'    => 'partneri/email/',
            'customer_create'   => 'partneri/snimi/',
            'order_create'      => 'NaloziProdaje/snimi/',
            'orders_get'        => 'NaloziProdaje/statusi/',
            'stock_get'         => 'StanjeZalihe/Skladiste/',
            'ind_stock_get'     => 'StanjeZalihe/ArtiklUID/',
            'raspis'            => 'NaloziProdaje/raspis/poslovnica/',
            'mjesta'            => 'mjesta/naziv/',
            'vrste_placanja'    => 'vrsteplacanja/list',
            //
            'stock_skladista'   => 'StanjeZalihe/Skladiste/',
            'stock_dobavljaca'  => 'StanjeZaliheDobavljaci/Lista',
            'stock_dobavljac'   => 'StanjeZaliheDobavljaci/Artikl/',
        ];
    }


    /**
     * @param $env
     *
     * @return string[]
     */
    public static function get($env)
    {
        if ($env == 'local') {
            return static::local();
        }

        return static::production();
    }
}