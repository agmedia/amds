<?php

use Agmedia\Luceed\Facade\LuceedProduct;
use Agmedia\LuceedOpencartWrapper\Helpers\ProductModelHelper;
use Agmedia\LuceedOpencartWrapper\Models\LOC_Product;
use Agmedia\Helpers\Log;

$count = 0;
$_loc = new LOC_Product(LuceedProduct::all());

$new_products = $_loc->checkDiff()->getProductsToAdd();

if ($new_products->count()) {
    foreach ($new_products as $product) {
        $data = $_loc->make($product);

        try {
            ProductModelHelper::add($data);

        } catch (Exception $e) {
            Log::store($e->getMessage(), 'import_errors');
        }

        $count++;
    }
}

echo $count;

