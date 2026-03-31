<?php

use Agmedia\Helpers\Log;
use Agmedia\Luceed\Facade\LuceedGroup;
use Agmedia\Luceed\Facade\LuceedManufacturer;
use Agmedia\Luceed\Facade\LuceedOrder;
use Agmedia\Luceed\Facade\LuceedPayments;
use Agmedia\Luceed\Facade\LuceedProduct;
use Agmedia\Luceed\Connection\LuceedService;
use Agmedia\Luceed\Facade\LuceedWarehouse;
use Agmedia\Luceed\Models\LuceedProductForRevision;
use Agmedia\Luceed\Models\LuceedProductForRevisionData;
use Agmedia\Luceed\Models\LuceedProductForUpdate;
use Agmedia\LuceedOpencartWrapper\Helpers\ProductHelper;
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
use Agmedia\Models\Product\ProductDescription;
use Carbon\Carbon;

class ControllerExtensionModuleLuceedSync extends Controller
{
    private const CSV_SYNC_SESSION_KEY = 'luceed_csv_sync_state';
    private const CSV_SYNC_BATCH_SIZE = 5;
    private const WEB_COUPON_EXPORT_BATCH_SIZE = 1000;

    private $error = array();
    private $product_columns = null;


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
        $data = $this->load->language('extension/module/luceed_sync');

        $this->document->setTitle($this->language->get('heading_title'));

