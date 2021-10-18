<?php

namespace Agmedia\Luceed;

use Agmedia\Luceed\Connection\LuceedService;

/**
 * Class Luceed
 * @package Agmedia\Luceed
 */
class Luceed
{

    /**
     * @var LuceedService
     */
    private $service;

    /**
     * @var string
     */
    private $env = 'local';

    /**
     * @var array
     */
    private $end_points = [];


    /**
     * Luceed constructor.
     */
    public function __construct()
    {
        $this->service    = new LuceedService();
        $this->env        = agconf('env');
        $this->end_points = LuceedEndPoints::get($this->env);
    }

    /*******************************************************************************
     *                                Copyright : AGmedia                           *
     *                              email: filip@agmedia.hr                         *
     *******************************************************************************/
    // GROUPS / CATEGORIES

    /**
     * @return mixed
     */
    public function getGroupList()
    {
        return $this->service->get($this->end_points['group_list']);
    }


    /*******************************************************************************
     *                                Copyright : AGmedia                           *
     *                              email: filip@agmedia.hr                         *
     *******************************************************************************/
    // MANUFACTURERS

    /**
     * @return false|mixed
     */
    public function getManufacturerList()
    {
        return $this->service->get($this->end_points['manufacturer_list']);
    }


    /**
     * @param string $manufacturer_uid
     *
     * @return false|mixed
     */
    public function getManufacturer(string $manufacturer_uid)
    {
        return $this->service->get($this->end_points['manufacturer_uid'] . $manufacturer_uid);
    }


    /*******************************************************************************
     *                                Copyright : AGmedia                           *
     *                              email: filip@agmedia.hr                         *
     *******************************************************************************/
    // MANUFACTURERS

    /**
     * @return false|mixed
     */
    public function getWarehouseList()
    {
        return $this->service->get($this->end_points['warehouse_list']);
    }


    /**
     * @param string $warehouse_uid
     *
     * @return false|mixed
     */
    public function getWarehouse(string $warehouse_uid)
    {
        return $this->service->get($this->end_points['warehouse'] . $warehouse_uid);
    }


    /*******************************************************************************
     *                                Copyright : AGmedia                           *
     *                              email: filip@agmedia.hr                         *
     *******************************************************************************/
    // MANUFACTURERS

    /**
     * @return false|mixed
     */
    public function getPlaces()
    {
        return $this->service->get($this->end_points['mjesta']);
    }


    /**
     * @param string $id
     *
     * @return false|mixed
     */
    public function getPlaceByName(string $name)
    {
        return $this->service->get($this->end_points['mjesta'] . $name);
    }


    /*******************************************************************************
     *                                Copyright : AGmedia                           *
     *                              email: filip@agmedia.hr                         *
     *******************************************************************************/
    // MANUFACTURERS

    /**
     * @return false|mixed
     */
    public function getPayments()
    {
        return $this->service->get($this->end_points['vrste_placanja']);
    }


    /*******************************************************************************
     *                                Copyright : AGmedia                           *
     *                              email: filip@agmedia.hr                         *
     *******************************************************************************/
    // PRODUCTS

    /**
     * @param array|null $query
     *
     * @return mixed
     */
    public function getProductsList(array $query = null)
    {
        return $this->service->get($this->end_points['product_list'] . ($query ?: ''));
    }


    /**
     * @param string|array $id
     *
     * @return false|mixed
     */
    public function getProduct($id)
    {
        if ($this->env == 'local') {
            return $this->service->get($this->end_points['product_' . $id]);
        }

        return $this->service->get($this->end_points['product'], $id);
    }


    /**
     * @param array|null $query
     *
     * @return mixed
     */
    public function getProductsActions(array $query = null)
    {
        return $this->service->get($this->end_points['product_actions'] . ($query ?: ''));
    }


    /**
     * @param string $uid
     *
     * @return mixed
     */
    public function getProductImage($uid)
    {
        return $this->service->get($this->end_points['product_image'], $uid);
    }


    /*******************************************************************************
     *                                Copyright : AGmedia                           *
     *                              email: filip@agmedia.hr                         *
     *******************************************************************************/
    // STOCK

    /**
     * @param string|array $warehouse_uid
     *
     * @return false|mixed
     */
    public function getStock($warehouse_uid, $product)
    {
        $query = $warehouse_uid . '/' . $product;

        return $this->service->get($this->end_points['stock_get'], $query);
    }


    /**
     * @param string|array $warehouse_uid
     *
     * @return false|mixed
     */
    public function getWarehouseStock(array $uids = null)
    {
        $query = '[' . implode(',', $uids) . ']';

        return $this->service->get($this->end_points['stock_skladista'], $query);
    }


    /**
     * @param $article_uid
     *
     * @return false|mixed
     */
    public function getSuplierStock(string $article_uid = '')
    {
        $end_point = $article_uid == '' ? $this->end_points['stock_dobavljaca'] : $this->end_points['stock_dobavljac'];

        return $this->service->get($end_point, $article_uid);
    }


    /*******************************************************************************
     *                                Copyright : AGmedia                           *
     *                              email: filip@agmedia.hr                         *
     *******************************************************************************/
    // CUSTOMERS

    /**
     * @param array $customer
     *
     * @return mixed
     */
    public function createCustomer(array $customer)
    {
        return $this->service->post($this->end_points['customer_create'], $customer);
    }


    /**
     * @param array $customer
     *
     * @return mixed
     */
    public function updateCustomer(array $customer)
    {
        return $this->service->put($this->end_points['customer_create'], $customer);
    }


    /**
     * @param array $customer
     *
     * @return mixed
     */
    public function getCustomerByEmail(string $email)
    {
        return $this->service->get($this->end_points['customer_email'], $email);
    }


    /*******************************************************************************
     *                                Copyright : AGmedia                           *
     *                              email: filip@agmedia.hr                         *
     *******************************************************************************/
    // ORDERS

    /**
     * @param array $order
     *
     * @return mixed
     */
    public function createOrder(array $order)
    {
        return $this->service->post($this->end_points['order_create'], $order);
    }


    /**
     * @param string $uid
     *
     * @return false|mixed
     */
    public function orderWrit(string $uid)
    {
        return $this->service->get($this->end_points['raspis'], $uid);
    }


    /**
     * @param string $query
     *
     * @return false|mixed
     */
    public function getOrders(string $query)
    {
        return $this->service->get($this->end_points['orders_get'], $query);
    }

}