<?php
class ModelExtensionModuleDatabaseSpeedUp extends Model {
    private $data = array();
    private $modulePath;

    public function __construct($registry) {
        parent::__construct($registry);

        $this->config->load('isenselabs/databasespeedup');
        $this->modulePath = $this->config->get('databasespeedup_path');
        $this->moduleName        = $this->config->get('databasespeedup_module_name');

        if(version_compare(VERSION, '2.2.0.0', "<=")) {
            $this->ext = '.tpl';
        } else {
            $this->ext = '';
        }
    }

    public function install() {

    }

    public function uninstall() {

    }

    public function checkMissingOrders(){
        $query = $this->db->query("SELECT order_id FROM `" . DB_PREFIX . "order` WHERE order_status_id = 0 AND date_modified < date_sub(now(), interval 2 month)");

        if ($query->num_rows) {
            return $query;
        }else{
            return false;
        }
    }

    public function deleteMissingOrders($order_ids){

        $json = array();

        foreach ($order_ids as $order) {
            $this->db->query("DELETE FROM `" . DB_PREFIX . "order` WHERE order_id = '" . (int)$order['order_id'] . "'");
    		$this->db->query("DELETE FROM `" . DB_PREFIX . "order_product` WHERE order_id = '" . (int)$order['order_id'] . "'");
    		$this->db->query("DELETE FROM `" . DB_PREFIX . "order_option` WHERE order_id = '" . (int)$order['order_id'] . "'");
    		$this->db->query("DELETE FROM `" . DB_PREFIX . "order_voucher` WHERE order_id = '" . (int)$order['order_id'] . "'");
    		$this->db->query("DELETE FROM `" . DB_PREFIX . "order_total` WHERE order_id = '" . (int)$order['order_id'] . "'");
    		$this->db->query("DELETE FROM `" . DB_PREFIX . "order_history` WHERE order_id = '" . (int)$order['order_id'] . "'");
    		$this->db->query("DELETE `or`, ort FROM `" . DB_PREFIX . "order_recurring` `or`, `" . DB_PREFIX . "order_recurring_transaction` `ort` WHERE order_id = '" . (int)$order['order_id'] . "' AND ort.order_recurring_id = `or`.order_recurring_id");
    		$this->db->query("DELETE FROM `" . DB_PREFIX . "customer_transaction` WHERE order_id = '" . (int)$order['order_id'] . "'");
        }

    }
}
?>