        if ($this->request->server['REQUEST_METHOD'] === 'POST' && isset($this->request->files['csv_file'])) {
            $this->processCsvSyncUpload();
        }

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
            'href' => $this->url->link('extension/module/luceed_sync', 'user_token=' . $this->session->data['user_token'], true)
        );

        if (isset($this->error['warning'])) {
            $data['error_warning'] = $this->error['warning'];
        } elseif (isset($this->session->data['error_warning'])) {
            $data['error_warning'] = $this->session->data['error_warning'];

            unset($this->session->data['error_warning']);
        } else {
            $data['error_warning'] = '';
        }

        if (isset($this->session->data['success'])) {
            $data['success'] = $this->session->data['success'];

            unset($this->session->data['success']);
        } else {
            $data['success'] = '';
        }

        $data['generate_excel_link'] = $this->url->link('extension/module/luceed_sync/generateExcel', 'user_token=' . $this->session->data['user_token'], true);
        $data['web_coupon_export_link'] = $this->url->link('extension/module/luceed_sync/exportWebCoupons', 'user_token=' . $this->session->data['user_token'], true);
        $data['sync_csv_action'] = $this->url->link('extension/module/luceed_sync', 'user_token=' . $this->session->data['user_token'], true);
        $data['sync_csv_start_action'] = $this->url->link('extension/module/luceed_sync/startCsvSync', 'user_token=' . $this->session->data['user_token'], true);
        $data['sync_csv_batch_action'] = $this->url->link('extension/module/luceed_sync/processCsvSyncBatch', 'user_token=' . $this->session->data['user_token'], true);
        $default_web_coupon_range = $this->getDefaultWebCouponExportRange();
        $data['web_coupon_date_start'] = $default_web_coupon_range['start'];
        $data['web_coupon_date_end'] = $default_web_coupon_range['end'];

        $data['user_token'] = $this->session->data['user_token'];

        $data['header']      = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer']      = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('extension/module/luceed_sync_dash', $data));
    }


    /**
     * Upload CSV with product models and sync only those products from Luceed.
     *
     * @return void
     */
    public function syncSelectedProducts()
    {
        $this->load->language('extension/module/luceed_sync');
        $this->processCsvSyncUpload();

        return $this->redirectToModule();
    }


    /**
     * Start CSV sync in batches and return initial state as JSON.
     *
     * @return void
     */
    public function startCsvSync()
    {
        $this->load->language('extension/module/luceed_sync');
        $this->prepareLongRunningRequest();

        $this->session->data['success'] = '';
        unset($this->session->data['error_warning']);

        if (!$this->validateModifyPermission()) {
            $this->output(['status' => 300, 'message' => $this->error['warning']]);

            return;
        }

        if ($this->request->server['REQUEST_METHOD'] !== 'POST') {
            $this->output(['status' => 300, 'message' => $this->language->get('error_csv_file')]);

            return;
        }

        if (empty($this->request->files['csv_file']['tmp_name']) || !is_file($this->request->files['csv_file']['tmp_name'])) {
            $this->output(['status' => 300, 'message' => $this->language->get('error_csv_file')]);

            return;
        }

        try {
            $items = $this->extractModelsFromCsv($this->request->files['csv_file']['tmp_name']);

            if (!$items) {
                throw new \RuntimeException($this->language->get('error_csv_empty'));
            }

            $this->clearCsvSyncState();

            $file = $this->createCsvSyncItemsFile($items);

            $this->session->data[self::CSV_SYNC_SESSION_KEY] = [
                'file' => $file,
                'offset' => 0,
                'total' => count($items),
                'chunk_size' => self::CSV_SYNC_BATCH_SIZE,
                'result' => $this->createEmptyCsvSyncResult(count($items)),
            ];

            $this->output([
                'status' => 200,
                'message' => sprintf($this->language->get('text_products_csv_sync_started'), self::CSV_SYNC_BATCH_SIZE, count($items)),
                'processed' => 0,
                'total' => count($items),
                'done' => false,
            ]);
        } catch (\Throwable $exception) {
            $this->clearCsvSyncState();

            Log::store(
                [
                    'message' => $exception->getMessage(),
                    'trace' => $exception->getTraceAsString()
                ],
                'luceed_csv_sync_error'
            );

            $this->output([
                'status' => 300,
                'message' => sprintf($this->language->get('error_csv_sync'), $exception->getMessage())
            ]);
        }
    }


    /**
     * Process the next CSV sync batch and return progress as JSON.
     *
     * @return void
     */
    public function processCsvSyncBatch()
    {
        $this->load->language('extension/module/luceed_sync');
        $this->prepareLongRunningRequest();

        if (!$this->validateModifyPermission()) {
            $this->output(['status' => 300, 'message' => $this->error['warning']]);

            return;
        }

        try {
            $state = $this->getCsvSyncState();
            $items = $this->loadCsvSyncItems($state['file']);
            $chunk = array_slice($items, (int)$state['offset'], (int)$state['chunk_size']);

            if (!$chunk) {
                $this->finalizeCsvSyncState($state['result']);

                $this->output([
                    'status' => 200,
                    'message' => $this->buildCsvSyncMessage($state['result'], !($state['result']['updated'] || $state['result']['imported'])),
                    'processed' => (int)$state['offset'],
                    'total' => (int)$state['total'],
                    'done' => true,
                ]);

                return;
            }

            $chunk_result = $this->syncProductsByModel($chunk);
            $state['result'] = $this->mergeCsvSyncResults($state['result'], $chunk_result);
            $state['offset'] += count($chunk);

            $done = (int)$state['offset'] >= (int)$state['total'];

            if ($done) {
                $this->finalizeCsvSyncState($state['result']);
            } else {
                $this->session->data[self::CSV_SYNC_SESSION_KEY] = $state;
            }

            $this->output([
                'status' => 200,
                'message' => $done
                    ? $this->buildCsvSyncMessage($state['result'], !($state['result']['updated'] || $state['result']['imported']))
                    : $this->buildCsvSyncProgressMessage($state['offset'], $state['total'], $state['result']),
                'processed' => (int)$state['offset'],
                'total' => (int)$state['total'],
                'done' => $done,
            ]);
        } catch (\Throwable $exception) {
            $this->clearCsvSyncState();
            $this->session->data['error_warning'] = sprintf(
                $this->language->get('error_csv_sync'),
                $exception->getMessage()
            );

            Log::store(
                [
                    'message' => $exception->getMessage(),
                    'trace' => $exception->getTraceAsString()
                ],
                'luceed_csv_sync_error'
            );

            $this->output([
                'status' => 300,
                'message' => sprintf($this->language->get('error_csv_sync'), $exception->getMessage())
            ]);
        }
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

    public function reimportCategories()
    {
        $all_products = Product::query()->pluck('model', 'product_id');

        foreach ($all_products->toArray() as $id => $sifra) {
            if ($sifra) {
                $feed    = LuceedProduct::getById((string) $sifra);
                $product = new LOC_Product($feed);

                Log::store($id . '___' . $sifra, 'sifra');

                if ($product->getProducts()->count()) {
                    $product_array = collect($product->getProducts()->first())->toArray();
                    $cats_arr      = ProductHelper::getCategoriesFromAttributes($product_array);

                    if ($cats_arr && ! empty($cats_arr)) {
                        $this->db->query("DELETE FROM " . DB_PREFIX . "product_to_category WHERE product_id = '" . (int) $id . "'");

                        foreach ($cats_arr as $cat_id) {
                            $pc = ProductCategory::query()->where('category_id', $cat_id)->where('product_id', $id)->first();

                            if ( ! $pc) {
                                $this->db->query("INSERT INTO " . DB_PREFIX . "product_to_category SET product_id = '" . (int) $id . "', category_id = '" . (int) $cat_id . "'");
                            }
                        }
                    }
                }
            }
        }

        $this->disableEmptyCategories();
        $this->setNovoProducts();

        return $this->response(Product::query()->count(), 'categories');
    }


    /**
     * @return int
     */
    public function disableEmptyCategories()
    {
        $all_cats = Category::query()->pluck('category_id');
        $cats = ProductCategory::query()->get()->unique('category_id')->pluck('category_id');

        $diff = $all_cats->diff($cats);

        if ($diff->count() > 0) {
            foreach ($diff as $id) {
                Category::query()->where('category_id', $id)->update(['status' => 0]);
            }
        }

        return $diff->count();
    }


    /**
     * @return void
     */
    public function setNovoProducts()
    {
        $cat_id = agconf('import.novo_category') ?: 111;
        //$prods = Product::query()->orderBy('product_id', 'desc')->take(200)->pluck('product_id');
        $prods = Product::query()->where('date_modified', '>', Carbon::now()->subMonth(2))->pluck('product_id');

        $this->db->query("DELETE FROM " . DB_PREFIX . "product_to_category WHERE category_id = '" . (int) $cat_id . "'");

        foreach ($prods as $id) {
            $this->db->query("INSERT INTO " . DB_PREFIX . "product_to_category SET product_id = '" . (int) $id . "', category_id = '" . (int) $cat_id . "'");
        }
    }


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
        $temp = $this->db->query('SELECT LEFT(sku, 6) as sku, special FROM temp GROUP BY LEFT(sku, 6);');
        $arr = [];

        foreach ($temp->rows as $row) {
            array_push($arr, $row['sku']);
        }

        $ids = \Agmedia\Models\Product\Product::query()->whereIn('model', $arr)->groupBy('product_id')->pluck('product_id');
        $sezonsko = 401;
        $p_str = '';
        $str = '';

        foreach ($ids as $id) {
            $spol = 0; // Muški
            $new_cat = 0;

            $cats = ProductCategory::query()->where('product_id', $id)->get();

            if ($cats->count()) {
                foreach ($cats as $cat) {
                    if (in_array($cat->category_id, [5, 8])) {
                        $spol = 571; // Muški
                    }
                    if (in_array($cat->category_id, [2, 12])) {
                        $spol = 572; // Ženski
                    }

                 /*   foreach (agconf('cats') as $old_cat_id => $new_cat_id) {
                        if ($cat->category_id == $old_cat_id) {
                            $new_cat = $new_cat_id;
                        }
                    }*/
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
            $p_str .= '(' . $product->product_id . ', 1, 0, ' . $row['special'] . ', "0000-00-00", "2024-01-27"),';

          //  $this->db->query("UPDATE " . DB_PREFIX . "product SET price = '" . $row['price'] . "' WHERE product_id = '" . $product->product_id . "'");
        }

        Log::store($str, 'string_cats');
        Log::store($p_str, 'string_prices');

        $query = "INSERT INTO " . DB_PREFIX . "product_to_category (product_id, category_id) VALUES " . substr($str, 0, -1) . ";";
        $query_p = "INSERT INTO " . DB_PREFIX . "product_special (product_id, customer_group_id, priority, price, date_start, date_end) VALUES " . substr($p_str, 0, -1) . ";";

        $this->db->query($query);
        $this->db->query($query_p);

        return $this->response(1, 'warehouses');


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

            foreach ($new_products->all() as $product) {
                $this->model_catalog_product->addProduct(
                    $_loc->make($product)
                );

                $count++;
            }
        }

        $this->setNovoProducts();

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
        Log::store('Download time ::: ' . $time . ' sec.', 'testing_update_time');

        $updated = $_loc->sortForUpdate()->update();

        //Helper::overwritePricesAndSpecialsFromTempTable();

        return $this->response($updated, 'update');
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
     * @return mixed
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     */
    public function generateExcel()
    {
        $this->load->model('catalog/product');
        $this->load->model('catalog/category');

        $products = [];
        $ids = Product::query()->where('quantity', '>', 0)->pluck('product_id');

        foreach ($ids as $product_id) {
            $categories = $this->model_catalog_product->getProductCategories($product_id);

            $data = [];

            foreach ($categories as $category_id) {
                $category_info = $this->model_catalog_category->getCategory($category_id);

                if ($category_info) {
                    $data[] = array(
                        'category_id' => $category_info['category_id'],
                        'name'        => ($category_info['path']) ? htmlspecialchars_decode(str_replace('&nbsp;', ' ', $category_info['path'] . ' &gt; ' . $category_info['name'])) : $category_info['name']
                    );
                }
            }

            $products[] = [
                'id' => $product_id,
                'title' => ProductDescription::query()->where('product_id', $product_id)->first()->name,
                'categories' => $data
            ];
        }

        $excel = new \Agmedia\LuceedOpencartWrapper\Helpers\Excel('simple', $products);

        return $excel->make()->response('stream');
    }


    /**
     * Export orders for the selected date range into an Excel-compatible CSV file.
     *
     * @return void
     */
    public function exportWebCoupons()
    {
        $this->load->language('extension/module/luceed_sync');
        $this->prepareLongRunningRequest();

        $file = null;

        try {
            [$start_date, $end_date, $start_datetime, $end_datetime] = $this->resolveWebCouponExportRange();
            $file = $this->buildWebCouponExportFile($start_datetime, $end_datetime);
            $filename = sprintf('web_kuponi_%s_%s.csv', $start_date, $end_date);

            while (ob_get_level() > 0) {
                @ob_end_clean();
            }

            header('Content-Type: text/csv; charset=UTF-8');
            header('Content-Disposition: attachment;filename="' . basename($filename) . '"');
            header('Cache-Control: max-age=0');
            header('Cache-Control: max-age=1');
            header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
            header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
            header('Cache-Control: cache, must-revalidate');
            header('Pragma: public');

            readfile($file);
            @unlink($file);
        } catch (\Throwable $exception) {
            if ($file && is_file($file)) {
                @unlink($file);
            }

            Log::store(
                [
                    'message' => $exception->getMessage(),
                    'trace' => $exception->getTraceAsString()
                ],
                'luceed_web_coupon_export_error'
            );

            $this->session->data['error_warning'] = sprintf(
                $this->language->get('error_web_coupon_export'),
                $exception->getMessage()
            );

            $this->redirectToModule();
        }
    }


    /**
     * @return array
     */
    private function getDefaultWebCouponExportRange(): array
    {
        $reference = Carbon::now()->subMonthNoOverflow();

        return [
            'start' => $reference->copy()->startOfMonth()->format('Y-m-d'),
            'end' => $reference->copy()->endOfMonth()->format('Y-m-d'),
        ];
    }


    /**
     * @return array
     */
    private function resolveWebCouponExportRange(): array
    {
        $defaults = $this->getDefaultWebCouponExportRange();
        $start_input = isset($this->request->get['web_coupon_date_start']) ? (string)$this->request->get['web_coupon_date_start'] : $defaults['start'];
        $end_input = isset($this->request->get['web_coupon_date_end']) ? (string)$this->request->get['web_coupon_date_end'] : $defaults['end'];

        $start = $this->parseWebCouponExportDate($start_input)->startOfDay();
        $end = $this->parseWebCouponExportDate($end_input)->endOfDay();

        if ($start->gt($end)) {
            throw new \RuntimeException($this->language->get('error_web_coupon_export_dates'));
        }

        return [
            $start->format('Y-m-d'),
            $end->format('Y-m-d'),
            $start->format('Y-m-d H:i:s'),
            $end->format('Y-m-d H:i:s'),
        ];
    }


    /**
     * @param string $value
     *
     * @return Carbon
     */
    private function parseWebCouponExportDate(string $value): Carbon
    {
        try {
            $date = Carbon::createFromFormat('!Y-m-d', trim($value));
        } catch (\Throwable $exception) {
            $date = false;
        }

        if (!$date || $date->format('Y-m-d') !== trim($value)) {
            throw new \RuntimeException($this->language->get('error_web_coupon_export_dates'));
        }

        return $date;
    }


    /**
     * @param string $start_datetime
     * @param string $end_datetime
     *
     * @return string
     */
    private function buildWebCouponExportFile(string $start_datetime, string $end_datetime): string
    {
        $directory = rtrim(DIR_STORAGE, '/\\') . '/cache/';

        if (!is_dir($directory) && !mkdir($directory, 0777, true) && !is_dir($directory)) {
            throw new \RuntimeException($this->language->get('error_web_coupon_export_file'));
        }

        $file = $directory . 'web_coupon_export_' . md5(uniqid('', true)) . '.csv';
        $handle = fopen($file, 'wb');

        if ($handle === false) {
            throw new \RuntimeException($this->language->get('error_web_coupon_export_file'));
        }

        try {
            fwrite($handle, "\xEF\xBB\xBF");
            $this->writeWebCouponExportCsvRow($handle, $this->getWebCouponExportHeaders());

            $last_order_id = null;

            do {
                $rows = $this->getWebCouponExportBatch($start_datetime, $end_datetime, $last_order_id, self::WEB_COUPON_EXPORT_BATCH_SIZE);

                foreach ($rows as $row) {
                    $this->writeWebCouponExportCsvRow($handle, $this->formatWebCouponExportRow($row));
                    $last_order_id = (int)$row['id'];
                }
            } while (count($rows) === self::WEB_COUPON_EXPORT_BATCH_SIZE);
        } catch (\Throwable $exception) {
            fclose($handle);
            @unlink($file);

            throw $exception;
        }

        fclose($handle);

        return $file;
    }


    /**
     * @return array
     */
    private function getWebCouponExportHeaders(): array
    {
        return [
            'id',
            'invoice_no',
            'invoice_prefix',
            'date_added',
            'firstname',
            'lastname',
            'email',
            'telephone',
            'payment_method',
            'shipping_method',
            'sub_total',
            'shipping',
            'coupon_discount',
            'order_total',
            'coupon_name',
            'coupon_code',
        ];
    }


    /**
     * @param resource $handle
     * @param array    $row
     *
     * @return void
     */
    private function writeWebCouponExportCsvRow($handle, array $row): void
    {
        if (fputcsv($handle, $row, ';') === false) {
            throw new \RuntimeException($this->language->get('error_web_coupon_export_file'));
        }
    }


    /**
     * @param string   $start_datetime
     * @param string   $end_datetime
     * @param int|null $last_order_id
     * @param int      $limit
     *
     * @return array
     */
    private function getWebCouponExportBatch(string $start_datetime, string $end_datetime, ?int $last_order_id, int $limit): array
    {
        $sql = "SELECT
                    o.order_id AS id,
                    o.invoice_no,
                    o.invoice_prefix,
                    o.date_added,
                    o.payment_firstname AS firstname,
                    o.payment_lastname AS lastname,
                    o.email,
                    o.telephone,
                    o.payment_method,
                    o.shipping_method,
                    CAST(MAX(ot_sub_total.value) AS DECIMAL(15,4)) AS sub_total,
                    CAST(MAX(ot_shipping.value) AS DECIMAL(15,4)) AS shipping,
                    CAST(MAX(ot_coupon.value) AS DECIMAL(15,4)) AS coupon_discount,
                    CAST(MAX(ot_total.value) AS DECIMAL(15,4)) AS order_total,
                    MAX(ot_coupon.title) AS coupon_name,
                    MAX(ot_coupon.title) AS coupon_code
                FROM (
                    SELECT
                        order_id,
                        invoice_no,
                        invoice_prefix,
                        date_added,
                        payment_firstname,
                        payment_lastname,
                        email,
                        telephone,
                        payment_method,
                        shipping_method
                    FROM " . DB_PREFIX . "order
                    WHERE date_added >= '" . $this->db->escape($start_datetime) . "'
                      AND date_added <= '" . $this->db->escape($end_datetime) . "'";

        if ($last_order_id !== null) {
            $sql .= " AND order_id < " . (int)$last_order_id;
        }

        $sql .= " ORDER BY order_id DESC LIMIT " . (int)$limit . "
                ) o
                LEFT JOIN " . DB_PREFIX . "order_total ot_sub_total
                    ON (ot_sub_total.order_id = o.order_id AND ot_sub_total.code = 'sub_total')
                LEFT JOIN " . DB_PREFIX . "order_total ot_shipping
                    ON (ot_shipping.order_id = o.order_id AND ot_shipping.code = 'shipping')
                LEFT JOIN " . DB_PREFIX . "order_total ot_coupon
                    ON (ot_coupon.order_id = o.order_id AND ot_coupon.code = 'coupon')
                LEFT JOIN " . DB_PREFIX . "order_total ot_total
                    ON (ot_total.order_id = o.order_id AND ot_total.code = 'total')
                GROUP BY
                    o.order_id,
                    o.invoice_no,
                    o.invoice_prefix,
                    o.date_added,
                    o.payment_firstname,
                    o.payment_lastname,
                    o.email,
                    o.telephone,
                    o.payment_method,
                    o.shipping_method
                ORDER BY o.order_id DESC";

        return $this->db->query($sql)->rows;
    }


    /**
     * @param array $row
     *
     * @return array
     */
    private function formatWebCouponExportRow(array $row): array
    {
        return [
            (int)$row['id'],
            $this->normalizeWebCouponExportValue($row['invoice_no']),
            $this->normalizeWebCouponExportValue($row['invoice_prefix']),
            $this->normalizeWebCouponExportValue($row['date_added']),
            $this->normalizeWebCouponExportValue($row['firstname']),
            $this->normalizeWebCouponExportValue($row['lastname']),
            $this->normalizeWebCouponExportValue($row['email']),
            $this->normalizeWebCouponExportValue($row['telephone']),
            $this->normalizeWebCouponExportValue($row['payment_method']),
            $this->normalizeWebCouponExportValue($row['shipping_method']),
            $this->normalizeWebCouponExportAmount($row['sub_total']),
            $this->normalizeWebCouponExportAmount($row['shipping']),
            $this->normalizeWebCouponExportAmount($row['coupon_discount']),
            $this->normalizeWebCouponExportAmount($row['order_total']),
            $this->normalizeWebCouponExportValue($row['coupon_name']),
            $this->normalizeWebCouponExportValue($row['coupon_code']),
        ];
    }


    /**
     * @param mixed $value
     *
     * @return string
     */
    private function normalizeWebCouponExportValue($value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        return trim(str_replace(["\r", "\n"], ' ', html_entity_decode((string)$value, ENT_QUOTES, 'UTF-8')));
    }


    /**
     * @param mixed $value
     *
     * @return string
     */
    private function normalizeWebCouponExportAmount($value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        return number_format((float)$value, 4, ',', '');
    }


    /**
     * @param array $models
     *
     * @return array
     */
    private function syncProductsByModel(array $items): array
    {
        $this->load->model('catalog/product');

        $_loc = new LOC_Product();

        $updated = 0;
        $imported = 0;
        $missing_local = [];
        $missing_luceed = [];
        $errors = [];

        foreach ($items as $item) {
            $model = $item['model'];
            $oc_product = Product::query()->select('product_id', 'model', 'date_added')->where('model', $model)->first();

            $luceed_items = collect($this->fetchLuceedProductsByModel($model));

            $luceed_product = $luceed_items
                ->where('artikl', '==', $model)
                ->where('osnovni__artikl', '==', null)
                ->first();

            if (!$luceed_product) {
                $luceed_product = $luceed_items->where('artikl', '==', $model)->first();
            }

            if (!$luceed_product) {
                $missing_luceed[] = $model;
                continue;
            }

            $luceed_product->opcije = $this->mapLuceedOptions(
                $luceed_items->where('osnovni__artikl', '==', $model)->values()->all()
            );

            try {
                $payload = $_loc->make($luceed_product);
                $payload = $this->applyImmediateQuantityState($payload, $luceed_product->opcije);
                $payload['sku'] = $model;

                if ($oc_product) {
                    $payload = array_merge($payload, $this->resolveOldProductData(['product_id' => $oc_product->product_id]));

                    if ($this->shouldAssignNovoCategoryFromCsvRow($item, $oc_product)) {
                        $payload = $this->appendNovoCategory($payload);
                    }

                    $this->model_catalog_product->editProduct((int)$oc_product->product_id, $payload);
                    $this->markProductAsSynced((int)$oc_product->product_id, $luceed_product);

                    $updated++;
                } else {
                    if ($this->shouldAssignNovoCategoryFromCsvRow($item)) {
                        $payload = $this->appendNovoCategory($payload);
                    }

                    $product_id = (int)$this->model_catalog_product->addProduct($payload);

                    if (!$product_id) {
                        throw new \RuntimeException('Import nije vratio product_id za model ' . $model . '.');
                    }

                    $this->markProductAsSynced($product_id, $luceed_product);

                    $imported++;
                }
            } catch (\Throwable $exception) {
                $errors[$model] = $exception->getMessage();

                Log::store(
                    [
                        'model' => $model,
                        'exists_local' => $oc_product ? 1 : 0,
                        'message' => $exception->getMessage(),
                    ],
                    'luceed_csv_sync_product_error'
                );
            }
        }

        return [
            'requested' => count($items),
            'updated' => $updated,
            'imported' => $imported,
            'missing_local' => $missing_local,
            'missing_luceed' => $missing_luceed,
            'errors' => $errors,
        ];
    }


    /**
     * Fetch a single Luceed product payload with all its options.
     *
     * @param string $model
     *
     * @return array
     */
    private function fetchLuceedProductsByModel(string $model): array
    {
        if (agconf('env') === 'local') {
            $products = json_decode(LuceedProduct::all());

            if (!isset($products->result[0]->artikli) || !is_array($products->result[0]->artikli)) {
                return [];
            }

            return collect($products->result[0]->artikli)
                ->filter(function ($item) use ($model) {
                    return $item->artikl === $model || (isset($item->osnovni__artikl) && $item->osnovni__artikl === $model);
                })
                ->values()
                ->all();
        }

        $service = new LuceedService();
        $response = $service->get('artikli/sifradio/' . rawurlencode($model));

        if (!$response) {
            return [];
        }

        $decoded = json_decode($response);

        if (!isset($decoded->result[0]->artikli) || !is_array($decoded->result[0]->artikli)) {
            return [];
        }

        return $decoded->result[0]->artikli;
    }


    /**
     * @param array $items
     *
     * @return array
     */
    private function mapLuceedOptions(array $items): array
    {
        $options = [];

        foreach ($items as $item) {
            if (!isset($item->artikl_uid) || !isset($item->artikl)) {
                continue;
            }

            $option_name = $this->resolveLuceedOptionName($item);

            $options[] = [
                'uid' => $item->artikl_uid,
                'artikl' => $item->artikl,
                'barcode' => isset($item->barcode) ? $item->barcode : '',
                'mpc' => isset($item->mpc) ? $item->mpc : 0,
                'velicina_uid' => isset($item->velicina_uid) ? $item->velicina_uid : '',
                'velicina' => $option_name,
                'velicina_naziv' => $option_name,
                'raspolozivo_kol' => (int)(isset($item->raspolozivo_kol) ? $item->raspolozivo_kol : (isset($item->stanje_kol) ? $item->stanje_kol : 0))
            ];
        }

        return $options;
    }


    /**
     * Resolve the OpenCart option label from the Luceed variant code.
     * Example: 134747-S => S
     *
     * @param object $item
     *
     * @return string
     */
    private function resolveLuceedOptionName($item): string
    {
        if (isset($item->artikl) && is_string($item->artikl)) {
            $position = strrpos($item->artikl, '-');

            if ($position !== false) {
                $suffix = trim(substr($item->artikl, $position + 1));

                if ($suffix !== '') {
                    return $suffix;
                }
            }
        }

        if (isset($item->velicina) && trim((string)$item->velicina) !== '') {
            return trim((string)$item->velicina);
        }

        if (isset($item->velicina_naziv) && trim((string)$item->velicina_naziv) !== '') {
            return trim((string)$item->velicina_naziv);
        }

        return isset($item->artikl) ? trim((string)$item->artikl) : '';
    }


    /**
     * Ensure newly imported products are assigned to the Novo category.
     *
     * @param array $payload
     *
     * @return array
     */
    private function appendNovoCategory(array $payload): array
    {
        $novo_category_id = (int) (agconf('import.novo_category') ?: 111);

        if (!isset($payload['product_category']) || !is_array($payload['product_category'])) {
            $payload['product_category'] = [];
        }

        $payload['product_category'][] = $novo_category_id;
        $payload['product_category'] = array_values(array_unique(array_map('intval', $payload['product_category'])));

        return $payload;
    }


    /**
     * Assign the Novo category only to products added within the last 30 days.
     *
     * @param mixed $product
     *
     * @return bool
     */
    private function shouldAssignNovoCategory($product): bool
    {
        $date_added = '';

        if (is_array($product) && !empty($product['date_added'])) {
            $date_added = (string)$product['date_added'];
        } elseif (is_object($product) && !empty($product->date_added)) {
            $date_added = (string)$product->date_added;
        }

        if ($date_added === '') {
            return false;
        }

        $added_at = Carbon::make($date_added);

        if (!$added_at) {
            return false;
        }

        return $added_at->greaterThanOrEqualTo(Carbon::now()->subDays(30)->startOfDay());
    }


    /**
     * Decide Novo assignment for CSV sync rows.
     * If CSV has a second column, only rows with value A26 should get Novo.
     * Otherwise keep the existing behavior.
     *
     * @param array $row
     * @param mixed $product
     *
     * @return bool
     */
    private function shouldAssignNovoCategoryFromCsvRow(array $row, $product = null): bool
    {
        if (!empty($row['has_secondary_column'])) {
            return isset($row['novo_marker']) && strtoupper(trim((string)$row['novo_marker'])) === 'A26';
        }

        if ($product) {
            return $this->shouldAssignNovoCategory($product);
        }

        return true;
    }


    /**
     * Aggregate the product stock immediately from Luceed options.
     *
     * @param array $payload
     * @param array $options
     *
     * @return array
     */
    private function applyImmediateQuantityState(array $payload, array $options): array
    {
        if ($options) {
            $quantity = 0;

            foreach ($options as $option) {
                $quantity += (int)$option['raspolozivo_kol'];
            }

            $payload['quantity'] = $quantity;
            $payload['stock_status_id'] = $quantity ? agconf('import.default_stock_full') : agconf('import.default_stock_empty');
            $payload['status'] = $quantity ? $payload['status'] : 0;
        }

        return $payload;
    }


    /**
     * @param int      $product_id
     * @param \stdClass $luceed_product
     *
     * @return void
     */
    private function markProductAsSynced(int $product_id, \stdClass $luceed_product): void
    {
        $updates = [];

        if ($this->hasProductColumn('luceed_uid')) {
            $updates[] = "luceed_uid = '" . $this->db->escape($luceed_product->artikl_uid) . "'";
        }

        if ($this->hasProductColumn('updated')) {
            $updates[] = "updated = 1";
        }

        if ($this->hasProductColumn('imported')) {
            $updates[] = "imported = 1";
        }

        if ($this->hasProductColumn('hash')) {
            $hash = ProductHelper::hashLuceedData(ProductHelper::collectLuceedData($luceed_product));
            $updates[] = "hash = '" . $this->db->escape($hash) . "'";
        }

        if ($updates) {
            $this->db->query("UPDATE " . DB_PREFIX . "product SET " . implode(', ', $updates) . " WHERE product_id = '" . (int)$product_id . "'");
        }
    }


    /**
     * @param string $column
     *
     * @return bool
     */
    private function hasProductColumn(string $column): bool
    {
        if ($this->product_columns === null) {
            $this->product_columns = [];

            $query = $this->db->query("SHOW COLUMNS FROM " . DB_PREFIX . "product");

            foreach ($query->rows as $row) {
                $this->product_columns[] = $row['Field'];
            }
        }

        return in_array($column, $this->product_columns, true);
    }


    /**
     * @param string $file
     *
     * @return array
     */
    private function extractModelsFromCsv(string $file): array
    {
        $handle = fopen($file, 'rb');

        if (!$handle) {
            throw new \RuntimeException($this->language->get('error_csv_file'));
        }

        $delimiter = $this->detectCsvDelimiter($file);
        $items = [];
        $model_index = null;
        $line = 0;
        $has_secondary_column = false;

        while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
            $line++;
            $row = array_map([$this, 'normalizeCsvCell'], $row);

            if (!$row || count(array_filter($row, function ($value) {
                return $value !== '';
            })) === 0) {
                continue;
            }

            if (count($row) > 1) {
                $has_secondary_column = true;
            }

            if ($line === 1) {
                $model_index = $this->resolveModelColumnIndex($row);

                if ($model_index !== null) {
                    continue;
                }

                if (in_array(strtolower($row[0]), ['model', 'artikl', 'sifra', 'sku'], true)) {
                    continue;
                }
            }

            $value = $model_index !== null && isset($row[$model_index]) ? $row[$model_index] : '';

            if ($value === '') {
                foreach ($row as $cell) {
                    if ($cell !== '') {
                        $value = $cell;
                        break;
                    }
                }
            }

            if ($value !== '') {
                $items[$value] = [
                    'model' => $value,
                    'novo_marker' => isset($row[1]) ? $row[1] : '',
                ];
            }
        }

        fclose($handle);

        foreach ($items as &$item) {
            $item['has_secondary_column'] = $has_secondary_column;
        }
        unset($item);

        return array_values($items);
    }


    /**
     * @param array $row
     *
     * @return int|null
     */
    private function resolveModelColumnIndex(array $row)
    {
        foreach ($row as $index => $value) {
            if (in_array(strtolower($value), ['model', 'artikl', 'sifra', 'sku'], true)) {
                return $index;
            }
        }

        return null;
    }


    /**
     * @param string $value
     *
     * @return string
     */
    private function normalizeCsvCell(string $value): string
    {
        return trim(str_replace("\xEF\xBB\xBF", '', $value));
    }


    /**
     * @param string $file
     *
     * @return string
     */
    private function detectCsvDelimiter(string $file): string
    {
        $handle = fopen($file, 'rb');
        $line = $handle ? (string)fgets($handle) : '';

        if ($handle) {
            fclose($handle);
        }

        $delimiters = [
            ';' => substr_count($line, ';'),
            ',' => substr_count($line, ','),
            "\t" => substr_count($line, "\t"),
        ];

        arsort($delimiters);

        return (string)key($delimiters);
    }


    /**
     * @param array $result
     * @param bool  $warning
     *
     * @return string
     */
    private function buildCsvSyncMessage(array $result, bool $warning = false): string
    {
        $processed = (int)$result['updated'] + (int)$result['imported'];

        $message = sprintf(
            $this->language->get($warning ? 'text_warning_products_csv_sync' : 'text_success_products_csv_sync'),
            $processed,
            $result['requested']
        );

        if (!empty($result['updated'])) {
            $message .= ' ' . sprintf($this->language->get('text_products_csv_updated'), $result['updated']);
        }

        if (!empty($result['imported'])) {
            $message .= ' ' . sprintf($this->language->get('text_products_csv_imported'), $result['imported']);
        }

        if ($result['missing_luceed']) {
            $message .= ' ' . sprintf($this->language->get('text_products_csv_missing_luceed'), implode(', ', $result['missing_luceed']));
        }

        if ($result['missing_local']) {
            $message .= ' ' . sprintf($this->language->get('text_products_csv_missing_local'), implode(', ', $result['missing_local']));
        }

        if ($result['errors']) {
            $message .= ' ' . sprintf($this->language->get('text_products_csv_errors'), implode(', ', array_keys($result['errors'])));
        }

        return $message;
    }


    /**
     * @param int   $processed
     * @param int   $total
     * @param array $result
     *
     * @return string
     */
    private function buildCsvSyncProgressMessage(int $processed, int $total, array $result): string
    {
        return sprintf(
            $this->language->get('text_products_csv_progress'),
            $processed,
            $total,
            (int)$result['updated'],
            (int)$result['imported']
        );
    }


    /**
     * @param int $requested
     *
     * @return array
     */
    private function createEmptyCsvSyncResult(int $requested): array
    {
        return [
            'requested' => $requested,
            'updated' => 0,
            'imported' => 0,
            'missing_local' => [],
            'missing_luceed' => [],
            'errors' => [],
        ];
    }


    /**
     * @param array $total
     * @param array $chunk
     *
     * @return array
     */
    private function mergeCsvSyncResults(array $total, array $chunk): array
    {
        $total['updated'] += (int)$chunk['updated'];
        $total['imported'] += (int)$chunk['imported'];
        $total['missing_local'] = array_values(array_unique(array_merge($total['missing_local'], $chunk['missing_local'])));
        $total['missing_luceed'] = array_values(array_unique(array_merge($total['missing_luceed'], $chunk['missing_luceed'])));
        $total['errors'] = array_merge($total['errors'], $chunk['errors']);

        return $total;
    }


    /**
     * @return void
     */
    private function prepareLongRunningRequest(): void
    {
        if (function_exists('ignore_user_abort')) {
            ignore_user_abort(true);
        }

        if (function_exists('set_time_limit')) {
            @set_time_limit(0);
        }
    }


    /**
     * @param array $items
     *
     * @return string
     */
    private function createCsvSyncItemsFile(array $items): string
    {
        $directory = rtrim(DIR_STORAGE, '/\\') . '/cache/';

        if (!is_dir($directory) && !mkdir($directory, 0777, true) && !is_dir($directory)) {
            throw new \RuntimeException($this->language->get('error_csv_sync_state'));
        }

        $file = $directory . 'luceed_csv_sync_' . md5(uniqid('', true)) . '.json';
        $written = file_put_contents($file, json_encode(array_values($items)));

        if ($written === false) {
            throw new \RuntimeException($this->language->get('error_csv_sync_state'));
        }

        return $file;
    }


    /**
     * @param string $file
     *
     * @return array
     */
    private function loadCsvSyncItems(string $file): array
    {
        if (!is_file($file)) {
            throw new \RuntimeException($this->language->get('error_csv_sync_state'));
        }

        $decoded = json_decode((string)file_get_contents($file), true);

        if (!is_array($decoded)) {
            throw new \RuntimeException($this->language->get('error_csv_sync_state'));
        }

        return $decoded;
    }


    /**
     * @return array
     */
    private function getCsvSyncState(): array
    {
        if (empty($this->session->data[self::CSV_SYNC_SESSION_KEY]) || !is_array($this->session->data[self::CSV_SYNC_SESSION_KEY])) {
            throw new \RuntimeException($this->language->get('error_csv_sync_state'));
        }

        return $this->session->data[self::CSV_SYNC_SESSION_KEY];
    }


    /**
     * @param array $result
     *
     * @return void
     */
    private function finalizeCsvSyncState(array $result): void
    {
        if ($result['updated'] || $result['imported']) {
            $this->session->data['success'] = $this->buildCsvSyncMessage($result);
        } else {
            $this->session->data['error_warning'] = $this->buildCsvSyncMessage($result, true);
        }

        $this->clearCsvSyncState();
    }


    /**
     * @return void
     */
    private function clearCsvSyncState(): void
    {
        if (!empty($this->session->data[self::CSV_SYNC_SESSION_KEY]['file'])) {
            $file = $this->session->data[self::CSV_SYNC_SESSION_KEY]['file'];

            if (is_string($file) && is_file($file)) {
                @unlink($file);
            }
        }

        unset($this->session->data[self::CSV_SYNC_SESSION_KEY]);
    }


    /**
     * Process the uploaded CSV and populate flash messages for the module page.
     *
     * @return void
     */
    private function processCsvSyncUpload(): void
    {
        $this->prepareLongRunningRequest();
        $this->session->data['success'] = '';
        unset($this->session->data['error_warning']);

        if (!$this->validateModifyPermission()) {
            $this->session->data['error_warning'] = $this->error['warning'];

            return;
        }

        if ($this->request->server['REQUEST_METHOD'] !== 'POST') {
            $this->session->data['error_warning'] = $this->language->get('error_csv_file');

            return;
        }

        if (empty($this->request->files['csv_file']['tmp_name']) || !is_file($this->request->files['csv_file']['tmp_name'])) {
            $this->session->data['error_warning'] = $this->language->get('error_csv_file');

            return;
        }

        try {
            $items = $this->extractModelsFromCsv($this->request->files['csv_file']['tmp_name']);

            if (!$items) {
                throw new \RuntimeException($this->language->get('error_csv_empty'));
            }

            $result = $this->syncProductsByModel($items);

            if ($result['updated'] || $result['imported']) {
                $this->session->data['success'] = $this->buildCsvSyncMessage($result);
            } else {
                $this->session->data['error_warning'] = $this->buildCsvSyncMessage($result, true);
            }
        } catch (\Throwable $exception) {
            Log::store(
                [
                    'message' => $exception->getMessage(),
                    'trace' => $exception->getTraceAsString()
                ],
                'luceed_csv_sync_error'
            );

            $this->session->data['error_warning'] = sprintf(
                $this->language->get('error_csv_sync'),
                $exception->getMessage()
            );
        }
    }


    /**
     * @return void
     */
    private function redirectToModule()
    {
        $this->response->redirect(
            $this->url->link('extension/module/luceed_sync', 'user_token=' . $this->session->data['user_token'], true)
        );
    }


    /**
     * @return bool
     */
    protected function validateRole()
    {
        if ( ! $this->user->hasPermission('modify', 'extension/module/luceed_sync')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        return ! $this->error;
    }


    /**
     * @return bool
     */
    private function validateModifyPermission(): bool
    {
        if (!$this->user->hasPermission('modify', 'extension/module/luceed_sync')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        return !$this->error;
    }


    /**
     * @param array|null $order
     *
     * @throws Exception
     */
    private function sendMail(?array $order = null)
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
        while (ob_get_level() > 0) {
            @ob_end_clean();
        }

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
