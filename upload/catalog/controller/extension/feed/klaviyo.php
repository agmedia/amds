<?php

class ControllerExtensionFeedKlaviyo extends Controller {

    public function index()
    {

        $output = '<?xml version="1.0" encoding="UTF-8"?>';

        $output .= '<Products>';



        $this->load->model('catalog/product');
        $this->load->model('catalog/category');



        $products = $this->model_catalog_product->getProducts();


        foreach ($products as $product) {

            if($product['quantity'] > 0 && $product['model']!='') {



                $description = strip_tags(html_entity_decode($product['meta_description']));
                $description = str_replace('&nbsp;', '', $description);
                $description = str_replace('', '', $description);
                $description = str_replace('', '', $description);
                $description = str_replace('&#44', '', $description);
                $description = str_replace("'", '', $description);
                $description = str_replace('', '', $description);
                $description = str_replace('.', '', $description);
                $description = str_replace('', '', $description);




                $output .= '<Product>';

                $output .= '<id>' . $this->wrapInCDATA($product['model']) . '</id>';
                $output .= '<title>' . $this->wrapInCDATA($product['name']) . '</title>';


                $output .= '<description>' . $this->wrapInCDATA($description) . '</description>';
                $output .= '<link>' . $this->url->link('product/product', 'product_id=' . $product['product_id']) . '</link>';
                $output .= '<image_link>' . $this->wrapInCDATA(HTTPS_SERVER.'image/' . $product['image']) . '</image_link>';


                $output .= '<price>' . number_format($product['price'], '2','.','')  '</price>';






                $output .= '</Product>';




            }
        }
        $output .= '</Products>';



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