<?php

namespace Agmedia\LuceedOpencartWrapper\Models;

use Agmedia\Helpers\Log;
use Agmedia\Kaonekad\Models\ShippingCollector;
use Agmedia\Luceed\Facade\LuceedProduct;
use Agmedia\Luceed\Luceed;
use Agmedia\Models\Coupon;
use Agmedia\Models\Location;
use Agmedia\Models\Order\Order;
use Agmedia\Models\Order\OrderOption;
use Agmedia\Models\Order\OrderProduct;
use Agmedia\Models\Order\OrderTotal;
use Agmedia\Models\Product\Product;
use Agmedia\Models\Product\ProductOption;
use Illuminate\Support\Carbon;

/**
 * Class LOC_Order
 * @package Agmedia\LuceedOpencartWrapper\Models
 */
class LOC_Order
{

    /**
     * @var Luceed
     */
    private $service;

    /**
     * @var array|null
     */
    private $order;

    /**
     * @var array|null
     */
    private $oc_order;

    /**
     * @var array|null
     */
    private $response;

    /**
     * @var string|null
     */
    private $customer_uid = null;

    /**
     * @var array
     */
    private $call_raspis = true;

    /**
     * @var int
     */
    private $discount;

    /**
     * @var bool
     */
    private $has_all_in_main_warehouse = false;

    /**
     * @var array|null
     */
    private $has_all_in_warehouses = null;


    /**
     * LOC_Order constructor.
     *
     * @param array|null $order
     */
    public function __construct(array $order = null)
    {
        $this->oc_order = $order;
        $this->service = new Luceed();

        $this->resolveCouponDiscount();
    }


    /**
     * @param string $customer_uid
     *
     * @return $this
     */
    public function setCustomerUid(string $customer_uid)
    {
        $this->customer_uid = $customer_uid;

        return $this;
    }


    /**
     * @return false
     */
    public function store()
    {
        // Create luceed order data.
        $this->create();

        // Send order to luceed service.
        $this->response = json_decode(
            $this->service->createOrder(['nalozi_prodaje' => [$this->order]])
        );

        $this->log('Store order response: $this->response - LOC_Order #98.', $this->response);

        // If response ok.
        // Update order uid.
        if (isset($this->response->result[0])) {
            if ( ! $this->call_raspis) {
                $raspis = json_decode(
                    $this->service->orderWrit($this->response->result[0])
                );

                $this->log('Raspis response: $raspis - LOC_Order #110.', $raspis);
            }

            return Order::where('order_id', $this->oc_order['order_id'])->update([
                'luceed_uid' => $this->response->result[0]
            ]);
        }

        return false;
    }


    /**
     * Create luceed order data.
     */
    public function create(): void
    {
        $iznos = number_format($this->oc_order['total'], 2, '.', '');

        $this->order = [
            'nalog_prodaje_b2b' => 'AG-' . $this->oc_order['order_id'],
            'narudzba'          => $this->oc_order['order_id'],
            'datum'             => Carbon::make($this->oc_order['date_added'])->format(agconf('luceed.date')),
            'skladiste_uid'     => '565-2987',
            'status'            => agconf('luceed.status_uid'),
            'napomena'          => $this->oc_order['comment'],
            //'raspored'          => $this->getDeliveryTime(),
            'cijene_s_porezom'  => agconf('luceed.with_tax'),
            'partner_uid'       => $this->customer_uid,
            'iznos'             => (float) $iznos,
            'placanja'          => [
                [
                    'vrsta_placanja_uid' => $this->getPaymentType(),
                    'iznos'              => (float) $iznos,
                ]
            ],
            'stavke'            => $this->getItems(),
        ];

        if ($this->has_all_in_main_warehouse) {
            //$this->order['sa__skladiste_uid'] = agconf('luceed.default_warehouse_luid');
            //$this->order['na__skladiste_uid'] = '565-2987';
            //$this->order['skl_dokument'] = 'MSO';
            $this->order['vrsta_isporuke_uid'] = '3-2987';
            $this->order['korisnik__partner_uid'] = $this->customer_uid;
            $this->call_raspis = false;
        }

        if ( ! $this->has_all_in_main_warehouse &&$this->has_all_in_warehouses && isset($this->has_all_in_warehouses[0])) {
            $this->order['sa__skladiste_uid'] = $this->has_all_in_warehouses[0];
            $this->order['na__skladiste_uid'] = '565-2987';
            $this->order['skl_dokument'] = 'MS';
            $this->order['vrsta_isporuke_uid'] = '3-2987';
            $this->order['korisnik__partner_uid'] = $this->customer_uid;
        }

        if ( ! $this->has_all_in_main_warehouse && ! $this->has_all_in_warehouses) {
            $this->order['korisnik__partner_uid'] = $this->customer_uid;
            $this->call_raspis = false;
        }

        $this->log('Order create method: $this->>order - LOC_Order #156', $this->order);
    }


