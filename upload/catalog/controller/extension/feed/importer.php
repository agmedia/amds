<?php

class ControllerExtensionFeedImporter extends Controller {

    /**
     * http://www.amds.hr/index.php?route=extension/feed/importer/toCategoryFromExcel&key=v9jdnH6afskP92rdUatbcQQZLEu4QcW1
     * 
     * @throws \PhpOffice\PhpSpreadsheet\Reader\Exception
     */
    public function toCategoryFromExcel()
    {
        if ( ! $this->validateKey($this->request->get)) {
            $this->response->addHeader('Content-Type: application/json');
            $this->response->setOutput(collect(['error' => 'Unauthorized!'])->toJson());
            return;
        }

        // type category to import to.
        $category_to = 400;

        // prepare xlsx, convert rows to array.
        $inputFileName = DIR_STORAGE . 'upload/xls/ljetni_30.xlsx';
        $inputFileType = 'Xlsx';
        $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader($inputFileType);
        $spreadsheet = $reader->load($inputFileName);
        $worksheet = $spreadsheet->getActiveSheet();
        $rows = $worksheet->toArray();
        // unset title row
        unset($rows[0]);

        $query = '';
        foreach ($rows as $row) {
            $product = \Agmedia\Models\Product\Product::where('model', $row[5])->pluck('product_id')->first();

            if ($product) {
                $query .= '(' . $product . ', ' . $category_to . '),';
            }
        }

        $this->db->query("INSERT INTO " . DB_PREFIX . "product_to_category (product_id, category_id) VALUES " . substr($query, 0, -1) . ";");

        \Agmedia\Helpers\Log::store($query, 'excel');

        return $this->asJson([
            'status' => 200,
            'message' => 'Import uspješan.!!'
        ]);
    }


    /**
     * @param $key
     *
     * @return bool
     */
    private function validateKey($key)
    {
        if (isset($key['key']) && $key['key'] == IMPORT_KEY) {
            return true;
        }

        return false;
    }


    /**
     * @param $data
     */
    private function asJson($data)
    {
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($data));
    }

}

?>