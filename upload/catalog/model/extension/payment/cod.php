<?php
class ModelExtensionPaymentCOD extends Model {
    public function getMethod($address, $total) {
        $this->load->language('extension/payment/cod');

        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "zone_to_geo_zone WHERE geo_zone_id = '" . (int)$this->config->get('payment_cod_geo_zone_id') . "' AND country_id = '" . (int)$address['country_id'] . "' AND (zone_id = '" . (int)$address['zone_id'] . "' OR zone_id = '0')");

        if ($this->config->get('payment_cod_total') > 0 && $this->config->get('payment_cod_total') > $total) {
            $status = false;
        } elseif (!$this->cart->hasShipping()) {
            $status = false;
        } elseif (!$this->config->get('payment_cod_geo_zone_id')) {
            $status = true;
        } elseif ($query->num_rows) {
            $status = true;
        } else {
            $status = false;
        }

        $method_data = array();


        if (isset($this->session->data['shipping_method']['code']) && $this->session->data['shipping_method']['code'] == 'weight.weight_5') {
            $ptitle = 'Plaćanje prilikom preuzimanja paketa BOX NOW';
            $pdescription = '<br>Plaćanje se vrši isključivo digitalnim plaćanjem, bez gotovine putem povenice koju dobijete u svojoj e-pošti i sms-u prije preuzimanja pakteta.';

        }else{


            $ptitle = $this->language->get('text_title');
            $pdescription='';
        }

        if ($status) {
            $method_data = array(
                'code'       => 'cod',
                'title'      => $ptitle,
                'description'      => $pdescription,
                'terms'      => '',
                'sort_order' => $this->config->get('payment_cod_sort_order')
            );
        }

        return $method_data;
    }
}
