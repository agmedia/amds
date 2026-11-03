<?php
/** Offline sitemap regressions: php tests/sitemap/run.php */
if (PHP_SAPI !== 'cli') { exit("Run from the command line.\n"); }
error_reporting(E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED);
set_error_handler(function ($severity, $message, $file, $line) {
    if (error_reporting() & $severity) { throw new ErrorException($message, 0, $severity, $file, $line); }
    return false;
});

$root = dirname(__DIR__, 2);
require $root . '/upload/system/engine/registry.php';
require $root . '/upload/system/engine/controller.php';
require $root . '/upload/system/library/config.php';
require $root . '/upload/system/library/url.php';
require $root . '/upload/catalog/controller/extension/feed/google_sitemap.php';
require $root . '/upload/catalog/controller/startup/seo_url.php';

class SitemapResponse {
    public $headers = array();
    public $output = '';
    public function addHeader($header) { $this->headers[] = $header; }
    public function setOutput($output) { $this->output = $output; }
}
class SitemapLoader {
    public $loaded = array();
    public function model($route) {
        $allowed = array('extension/feed/google_sitemap', 'catalog/category', 'catalog/manufacturer', 'catalog/information', 'extension/blog/blog', 'extension/blog/blog_category');
        if (!in_array($route, $allowed, true)) {
            throw new RuntimeException('Unexpected heavyweight/image model loaded: ' . $route);
        }
        $this->loaded[] = $route;
    }
}
class SitemapProducts {
    public $rows;
    public $calls = array();
    public function __construct($count) {
        $this->rows = array();
        for ($id = 1; $id <= $count; $id++) {
            $this->rows[] = array('product_id' => $id, 'image' => '', 'date_modified' => '2024-03-15 12:34:56');
        }
        if ($count > 0) { $this->rows[0]['image'] = 'catalog/Ženske hlače/jeans & blue.jpg'; }
        if ($count > 1) { $this->rows[1]['date_modified'] = '0000-00-00 00:00:00'; }
        if ($count > 2) { $this->rows[2]['date_modified'] = 'invalid date'; }
        if ($count > 3) { $this->rows[3]['date_modified'] = date('Y-m-d H:i:s', time() + 86400 * 3); }
        if ($count > 4) { $this->rows[4]['date_modified'] = '2024-02-30 12:34:56'; }
        if ($count > 5) { $this->rows[5]['date_modified'] = '0000-01-01 00:00:00'; }
    }
    public function getTotalProducts() { return count($this->rows); }
    public function getProducts($start, $limit) {
        $this->calls[] = array($start, $limit);
        return array_slice($this->rows, $start, $limit);
    }
}
class SitemapCategories {
    public function getCategories($parent = 0) {
        $tree = array(0 => array(array('category_id' => 10)), 10 => array(array('category_id' => 11)), 11 => array());
        return isset($tree[$parent]) ? $tree[$parent] : array();
    }
}
class SitemapManufacturers {
    public function getManufacturers() { return array(array('manufacturer_id' => 7)); }
}
class SitemapInformations {
    public function getInformations() { return array(array('information_id' => 8)); }
}
class SitemapBlogs {
    public $calls = array();
    public function getTotalBlogs($data) { return 2; }
    public function getBlogs($data, $start, $limit) {
        $this->calls[] = array($data, $start, $limit);
        return array(array('blog_id' => 20), array('blog_id' => 21));
    }
}
class SitemapBlogCategories {
    public function getBlogCategories($parent = 0) {
        $tree = array(0 => array(array('blog_category_id' => 30)), 30 => array(array('blog_category_id' => 31)), 31 => array());
        return isset($tree[$parent]) ? $tree[$parent] : array();
    }
}
class SitemapDatabaseGuard {
    public function query($sql) { throw new RuntimeException('Sitemap alias unexpectedly queried SEO aliases'); }
    public function escape($value) { return addslashes($value); }
}

