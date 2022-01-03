<?php

namespace Agmedia\LuceedOpencartWrapper\Models;

use Agmedia\Helpers\Log;
use Agmedia\Luceed\Luceed;
use Agmedia\Models\Customer\Customer;

/**
 * Class LOC_Customer
 * @package Agmedia\LuceedOpencartWrapper\Models
 */
class LOC_Customer
{

    /**
     * @var array|null
     */
    public $customer;

    /**
     * @var Luceed
     */
    private $service;

    /**
     * @var bool
     */
    private $should_update;


    /**
     * LOC_Customer constructor.
     *
     * @param array|null $customer
     */
    public function __construct(array $customer = null)
    {
        $this->customer = $this->create($customer);
    }


    /**
     * @return string
     */
    public function getUid(): string
    {
        return $this->customer['uid'] ?: '';
    }


    /**
     * Check by Email if the customer exist
     * in the Luceed database.
     * Set uid data if exist.
     *
     * @return bool
     */
    public function exist(): bool
    {
        // If uid exist, customer exist.
        // Customer uid is set on class construct
        // if it exists in oc_db.
        if ($this->customer['uid']) {
            return true;
        }

        $this->service = new Luceed();
        // Set the response from Luceed service.
        $exist = $this->setResponseData(
            $this->service->getCustomerByEmail($this->customer['e_mail'])
        );

        Log::store('Customer exist();', 'proccess_order');
        Log::store($exist, 'proccess_order');

        if ( ! empty($exist)) {
            // If customer exist set uid data.
            $this->customer['uid'] = $exist[0]->partner_uid;

            if ($this->should_update) {
                $this->customer['partner_uid'] = $exist[0]->partner_uid;

                $response = json_decode(
                    $this->service->updateCustomer(['partner' => [$this->customer]])
                );

                Log::store('Customer update();', 'proccess_order');
                Log::store($response, 'proccess_order');
            }

            if ( ! $this->should_update && $this->customer['adresa'] != $exist[0]->adresa) {
                $this->customer['partner_uid'] = $exist[0]->partner_uid;

                $response = json_decode(
                    $this->service->updateCustomer(['partner' => [$this->customer]])
                );

                Log::store('Customer update() 2;', 'proccess_order');
                Log::store($response, 'proccess_order');
            }

            return true;
        }

        return false;
    }


    /**
     * Store the customer in Luceed database.
     * Set uid data if response ok.
     *
     * @return $this
     */
    public function store()
    {
        $response = json_decode(
            $this->service->createCustomer(['partner' => [$this->customer]])
        );

        Log::store('Customer store();', 'proccess_order');
        Log::store($response, 'proccess_order');

        if (isset($response->result[0])) {
            $this->customer['uid'] = $response->result[0];
        }

        return $this;
    }


    /**
     * @param array|null $customer
     *
     * @return array
     */
    private function create(array $customer = null): array
    {
        $this->should_update = $customer['should_update'];

        return [
            'uid'            => $this->setUid($customer['customer_id'], true),
            'naziv'          => $customer['fname'] . ' ' . $customer['lname'],
            'ime'            => $customer['fname'],
            'prezime'        => $customer['lname'],
            'enabled'        => 'D',
            'tip_komitenta'  => 'F',
            'adresa'         => $customer['address'],
            'telefon'        => ($customer['phone'] != '') ? $customer['phone'] : '000',
            'e_mail'         => $customer['email'],
            'postanski_broj' => str_replace(' ', '', $customer['zip'])
        ];
    }


    /**
     * Set customer uid data.
     * Param can be luceed_uid or oc_customer_id.
     * If it's customer_id 2 param should be true.
     *
     * @param       $uid
     * @param false $from_ocdb
     */
    public function setUid($uid, $from_ocdb = false): void
    {
        if ($uid && $from_ocdb) {
            $customer = Customer::where('customer_id', $uid)->first();

            if ($customer) {
                $this->customer['uid'] = $customer->luceed_uid;
            }
        } else {
            $this->customer['uid'] = $uid ?: null;
        }
    }


    /**
     * Return the corrected response from luceed service.
     * Without unnecessary tags.
     *
     * @param $response
     *
     * @return array
     */
    private function setResponseData($response): array
    {
        Log::store('Customer setResponseData($response);', 'proccess_order');
        Log::store($response, 'proccess_order');

        $data = json_decode($response);

        if (isset($data->result[0])) {
            return $data->result[0]->partner;
        }

        return false;
    }

}