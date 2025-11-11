<?php

use Agmedia\LuceedOpencartWrapper\Models\LOC_Customer;
use Agmedia\LuceedOpencartWrapper\Models\LOC_Order;
class ControllerExtensionPaymentWSPay extends Controller
{
    /** Helper: odabir endpointa */
    private function getGatewayUrl() {
        $test = $this->config->get('payment_wspay_test');
        return $test ? 'https://formtest.wspay.biz/Authorization.aspx'
            : 'https://form.wspay.biz/Authorization.aspx';
    }

    /** Helper: format za polje TotalAmount (zarez), npr. "3650,35" */
    private function formatAmountForForm($amount) {
        return number_format((float)$amount, 2, ',', '');
    }

    /** Helper: format za potpis (bez zareza/točaka), npr. 365035 */
    private function formatAmountForSignature($amount) {
        // 2 decimale, ukloni sve što nije znamenka
        $s = number_format((float)$amount, 2, ',', '');
        return preg_replace('/\D+/', '', $s);
    }

    /** Helper: SHA512 potpis */
    private function sha512($data) {
        return hash('sha512', $data);
    }

    /** Potpis za FORM (Version=2.0) */
    private function signForm($shopId, $secret, $cartId, $totalFormattedForSig) {
        // ShopID + SecretKey + ShoppingCartID + SecretKey + TotalAmount(sig) + SecretKey
        return $this->sha512($shopId . $secret . $cartId . $secret . $totalFormattedForSig . $secret);
    }

    /** Potpis za RETURN (browser povrat) i za server-to-server NOTIFY */
    private function signReturnOrNotify($shopId, $secret, $cartId, $success, $approvalCode) {
        // ShopID + SecretKey + ShoppingCartID + SecretKey + Success + SecretKey + ApprovalCode + SecretKey
        return $this->sha512($shopId . $secret . $cartId . $secret . $success . $secret . $approvalCode . $secret);
    }

    /** Sigurno čitanje JSON-a (za server-to-server notify) ili POST-a (za return) */
    private function readJsonBody() {
        $raw = file_get_contents('php://input');
        if (!$raw) return null;
        $json = json_decode($raw, true);
        return (json_last_error() === JSON_ERROR_NONE) ? $json : null;
    }

    /** ========== 1) PLAĆANJE – prikaz WSPay forme ========== */
    public function index() {
        $this->load->language('extension/payment/wspay');
        $this->load->model('checkout/order');

        if (empty($this->session->data['order_id'])) {
            return ''; // nema narudžbe
        }

        $order = $this->model_checkout_order->getOrder($this->session->data['order_id']);
        if (!$order) return '';

        $shopId    = $this->config->get('payment_wspay_merchant');
        $secretKey = $this->config->get('payment_wspay_password');
        $cartId    = (string)$order['order_id'];
        $totalForm = $this->formatAmountForForm($order['total']);
        $totalSig  = $this->formatAmountForSignature($order['total']);

        $data = [];
        $data['action']       = $this->getGatewayUrl();
        $data['Version']      = '2.0';
        $data['ShopID']       = $shopId;
        $data['ShoppingCartID'] = $cartId;
        $data['TotalAmount']  = $totalForm;
        $data['Language']     = 'HR';
        $data['Currency']     = $order['currency_code']; // npr. HRK/EUR
        $data['CustomerFirstName'] = $order['payment_firstname'];
        $data['CustomerLastName']  = $order['payment_lastname'];
        $data['CustomerAddress']   = $order['payment_address_1'];
        $data['CustomerCity']      = $order['payment_city'];
        $data['CustomerZIP']       = $order['payment_postcode'];
        $data['CustomerCountry']   = $order['payment_iso_code_2'];
        $data['CustomerPhone']     = $order['telephone'];
        $data['CustomerEmail']     = $order['email'];

        // Povratni URL-ovi (browser). Method=POST je uredniji; primarni izvor istine je NOTIFY.
        $data['ReturnMethod']     = 'POST';
        $data['ReturnURL']        = $this->url->link('extension/payment/wspay/return', '', true);
        $data['ReturnErrorURL']   = $this->url->link('extension/payment/wspay/error', '', true);
        $data['CancelURL']        = $this->url->link('extension/payment/wspay/cancel', '', true);

        // Server-to-server NOTIFY (Transaction report – CallbackURL) – konfigurira se u WSPay konzoli;
        // ipak ga šaljemo i ovdje ako WSPay form podržava prosljeđivanje (nije obavezno):
        $data['CallbackURL']      = $this->url->link('extension/payment/wspay/notify', '', true);

        // Potpis (SHA512)
        $data['Signature'] = $this->signForm($shopId, $secretKey, $cartId, $totalSig);

        // Gumb u checkoutu
        $data['button_confirm'] = $this->language->get('button_confirm');

        return $this->load->view('extension/payment/wspay', $data);
    }

