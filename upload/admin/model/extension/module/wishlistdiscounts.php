<?php
class ModelExtensionModuleWishlistDiscounts extends Model {

    public function __construct($register) {

        if (!defined('IMODULE_ROOT')) define('IMODULE_ROOT', substr(DIR_APPLICATION, 0, strrpos(DIR_APPLICATION, '/', -2)) . '/');
        if (!defined('IMODULE_SERVER_NAME')) define('IMODULE_SERVER_NAME', substr((defined('HTTP_CATALOG') ? HTTP_CATALOG : HTTP_SERVER), 7, strlen((defined('HTTP_CATALOG') ? HTTP_CATALOG : HTTP_SERVER)) - 8));
        parent::__construct($register);

        // Upgrade from 2.x to OC > 2.1.x 

        $this->db->query("CREATE TABLE IF NOT EXISTS " . DB_PREFIX . "wishlistdiscounts (
            wishlist_discount_id INT(11) NOT NULL AUTO_INCREMENT,
            customer_id INT(11) NOT NULL DEFAULT 0, 
            coupon_id INT(11) NULL DEFAULT NULL, 
            date_added DATE NULL DEFAULT NULL,
            product_id int(11) NOT NULL,
            store_id INT(11) NOT NULL,
            PRIMARY KEY (wishlist_discount_id))");

        $check_for_store_id = $this->db->query("SHOW COLUMNS FROM " .DB_PREFIX . "wishlistdiscounts LIKE 'store_id'");
        if ($check_for_store_id->num_rows==0) {
            $query = $this->db->query("ALTER TABLE " .DB_PREFIX . "wishlistdiscounts ADD store_id int(1) NOT NULL DEFAULT '0' AFTER coupon_id");
        } 

        $wishlist_discounts_content = $this->db->query("SELECT * FROM " . DB_PREFIX . "wishlistdiscounts");

        if($wishlist_discounts_content->num_rows > 0) { 

            if(!isset($wishlist_discounts_content->row['product_id'])) {

                $query = $this->db->query("ALTER TABLE " .DB_PREFIX . "wishlistdiscounts ADD product_id int(11) NOT NULL AFTER coupon_id");

                foreach($wishlist_discounts_content->rows as $wishlist_content) {

                    if(isset($wishlist_content['wishlist'])) {

                        unset($wishlist_product_ids);
                        $wishlist_product_ids = unserialize($wishlist_content['wishlist']);

                        foreach($wishlist_product_ids as $product_id) {

                            $query = $this->db->query("INSERT INTO " . DB_PREFIX . "wishlistdiscounts 
                                SET customer_id = '" . (int)$wishlist_content['customer_id'] . "',
                                product_id = '" . (int)$product_id . "', 	
                                coupon_id = '" . (int)$wishlist_content['coupon_id'] .  "', 
                                date_added ='" . $wishlist_content['date_added'] .  "', 
                                store_id = '". $wishlist_content['store_id']. "'");
                        }

                        $query = $this->db->query("DELETE FROM " .DB_PREFIX . "wishlistdiscounts WHERE wishlist_discount_id=" . $wishlist_content['wishlist_discount_id']);
                    }
                }

                if(isset($wishlist_discounts_content->row['wishlist'])) {
                    $query = $this->db->query("ALTER TABLE " .DB_PREFIX . "wishlistdiscounts DROP wishlist");
                }
            }
        }
    }

    public function getCustomerName($customer_id)
    {
        $query = $this->db->query("SELECT firstname, lastname, email FROM `".DB_PREFIX."customer` WHERE customer_id = ".(int)$customer_id);
        return $query->num_rows ? $query->row : false;
    }

    public function getCustomersWithWishList($data=array(), $store_id) {

        if($data){
            if($data['sort'] == 'name') {
                $data['sort'] = 'c.firstname ' . $data['order'] . ', c.lastname';
            }
            $orderClause = "ORDER BY " . $data['sort'] . " ". $data['order'] . " LIMIT " . $data['start'] . ", " . $data['limit'];
        } else {
            $orderClause = '';
        }

        $query = $this->db->query( "SELECT  *, c.date_added as date_added, c.customer_id AS customer_id, (SELECT COUNT(*) from `".DB_PREFIX."wishlistdiscounts_mail_log` WHERE c.customer_id = customer_id) as notified_count 
            FROM " . DB_PREFIX . "customer_wishlist AS cw 
            LEFT JOIN " . DB_PREFIX . "customer AS c ON cw.customer_id=c.customer_id 
            WHERE c.store_id ='" . $store_id . "'
            GROUP BY cw.customer_id " . $orderClause);		

        return $query->rows;
    }

    public function gethWishListArchive($data=array(), $store_id) {

        if($data){
            if($data['sort'] == 'name') {
                $data['sort'] = 'c.firstname ' . $data['order'] . ', c.lastname';
            }
            $orderClause = "ORDER BY " . $data['sort'] . " ". $data['order'] . " LIMIT " . $data['start'] . ", " . $data['limit'];
        } else {
            $orderClause = '';
        }

        $query = $this->db->query( "SELECT  *, c.date_added as date_added, c.customer_id AS customer_id 
            FROM " . DB_PREFIX . "customer_wishlist AS cw 
            LEFT JOIN " . DB_PREFIX . "customer AS c ON cw.customer_id=c.customer_id
            LEFT JOIN " . DB_PREFIX . "wishlistdiscounts AS wd ON (cw.customer_id=wd.customer_id AND cw.product_id=wd.product_id)
            WHERE c.store_id ='" . $store_id . "'
            AND wd.wishlist_discount_id IS NOT NULL
            GROUP BY cw.customer_id " . $orderClause);			

        return $query->rows;
    }

    public function getTotalCustomersWithWishList($store_id) {
        $query = $this->db->query("	SELECT * 
            FROM " . DB_PREFIX . "customer_wishlist AS cw 
            LEFT JOIN " . DB_PREFIX . "customer AS c ON cw.customer_id=c.customer_id
            WHERE c.store_id ='" . $store_id . "'
            GROUP BY cw.customer_id");		

        return $query->num_rows;

    }

    public function getTotalWishListArchive($store_id) {
        $query = $this->db->query("	SELECT *
            FROM " . DB_PREFIX . "customer_wishlist AS cw 
            LEFT JOIN " . DB_PREFIX . "customer AS c ON cw.customer_id=c.customer_id
            WHERE c.store_id ='" . $store_id . "'
            GROUP BY cw.customer_id");		
        return $query->num_rows;
    }

    public function getCustomerWishlist($customer_id, $store_id) {
        $pr_ids = $this->db->query("SELECT product_id FROM " . DB_PREFIX . "customer_wishlist WHERE customer_id='".(int)$customer_id . "'")->rows; 

        $implode     = array();
        $whereClause = '';
        if (!empty($pr_ids)) {
            foreach ($pr_ids as $product_id) {
                $implode[] = "p.product_id = '" . $product_id['product_id'] . "'";
            } 

            $whereClause = "WHERE (" . implode(" OR ", $implode) . ")";
            $query =  $this->db->query("SELECT *, ss.name AS stock_status, pd.name AS product_name, p.price AS regular_price, ps.price AS special_price, p.product_id AS wishlist_product_id
                FROM " . DB_PREFIX . "product AS p 
                JOIN " . DB_PREFIX . "product_description  AS pd ON p.product_id=pd.product_id 
                LEFT JOIN " . DB_PREFIX . "stock_status AS ss ON p.stock_status_id=ss.stock_status_id 
                LEFT JOIN " . DB_PREFIX . "product_special AS ps ON p.product_id=ps.product_id 
                LEFT JOIN " . DB_PREFIX . "product_to_store AS pts ON p.product_id=pts.product_id
                ". $whereClause . " AND pd.language_id='" . $this->config->get('config_language_id') . "' AND pts.store_id ='" . $store_id . "' AND p.quantity > 0 AND p.status !=0 GROUP BY p.product_id " );
            return $query->rows;
        } 
        return array();
    }

    public function getCustomers($customers, $store_id) {
        if(!empty($customers)) {  
            $in = implode(',',$customers);
            $whereClause = "WHERE customer_id IN ( $in ) AND store_id ='" . $store_id . "'";  
            $query =  $this->db->query("SELECT * FROM " . DB_PREFIX . "customer " . $whereClause);
            return $query->rows;  
        } else {
            return array();
        }
    }

    public function isUniqueCode($randomCode) {
        $query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "coupon` WHERE code='" . $this->db->escape($randomCode) . "'");
        if ($query->num_rows == 0) {
            return true;
        } else {
            return false;
        }			
    }

    public function getGivenCoupons($data=array(), $store_id) {
        $givenCoupons = $this->db->query("SELECT *
            FROM " . DB_PREFIX . "coupon as c
            JOIN `" . DB_PREFIX . "wishlistdiscounts` as wd ON wd.coupon_id = c.coupon_id
            WHERE c.name LIKE  '%WishlistDiscount [%'											
            AND wd.store_id ='" . (int)$store_id . "'
            GROUP BY wd.coupon_id 
            ORDER BY c." . $data['sort'] . " ". $data['order'] . " 
            LIMIT " . $data['start'].", " . $data['limit'] );										 
        return $givenCoupons->rows;
    }

    public function getTotalGivenCoupons($store_id) {
        $givenCoupons = $this->db->query("SELECT * FROM " . DB_PREFIX . "coupon as c
            LEFT JOIN `" . DB_PREFIX . "wishlistdiscounts` as wd ON wd.coupon_id = c.coupon_id
            WHERE c.name LIKE '%WishlistDiscount [%'
            AND wd.store_id ='" . $store_id . "'
            GROUP BY c.coupon_id "); 

        return $givenCoupons->num_rows;

    }

    public function getUsedCoupons($data=array(), $store_id ) {
        $usedCoupons = $this->db->query("SELECT *
            FROM `" . DB_PREFIX . "coupon` AS c
            JOIN `" . DB_PREFIX . "coupon_history` AS ch ON c.coupon_id=ch.coupon_id
            JOIN `" . DB_PREFIX . "wishlistdiscounts` as wd ON wd.coupon_id = c.coupon_id
            WHERE name LIKE  '%WishlistDiscount [%'
            AND wd.store_id ='" . $store_id . "'
            GROUP BY wd.coupon_id 
            ORDER BY " . $data['sort'] . " ". $data['order'] . " 
            LIMIT " . $data['start'].", " . $data['limit'] );
        return $usedCoupons->rows;
    }

    public function getTotalUsedCoupons($store_id ) {
        $givenCoupons = $this->db->query("SELECT * FROM `" . DB_PREFIX . "coupon` as c
            JOIN `" . DB_PREFIX . "coupon_history` AS ch ON c.coupon_id=ch.coupon_id
            JOIN `" . DB_PREFIX . "wishlistdiscounts` AS wd ON c.coupon_id=wd.coupon_id
            WHERE name LIKE  '%WishlistDiscount [%'  
            AND wd.store_id ='" . $store_id . "'
            GROUP BY wd.coupon_id ");

        return $givenCoupons->num_rows;

    }

    public function logDiscount($data, $store_id) {
        if($data['wishlist'] && !empty($data['wishlist'])) {
            foreach($data['wishlist'] as $product) { 
                if($this->db->query("SELECT * FROM " . DB_PREFIX . "wishlistdiscounts WHERE customer_id = '" .  (int)$data['customer_id']  . "' AND product_id  = '" .  (int)$product  . "'")->num_rows > 0){
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
                        store_id = '". $store_id. "'");
                }
            }
        }
    }

    public function install() {
        $this->db->query("CREATE TABLE IF NOT EXISTS " . DB_PREFIX . "wishlistdiscounts (
            wishlist_discount_id INT(11) NOT NULL AUTO_INCREMENT,
            customer_id INT(11) NOT NULL DEFAULT 0, 
            coupon_id INT(11) NULL DEFAULT NULL, 
            date_added DATE NULL DEFAULT NULL,
            product_id int(11) NOT NULL,
            store_id INT(11) NOT NULL,
            PRIMARY KEY (wishlist_discount_id))");

    }

    public function logCustomerNotification($customer_id)
    {
        $this->db->query("INSERT INTO `" . DB_PREFIX . "wishlistdiscounts_mail_log` SET customer_id = '".(int)$customer_id."', date_notified = '".$this->db->escape(date('Y-m-d H:i:s'))."'");
    }

    public function update()
    {
        $this->db->query("CREATE TABLE IF NOT EXISTS `".DB_PREFIX."wishlistdiscounts_mail_log` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `customer_id` int(11) NOT NULL,
            `date_notified` datetime NOT NULL,
            PRIMARY KEY (`id`)
        ) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;"
        );
    }

    public function uninstall() {
        $this->db->query("DROP TABLE IF EXISTS " . DB_PREFIX . "wishlistdiscounts");
        $this->db->query("DROP TABLE IF EXISTS " . DB_PREFIX . "wishlistdiscounts_mail_log");
    }

    public function moveWishlistToCart($customer_id, $store_id) {
        $query = $this->db->query("SELECT wishlist, cart FROM "  . DB_PREFIX  . "customer WHERE customer_id='" . (int)$customer_id."' AND store_id ='" . $store_id . "'");
        $wishlist = $query->row['wishlist'];
        $cart = $query->row['cart'];
        $query = $this->db->query("UPDATE "  . DB_PREFIX  . "customer SET cart='"  . $wishlist . "' WHERE customer_id='" . (int)$customer_id."' AND store_id ='" . $store_id . "'");	
    }
}
?>
