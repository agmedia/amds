<?php
/**
 * OpenCart 3.x Controller: Meta (Facebook/Instagram) Product XML Feed (RSS 2.0 + Google namespace)
 * Route: index.php?route=extension/feed/meta_feed[&key=YOUR_SECRET]
 * Place at: catalog/controller/extension/feed/meta_feed.php
 * Notes:
 *  - Content-Type: application/xml, Content-Disposition: inline (da se XML prikazuje u browseru).
 *  - Polja: id, title, description, link, image_link (+additional_image_link do 10), price, sale_price,
 *    sale_price_effective_date, availability, condition, brand, gtin/mpn, item_group_id, product_type,
 *    google_product_category (mapirano iz breadcrumbs-a).
 */
class ControllerExtensionFeedMetaFeed extends Controller {
    /** Optional access key; leave empty to disable key check */
    private $secret = '';

    /** Mapiranje lokalnih kategorija → Google Product Taxonomy. Dopuni prema stvarnim kategorijama. */
    private $googleCategoryMap = [
        // Odjeća i obuća
        'majice' => 'Apparel & Accessories > Clothing > Shirts & Tops',
        'hlače' => 'Apparel & Accessories > Clothing > Pants',
        'hlace' => 'Apparel & Accessories > Clothing > Pants',
        'haljine' => 'Apparel & Accessories > Clothing > Dresses',
        'jakne' => 'Apparel & Accessories > Clothing > Outerwear',
        'kaputi' => 'Apparel & Accessories > Clothing > Outerwear',
        'tenisice' => 'Apparel & Accessories > Shoes',
        'cipele' => 'Apparel & Accessories > Shoes',
        'torbe' => 'Luggage & Bags > Handbags, Wallets & Cases > Handbags',
        'satovi' => 'Apparel & Accessories > Jewelry > Watches',
        'naocale' => 'Apparel & Accessories > Clothing Accessories > Sunglasses',
        'naočale' => 'Apparel & Accessories > Clothing Accessories > Sunglasses',
        'kupaći kostimi' => 'Apparel & Accessories > Clothing > Swimwear',
        'kupaci kostimi' => 'Apparel & Accessories > Clothing > Swimwear',
        // Sport
        'sportska oprema' => 'Sporting Goods',
        'fitness' => 'Sporting Goods > Exercise & Fitness',
        // Dom
        'namještaj' => 'Furniture',
        'namjestaj' => 'Furniture',
        'dekoracije' => 'Home & Garden > Decor',
        // Elektronika
        'mobiteli' => 'Electronics > Communications > Telephony > Mobile Phones',
        'laptopi' => 'Computers > Laptops',
    ];

