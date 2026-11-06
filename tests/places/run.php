<?php
/** Offline checkout place regressions: php tests/places/run.php */
if (PHP_SAPI !== 'cli') { exit(1); }

error_reporting(E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED);
set_error_handler(function ($severity, $message, $file, $line) {
    if (error_reporting() & $severity) { throw new ErrorException($message, 0, $severity, $file, $line); }
    return false;
});

// Use the real place data and checkout endpoint without application/database startup.
define('DIR_STORAGE', __DIR__ . '/../../storage/');
require DIR_STORAGE . 'vendor/autoload.php';
require __DIR__ . '/../../upload/system/engine/registry.php';
require __DIR__ . '/../../upload/system/engine/controller.php';
require __DIR__ . '/../../upload/system/library/response.php';
require __DIR__ . '/../../upload/catalog/controller/checkout/checkout.php';

use Agmedia\LuceedOpencartWrapper\Models\LOC_Places;

$assertions = 0;
function check($condition, $message) {
    global $assertions;
    $assertions++;
    if (!$condition) { throw new RuntimeException($message); }
}

function checkoutPlaces($target, $query) {
    $registry = new Registry();
    $registry->set('request', (object) ['get' => [$target => $query]]);
    $response = new Response();
    $registry->set('response', $response);
    (new ControllerCheckoutCheckout($registry))->places();
    $places = json_decode($response->getOutput(), true);
    check(is_array($places), 'Checkout must return valid place JSON');
    return array_values($places);
}

function isBiograd($place) {
    return $place['ctrcode'] === 'HR'
        && (string) $place['zipcode'] === '23210'
        && $place['cityname'] === 'Biograd na moru';
}

foreach (['Biogr', 'BIOGR', 'Biograd na Moru', 'biograd na moru'] as $query) {
    $places = checkoutPlaces('city', $query);
    check(count($places) === 1 && isBiograd($places[0]), 'Biograd city search failed for ' . $query);
}

$places = checkoutPlaces('postcode', '23210');
check(count($places) === 1 && isBiograd($places[0]), 'Postal search must return Biograd without other excluded settlements');

foreach (['Vrgada' => '23210', 'Jankolovica' => '23210', 'Oton' => '23210', 'Koločep' => '20221', 'Lastovo' => '20290'] as $query => $postcode) {
    $matches = array_filter(checkoutPlaces('city', $query), function ($place) use ($query, $postcode) {
        return strcasecmp($place['cityname'], $query) === 0 && (string) $place['zipcode'] === $postcode;
    });
    check($matches === [], 'Existing exclusion changed for ' . $query);
}

$zagreb = checkoutPlaces('city', 'Zagreb');
check(count($zagreb) === 6, 'Existing Zagreb results changed');
check(count(checkoutPlaces('postcode', '10000')) === 1, 'Existing Zagreb postal search changed');
check(count(checkoutPlaces('city', 'a')) === 20, 'Checkout result limit changed');

// Quick checkout validation uses this same model directly for city and postal code.
$model = new LOC_Places();
check($model->getList()->find('Biograd na moru')->places->count() === 1, 'City validation cannot find Biograd');
check($model->getList()->find('23210', 'zipcode')->places->count() === 1, 'Postal validation cannot find Biograd');
check($model->getList()->find('23210', 'zipcode')->places->filter('isBiograd')->count() === 1, 'Postal validation returned an excluded settlement');

echo 'PASS: ' . $assertions . " checkout place assertions\n";
