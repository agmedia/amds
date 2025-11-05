<?php

namespace Agmedia\LuceedOpencartWrapper\Models;

use Agmedia\Helpers\Database;
use Agmedia\Helpers\Log;
use Agmedia\Luceed\Facade\LuceedProduct;
use Agmedia\Luceed\Luceed;
use Agmedia\LuceedOpencartWrapper\Helpers\ProductHelper;
use Agmedia\Models\Coupon;
use Agmedia\Models\Location;
use Agmedia\Models\Order\Order;
use Agmedia\Models\Order\OrderOption;
use Agmedia\Models\Order\OrderProduct;
use Agmedia\Models\Order\OrderStatus;
use Agmedia\Models\Order\OrderTotal;
use Agmedia\Models\Product\Product;
use Agmedia\Models\Product\ProductCategory;
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
     * @var
     */
    private $orders;

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
     * @var array
     */
    private $coupon_item = [];

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
        $this->service  = new Luceed();

        $this->resolveCouponDiscount();
    }


    /**
     * @param $orders
     */
    public function setOrders($orders)
    {
        $this->orders = collect($this->setLuceedOrders($orders));
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
            $created = Order::where('order_id', $this->oc_order['order_id'])->update([
                'luceed_uid' => $this->response->result[0]
            ]);

            $existing_order = Order::where('order_id', $this->oc_order['order_id'])->first();

            if ( ! $this->call_raspis && ! $existing_order->luceed_raspis_uid) {
                $raspis = json_decode(
                    $this->service->orderWrit($this->response->result[0])
                );

                if (isset($raspis->result[0])) {
                    $this->call_raspis = false;

                    Order::where('order_id', $this->oc_order['order_id'])->update([
                        'luceed_raspis_uid' => $raspis->result[0]
                    ]);

                    $this->log('Raspis response: $raspis - LOC_Order', $raspis);

                } else {
                    $this->log('GREŠKA ::::::::: Raspis response: $raspis - LOC_Order', $raspis);
                }
            }

            return $created;
        }

        return false;
    }


    /**
     * Create luceed order data.
     */
    public function create(): void
    {
        $iznos = $this->getTotal();

        $this->order = [
            'nalog_prodaje_b2b' => 'AG-' . $this->oc_order['order_id'],
            'narudzba'          => $this->oc_order['order_id'],
            'datum'             => Carbon::make($this->oc_order['date_added'])->format(agconf('luceed.date')),
            'skladiste_uid'     => '565-2987',
            'status'            => $this->getStatus(),
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
            $this->order['vrsta_isporuke_uid']    = '4-2987';
            $this->order['korisnik__partner_uid'] = $this->customer_uid;
            $this->call_raspis                    = false;
        }

        if ( ! $this->has_all_in_main_warehouse && $this->has_all_in_warehouses/* && isset($this->has_all_in_warehouses[0])*/) {
            $this->order['sa__skladiste_uid']     = $this->has_all_in_warehouses;
            $this->order['na__skladiste_uid']     = '565-2987';
            $this->order['skl_dokument']          = 'MS';
            $this->order['vrsta_isporuke_uid']    = '4-2987';
            $this->order['korisnik__partner_uid'] = $this->customer_uid;
        }

        if ( ! $this->has_all_in_main_warehouse && ! $this->has_all_in_warehouses) {
            $this->order['korisnik__partner_uid'] = $this->customer_uid;
            $this->call_raspis                    = false;
        }

        if ($this->oc_order['shipping_method'] == 'BOX NOW' && $this->oc_order['boxnow'] != '') {
            $data = explode(';', $this->oc_order['boxnow']);

            $this->order['dropoff_sifra']      = $data[1] ?: '';
            $this->order['dropoff_naziv']      = $data[0] ?: '';
            $this->order['vrsta_isporuke_uid'] = '7-2987';
            $this->order['skl_dokument']       = '';

            if ($this->has_all_in_warehouses) {
                $this->order['skl_dokument'] = 'MS';
            }

            if ( ! $this->has_all_in_main_warehouse && ! $this->has_all_in_warehouses) {
                unset($this->order['vrsta_isporuke_uid']);
            }
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
     * @param array|null $statuses
     *
     * @return string
     */
    public function collectStatuses(array $statuses = null): string
    {
        $string = '[';

        if ( ! $statuses) {
            $statuses = OrderStatus::whereNotNull('luceed_status_id')->get();
        }

        foreach ($statuses as $status) {
            $string .= $status->luceed_status_id . ',';
        }

        return substr($string, 0, -1) . ']';
    }


    /**
     * @return $this
     */
    public function sort()
    {
        $statuses = OrderStatus::where('luceed_status_id', '!=', '')->get();
        $orders   = Order::select('order_id', 'luceed_uid', 'email', 'payment_code', 'order_status_id', 'order_status_changed')
            ->where('order_status_id', '!=', 0)
            ->where('date_added', '>', Carbon::now()->subMonth())
            ->get();

        // Check if status have changed.
        foreach ($orders as $order) {
            $l_order = $this->orders->where('nalog_prodaje_uid', $order->luceed_uid)->first();

            if ($l_order) {
                $old_status = $statuses->where('order_status_id', $order->order_status_id)->first();
                $new_status = $statuses->where('luceed_status_id', $l_order->status)->first();

                if ($l_order->status != $old_status->luceed_status_id) {
                    $this->collection[] = [
                        'order_id'     => $order->order_id,
                        'status_from'  => $old_status->luceed_status_id,
                        'status_to'    => $l_order->status,
                        'oc_status_to' => $new_status->order_status_id,
                        'payment'      => $order->payment_code,
                        'email'        => $order->email
                    ];
                }
            }
        }

        if ( ! empty($this->collection)) {
            // Get the apropriate mail.
            for ($i = 0; $i < count($this->collection); $i++) {
                foreach (agconf('mail.' . $this->collection[$i]['payment']) as $key => $item) {
                    if ($key) {
                        if ($this->collection[$i]['status_from'] == $item['from'] && $this->collection[$i]['status_to'] == $item['to']) {
                            $this->collection[$i]['mail'] = $key;
                        }
                    }
                }
            }

            // Collect update status query.
            foreach ($this->collection as $item) {
                $this->query_update_status  .= '(' . $item['order_id'] . ', ' . $item['oc_status_to'] . ', NULL, NULL),';
                $this->query_update_history = '(' . $item['order_id'] . ', ' . $item['oc_status_to'] . ', 1, "", "' . Carbon::now() . '"),';
            }
        }

        return $this;
    }


    /**
     * @return int
     * @throws \Exception
     */
    public function updateStatuses(): int
    {
        if ($this->query_update_status != '') {
            $this->db = new Database(DB_DATABASE);

            $this->db->query("INSERT INTO " . DB_PREFIX . "order_temp (id, status, data_1, data_2) VALUES " . substr($this->query_update_status, 0, -1) . ";");
            $this->db->query("UPDATE " . DB_PREFIX . "order o INNER JOIN " . DB_PREFIX . "order_temp ot ON o.order_id = ot.id SET o.order_status_id = ot.status, o.order_status_changed = NOW();");
            $this->db->query("INSERT INTO " . DB_PREFIX . "order_history (order_id, order_status_id, notify, comment, date_added) VALUES " . substr($this->query_update_history, 0, -1) . ";");

            $this->deleteOrderTempDB();
        }

        return count($this->collection);
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
            $locations = Location::query()->where('stanje_web_shop', 1)->orderBy('prioritet')->get();
            $units     = $locations->pluck('skladiste')->flatten();

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
                                $all_available = 0;

                                if ($available->raspolozivo_kol >= $order_product->quantity) {
                                    $all_available = 1;
                                }

                                $availables[$available->skladiste_uid][] = [
                                    'uid' => $product->sifra,
                                    'qty' => $available->raspolozivo_kol,
                                    'all' => $all_available
                                ];
                            }
                        }
                    }
                }
            }

            $this->log('Available products: ', $availables);

            // Check if all in MAIN warehouse.
            if (isset($availables[agconf('luceed.default_warehouse_uid')])) {
                if ($order_products->count() == count($availables[agconf('luceed.default_warehouse_uid')])) {
                    $this->has_all_in_main_warehouse = true;
                }

                /*if ($order_products->count() <= $availables[agconf('luceed.default_warehouse_luid')][0]['qty']) {
                    $this->has_all_in_main_warehouse = true;
                }*/

                unset($availables[agconf('luceed.default_warehouse_uid')]);
            }

            if ( ! $this->has_all_in_main_warehouse) {
                // Check if is boxnow & remove stores
                if ($this->oc_order['shipping_method'] == 'BOX NOW' && $this->oc_order['boxnow'] != '') {
                    $locations = $locations->whereNotIn('location_id', [3, 10, 43, 58]);
                }
                // Check & collect warehouses that have all items.
                foreach ($locations->where('stanje_web_shop', 1) as $store) {
                    // if availables have a set store id.
                    if (isset($availables[$store['skladiste_uid']])) {
                        // if ordered products are accounted for.
                        if ($order_products->count() == count($availables[$store['skladiste_uid']])) {
                            $is_all_available_in_one_store = true;
                            // and if quantity is ok.
                            foreach ($availables[$store['skladiste_uid']] as $available_store) {
                                if ( ! $available_store['all']) {
                                    $is_all_available_in_one_store = false;
                                }
                            }

                            if ($is_all_available_in_one_store) {
                                $this->has_all_in_warehouses = $store['skladiste_uid'];
                            }
                        }

                        /*if ($order_products->count() <= $availables[$store['skladiste_uid']][0]['qty']) {
                            $this->has_all_in_warehouses[] = $store['skladiste_uid'];
                        }*/
                    }
                }
            }

            $this->log('has_all_in_main_warehouse: ', ($this->has_all_in_main_warehouse ? 'yes' : 'no'));
            $this->log('has_all_in_warehouses: ', ($this->has_all_in_warehouses ? 'yes' : 'no'));

            if ($this->has_all_in_warehouses && $this->has_all_in_warehouses) {
                return true;
            }
        }

        return true;
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


    private function getTotal()
    {
        /*if ($this->discount) {
            return number_format(
                ProductHelper::calculateDiscountPrice($this->oc_order['total'], $this->discount), 2, '.', ''
            );
        }*/

        return number_format($this->oc_order['total'], 2, '.', '');
    }


    /**
     * @return string
     */
    private function getStatus(): string
    {
        if ($this->oc_order['payment_code'] == 'bank_transfer') {
            return '02';
        }

        return agconf('luceed.status_uid');
    }


    /**
     * Get order payment type UID.
     *
     * @return mixed|string
     */
    private function getPaymentType()
    {
        if ($this->oc_order['payment_code'] == 'cod') {
            if ($this->oc_order['shipping_method'] == 'BOX NOW' && $this->oc_order['boxnow'] != '') {
                return agconf('luceed.payment.cod_boxnow');
            }

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

        if ($order_products->isEmpty()) {
            if ($this->discount) {
                $response[] = $this->coupon_item;
            }
            return $response;
        }

        // --- Priprema podataka za bulk lookupe ---
        $productIds       = $order_products->pluck('product_id')->filter()->unique()->values();
        $orderProductIds  = $order_products->pluck('order_product_id')->unique()->values();

        // Set product_id-a koji su u category_id = 1
        $inCategory = ProductCategory::query()
            ->whereIn('product_id', $productIds)
            ->where('category_id', 1)
            ->pluck('product_id')
            ->unique()
            ->mapWithKeys(fn ($pid) => [$pid => true]); // za brzi isset()

        // Sve opcije za ove order_product_id-ove (po jedan red najčešće)
        $orderOptions = OrderOption::query()
            ->where('order_id', $this->oc_order['order_id'])
            ->whereIn('order_product_id', $orderProductIds)
            ->get()
            ->keyBy('order_product_id');

        // Map product_option_value_id => sku
        $povIds = $orderOptions->pluck('product_option_value_id')->filter()->unique()->values();
        $productOptionsByPov = $povIds->isNotEmpty()
            ? ProductOption::query()
                ->whereIn('product_option_value_id', $povIds)
                ->get()
                ->keyBy('product_option_value_id')
            : collect();

        // Map product_id => Product (radi modela)
        $productsById = Product::query()
            ->whereIn('product_id', $productIds)
            ->get()
            ->keyBy('product_id');

        // --- Gradnja response-a ---
        foreach ($order_products as $order_product) {
            // Rabat po artiklu (reset u svakoj iteraciji)
            $rabat = isset($inCategory[$order_product->product_id]) ? 30 : 0;

            // Ako ćeš nekad vraćati strogo float, bolje round nego number_format+float cast
            $price = (float) number_format((float) $order_product->price, 2, '.', '');

            // Ima li ovaj order_product opciju?
            $option = $orderOptions->get($order_product->order_product_id);

            if ($option) {
                $po = $productOptionsByPov->get($option->product_option_value_id);
                if ($po && !empty($po->sku)) {
                    $response[] = [
                        'artikl_uid' => $po->sku,
                        'kolicina'   => (int) $order_product->quantity,
                        'cijena'     => $price,
                        'rabat'      => (int) $rabat,
                    ];
                    continue;
                }
                // Fallback ako nema SKU-a za opciju – padni na "običan" artikl
            }

            $product = $productsById->get($order_product->product_id);
            if ($product) {
                $response[] = [
                    'artikl'   => $order_product->model, // ili $product->model ako ti treba iz Producta
                    'kolicina' => (int) $order_product->quantity,
                    'cijena'   => $price,
                    'rabat'    => (int) $rabat,
                ];
            }
        }

        if ($this->discount) {
            $response[] = $this->coupon_item;
        }

        return $response;
    }



    /**
     * Resolve if an order has coupon discount.
     */
    private function resolveCouponDiscount(): void
    {
        $this->discount = 0;
        $order_total    = OrderTotal::where('order_id', $this->oc_order['order_id'])->get();

        $this->log('$order_total', $order_total->toArray());

        foreach ($order_total as $item) {
            if ($item->code == 'coupon') {
                preg_match('#\((.*?)\)#', $item->title, $code);

                $coupon = Coupon::where('code', $code[1])->first();

                if ($coupon) {
                    $this->log('$coupon', $coupon->toArray());

                    $this->discount = $coupon->discount;

                    $this->coupon_item = [
                        'artikl_uid' => '668150-2987',
                        'kolicina'   => 1,
                        'cijena'     => (float) number_format($item->value, 2, '.', ''),
                        'rabat'      => 0,
                    ];

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
            $cijena       = number_format($product->price, 2, '.', '');
            $return_rabat = number_format((($price / $product->price) * 100 - 100), 2);

            return [
                'cijena' => $cijena,
                'rabat'  => abs($return_rabat)
            ];
        }

        return [
            'cijena' => number_format($price, 2, '.', ''),
            'rabat'  => 0//$this->applyCouponDiscount()
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
     * Return the corrected response from luceed service.
     * Without unnecessary tags.
     *
     * @param $products
     *
     * @return array
     */
    private function setLuceedOrders($orders): array
    {
        $json = json_decode($orders);

        return $json->result[0]->nalozi_prodaje;
    }


    /**
     * @throws \Exception
     */
    private function deleteOrderTempDB(): void
    {
        $this->db->query("TRUNCATE TABLE `" . DB_PREFIX . "order_temp`");
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