    public function index() {
        // Optional key gate
        $req_key = isset($this->request->get['key']) ? $this->request->get['key'] : '';
        if ($this->secret !== '' && $req_key !== $this->secret) {
            $this->response->addHeader('HTTP/1.1 403 Forbidden');
            $this->response->setOutput('Forbidden');
            return;
        }

        // Load deps
        $this->load->model('catalog/product');
        $this->load->model('tool/image');
        $this->load->model('catalog/manufacturer');
        $this->load->model('catalog/category');
        $this->load->model('catalog/review');
        $this->load->language('product/product');

        // Base shop url (detect HTTPS)
        $server = (isset($this->request->server['HTTPS']) && ($this->request->server['HTTPS'] == 'on' || $this->request->server['HTTPS'] == '1'))
            ? $this->config->get('config_ssl')
            : $this->config->get('config_url');

        // Currency code (Croatia uses EUR). Change if needed.
        $currency_code = $this->config->get('config_currency') ?: 'EUR';

        // Pull all enabled products (increase limit as needed)
        $products = $this->model_catalog_product->getProducts([
            'filter_status' => 1,
            'start' => 0,
            'limit' => 100000
        ]);

        // Build XML
        $xml = new \DOMDocument('1.0', 'UTF-8');
        $xml->formatOutput = true;

        $rss = $xml->createElement('rss');
        $rss->setAttribute('version', '2.0');
        $rss->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:g', 'http://base.google.com/ns/1.0');
        $xml->appendChild($rss);

        $channel = $xml->createElement('channel');
        $rss->appendChild($channel);

        $channel->appendChild($xml->createElement('title', $this->config->get('config_name')));
        $channel->appendChild($xml->createElement('link', rtrim($server, '/')));
        $channel->appendChild($xml->createElement('description', 'Meta (Facebook/Instagram) product feed'));

        $customer_group_id = (int)$this->config->get('config_customer_group_id');

        foreach ($products as $p) {
            if (!$p['product_id'] || !$p['name']) continue; // sanity

            $item = $xml->createElement('item');

            // Core fields
            $item->appendChild($this->g($xml, 'id', $p['product_id']));
            $item->appendChild($this->g($xml, 'title', $this->strip($p['name'])));

            // Description (fallback to meta_description or trimmed description)
            $description = $p['meta_description'] ?: html_entity_decode(strip_tags($p['description']), ENT_QUOTES, 'UTF-8');
            $item->appendChild($this->g($xml, 'description', $this->trimLen($description, 5000)));

            // Product URL
            $product_url = $this->url->link('product/product', 'product_id=' . (int)$p['product_id']);
            $item->appendChild($this->g($xml, 'link', $product_url));

            // Image URL (main)
            $image_link = '';
            if (!empty($p['image'])) {
                try {
                    $image_link = $this->model_tool_image->resize($p['image'], 1200, 1200);
                } catch (\Exception $e) {
                    $image_link = rtrim($server, '/') . '/image/' . $p['image'];
                }
            }
            if ($image_link) {
                $item->appendChild($this->g($xml, 'image_link', $image_link));
            }
            // Additional images (up to 10)
            $images = $this->model_catalog_product->getProductImages((int)$p['product_id']);
            $count = 0;
            foreach ($images as $img) {
                if (empty($img['image'])) continue;
                try {
                    $link = $this->model_tool_image->resize($img['image'], 1200, 1200);
                } catch (\Exception $e) {
                    $link = rtrim($server, '/') . '/image/' . $img['image'];
                }
                if ($link) {
                    $item->appendChild($this->g($xml, 'additional_image_link', $link));
                    if (++$count >= 10) break;
                }
            }

            // Price & tax (Meta expects "199.00 EUR")
            $price_inc_tax = $this->tax->calculate((float)$p['price'], (int)$p['tax_class_id'], (bool)$this->config->get('config_tax'));
            $item->appendChild($this->g($xml, 'price', $this->formatPrice($price_inc_tax, $currency_code)));

            // Special (sale) price + effective date window
            $specialInfo = $this->getActiveSpecial((int)$p['product_id'], $customer_group_id);
            if ($specialInfo && isset($specialInfo['price']) && $specialInfo['price'] < $p['price']) {
                $special_inc_tax = $this->tax->calculate((float)$specialInfo['price'], (int)$p['tax_class_id'], (bool)$this->config->get('config_tax'));
                $item->appendChild($this->g($xml, 'sale_price', $this->formatPrice($special_inc_tax, $currency_code)));
                if (!empty($specialInfo['date_start']) || !empty($specialInfo['date_end'])) {
                    $start = (!empty($specialInfo['date_start']) && $specialInfo['date_start'] != '0000-00-00') ? date('c', strtotime($specialInfo['date_start'])) : '';
                    $end   = (!empty($specialInfo['date_end'])   && $specialInfo['date_end']   != '0000-00-00') ? date('c', strtotime($specialInfo['date_end']))   : '';
                    $range = $start && $end ? ($start . '/' . $end) : ($start ?: $end);
                    if ($range) {
                        $item->appendChild($this->g($xml, 'sale_price_effective_date', $range));
                    }
                }
            }

            // Brand (manufacturer)
            $brand = '';
            if (!empty($p['manufacturer_id'])) {
                $m = $this->model_catalog_manufacturer->getManufacturer((int)$p['manufacturer_id']);
                if ($m && !empty($m['name'])) $brand = $m['name'];
            }
            if ($brand) {
                $item->appendChild($this->g($xml, 'brand', $this->strip($brand)));
            } elseif (!empty($p['manufacturer'])) {
                $item->appendChild($this->g($xml, 'brand', $this->strip($p['manufacturer'])));
            }

            // Availability & condition
            $availability = ((int)$p['quantity'] > 0 && (int)$p['status'] === 1) ? 'in stock' : 'out of stock';
            $item->appendChild($this->g($xml, 'availability', $availability));
            $item->appendChild($this->g($xml, 'condition', 'new'));

            // Product type (breadcrumb of categories)
            $product_types = $this->buildProductTypeBreadcrumbs((int)$p['product_id']);
            if ($product_types) {
                $item->appendChild($this->g($xml, 'product_type', implode(' > ', $product_types)));
                // Mapiraj na Google Product Taxonomy
                $google_cat = $this->mapGoogleCategory($product_types);
                if ($google_cat) {
                    $item->appendChild($this->g($xml, 'google_product_category', $google_cat));
                }
            }

            // Identifikatori
            if (!empty($p['upc'])) $item->appendChild($this->g($xml, 'gtin', $p['upc']));
            if (!empty($p['ean'])) $item->appendChild($this->g($xml, 'gtin', $p['ean']));
            if (!empty($p['mpn'])) $item->appendChild($this->g($xml, 'mpn', $p['mpn']));

            // Grupiranje varijanti (item_group_id)
            $groupId = '';
            if (!empty($p['model'])) $groupId = $p['model'];
            if (empty($groupId) && !empty($p['sku'])) $groupId = $p['sku'];
            if (!empty($groupId)) $item->appendChild($this->g($xml, 'item_group_id', $this->strip($groupId)));

            // Append
            $channel->appendChild($item);
        }

        $this->response->addHeader('Content-Type: application/xml; charset=UTF-8');
        $this->response->addHeader('Content-Disposition: inline; filename="meta.xml"');
        $this->response->setOutput($xml->saveXML());
    }