    /** ========== 2) BROWSER POVRAT – ne presudno, ali korisno za UX ========== */
    public function return() {
        // WSPay pošalje Success, ApprovalCode, ShoppingCartID, Signature (POST ako smo stavili ReturnMethod=POST)
        $post = $this->request->post ?: $this->request->get;

        $shopId    = $this->config->get('payment_wspay_merchant');
        $secretKey = $this->config->get('payment_wspay_password');

        $cartId       = isset($post['ShoppingCartID']) ? (string)$post['ShoppingCartID'] : '';
        $success      = isset($post['Success']) ? (string)$post['Success'] : '0';
        $approvalCode = isset($post['ApprovalCode']) ? (string)$post['ApprovalCode'] : '';
        $signature    = isset($post['Signature']) ? (string)$post['Signature'] : '';

        $calc = $this->signReturnOrNotify($shopId, $secretKey, $cartId, $success, $approvalCode);

        if ($cartId && $success === '1' && !empty($approvalCode) && hash_equals($calc, $signature)) {
            $this->load->model('checkout/order');
            $this->model_checkout_order->addOrderHistory(
                (int)$cartId,
                1,
                '', true
            );
            $this->response->redirect($this->url->link('checkout/success'));
        } else {
            // ako je nešto sumnjivo, pošalji korisnika na checkout uz poruku
            $this->session->data['error'] = 'Plaćanje nije potvrđeno. Ako je iznos terećen, kontaktirajte podršku.';
            $this->response->redirect($this->url->link('checkout/checkout'));
        }
    }

    public function error() {
        // neuspješno plaćanje po browser povratu
        $this->session->data['error'] = 'Transakcija je odbijena.';
        $this->response->redirect($this->url->link('checkout/checkout'));
    }

    public function cancel() {
        $this->session->data['error'] = 'Plaćanje je otkazano.';
        $this->response->redirect($this->url->link('checkout/checkout'));
    }

    /** ========== 3) SERVER-TO-SERVER NOTIFY (glavni izvor istine) ========== */
    public function notify() {
        // WSPay šalje JSON (Transaction report – CallbackURL)
        $json = $this->readJsonBody();

        // neki setupovi znaju slati kao application/x-www-form-urlencoded; fallback:
        if ($json === null && !empty($this->request->post)) {
            $json = $this->request->post;
        }

        if (!$json || empty($json['ShoppingCartID'])) {
            $this->response->addHeader('HTTP/1.1 400 Bad Request');
            $this->response->setOutput('Missing payload');
            return;
        }

        $shopId    = $this->config->get('payment_wspay_merchant');
        $secretKey = $this->config->get('payment_wspay_password');

        $cartId       = (string)$json['ShoppingCartID'];
        $success      = isset($json['Success']) ? (string)$json['Success'] : '0';
        $approvalCode = isset($json['ApprovalCode']) ? (string)$json['ApprovalCode'] : '';
        $signature    = isset($json['Signature']) ? (string)$json['Signature'] : '';

        // Validacija potpisa
        $calc = $this->signReturnOrNotify($shopId, $secretKey, $cartId, $success, $approvalCode);
        if (!hash_equals($calc, $signature)) {
            $this->response->addHeader('HTTP/1.1 400 Bad Signature');
            $this->response->setOutput('Invalid signature');
            return;
        }

        // Ako je uspjeh i ApprovalCode postoji – zaključaj narudžbu i pošalji mail (bez sessiona!)
        if ($success === '1' && !empty($approvalCode)) {
            $this->load->model('checkout/order');
            $this->model_checkout_order->addOrderHistory(
                (int)$cartId,
                $this->config->get('payment_wspay_order_status_id'),
                'WSPay approval: ' . $approvalCode,
                true // email kupcu
            );
        }

        // Odgovor 200 OK (WSPay očekuje 200 da ne bi ponovno slali)
        $this->response->addHeader('Content-Type: text/plain; charset=utf-8');
        $this->response->setOutput('OK');
    }


