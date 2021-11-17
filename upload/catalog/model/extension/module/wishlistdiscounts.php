<?php
class ModelExtensionModuleWishlistDiscounts extends Model {

    public function getCustomers($store_id, $interval)
    {
        $query = $this->db->query("SELECT cw.customer_id, c.firstname, c.lastname, c.email, cw.date_added,
                (SELECT COUNT(*) from `" . DB_PREFIX . "wishlistdiscounts_mail_log` cwl WHERE cwl.customer_id = c.customer_id) as notified_count 
            FROM `" . DB_PREFIX . "customer_wishlist` cw 
                LEFT JOIN `" . DB_PREFIX . "customer` c ON cw.customer_id = c.customer_id 
            WHERE c.store_id = '" . (int)$store_id . "'
                AND DATE(cw.date_added) = DATE(DATE_SUB(NOW(), INTERVAL " . (int)$interval . " DAY))
            GROUP BY cw.customer_id
                HAVING notified_count = 0;");

        return $query->rows;
    }

    // AdminModelExtensionModuleWishlistDiscounts::getCustomerWishlist
    public function getCustomerWishlist($customer_id, $store_id)
    {
        $pr_ids = $this->db->query("SELECT product_id FROM " . DB_PREFIX . "customer_wishlist WHERE customer_id='" . (int)$customer_id . "'")->rows; 

        if (!empty($pr_ids)) {
            $implode = array();
            foreach ($pr_ids as $product_id) {
                $implode[] = "p.product_id = '" . $product_id['product_id'] . "'";
            } 

            $whereClause = !empty($implode) ? " (" . implode(" OR ", $implode) . ") AND " : '';
            $query =  $this->db->query("SELECT *, ss.name AS stock_status, pd.name AS product_name, p.price AS regular_price, ps.price AS special_price, p.product_id AS wishlist_product_id
                FROM " . DB_PREFIX . "product AS p 
                    LEFT JOIN " . DB_PREFIX . "product_description AS pd ON p.product_id=pd.product_id 
                    LEFT JOIN " . DB_PREFIX . "stock_status AS ss ON p.stock_status_id=ss.stock_status_id 
                    LEFT JOIN " . DB_PREFIX . "product_special AS ps ON p.product_id=ps.product_id 
                    LEFT JOIN " . DB_PREFIX . "product_to_store AS pts ON p.product_id=pts.product_id
                 WHERE " . $whereClause . "
                    pd.language_id='" . $this->config->get('config_language_id') . "'
                    AND pts.store_id ='" . (int)$store_id . "'
                    AND p.quantity > 0 
                    AND p.status !=0
                GROUP BY p.product_id;");

            return $query->rows;
        } 

        return array();
    }

    // AdminModelExtensionModuleWishlistDiscounts::isUniqueCode
    public function isUniqueCode($randomCode)
    {
        $query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "coupon` WHERE code='" . $this->db->escape($randomCode) . "'");

        if ($query->num_rows == 0) {
            return true;
        } else {
            return false;
        }
    }

    // AdminModelMarketingCoupon::addCoupon
    public function addCoupon($data)
    {
        $this->db->query("INSERT INTO " . DB_PREFIX . "coupon SET name = '" . $this->db->escape($data['name']) . "', code = '" . $this->db->escape($data['code']) . "', discount = '" . (float)$data['discount'] . "', type = '" . $this->db->escape($data['type']) . "', total = '" . (float)$data['total'] . "', logged = '" . (int)$data['logged'] . "', shipping = '" . (int)$data['shipping'] . "', date_start = '" . $this->db->escape($data['date_start']) . "', date_end = '" . $this->db->escape($data['date_end']) . "', uses_total = '" . (int)$data['uses_total'] . "', uses_customer = '" . (int)$data['uses_customer'] . "', status = '" . (int)$data['status'] . "', date_added = NOW()");

        $coupon_id = $this->db->getLastId();

        if (isset($data['coupon_product'])) {
            foreach ($data['coupon_product'] as $product_id) {
                $this->db->query("INSERT INTO " . DB_PREFIX . "coupon_product SET coupon_id = '" . (int)$coupon_id . "', product_id = '" . (int)$product_id . "'");
            }
        }

        if (isset($data['coupon_category'])) {
            foreach ($data['coupon_category'] as $category_id) {
                $this->db->query("INSERT INTO " . DB_PREFIX . "coupon_category SET coupon_id = '" . (int)$coupon_id . "', category_id = '" . (int)$category_id . "'");
            }
        }

        return $coupon_id;
    }

    // AdminModelExtensionModuleWishlistDiscounts::logCustomerNotification
    public function logCustomerNotification($customer_id)
    {
        $this->db->query("INSERT INTO `" . DB_PREFIX . "wishlistdiscounts_mail_log` SET customer_id = '" . (int)$customer_id . "', date_notified = '" . $this->db->escape(date('Y-m-d H:i:s')) . "'");
    }

    // AdminModelExtensionModuleWishlistDiscounts::logDiscount
    public function logDiscount($data, $store_id)
    {
        if($data['wishlist'] && !empty($data['wishlist'])) {
            foreach($data['wishlist'] as $product) { 
                if($this->db->query("SELECT * FROM " . DB_PREFIX . "wishlistdiscounts WHERE customer_id = '" . (int)$data['customer_id'] . "' AND product_id  = '" . (int)$product . "'")->num_rows > 0){
                    $this->db->query("UPDATE " . DB_PREFIX . "wishlistdiscounts 
                        SET coupon_id = '" . (int)$data['coupon_id'] . "', 
                        date_added = NOW()
                        WHERE customer_id='" . (int)$data['customer_id'] . "'
                        AND product_id  = '" .  (int)$product . "' 
                        AND store_id = '" . $store_id . "'"); 
                } else {
                    $this->db->query("INSERT INTO " . DB_PREFIX . "wishlistdiscounts 
                        SET 
                        customer_id = '" . (int)$data['customer_id'] . "',
                        product_id = '" . $product . "',    
                        coupon_id ='" . (int)$data['coupon_id'] .  "', 
                        date_added = NOW(),
                        store_id = '" . $store_id. "'");
                }
            }
        }
    }
}