    /**
     * @return array
     */
    public function getCustomerData(): array
    {
        $update = $this->checkAddress();

        return [
            'customer_id'   => $this->oc_order['customer_id'],
            'fname'         => $update ? $this->oc_order['shipping_firstname'] : $this->oc_order['payment_firstname'],
            'lname'         => $update ? $this->oc_order['shipping_lastname'] : $this->oc_order['payment_lastname'],
            'email'         => $this->oc_order['email'],
            'phone'         => $this->oc_order['telephone'],
            'company'       => $update ? $this->oc_order['shipping_company'] : $this->oc_order['payment_company'],
            'address'       => $update ? $this->oc_order['shipping_address_1'] : $this->oc_order['payment_address_1'],
            'zip'           => $update ? $this->oc_order['shipping_postcode'] : $this->oc_order['payment_postcode'],
            'city'          => $update ? $this->oc_order['shipping_city'] : $this->oc_order['payment_city'],
            'country'       => $update ? $this->oc_order['shipping_country'] : $this->oc_order['payment_country'],
            'should_update' => $update
        ];
    }


    /**
     * @return mixed
     */
    public function recordError()
    {
        return Order::where('order_id', $this->oc_order['order_id'])->update([
            'luceed_uid' => $this->response->error
        ]);
    }


    /**
     * @return bool
     */
    public function collectProductsFromWarehouses(): bool
    {
        $availables     = [];
        $order_products = OrderProduct::where('order_id', $this->oc_order['order_id'])->get();

        //Log::store($order_products->toArray());

        if ($order_products->count()) {
            $locations = Location::orderBy('prioritet')->get();
            $units = $locations->pluck('skladiste')->flatten();

            foreach ($order_products as $order_product) {
                $option = OrderOption::where('order_id', $this->oc_order['order_id'])
                                     ->where('order_product_id', $order_product->order_product_id)
                                     ->first();

                if ($option) {
                    $product = ProductOption::where('product_option_value_id', $option->product_option_value_id)->first();

                    if ($product && $product->sifra) {
                        $availables_data = collect(
                            $this->setAvailables(
                                LuceedProduct::stock($this->getUnitsQuery($units), urlencode($product->sifra))
                            )
                        )->where('raspolozivo_kol', '>', 0);

                        /*Log::store($product->sifra);
                        Log::store($availables_data->toArray());*/

                        if ($availables_data->count()) {
                            foreach ($availables_data as $available) {
                                $availables[$available->skladiste_uid][] = [
                                    'uid' => $product->sifra,
                                    'qty' => $available->raspolozivo_kol
                                ];
                            }
                        }
                    }
                }
            }

            $this->log('Available products: ', $availables);

            // Check if all in MAIN warehouse.
            if (isset($availables[agconf('luceed.default_warehouse_luid')])) {
                if ($order_products->count() == count($availables[agconf('luceed.default_warehouse_luid')])) {
                    $this->has_all_in_main_warehouse = true;
                }

                /*if ($order_products->count() <= $availables[agconf('luceed.default_warehouse_luid')][0]['qty']) {
                    $this->has_all_in_main_warehouse = true;
                }*/

                unset($availables[agconf('luceed.default_warehouse_luid')]);
            }


            // Check & collect warehouses that have all items.
            foreach ($locations->where('stanje_web_shop', 1) as $store) {
                if (isset($availables[$store['skladiste_uid']])) {
                    if ($order_products->count() == count($availables[$store['skladiste_uid']])) {
                        $this->has_all_in_warehouses[] = $store['skladiste_uid'];
                    }

                    /*if ($order_products->count() <= $availables[$store['skladiste_uid']][0]['qty']) {
                        $this->has_all_in_warehouses[] = $store['skladiste_uid'];
                    }*/
                }
            }

            $this->log('has_all_in_main_warehouse: ', $this->has_all_in_main_warehouse ? 1 : 0);
            $this->log('has_all_in_warehouses: ', $this->has_all_in_warehouses ? 1 : 0);

            if ($this->has_all_in_warehouses && isset($this->has_all_in_warehouses[0])) {
                return true;
            }
        }

        return false;
    }


    /**
     * @param $units
     *
     * @return string
     */
    private function getUnitsQuery($units): string
    {
        $string = '[';

        foreach ($units as $unit) {
            $string .= $unit . ',';
        }

        $string = substr($string, 0, -1);

        $string .= ']';

        return $string;
    }


    /**
     * @param $warehouses
     *
     * @return array
     */
    private function setAvailables($items): array
    {
        $json = json_decode($items);

        return $json->result[0]->stanje;
    }


    /**
     * Calculate discount between two prices
     *
     * @param $regular_price
     * @param $action_price
     *
     * @return float
     */
    public static function calculateDiscount($regular_price, $action_price)
    {
        $value = (($regular_price - $action_price) / $regular_price) * 100;

        return floor($value);
    }


    /**
     * Get order payment type UID.
     *
     * @return mixed|string
     */
    private function getPaymentType()
    {
        if ($this->oc_order['payment_code'] == 'cod') {
            return agconf('luceed.payment.cod');
        }

        if ($this->oc_order['payment_code'] == 'bank_transfer') {
            return agconf('luceed.payment.bank_transfer');
        }

        if ($this->oc_order['payment_code'] == 'wspay') {
            foreach (agconf('luceed.payment.cards') as $card => $uid) {
                if ($card == $this->oc_order['payment_card']) {
                    return $uid;
                }
            }

            return agconf('luceed.payment.card_default');
        }

        return 'false';
    }