    /** Push narudžbe u Luceed (idempotentno: preskače ako postoji luceed_uid) */
    private function pushToLuceed($order_id) {
        $this->load->model('checkout/order');
        $oc_order = $this->model_checkout_order->getOrder((int)$order_id);
        if (!$oc_order) return false;

        // Ako je već poslana u Luceed (ima UID), nemoj duplo
        if (!empty($oc_order['luceed_uid'])) return true;

        try {
            $order    = new LOC_Order($oc_order);
            $customer = new LOC_Customer($order->getCustomerData());

            \Agmedia\Helpers\Log::store($order, 'luceed_wspay');

            // provjeri količine po skladištima
            $has_qty = $order->collectProductsFromWarehouses();

            \Agmedia\Helpers\Log::store($has_qty ? 'Ima qty' : 'Nema qty', 'luceed_wspay');

            if (!$has_qty) {
                if (method_exists($order, 'recordError')) $order->recordError();
                return false;
            }

            if (!$customer->exist()) {
                $customer->store();
            }

            $sent = $order->setCustomerUid($customer->getUid())->store();

            \Agmedia\Helpers\Log::store($sent ? 'Success sent' : 'Error sent', 'luceed_wspay');

            if (!$sent) {
                if (method_exists($order, 'recordError')) $order->recordError();
                return false;
            }
        } catch (\Throwable $e) {
            if (class_exists('Log')) {
                $log = new Log('luceed_error.log');
                $log->write('Luceed push error for order ' . (int)$order_id . ': ' . $e->getMessage());
            }
            return false;
        }

        return true;
    }


    /** ========== 4) CRON fallback: StatusCheck za “missing/pending” ========== */
    public function statusCheck() {
        // 0) Zaštita URL-om s ključem (iz config.php)
        $key = isset($this->request->get['key']) ? $this->request->get['key'] : '';
        $cronKey = defined('WSPAY_CRON_KEY') ? WSPAY_CRON_KEY : '';
        if (empty($cronKey) || !hash_equals($cronKey, $key)) {
            $this->response->addHeader('HTTP/1.1 403 Forbidden');
            $this->response->setOutput('Forbidden');
            return;
        }

        // 1) Uzmimo baš "missing" (status 0 ili kako si definirao u configu)
        //    Preporuka: u config.php postavi define('WSPAY_MISSING_STATUS_ID', 0);
        $missing_status_id = defined('WSPAY_MISSING_STATUS_ID') ? (int)WSPAY_MISSING_STATUS_ID : 0;

        $orders = $this->db->query("
        SELECT order_id, total
        FROM `" . DB_PREFIX . "order`
        WHERE order_status_id = " . (int)$missing_status_id . "
        ORDER BY date_added DESC
        LIMIT 200
    ")->rows;

        if (!$orders) {
            $this->response->setOutput('No missing orders');
            return;
        }

        $shopId         = $this->config->get('payment_wspay_merchant');
        $secretKey      = $this->config->get('payment_wspay_password');
        $paid_status_id = 1;//(int)$this->config->get('payment_wspay_order_status_id');

        // 2) WSPay statusCheck endpoint (isti base; razlikovanje po ShopID-u)
        $apiBase = 'https://public.wspay.biz/api/services';

        $updated = 0;

        foreach ($orders as $o) {
            $cartId = (string)$o['order_id'];

            // 2.1) Potpis za statusCheck
            $sig = hash('sha512', $shopId . $secretKey . $cartId . $secretKey);

            $payload = json_encode([
                'Version'        => '2.0',
                'ShopID'         => $shopId,
                'ShoppingCartID' => $cartId,
                'Signature'      => $sig
            ]);

            // 2.2) cURL poziv prema WSPay
            $ch = curl_init($apiBase . '/statusCheck');
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST           => true,
                CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
                CURLOPT_POSTFIELDS     => $payload,
                CURLOPT_TIMEOUT        => 20,
            ]);
            $resp = curl_exec($ch);
            $http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($http !== 200 || !$resp) {
                continue;
            }

            $json = json_decode($resp, true);
            if (!$json) {
                continue;
            }

            // 2.3) Parsiraj odgovor: tražimo potvrdu naplate
            $success      = isset($json['Success']) ? (string)$json['Success'] : '0';
            $approvalCode = isset($json['ApprovalCode']) ? (string)$json['ApprovalCode'] : '';

            if ($success === '1' && !empty($approvalCode)) {
                $this->load->model('checkout/order');
                $order_info = $this->model_checkout_order->getOrder((int)$cartId);

                // 3) Ako je narudžba još uvijek MISSING (status = 0 ili onaj iz configa),
                //    sad PRVI PUT postavljamo status -> OC će poslati STANDARDAN "order confirmation" mail kupcu.
                if ((int)$order_info['order_status_id'] === (int)$missing_status_id) {
                    $this->model_checkout_order->addOrderHistory(
                        (int)$cartId,
                        $paid_status_id,
                        'WSPay approval (cron): ' . $approvalCode,
                        true // ← pošalji standardan mail kupcu (prvo postavljanje statusa)
                    );
                    $this->pushToLuceed((int)$cartId);
                    $updated++;
                }
                // Ako slučajno više nije missing (netko ga je ručno promijenio),
                // preskačemo da ne šaljemo dupli mail. Po potrebi ovdje možeš slati "update" mail.
            }
        }

        $this->response->setOutput('Updated: ' . (int)$updated);
    }

}
