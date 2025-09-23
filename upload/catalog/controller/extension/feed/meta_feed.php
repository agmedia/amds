<?php
/**
 * OpenCart 3.x Controller: Meta (Facebook/Instagram) Product XML Feed (RSS 2.0 + Google namespace)
 * Route: index.php?route=extension/feed/meta_feed[&key=YOUR_SECRET]
 * Place at: catalog/controller/extension/feed/meta_feed.php
 * Optional: set SECRET below or pass &key=... to restrict access.
 */
class ControllerExtensionFeedMetaFeed extends Controller {
    /** @var string Optional access key; leave empty to disable key check */
    private $secret = '';

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
            // Skip products without required basics
            if (!$p['product_id'] || !$p['name']) continue;

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

            // Image URL (use main image, resize to decent size if available)
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

            // Price & tax handling (Meta expects number + space + currency, e.g. "199.00 EUR")
            $price_inc_tax = $this->tax->calculate((float)$p['price'], (int)$p['tax_class_id'], (bool)$this->config->get('config_tax'));
            $item->appendChild($this->g($xml, 'price', $this->formatPrice($price_inc_tax, $currency_code)));

            // Special (sale) price if active
            $special = $this->getActiveSpecial((int)$p['product_id'], $customer_group_id);
            if ($special !== null && $special < $p['price']) {
                $special_inc_tax = $this->tax->calculate((float)$special, (int)$p['tax_class_id'], (bool)$this->config->get('config_tax'));
                $item->appendChild($this->g($xml, 'sale_price', $this->formatPrice($special_inc_tax, $currency_code)));
            }

            // Brand (manufacturer)
            $brand = '';
            if (!empty($p['manufacturer_id'])) {
                $m = $this->model_catalog_manufacturer->getManufacturer((int)$p['manufacturer_id']);
                if ($m && !empty($m['name'])) $brand = $m['name'];
            }
            if ($brand) {
                $item->appendChild($this->g($xml, 'brand', $this->strip($brand)));
            }

            // Availability
            $availability = ((int)$p['quantity'] > 0 && (int)$p['status'] === 1) ? 'in stock' : 'out of stock';
            $item->appendChild($this->g($xml, 'availability', $availability));

            // Product type (breadcrumb of categories)
            $product_types = $this->buildProductTypeBreadcrumbs((int)$p['product_id']);
            if ($product_types) {
                $item->appendChild($this->g($xml, 'product_type', implode(' > ', $product_types)));
            }

            // Optional: GTIN/MPN if available in custom fields (adapt if stored elsewhere)
            if (!empty($p['upc'])) {
                $item->appendChild($this->g($xml, 'gtin', $p['upc']));
            }
            if (!empty($p['mpn'])) {
                $item->appendChild($this->g($xml, 'mpn', $p['mpn']));
            }

            // Append item
            $channel->appendChild($item);
        }

        $this->response->addHeader('Content-Type: application/rss+xml; charset=UTF-8');
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

    /** Return active special price or null */
    private function getActiveSpecial($product_id, $customer_group_id) {
        $sql = "SELECT price FROM `" . DB_PREFIX . "product_special` WHERE product_id = " . (int)$product_id . " AND customer_group_id = " . (int)$customer_group_id . " AND ((date_start IS NULL OR date_start = '0000-00-00' OR date_start <= NOW()) AND (date_end IS NULL OR date_end = '0000-00-00' OR date_end >= NOW())) ORDER BY priority ASC, price ASC LIMIT 1";
        $query = $this->db->query($sql);
        if ($query->num_rows) {
            return (float)$query->row['price'];
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
            if ($path) {
                $names = $path; // take the first non-empty path (most specific)
                break;
            }
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
}