$assertions = 0;
function sitemapAssert($condition, $message) {
    global $assertions;
    $assertions++;
    if (!$condition) { throw new RuntimeException($message); }
}
function sitemapFixture($get = array(), $count = 1003, $enabled = true) {
    $registry = new Registry();
    $config = new Config();
    $config->set('feed_google_sitemap_status', $enabled ? 1 : 0);
    $config->set('config_url', 'http://shop.example/');
    $config->set('config_ssl', 'https://shop.example/');
    $config->set('config_seo_url', true);
    $config->set('config_language_id', 2);
    $config->set('config_store_id', 0);
    $registry->set('config', $config);
    $registry->set('request', (object)array('get' => $get, 'server' => array('SERVER_PROTOCOL' => 'HTTP/1.1', 'HTTPS' => true)));
    $registry->set('response', new SitemapResponse());
    $registry->set('load', new SitemapLoader());
    $registry->set('url', new Url('http://shop.example/', 'https://shop.example/'));
    $registry->set('db', new SitemapDatabaseGuard());
    $registry->set('model_extension_feed_google_sitemap', new SitemapProducts($count));
    $registry->set('model_catalog_category', new SitemapCategories());
    $registry->set('model_catalog_manufacturer', new SitemapManufacturers());
    $registry->set('model_catalog_information', new SitemapInformations());
    $registry->set('model_extension_blog_blog', new SitemapBlogs());
    $registry->set('model_extension_blog_blog_category', new SitemapBlogCategories());
    return $registry;
}
function sitemapRun($registry) {
    $controller = new ControllerExtensionFeedGoogleSitemap($registry);
    $controller->index();
    return $registry->get('response');
}
function sitemapXml($response, $rootName) {
    sitemapAssert((bool)preg_grep('/^Content-Type:\s*application\/xml\s*;\s*charset=UTF-8$/i', $response->headers), 'XML response advertises UTF-8');
    $old = libxml_use_internal_errors(true);
    $xml = simplexml_load_string($response->output);
    $errors = libxml_get_errors();
    libxml_clear_errors();
    libxml_use_internal_errors($old);
    sitemapAssert($xml !== false && !$errors, 'Sitemap parses as valid XML');
    sitemapAssert($xml->getName() === $rootName, 'Expected ' . $rootName . ' root');
    $namespaces = $xml->getDocNamespaces();
    sitemapAssert(isset($namespaces['']) && $namespaces[''] === 'http://www.sitemaps.org/schemas/sitemap/0.9', 'Standard sitemap namespace');
    $xml->registerXPathNamespace('s', 'http://www.sitemaps.org/schemas/sitemap/0.9');
    $xml->registerXPathNamespace('image', 'http://www.google.com/schemas/sitemap-image/1.1');
    return $xml;
}
function sitemapLocs($xml, $xpath) {
    return array_map('strval', $xml->xpath($xpath));
}
function sitemapNotFound($registry, $label) {
    $response = sitemapRun($registry);
    sitemapAssert((bool)preg_grep('/\b404\b/', $response->headers), $label . ': HTTP 404');
    sitemapAssert($response->output === '', $label . ': no misleading empty XML sitemap');
    sitemapAssert(!$registry->get('model_extension_feed_google_sitemap')->calls, $label . ': no product page query');
}

$registry = sitemapFixture();
$response = sitemapRun($registry);
$xml = sitemapXml($response, 'sitemapindex');
sitemapAssert(sitemapLocs($xml, '/s:sitemapindex/s:sitemap/s:loc') === array(
    'https://shop.example/site-map-pages.xml',
    'https://shop.example/site-map-products-1.xml',
    'https://shop.example/site-map-products-2.xml'
), '1003 products split into two discoverable shards plus pages');
sitemapAssert(!$registry->get('model_extension_feed_google_sitemap')->calls, 'Index only counts products');

$xml = sitemapXml(sitemapRun(sitemapFixture(array(), 0)), 'sitemapindex');
sitemapAssert(sitemapLocs($xml, '/s:sitemapindex/s:sitemap/s:loc') === array('https://shop.example/site-map-pages.xml'), 'Empty catalog index contains only pages sitemap');
$xml = sitemapXml(sitemapRun(sitemapFixture(array(), 1000)), 'sitemapindex');
sitemapAssert(count($xml->xpath('/s:sitemapindex/s:sitemap')) === 2, 'Exact chunk boundary does not create an empty shard');

$registry = sitemapFixture(array('type' => 'pages'));
$response = sitemapRun($registry);
$xml = sitemapXml($response, 'urlset');
$locations = sitemapLocs($xml, '/s:urlset/s:url/s:loc');
sitemapAssert(count($locations) === 11 && count(array_unique($locations)) === 11, 'Pages contain home, contact, catalog pages and public blog content once');
foreach ($locations as $location) {
    sitemapAssert(strpos($location, 'https://shop.example/') === 0, 'Page uses canonical HTTPS host');
    sitemapAssert(strpos($location, '&amp;') === false, 'Parsed query URLs contain a single decoded ampersand');
    sitemapAssert(strpos($location, 'product_id=') === false, 'Products appear only in product shards');
}
sitemapAssert(in_array('https://shop.example/index.php?route=product/category&path=11', $locations, true), 'Nested category uses leaf-only canonical path');
sitemapAssert(in_array('https://shop.example/index.php?route=information/contact', $locations, true), 'Contact page is discoverable');
sitemapAssert(in_array('https://shop.example/index.php?route=extension/blog/home', $locations, true), 'Blog landing page is discoverable');
foreach (array(20, 21) as $blogId) {
    sitemapAssert(in_array('https://shop.example/index.php?route=extension/blog/blog&blog_id=' . $blogId, $locations, true), 'Blog post uses canonical ID without category variants');
}
sitemapAssert(in_array('https://shop.example/index.php?route=extension/blog/category&blogpath=30_31', $locations, true), 'Nested blog category retains its full category path');
sitemapAssert($registry->get('model_extension_blog_blog')->calls === array(array(array(), 0, 2)), 'All public blog posts requested beyond default pagination');
sitemapAssert(strpos($response->output, '&amp;amp;') === false, 'XML does not double-escape URL ampersands');
sitemapAssert(!$registry->get('model_extension_feed_google_sitemap')->calls, 'Pages do not fetch products per category/manufacturer');

