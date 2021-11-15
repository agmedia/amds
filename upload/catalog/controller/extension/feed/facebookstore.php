<?php

class ControllerExtensionFeedFacebookstore extends Controller {

    public function index()
    {

        $output = '<?xml version="1.0" encoding="UTF-8"?>';
        $output .= '<rss xmlns:g="http://base.google.com/ns/1.0" version="2.0">';
        $output .= '<channel>';

        $output .= '<title>amds.hr</title>';
        $output .= '<link>'.HTTPS_SERVER.'index.php?route=extension/feed/facebookstore</link>';

        $this->load->model('catalog/product');
        $this->load->model('catalog/category');



        $products = $this->model_catalog_product->getProducts();


        foreach ($products as $product) {

            if($product['quantity']>0 || $product['model']!='') {



                $description = strip_tags(html_entity_decode($product['meta_description']));
                $description = str_replace('&nbsp;', '', $description);
                $description = str_replace('', '', $description);
                $description = str_replace('', '', $description);
                $description = str_replace('&#44', '', $description);
                $description = str_replace("'", '', $description);
                $description = str_replace('', '', $description);
                $description = str_replace('.', '', $description);
                $description = str_replace('', '', $description);




                $output .= '<item>';

                $output .= '<g:id>' . $this->wrapInCDATA($product['model']) . '</g:id>';
                $output .= '<g:title>' . $this->wrapInCDATA($product['name']) . '</g:title>';


                $output .= '<g:description>' . $this->wrapInCDATA($description) . '</g:description>';
                $output .= '<g:link>' . $this->url->link('product/product', 'product_id=' . $product['product_id']) . '</g:link>';
                $output .= '<g:image_link>' . $this->wrapInCDATA(HTTPS_SERVER.'image/' . $product['image']) . '</g:image_link>';
                $output .= '<g:brand>' . $this->wrapInCDATA($product['manufacturer']) . '</g:brand>';
                $output .= '<g:condition>new</g:condition>';
                $output .= '<g:availability>in stock</g:availability>';

                $output .= '<g:price>' . number_format($product['price'], '2','.','') . ' HRK</g:price>';

                if($product['special']!=''){

                    $output .= '<g:sale_price>' .  number_format($product['special'], '2','.','') . ' HRK</g:sale_price>';

                }

                $output .= '<g:google_product_category>222</g:google_product_category>';





                $output .= '</item>';




            }
        }
        $output .= '</channel>';
        $output .= '</rss>';


        $dom = new DOMDocument;
        $dom->preserveWhiteSpace = false;
        $dom->loadXml($output);
        $xpath = new DOMXPath($dom);
        foreach ($xpath->query('//text()') as $domText) {
            $domText->data = trim($domText->nodeValue);
        }
        $dom->formatOutput = true;
        echo $dom->saveXml();


        // $this->response->addHeader('Content-Type: application/xml');

        // $this->response->setOutput($output);


    }


    private function wrapInCDATA($in)
    {
        return "<![CDATA[ " . $in . " ]]>";
        //return $in;
    }


    private function removeChar($string, $char)
    {
        return str_replace($char, '', $string);
    }


    protected function getPath($parent_id, $current_path = '') {
        $category_info = $this->model_catalog_category->getCategory($parent_id);

        if ($category_info) {
            if (!$current_path) {
                $new_path = $category_info['category_id'];
            } else {
                $new_path = $category_info['category_id'] . '_' . $current_path;
            }

            $path = $this->getPath($category_info['parent_id'], $new_path);

            if ($path) {
                return $path;
            } else {
                return $new_path;
            }
        }
    }


    /**
     * Construct category and parent name
     * and return it
     *
     * @param $id
     *
     * @return string
     */
    public function getCategoriesName($id)
    {
        $this->load->model('catalog/category');
        $data = $this->model_catalog_product->getCategories($id);
        $name = '';

        foreach ($data as $item) {
            if (empty($category)) {
                $category = $this->model_catalog_category->getCategory($item['category_id']);
                $name     = $category['name'];

                if ($category['parent_id'] != 0) {
                    $parent = $this->model_catalog_category->getCategory($category['parent_id']);
                    $name   = $parent['name'] . ' > ' . $category['name'];
                }
            }
        }

        return $name;
    }

}

?>