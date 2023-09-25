<?php

use Agmedia\Helpers\Log;
use Agmedia\Luceed\Facade\LuceedGroup;
use Agmedia\Luceed\Facade\LuceedManufacturer;
use Agmedia\Luceed\Facade\LuceedOrder;
use Agmedia\Luceed\Facade\LuceedPayments;
use Agmedia\Luceed\Facade\LuceedProduct;
use Agmedia\Luceed\Facade\LuceedWarehouse;
use Agmedia\Luceed\Models\LuceedProductForRevision;
use Agmedia\Luceed\Models\LuceedProductForRevisionData;
use Agmedia\Luceed\Models\LuceedProductForUpdate;
use Agmedia\LuceedOpencartWrapper\Models\LOC_Action;
use Agmedia\LuceedOpencartWrapper\Models\LOC_Category;
use Agmedia\LuceedOpencartWrapper\Models\LOC_Customer;
use Agmedia\LuceedOpencartWrapper\Models\LOC_Manufacturer;
use Agmedia\LuceedOpencartWrapper\Models\LOC_Order;
use Agmedia\LuceedOpencartWrapper\Models\LOC_Payment;
use Agmedia\LuceedOpencartWrapper\Models\LOC_Product;
use Agmedia\LuceedOpencartWrapper\Models\LOC_ProductSingle;
use Agmedia\LuceedOpencartWrapper\Models\LOC_Stock;
use Agmedia\LuceedOpencartWrapper\Models\LOC_Warehouse;
use Agmedia\Models\Category\Category;
use Agmedia\Models\Order\Order;
use Agmedia\Models\Product\Product;
use Agmedia\Models\Product\ProductCategory;
use Agmedia\LuceedOpencartWrapper\Helpers\Helper;
use Carbon\Carbon;

class ControllerExtensionModuleLuceedSync extends Controller
{

    private $error = array();


    public function install()
    {
        /*$this->load->model('setting/setting');
        $this->model_setting_setting->editSetting('module_luceed_sync', [
            ['module_luceed_sync_status'] => 1
        ]);*/

        $this->db->query("ALTER TABLE " . DB_PREFIX . "category ADD COLUMN luceed_uid VARCHAR(255) NULL AFTER parent_id;");
        $this->db->query("ALTER TABLE " . DB_PREFIX . "manufacturer ADD COLUMN luceed_uid VARCHAR(255) NULL AFTER manufacturer_id;");
        $this->db->query("ALTER TABLE " . DB_PREFIX . "customer ADD COLUMN luceed_uid VARCHAR(255) NULL AFTER customer_id;");
        $this->db->query("ALTER TABLE " . DB_PREFIX . "order ADD COLUMN luceed_uid VARCHAR(255) NULL AFTER order_id;");
    }


    public function uninstall()
    {
        //$this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "shipping_collector`");

        $this->db->query("ALTER TABLE " . DB_PREFIX . "category DROP COLUMN luceed_uid;");
        $this->db->query("ALTER TABLE " . DB_PREFIX . "manufacturer DROP COLUMN luceed_uid;");
        $this->db->query("ALTER TABLE " . DB_PREFIX . "customer DROP COLUMN luceed_uid;");
        $this->db->query("ALTER TABLE " . DB_PREFIX . "order DROP COLUMN luceed_uid;");
    }


    public function index()
    {
        $this->load->language('extension/module/luceed_sync');

        $this->document->setTitle($this->language->get('heading_title'));

        $data['revision_products'] = LuceedProductForRevision::with('product')->get();
        $data['rev_ids']           = $data['revision_products']->pluck('sku')->take(200)->flatten();
        $last_rev                  = LuceedProductForRevisionData::orderBy('last_revision_date', 'desc')->first();
        
        $data['last_rev'] = 'Nepoznato';
        if ($last_rev) {
            $data['last_rev'] = Carbon::make($last_rev->last_revision_date)->diffForHumans();
        }

        $data['breadcrumbs'] = array();

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_extension'),
            'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('extension/module/shipping_collector', 'user_token=' . $this->session->data['user_token'], true)
        );

        if (isset($this->error['warning'])) {
            $data['error_warning'] = $this->error['warning'];
        } else {
            $data['error_warning'] = '';
        }

        if (isset($this->session->data['success'])) {
            $data['success'] = $this->session->data['success'];

            unset($this->session->data['success']);
        } else {
            $data['success'] = '';
        }

        $data['user_token'] = $this->session->data['user_token'];

