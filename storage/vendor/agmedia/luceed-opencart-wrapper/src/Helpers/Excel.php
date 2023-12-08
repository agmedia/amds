<?php


namespace Agmedia\LuceedOpencartWrapper\Helpers;


use Agmedia\Helpers\Log;
use Agmedia\Models\Product\Product;
use Agmedia\Models\Product\ProductDescription;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

class Excel
{
    
    /**
     * @var array
     */
    protected $data;
    
    /**
     * @var Spreadsheet
     */
    private $sheet;
    
    /**
     * @var string
     */
    private $type;


    /**
     * @param string $type
     * @param array  $data
     */
    public function __construct(string $type = 'simple', array $data = [])
    {
        $this->data  = $data;
        $this->sheet = new Spreadsheet();
        $this->type = $type;
        
        $this->sheet->getProperties()->setCreator('AMDS')->setTitle($this->type);
    }
    
    
    /**
     * @param $type
     *
     * @return $this
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     */
    public function make()
    {
        if ($this->type == 'simple') {
            $this->setSimpleProducts();
        }
        
        return $this;
    }
    
    
    /**
     * @param      $type
     * @param null $name
     *
     * @return mixed
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     */
    public function response($type, $name = null)
    {
        if ( ! $name) {
            $name = $this->type . '_amds_' . time();
        }
        
        if ($type == 'stream') {
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment;filename="' . basename($name) . '.xlsx"');
            header('Cache-Control: max-age=0');
            // If you're serving to IE 9, then the following may be needed
            header('Cache-Control: max-age=1');
            // If you're serving to IE over SSL, then the following may be needed
            header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
            header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT'); // always modified
            header('Cache-Control: cache, must-revalidate'); // HTTP/1.1
            header('Pragma: public'); // HTTP/1.0
        }
        
        $writer = IOFactory::createWriter($this->sheet, 'Xlsx');
        
        return $writer->save('php://output');
    }

    /*******************************************************************************
    *                                Copyright : AGmedia                           *
    *                              email: filip@agmedia.hr                         *
    *******************************************************************************/

    /**
     * @return void
     */
    private function setSimpleProducts(): void
    {
        $i = 1;
        
        $this->sheet->getActiveSheet()->setCellValue('A' . $i, 'Šifra');
        $this->sheet->getActiveSheet()->setCellValue('B' . $i, 'Naziv');
        $this->sheet->getActiveSheet()->setCellValue('C' . $i, 'Kategorija');
        $this->sheet->getActiveSheet()->setCellValue('D' . $i, 'Kategorija 2');
        $this->sheet->getActiveSheet()->setCellValue('E' . $i, 'Kategorija 3');
        $this->sheet->getActiveSheet()->setCellValue('F' . $i, 'Kategorija 4');
        $this->sheet->getActiveSheet()->setCellValue('G' . $i, 'Kategorija 5');
        $this->sheet->getActiveSheet()->setCellValue('H' . $i, 'Kategorija 6');

        foreach ($this->data as $item) {
            $i++;

            $this->sheet->getActiveSheet()->setCellValue('A' . $i, $item['id']);
            $this->sheet->getActiveSheet()->setCellValue('B' . $i, $item['title']);
            $this->sheet->getActiveSheet()->setCellValue('C' . $i, (isset($item['categories'][0]) ? $item['categories'][0]['name'] : ''));
            $this->sheet->getActiveSheet()->setCellValue('D' . $i, (isset($item['categories'][1]) ? $item['categories'][1]['name'] : ''));
            $this->sheet->getActiveSheet()->setCellValue('E' . $i, (isset($item['categories'][2]) ? $item['categories'][2]['name'] : ''));
            $this->sheet->getActiveSheet()->setCellValue('F' . $i, (isset($item['categories'][3]) ? $item['categories'][3]['name'] : ''));
            $this->sheet->getActiveSheet()->setCellValue('G' . $i, (isset($item['categories'][4]) ? $item['categories'][4]['name'] : ''));
            $this->sheet->getActiveSheet()->setCellValue('H' . $i, (isset($item['categories'][5]) ? $item['categories'][5]['name'] : ''));
        }

        $this->sheet->getActiveSheet()->setTitle('Artikli-simple');
    }
    
}