$registry = sitemapFixture(array('type' => 'products', 'page' => '1'));
$response = sitemapRun($registry);
$xml = sitemapXml($response, 'urlset');
$locations = sitemapLocs($xml, '/s:urlset/s:url/s:loc');
sitemapAssert(count($locations) === 1000 && count(array_unique($locations)) === 1000, 'First product shard contains 1000 unique products');
sitemapAssert($locations[0] === 'https://shop.example/index.php?route=product/product&product_id=1', 'Product URL has only its canonical ID');
sitemapAssert(in_array('https://shop.example/index.php?route=product/product&product_id=2', $locations, true), 'Product without an image remains indexed');
sitemapAssert($registry->get('model_extension_feed_google_sitemap')->calls === array(array(0, 1000)), 'First shard uses bounded lean product query');
sitemapAssert(sitemapLocs($xml, '/s:urlset/s:url[1]/image:image/image:loc') === array('https://shop.example/image/catalog/%C5%BDenske%20hla%C4%8De/jeans%20%26%20blue.jpg'), 'Original image path is encoded per component');
sitemapAssert(count($xml->xpath('/s:urlset/s:url[2]/image:image')) === 0, 'No empty image entry for product without image');
sitemapAssert(sitemapLocs($xml, '/s:urlset/s:url[1]/s:lastmod') === array('2024-03-15'), 'Valid modification date rendered as calendar date');
foreach (array(2, 3, 4, 5, 6) as $position) {
    sitemapAssert(count($xml->xpath('/s:urlset/s:url[' . $position . ']/s:lastmod')) === 0, 'Zero, malformed, future or invalid calendar date omitted at position ' . $position);
}
sitemapAssert(strpos($response->output, '&amp;amp;') === false, 'Product query URL XML is escaped once');
foreach ($locations as $location) {
    sitemapAssert(strpos($location, 'https://shop.example/') === 0, 'Product uses HTTPS');
    sitemapAssert(strpos($location, 'path=') === false && strpos($location, 'manufacturer_id=') === false, 'No duplicate category/manufacturer product variants');
}

$registry = sitemapFixture(array('type' => 'products', 'page' => '2'));
$xml = sitemapXml(sitemapRun($registry), 'urlset');
$lastLocations = sitemapLocs($xml, '/s:urlset/s:url/s:loc');
sitemapAssert(count($lastLocations) === 3, 'Last shard contains the remaining products');
sitemapAssert(!$lastLocations || !array_intersect($locations, $lastLocations), 'Adjacent product shards never overlap');
sitemapAssert($registry->get('model_extension_feed_google_sitemap')->calls === array(array(1000, 1000)), 'Second shard uses bounded offset');

foreach (array(array(), array('type' => 'pages'), array('type' => 'products', 'page' => '1')) as $get) {
    sitemapNotFound(sitemapFixture($get, 1003, false), 'Disabled feed');
}
foreach (array('unknown', array('pages')) as $type) {
    sitemapNotFound(sitemapFixture(array('type' => $type)), 'Unknown or non-scalar sitemap type');
}
foreach (array('0', '-1', '1.5', 'abc', '3', '9999999999999999999999999999', array('1')) as $page) {
    sitemapNotFound(sitemapFixture(array('type' => 'products', 'page' => $page)), 'Invalid or out-of-range product shard');
}
sitemapNotFound(sitemapFixture(array('type' => 'products', 'page' => '1'), 0), 'Empty catalog product shard');

foreach (array(
    'site-map.xml' => array('type' => 'index', 'page' => 1),
    'sitemap.xml' => array('type' => 'index', 'page' => 1),
    'site-map-pages.xml' => array('type' => 'pages', 'page' => 1),
    'site-map-products-1.xml' => array('type' => 'products', 'page' => 1),
    'site-map-products-2.xml' => array('type' => 'products', 'page' => 2)
) as $path => $expected) {
    foreach (array(true, false) as $seoEnabled) {
        $registry = sitemapFixture(array('_route_' => $path, 'type' => array('untrusted'), 'page' => '-999'));
        $registry->get('config')->set('config_seo_url', $seoEnabled);
        $controller = new ControllerStartupSeoUrl($registry);
        $controller->index();
        $get = $registry->get('request')->get;
        sitemapAssert(isset($get['route']) && $get['route'] === 'extension/feed/google_sitemap', $path . ': public alias resolves without SEO database dependency');
        foreach ($expected as $key => $value) {
            sitemapAssert(isset($get[$key]) && (string)$get[$key] === (string)$value, $path . ': canonical alias overrides untrusted ' . $key);
        }
    }
}

echo 'Sitemap regressions passed (' . $assertions . " assertions).\n";