        $data['header']      = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer']      = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('extension/module/luceed_sync_dash', $data));
    }


    /**
     * @return object
     */
    public function importCategories()
    {
        $_loc = new LOC_Category(LuceedGroup::all());

        $imported = $_loc->checkDiff()->import();

        return $this->response($imported, 'categories');
    }


    /**
     * @return object
     */
    /*public function updateCategories()
    {
        $_loc = new LOC_Category(LuceedGroup::all());

        $updated = $_loc->joinByUid()->update();

        return $this->response($updated, 'categories');
    }*/


    /**
     * @return mixed
     */
    /*public function importManufacturers()
    {
        $_loc = new LOC_Manufacturer(LuceedManufacturer::all());

        $imported = $_loc->checkDiff()->import();

        return $this->response($imported, 'manufacturers');
    }*/


    /**
     * @return mixed
     */
    /*public function importInitialManufacturers()
    {
        $_loc = new LOC_Manufacturer();

        $imported = $_loc->initialImport();

        return $this->response($imported, 'manufacturers');
    }*/


    /**
     * @return mixed
     */
    /*public function importInitialCustomers()
    {
        $_loc = new LOC_Customer();

        $imported = $_loc->initialImport();

        return $this->response($imported, 'customers');
    }*/


    /**
     * @return mixed
     */
    public function importWarehouses()
    {
        /*$temp = $this->db->query('SELECT LEFT(sku, 6) as sku, price, special FROM temp GROUP BY LEFT(sku, 6);');
        $arr = [];

        foreach ($temp->rows as $row) {
            array_push($arr, $row['sku']);
        }

        $ids = \Agmedia\Models\Product\Product::query()->whereIn('model', $arr)->groupBy('product_id')->pluck('product_id');
        $sezonsko = 429;
        $p_str = '';
        $str = '';

        foreach ($ids as $id) {
            $spol = 0; // Muški
            $new_cat = 0;

            $cats = ProductCategory::query()->where('product_id', $id)->get();

            if ($cats->count()) {
                foreach ($cats as $cat) {
                    if (in_array($cat->category_id, [5, 8])) {
                        $spol = 430; // Muški
                    }
                    if (in_array($cat->category_id, [2, 12])) {
                        $spol = 431; // Ženski
                    }

                    foreach (agconf('cats') as $old_cat_id => $new_cat_id) {
                        if ($cat->category_id == $old_cat_id) {
                            $new_cat = $new_cat_id;
                        }
                    }
                }
            }

            ProductCategory::query()->where('product_id', $id)->delete();

            $str .= '(' . $id . ', ' . $sezonsko . '),';

            if ($spol) {
                $str .= '(' . $id . ', ' . $spol . '),';
            }

            if ($new_cat) {
                $str .= '(' . $id . ', ' . $new_cat . '),';
            }
        }

        foreach ($temp->rows as $row) {
            $product = \Agmedia\Models\Product\Product::query()->where('model', $row['sku'])->first();

            // put special
            $p_str .= '(' . $product->product_id . ', 1, 0, ' . $row['special'] . ', "0000-00-00", "2023-01-27"),';

            $this->db->query("UPDATE " . DB_PREFIX . "product SET price = '" . $row['price'] . "' WHERE product_id = '" . $product->product_id . "'");
        }

        Log::store($str, 'string_cats');
        Log::store($p_str, 'string_prices');

        $query = "INSERT INTO " . DB_PREFIX . "product_to_category (product_id, category_id) VALUES " . substr($str, 0, -1) . ";";
        $query_p = "INSERT INTO " . DB_PREFIX . "product_special (product_id, customer_group_id, priority, price, date_start, date_end) VALUES " . substr($p_str, 0, -1) . ";";

        $this->db->query($query);
        $this->db->query($query_p);

        return $this->response(1, 'warehouses');*/


        $_loc = new LOC_Warehouse(LuceedWarehouse::all());

        $imported = $_loc->import($_loc->getWarehouses());

        return $this->response($imported, 'warehouses');
    }


    /**
     * @return mixed
     */
    public function importPayments()
    {
        $_loc  = new LOC_Product(LuceedProduct::all());

        $images = $_loc->collectImages();

        return $this->response(1, 'payments');

        /*$_loc = new LOC_Payment(LuceedPayments::all());

        $imported = $_loc->import($_loc->getList());

        return $this->response($imported, 'payments');*/
    }


    /**
     * @return object
     */
    public function importProducts()
    {
        $_loc  = new LOC_Product(LuceedProduct::all());
        $count = 0;

        $new_products = $_loc->checkDiff()->getProductsToAdd();

        if ($new_products->count()) {
            $this->load->model('catalog/product');

            foreach ($new_products as $product) {
                $this->model_catalog_product->addProduct(
                    $_loc->make($product)
                );

                $count++;
            }
        }

        return $this->response($count, 'products');
    }


    /**
     * @return mixed
     * @throws Exception
     */
    public function importLuceedProducts()
    {
        /*Product::where('updated', 1)->update([
            'updated'  => 0,
            'hash' => '_'
        ]);*/

        $_loc = new LOC_Product(LuceedProduct::all());

        return $this->output($_loc->populateLuceedData());
    }


    /**
     * @return mixed
     */
    /*public function updateProduct()
    {
        $_loc_ps = new LOC_ProductSingle();
        $this->load->model('catalog/product');

        // Ako smo mu dali uid preko revision liste.
        if (isset($this->request->get['products'])) {
            $_loc_p     = new LOC_Product(LuceedProduct::all());
            $list       = substr($this->request->get['products'], 1, -1);
            $for_update = $_loc_p->sortForUpdate($list)
                                 ->getProductsToAdd();

            $_loc_p->cleanRevisionTable($for_update->pluck('artikl_uid'));

            foreach ($for_update as $product) {
                $_loc_ps->setForUpdate($product);

                if ($_loc_ps->product) {
                    $product = $this->resolveOldProductData($_loc_ps->product_to_update);

                    $this->model_catalog_product->editProduct(
                        $_loc_ps->product_to_update['product_id'],
                        $_loc_ps->makeForUpdate($product)
                    );
                } else {
                    if ($_loc_ps->product_to_insert) {
                        $this->model_catalog_product->addProduct(
                            $_loc_ps->makeForInsert()
                        );
                    }
                }
            }

            return $this->response($for_update->count(), 'products');
        }

        // Ako ima proizvoda za UPDATE
        if ($_loc_ps->hasForUpdate()) {
            if ( ! isset($_loc_ps->product['naziv'])) {
                return $this->output($_loc_ps->finishUpdateError());
            }

            $product = $this->resolveOldProductData($_loc_ps->product_to_update);
            // first check known errors
            $product_for_update = $_loc_ps->makeForUpdate($product);

            if ($product_for_update['sku'] == '6129256300') {
                return $this->output($_loc_ps->finishUpdate());
            }

            $this->model_catalog_product->editProduct(
                $_loc_ps->product_to_update['product_id'],
                $product_for_update
            );

            LuceedProductForUpdate::where('uid', $_loc_ps->product_to_update['luceed_uid'])->delete();

            return $this->output($_loc_ps->finishUpdate());

        } else {
            // Ako ima proizvoda za INSERT
            if ($_loc_ps->hasForInsert()) {
                // first check known errors
                $product_for_insert = $_loc_ps->makeForInsert();

                $this->model_catalog_product->addProduct(
                    $product_for_insert
                );

                return $this->output($_loc_ps->finishInsert());
            }

            if ($_loc_ps->hasForDelete()) {
                $this->model_catalog_product->deleteProduct(
                    $_loc_ps->getDeleteProductId()
                );

                $_loc_ps->deleteFromRevision();

                return $this->output($_loc_ps->finishDelete());
            }
        }

        return $this->output($_loc_ps->finish());
    }*/


    /**
     * @return mixed
     */
    public function checkRevision()
    {
        $loc_p = new LOC_Product();

        return $this->response($loc_p->checkRevisionTable(), 'products');
    }


    /**
     *
     */
    public function finishUpdateProduct()
    {
        \Agmedia\Helpers\Log::store($this->request->post['data'], 'finish');

        if (isset($this->request->post['data'])) {
            $inserted = LuceedProductForRevisionData::insert([
                'last_revision_date' => Carbon::now(),
                'data'               => serialize($this->request->post['data'])
            ]);

            $this->sendRevisionMail();

            $this->db->query("UPDATE `" . DB_PREFIX . "product` SET updated = 0 WHERE 1");

            $this->updateQuantities();

            return $this->output($inserted);
        }
    }


    /**
     * @return mixed
     * @throws Exception
     */
    public function importActions()
    {
        $_loc = new LOC_Action(LuceedProduct::getActions());

        $imported = $_loc->collectActive()
                         ->sortActions()
                         ->import();

        $_loc->updateSpecialsFromTemp();

        return $this->response($imported, 'products_actions');
    }


    /**
     * @return mixed
     */
    public function checkMinQty()
    {
        $activated   = 0;
        $deactivated = 0;
        $products    = Product::all();

        foreach ($products as $product) {
            if ($product->quantity < $product->active_min && $product->status) {
                $product->update(['status' => 0]);
                $deactivated++;
            }

            if ($product->quantity >= $product->active_min && ! $product->status) {
                $product->update(['status' => 1]);
                $activated++;
            }
        }

        return $this->response([$activated, $deactivated], 'active');
    }


    /**
     * @return mixed
     */
    public function checkMinQtyOfCategories()
    {
        $prod_min = $this->checkMinQty();

        $activated   = 0;
        $deactivated = 0;
        $categories  = Category::all();

        foreach ($categories as $category) {
            $active   = false;
            $pids     = ProductCategory::where('category_id', $category->category_id)->pluck('product_id');
            $products = Product::whereIn('product_id', $pids)->get();

            if ($products->count()) {
                foreach ($products as $product) {
                    if ($product->status) {
                        $active = true;
                    }
                }
            }

            if ($active) {
                $category->update(['status' => 1]);
                $activated++;
            } else {
                $category->update(['status' => 0]);
                $deactivated++;
            }
        }

        return $this->response([$activated, $deactivated], 'cat_active');
    }


    /**
     * @return mixed
     * @throws Exception
     */
    public function updatePricesAndQuantities()
    {
        $start = microtime(true);
        
        $_loc = new LOC_Product(LuceedProduct::shortList());
        
        $end = microtime(true);
        $time = number_format(($end - $start), 2, ',', '.');
        Log::store('Download time ::: ' . $time . ' sec.');

        $updated = $_loc->sortForUpdate()->update();

        //Helper::overwritePricesAndSpecialsFromTempTable();

        return $this->response(1, 'update');
    }


    /**
     * @return mixed
     * @throws Exception
     */
    public function updatePrices()
    {
        $_loc = new LOC_Action(LuceedProduct::getActions());

        $updated = $_loc->collectWebPrices()
                        ->update();

        return $this->response($updated, 'update');
    }


    /**
     * @return mixed
     * @throws Exception
     */
    public function updateQuantities()
    {
        $_loc = new LOC_Stock();

        $_loc->setSkladista(
            LuceedProduct::getWarehouseStock(agconf('import.warehouse.default'))
        )->sort();

        $_loc->setDobavljaci(
            LuceedProduct::getSuplierStock()
        )->sort();

        $updated = $_loc->createQuery()->update();

        return $this->response($updated, 'update_stock');
    }


    /**
     * @return mixed
     */
    public function updateOrderStatuses()
    {
        $loc = new LOC_Order();

        $loc->setOrders(
            LuceedOrder::get(
                $loc->collectStatuses(),
                Carbon::now()->subMonth()->format('d.m.Y') //agconf('import.orders.from_date')
            )
        );

        $updated = $loc->sort()->updateStatuses();

        foreach ($loc->collection as $order) {
            $this->sendMail($order);
        }

        return $this->response($updated, 'orders');
    }


    /**
     * @return mixed
     */
    public function checkOrderStatusDuration()
    {
        $loc = new LOC_Order();

        $updated = $loc->checkStatusDuration();

        foreach ($loc->collection as $order) {
            $this->sendMail($order);
        }

        return $this->response($updated, 'orders');
    }


    /**
     * @return bool
     */
    protected function validateRole()
    {
        if ( ! $this->user->hasPermission('modify', 'extension/module/luceed_sync_dash')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        return ! $this->error;
    }


    /**
     * @param array $order
     *
     * @throws Exception
     */
    private function sendMail(array $order = null)
    {
        if ($order && isset($order['order_id']) && isset($order['mail'])) {
            $email             = $this->loadEmails($order['mail']);
            $data              = Order::where('order_id', $order['order_id'])->with('products', 'totals')->first()->toArray();
            $data['mail_text'] = sprintf($email['text'], $order['order_id']);

            for ($i = 0; $i < count($data['products']); $i++) {
                $data['products'][$i]['image'] = HTTPS_CATALOG . 'image/' . Product::where('product_id', $data['products'][$i]['product_id'])->pluck('image')->first();
            }

            $data['mail_logo']          = HTTPS_CATALOG . 'image/catalog/logo.png';
            $data['mail_title']         = sprintf($email['subject'], $order['order_id']);
            $data['mail_data']          = $email['data'];
            $nhs_no                     = $order['order_id'] . date("ym");
            $data['mail_poziv_na_broj'] = $nhs_no . $this->mod11INI($nhs_no);

            $data['mail_order_id']         =  $order['order_id'];

            $mail                = new Mail($this->config->get('config_mail_engine'));
            $mail->parameter     = $this->config->get('config_mail_parameter');
            $mail->smtp_hostname = $this->config->get('config_mail_smtp_hostname');
            $mail->smtp_username = $this->config->get('config_mail_smtp_username');
            $mail->smtp_password = html_entity_decode($this->config->get('config_mail_smtp_password'), ENT_QUOTES, 'UTF-8');
            $mail->smtp_port     = $this->config->get('config_mail_smtp_port');
            $mail->smtp_timeout  = $this->config->get('config_mail_smtp_timeout');
            $mail->setTo($order['email']);
            $mail->setFrom($this->config->get('config_email'));
            $mail->setSender(html_entity_decode($this->config->get('config_name'), ENT_QUOTES, 'UTF-8'));
            $mail->setSubject(sprintf($email['subject'], $order['order_id']));
            $mail->setHtml($this->load->view('mail/mail', $data));
            $mail->send();
        }
    }


    /**
     * @param null $key
     *
     * @return array|\Illuminate\Support\Collection|mixed
     */
    private function loadEmails($key = null)
    {
        $file = json_decode(file_get_contents(DIR_STORAGE . 'upload/assets/emails.json'), true);

        if ($file) {
            if ($key) {
                return collect($file[$key]);
            }

            return collect($file);
        }

        return [];
    }


    /**
     * @throws Exception
     */
    private function sendRevisionMail()
    {
        $products = LuceedProductForRevision::query()->pluck('name', 'sku');

        // $products = LuceedProductForRevision::query()->select('name', 'sku')->toArray();

        $data = [];

        $data['products'] = $products;
        \Agmedia\Helpers\Log::store($data);

        $mail                = new Mail($this->config->get('config_mail_engine'));
        $mail->parameter     = $this->config->get('config_mail_parameter');
        $mail->smtp_hostname = $this->config->get('config_mail_smtp_hostname');
        $mail->smtp_username = $this->config->get('config_mail_smtp_username');
        $mail->smtp_password = html_entity_decode($this->config->get('config_mail_smtp_password'), ENT_QUOTES, 'UTF-8');
        $mail->smtp_port     = $this->config->get('config_mail_smtp_port');
        $mail->smtp_timeout  = $this->config->get('config_mail_smtp_timeout');
        $mail->setTo('pmovi@chipoteka.hr');
        $mail->setFrom($this->config->get('config_email'));
        $mail->setSender(html_entity_decode($this->config->get('config_name'), ENT_QUOTES, 'UTF-8'));
        $mail->setSubject('Proizvodi za reviziju... ' . Carbon::now()->format('d.m.Y'));
        $mail->setHtml($this->load->view('mail/updatemail', $data));
        $mail->send();
    }


    /**
     * @param $product
     *
     * @return array
     */
    private function resolveOldProductData($product): array
    {
        $this->load->model('catalog/product');

        $data                     = [];
        $data['product_discount'] = $this->model_catalog_product->getProductDiscounts($product['product_id']);
        $data['product_special']  = $this->model_catalog_product->getProductSpecials($product['product_id']);
        $data['product_download'] = $this->model_catalog_product->getProductDownloads($product['product_id']);
        $data['product_filter']   = $this->model_catalog_product->getProductFilters($product['product_id']);
        $data['product_related']  = $this->model_catalog_product->getProductRelated($product['product_id']);
        $data['product_reward']   = $this->model_catalog_product->getProductRewards($product['product_id']);

        return $data;
    }


    /**
     * @param int|string $condition
     * @param string     $text
     *
     * @return mixed
     */
    private function response($condition, string $text)
    {
        $this->load->language('extension/module/luceed_sync');

        if ($condition) {
            if (is_string($condition) || is_integer($condition)) {
                return $this->output(['status' => 200, 'message' => sprintf($this->language->get('text_success_' . $text), $condition)]);
            }

            return $this->output(['status' => 200, 'message' => sprintf($this->language->get('text_success_' . $text), $condition[0], $condition[1])]);
        }

        return $this->output(['status' => 300, 'message' => $this->language->get('text_warning_' . $text)]);
    }


    /**
     * @param $data
     *
     * @return mixed
     */
    private function output($data)
    {
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(collect($data)->toJson());
    }


    /**
     * @param string $nb
     *
     * @return float|int|mixed
     */
    public function mod11INI(string $nb)
    {
        $i = 0;
        $v = 0;
        $p = 2;
        $c = ' ';

        for ($i = strlen($nb); $i >= 1; $i--) {
            $c = substr($nb, $i - 1, 1);

            if ('0' <= $c && $c <= '9' && $v >= 0) {
                $v = $v + $p * $c;
                $p = $p + 1;
            } else {
                $v = -1;
            }
        }

        if ($v >= 0) {
            $v = 11 - ($v % 11);

            if ($v > 9) {
                $v = 0;
            }
        }

        return $v;
    }

}