    /** Create <g:...> element */
    private function g(\DOMDocument $xml, $name, $value) {
        return $xml->createElementNS('http://base.google.com/ns/1.0', 'g:' . $name, $this->sanitize($value));
    }

    private function sanitize($value) {
        // Ensure it's a string and strip control chars
        $s = html_entity_decode((string)$value, ENT_QUOTES, 'UTF-8');
        $s = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/u', '', $s);
        return $s;
    }

    private function strip($value) {
        return trim(strip_tags(html_entity_decode((string)$value, ENT_QUOTES, 'UTF-8')));
    }

    private function trimLen($value, $max) {
        $s = $this->strip($value);
        if (mb_strlen($s, 'UTF-8') > $max) {
            $s = mb_substr($s, 0, $max - 1, 'UTF-8') . '…';
        }
        return $s;
    }

    private function formatPrice($price, $currency_code) {
        // Use number_format with dot decimal as Meta expects, 2 decimals
        return number_format((float)$price, 2, '.', '') . ' ' . $currency_code;
    }

    /** Active special (price + optional dates) */
    private function getActiveSpecial($product_id, $customer_group_id) {
        $sql = "SELECT price, date_start, date_end FROM `" . DB_PREFIX . "product_special`
                WHERE product_id = " . (int)$product_id . "
                  AND customer_group_id = " . (int)$customer_group_id . "
                  AND ((date_start IS NULL OR date_start = '0000-00-00' OR date_start <= NOW())
                   AND (date_end   IS NULL OR date_end   = '0000-00-00' OR date_end   >= NOW()))
                ORDER BY priority ASC, price ASC LIMIT 1";
        $q = $this->db->query($sql);
        if ($q->num_rows) {
            return [
                'price'      => (float)$q->row['price'],
                'date_start' => $q->row['date_start'] ?? null,
                'date_end'   => $q->row['date_end'] ?? null,
            ];
        }
        return null;
    }

    /** Build product_type from category hierarchy */
    private function buildProductTypeBreadcrumbs($product_id) {
        $this->load->model('catalog/product');
        $this->load->model('catalog/category');
        $cats = $this->model_catalog_product->getProductCategories($product_id);
        $names = [];
        foreach ($cats as $category_id) {
            $path = $this->getCategoryPath($category_id);
            if ($path) { $names = $path; break; }
        }
        return $names;
    }

    private function getCategoryPath($category_id) {
        $this->load->model('catalog/category');
        $names = [];
        $category_info = $this->model_catalog_category->getCategory($category_id);
        if (!$category_info) return [];
        // Walk up via parent_id
        $stack = [];
        while ($category_info) {
            array_unshift($stack, $category_info['name']);
            if ($category_info['parent_id']) {
                $category_info = $this->model_catalog_category->getCategory((int)$category_info['parent_id']);
            } else {
                $category_info = null;
            }
        }
        // Clean names
        foreach ($stack as $n) {
            $names[] = $this->strip($n);
        }
        return $names;
    }

    /** Normalize (lowercase + bez dijakritika) */
    private function normalizeText($s) {
        $s = strtolower(trim($this->strip($s)));
        $t = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $s);
        return $t !== false ? $t : $s;
    }

    /** Breadcrumbs → Google Product Taxonomy */
    private function mapGoogleCategory($product_types) {
        if (empty($product_types)) return '';
        // Kreni od najkonkretnije kategorije
        for ($i = count($product_types) - 1; $i >= 0; $i--) {
            $key = $this->normalizeText($product_types[$i]);
            if (isset($this->googleCategoryMap[$key])) {
                return $this->googleCategoryMap[$key];
            }
        }
        return '';
    }
}