    /**
     * @return string
     */
    private function getDeliveryTime(): string
    {
        $time = '01.01.2021 00:00:00';

        if ($this->oc_order['shipping_code'] == 'collector.collector') {
            $collector = ShippingCollector::find($this->oc_order['shipping_collector_id']);

            if ($collector) {
                foreach (agconf('shipping_collector_defaults') as $item) {
                    if ($item['time'] == $collector->collect_time) {
                        $time_str = substr($collector->collect_date, 0, 11) . substr($collector->collect_time, 0, 2) . ':00:00';
                        $time     = Carbon::make($time_str)->format(agconf('luceed.datetime'));
                    }
                }
            }
        }

        return $time;
    }


    /**
     * Get the array data for cart items.
     * Also apply shipping dummy product.
     *
     * @return array
     */
    private function getItems(): array
    {
        // Get the regular products from cart.
        $response = $this->getRegularProducts();
        // Apply shipping dummy product.
        $response[] = $this->getShippingProduct();

        return $response;
    }


    /**
     * @return array
     */
    private function getRegularProducts(): array
    {
        $response       = [];
        $order_products = OrderProduct::where('order_id', $this->oc_order['order_id'])->get();

        $this->log('$order_products', $order_products->toArray());

        if ($order_products->count()) {
            foreach ($order_products as $order_product) {
                $option = OrderOption::where('order_id', $this->oc_order['order_id'])
                                     ->where('order_product_id', $order_product->order_product_id)
                                     ->first();

                if ($option) {
                    $product = ProductOption::where('product_option_value_id', $option->product_option_value_id)->first();

                    $price = $this->getItemPrices($order_product->product_id, $order_product->price);

                    if ( ! $price['rabat']) {
                        $price['rabat'] = $this->applyCouponDiscount();
                    }

                    $response[] = [
                        'artikl_uid' => $product->sku,
                        'kolicina'   => (int) $order_product->quantity,
                        'cijena'     => (float) $price['cijena'],
                        'rabat'      => (int) $price['rabat'],
                    ];
                }
            }
        }

        return $response;
    }


    /**
     * Resolve if an order has coupon discount.
     */
    private function resolveCouponDiscount(): void
    {
        $this->discount = 0;
        $order_total = OrderTotal::where('order_id', $this->oc_order['order_id'])->get();

        $this->log('$order_total', $order_total->toArray());

        foreach ($order_total as $item) {
            if ($item->code == 'coupon') {
                preg_match('#\((.*?)\)#', $item->title, $code);

                $coupon = Coupon::where('code', $code[1])->first();

                if ($coupon) {
                    $this->log('$coupon', $coupon->toArray());

                    $this->discount = $coupon->discount;

                    $this->log('$this->discount', $this->discount);
                }
            }
        }
    }


    /**
     * Apply the coupon discount on a price.
     * If discount is not 0.
     *
     * @return int
     */
    private function applyCouponDiscount()
    {
        if ($this->discount) {
            return abs($this->discount);
        }

        return 0;
    }


    /**
     * Apply shipping dummy product.
     *
     * @return array
     */
    private function getShippingProduct()
    {
        $shipping_amount = agconf('default_shipping_price');

        /*if ($this->oc_order['total'] > agconf('free_shipping_amount')) {
            $shipping_amount = 0;
        }*/

        $order_total = OrderTotal::where('order_id', $this->oc_order['order_id'])->get();

        foreach ($order_total as $item) {
            if ($item->code == 'shipping') {
                $shipping_amount = $item->value;
            }
        }

        return [
            'artikl_uid' => agconf('luceed.shipping_article_uid'),
            'kolicina'   => (int) 1,
            'cijena'     => (float) $shipping_amount,
            'rabat'      => (int) 0,
        ];
    }


    /**
     * @param int   $product_id
     * @param float $price
     *
     * @return array
     */
    private function getItemPrices(int $product_id, float $price): array
    {
        $product = Product::find($product_id);

        if ($price < $product->price) {
            $cijena = number_format($product->price, 2, '.', '');
            $return_rabat = number_format((($price / $product->price) * 100 - 100), 2);

            return [
                'cijena' => $cijena,
                'rabat'  => abs($return_rabat)
            ];
        }

        return [
            'cijena' => number_format($price, 2, '.', ''),
            'rabat'  => 0
        ];
    }


    /**
     * @return bool
     */
    private function checkAddress()
    {
        if ($this->oc_order['payment_address_1'] == $this->oc_order['shipping_address_1']) {
            return false;
        }

        return true;
    }


    /**
     * @param string|null $string
     * @param             $data
     */
    private function log(string $string = null, $data = null): void
    {
        if ($string) {
            Log::store($string, 'procces_order_' . $this->oc_order['order_id']);
        }

        if ($data) {
            Log::store($data, 'procces_order_' . $this->oc_order['order_id']);
        }
    }


}