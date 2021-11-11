<?php
class ControllerExtensionModuleDatabaseSpeedUp extends Controller {
    private $moduleName;
    private $moduleVersion;
    private $callModel;
    private $modulePath;
    private $moduleModel;
    private $extensionsLink;
    private $error = array();
    private $data = array();

    const CONFIG_FILE = 'isenselabs/databasespeedup_engine_config/config.ini';

	public function __construct($registry) {
        parent::__construct($registry);
        $this->config->load('isenselabs/databasespeedup');

        // Token
        $this->data['token_string'] = $this->config->get('databasespeedup_token_string');
        $this->data['user_token']        = $this->session->data[$this->data['token_string']];

        // Module VERSION
        $this->moduleVersion = $this->config->get('databasespeedup_version');

        /* OC version-specific declarations - Begin */
        $this->moduleName        = $this->config->get('databasespeedup_module_name');
        $this->callModel         = $this->config->get('databasespeedup_model');
        $this->modulePath        = $this->config->get('databasespeedup_path');
        $this->extensionsLink    = $this->url->link($this->config->get('databasespeedup_link'), 'user_token=' . $this->session->data['user_token'].$this->config->get('databasespeedup_link_params'), 'SSL');
        /* OC version-specific declarations - End */

        /* Module-specific declarations - Begin */
        $this->language_variables = $this->load->language($this->modulePath);
        foreach ($this->language_variables as $code => $languageVariable) {

            if ($code == 'text_isense_db_missing_orders_helper') {
                    $languageVariable = sprintf($languageVariable, date("Y-m-d", strtotime('-2 months')));
            }

		    $this->data[$code] = $languageVariable;
		}

        $this->load->model($this->modulePath);
        $this->moduleModel = $this->{$this->callModel};

        // Multi-Store
        $this->load->model('setting/store');
        // Settings
        $this->load->model('setting/setting');
        // Multi-Lingual
        $this->load->model('localisation/language');

		$this->load->model('catalog/category');

        // Variables
        $this->data['moduleName'] 		= $this->moduleName;
        $this->data['modulePath']       = $this->modulePath;
        /* Module-specific loaders - End */

        // Store Data
		if (!isset($this->request->get['store_id'])) {
            $this->request->get['store_id'] = 0;
            $this->store_id = $this->request->get['store_id'];
        } else if (isset($this->request->get['store_id'])) {
            $this->store_id = (int) $this->request->get['store_id'];
        } else {
            $this->store_id = 0;
        }
    }

    public function index() {

        // Title
        $this->data['heading_title'] = $this->data['heading_title'] . ' ' . $this->moduleVersion;
        $this->document->setTitle($this->data['heading_title']);

		$this->data['store'] = $this->getCurrentStore($this->store_id);

        $this->data['stores'] = array_merge(array(0 => $this->getCurrentStore(0)), $this->model_setting_store->getStores());

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
            if (!empty($this->request->post['OaXRyb1BhY2sgLSBDb21'])) {
				$this->request->post[$this->moduleName]['LicensedOn'] = $this->request->post['OaXRyb1BhY2sgLSBDb21'];
			}
			if (!empty($this->request->post['cHRpbWl6YXRpb24ef4fe'])) {
				$this->request->post[$this->moduleName]['License'] = json_decode(base64_decode($this->request->post['cHRpbWl6YXRpb24ef4fe']),true);
			}

            $this->update_ini_file($this->request->post, DIR_CONFIG . self::CONFIG_FILE);

            if ($this->request->post[($this->moduleName)]['status'] == '1'){
               $this->model_setting_setting->editSetting(strtolower($this->moduleName).'_status', array(($this->moduleName).'_status' => 1));
               $this->model_setting_setting->editSetting('module_'.strtolower($this->moduleName).'_status', array('module_'.($this->moduleName).'_status' => 1));

            } else{
               $this->model_setting_setting->editSetting('module_'.($this->moduleName).'_status', array('module_'.($this->moduleName).'_status' => 0));
               $this->model_setting_setting->editSetting(($this->moduleName).'_status', array(($this->moduleName).'_status' => 0));
            }

			$this->model_setting_setting->editSetting($this->moduleName, $this->request->post, $this->store_id);

			$this->session->data['success'] = $this->language->get('text_success');

            $this->response->redirect($this->url->link($this->modulePath, $this->data['token_string'] . '=' . $this->data['user_token'] . '&store_id='.$this->store_id, 'SSL'));
		}

        // Sucess & Error messages
		if (isset($this->session->data['success'])) {
			$this->data['success'] = $this->session->data['success'];
			unset($this->session->data['success']);
		} else {
			$this->data['success'] = '';
		}

		if (isset($this->error['warning'])) {
			$this->data['error_warning'] = $this->error['warning'];
		} else if(isset($this->session->data['warning'])){
            $this->data['error_warning'] = $this->session->data['warning'];
        	unset($this->session->data['warning']);
        } else {
			$this->data['error_warning'] = '';
		}

		// Breadcrumbs
  		$this->data['breadcrumbs'] = array();
   		$this->data['breadcrumbs'][] = array(
       		'text'      => $this->language->get('text_home'),
			'href'      => $this->url->link('common/dashboard', $this->data['token_string'] . '=' . $this->data['user_token'], 'SSL'),
      		'separator' => false
   		);
   		$this->data['breadcrumbs'][] = array(
       		'text'      => $this->language->get('text_module'),
			'href'      => $this->extensionsLink,
      		'separator' => ' :: '
   		);
   		$this->data['breadcrumbs'][] = array(
       		'text'      => $this->language->get('heading_title'),
			'href'      => $this->url->link($this->modulePath, $this->data['token_string'] . '=' . $this->data['user_token'], 'SSL'),
      		'separator' => ' :: '
   		);

        // Variables for the view
		$this->data['languages']                = $this->model_localisation_language->getLanguages();
		foreach ($this->data['languages'] as $key => $value) {
			if(version_compare(VERSION, '2.2.0.0', "<")) {
				$this->data['languages'][$key]['flag_url'] = 'view/image/flags/'.$this->data['languages'][$key]['image'];
			} else {
				$this->data['languages'][$key]['flag_url'] = 'language/'.$this->data['languages'][$key]['code'].'/'.$this->data['languages'][$key]['code'].'.png"';
			}
		}
		$this->data['action']					= $this->url->link($this->modulePath, $this->data['token_string'] . '=' . $this->data['user_token'] . '&store_id=' . $this->store_id, 'SSL');
		$this->data['cancel']					= $this->extensionsLink;
        $this->data['config_language_id']       = $this->config->get('config_language_id');
        $this->data['moduleSettings']			= $this->model_setting_setting->getSetting($this->moduleName, $this->store_id);

        $this->data['moduleData']				= (isset($this->data['moduleSettings'][$this->moduleName])) ? $this->data['moduleSettings'][$this->moduleName] : array();
		$this->data['storeId']					= $this->store_id;

        $this->data['memcache_engine_exists'] = false;
        $this->data['memcached_engine_exists'] = false;
        $this->data['redis_engine_exists'] = false;

        if (class_exists('Memcache')) {
            $this->data['memcache_engine_exists'] = true;
        }

        if (class_exists('Memcached')) {
            $this->data['memcached_engine_exists'] = true;
        }

        if (class_exists('Redis')) {
            $this->data['redis_engine_exists'] = true;
        }

        // Check for missing orders
        $this->data['have_missing_order'] = false;
        $missing_orders = !empty($this->{$this->callModel}->checkMissingOrders()->num_rows) ? $this->{$this->callModel}->checkMissingOrders() : '';

        if (!empty($missing_orders)) {
            $this->data['have_missing_order'] = true;
            $this->data['missing_orders_count'] = $missing_orders->num_rows;
        }

		// License data
		$this->data['licensedData']				= empty($this->data['moduleData']['LicensedOn']) ? base64_decode('ICAgIDxkaXYgY2xhc3M9ImFsZXJ0IGFsZXJ0LWRhbmdlciBmYWRlIGluIj4NCiAgICAgICAgPGJ1dHRvbiB0eXBlPSJidXR0b24iIGNsYXNzPSJjbG9zZSIgZGF0YS1kaXNtaXNzPSJhbGVydCIgYXJpYS1oaWRkZW49InRydWUiPsOXPC9idXR0b24+DQogICAgICAgIDxoND5XYXJuaW5nISBZb3UgYXJlIHJ1bm5pbmcgdW5saWNlbnNlZCB2ZXJzaW9uIG9mIHRoZSBtb2R1bGUhPC9oND4NCiAgICAgICAgPHA+WW91IGFyZSBydW5uaW5nIGFuIHVubGljZW5zZWQgdmVyc2lvbiBvZiB0aGlzIG1vZHVsZSEgWW91IG5lZWQgdG8gZW50ZXIgeW91ciBsaWNlbnNlIGNvZGUgdG8gZW5zdXJlIHByb3BlciBmdW5jdGlvbmluZywgYWNjZXNzIHRvIHN1cHBvcnQgYW5kIHVwZGF0ZXMuPC9wPjxkaXYgc3R5bGU9ImhlaWdodDo1cHg7Ij48L2Rpdj4NCiAgICAgICAgPGEgY2xhc3M9ImJ0biBidG4tZGFuZ2VyIiBocmVmPSJqYXZhc2NyaXB0OnZvaWQoMCkiIG9uY2xpY2s9IiQoJ2FbaHJlZj0jaXNlbnNlLXN1cHBvcnRdJykudHJpZ2dlcignY2xpY2snKSI+RW50ZXIgeW91ciBsaWNlbnNlIGNvZGU8L2E+DQogICAgPC9kaXY+') : '';
		$hostname = (!empty($_SERVER['HTTP_HOST'])) ? $_SERVER['HTTP_HOST'] : '' ;
		$hostname = (strstr($hostname,'http://') === false) ? 'http://' . $hostname : $hostname;
		$this->data['domain']					= base64_encode($hostname);
		$this->data['domainRaw']				= $hostname;
		$this->data['timeNow']					= time();
        $this->data['mid']                      = '9AS5RN3MAY';
		$this->data['licenseEncoded']			= !empty($this->data['moduleData']['License']) ? base64_encode(json_encode($this->data['moduleData']['License'])) : '';
		$this->data['supportTicketLink']		= 'http://isenselabs.com/tickets/open/' . base64_encode('Support Request') . '/' . base64_encode('472'). '/' . base64_encode($_SERVER['SERVER_NAME']);
		$this->data['licenseExpireDate'] 		= !empty($this->data['moduleData']['License']) ? date("F j, Y", strtotime($this->data['moduleData']['License']['licenseExpireDate'])) : "";

		$this->data['moduleEnabled']			= !empty($this->data['moduleData']['status']) && $this->data['moduleData']['status'] == 1 ? true : false;

		// View variables
		$this->data['header']                   = $this->load->controller('common/header');
		$this->data['column_left']              = $this->load->controller('common/column_left');
		$this->data['footer']                   = $this->load->controller('common/footer');

        $this->data['support']              = $this->load->view($this->modulePath . '/tab_support', $this->data);
        $this->data['dashboard']            = $this->load->view($this->modulePath . '/tab_dashboard', $this->data);

		$this->response->setOutput($this->load->view($this->modulePath.'/'. $this->moduleName, $this->data));
	}

    protected function validate() {

        if (!$this->user->hasPermission('modify', $this->modulePath)) {
            $this->error['warning'] = $this->language->get('error_permission');
        }
        return !$this->error;
    }

	public function install() {
	    $this->{$this->callModel}->install();
    }

	public function uninstall() {
        $this->{$this->callModel}->uninstall();
    }

    public function clearCache(){
        $json = array();

        if ($this->request->post['status'] == "0") {
            $json['error']['status'] = 'disabled';
            $this->session->data['warning'] = $this->language->get('error_clear_cache');
        } else{
            if (\databasespeedup\cache::purge()) {
                $this->session->data['success'] = $this->language->get('text_success_clear_cache');
                $json['success'] = true;
            }else{
                $this->session->data['warning'] = $this->language->get('text_warning_clear_cache');
                $json['success'] = false;
            }
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function clearMissingOrders(){
        $json = array();

        $missing_orders = !empty($this->{$this->callModel}->checkMissingOrders()->num_rows) ? $this->{$this->callModel}->checkMissingOrders() : '';

        $this->{$this->callModel}->deleteMissingOrders($missing_orders->rows);

        $this->session->data['success'] = $this->language->get('text_success_missing_orders');
        $json['success'] = true;

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function loadEngineTemplates(){

        $this->data['moduleSettings']			= $this->model_setting_setting->getSetting($this->moduleName, $this->store_id);

        $this->data['moduleData']				= (isset($this->data['moduleSettings'][$this->moduleName])) ? $this->data['moduleSettings'][$this->moduleName] : array();

        $this->data['db_prefix']				= DB_PREFIX;

        if ($this->request->get['engine'] == 'file') {

            //Cache statistics
            $cache_engine = !empty($this->data['moduleData']['engine']) ? $this->data['moduleData']['engine'] : '';

            if (!empty($cache_engine) && $cache_engine == 'file') {
                if (is_dir(defined('DIR_STORAGE') ? DIR_STORAGE : DIR_CACHE . 'databasespeedup_cache_file')) {
                    $count_files = new FilesystemIterator((defined('DIR_STORAGE') ? DIR_STORAGE : DIR_CACHE) . 'databasespeedup_cache_file');
                    $this->data['moduleData'][$cache_engine]['current_files_count'] = iterator_count($count_files);
                }
            }

            $this->response->setOutput($this->load->view(($this->modulePath .'/engine/' . $this->request->get['engine']), $this->data));
        }
        if ($this->request->get['engine'] == 'memcached') {
            $this->response->setOutput($this->load->view(($this->modulePath .'/engine/'. $this->request->get['engine']), $this->data));
        }
        if ($this->request->get['engine'] == 'memcache') {
            $this->response->setOutput($this->load->view(($this->modulePath .'/engine/' . $this->request->get['engine']), $this->data));
        }
        if ($this->request->get['engine'] == 'redis') {
            $this->response->setOutput($this->load->view(($this->modulePath .'/engine/' . $this->request->get['engine']), $this->data));
        }
    }

	private function getCatalogURL() {
        if (isset($_SERVER['HTTPS']) && (($_SERVER['HTTPS'] == 'on') || ($_SERVER['HTTPS'] == '1'))) {
            $storeURL = HTTPS_CATALOG;
        } else {
            $storeURL = HTTP_CATALOG;
        }
        return $storeURL;
    }

    private function getCurrentStore($store_id) {
        if($store_id && $store_id != 0) {
            $store = $this->model_setting_store->getStore($store_id);
        } else {
            $store['store_id'] = 0;
            $store['name'] = $this->config->get('config_name');
            $store['url'] = $this->getCatalogURL();
        }
        return $store;
    }

    private function update_ini_file($data, $filepath) {
		$content = "";

		$parsed_ini = parse_ini_file(DIR_CONFIG . self::CONFIG_FILE, true);

        $content .= "[main]". PHP_EOL;
		foreach($data["databasespeedup"] as $post_section => $post_value){
            if ($post_section == 'LicensedOn' || $post_section == 'License' || $post_section == 'LicenseCode' || $post_section == 'engine_file' || $post_section == 'engine_redis' || $post_section == 'engine_memcached' || $post_section == 'engine_memcache') {
                continue;
            }

            $content .= $post_section."=".$post_value . PHP_EOL;
		}

        if ($data["databasespeedup"]['engine'] == 'file') {
            $content .= PHP_EOL;
            $content .= "[engine_file]". PHP_EOL;
            $content .= PHP_EOL;

            foreach($data["databasespeedup"] as $post_section => $post_value){
                if ($post_section == 'engine_file') {
                    foreach ($post_value as $key => $value) {
                        $content .= $key."=".$value . PHP_EOL;
                    }
                }
    		}
        }

        if ($data["databasespeedup"]['engine'] == 'memcached') {
            $content .= PHP_EOL;
            $content .= "[engine_memcached]". PHP_EOL;
            $content .= PHP_EOL;

            foreach($data["databasespeedup"] as $post_section => $post_value){
                if ($post_section == 'engine_memcached') {
                    foreach ($post_value as $key => $value) {
                        $content .= $key."=".$value . PHP_EOL;
                    }
                }
    		}
        }

        if ($data["databasespeedup"]['engine'] == 'memcache') {
            $content .= PHP_EOL;
            $content .= "[engine_memcache]". PHP_EOL;
            $content .= PHP_EOL;

            foreach($data["databasespeedup"] as $post_section => $post_value){
                if ($post_section == 'engine_memcache') {
                    foreach ($post_value as $key => $value) {
                        $content .= $key."=".$value . PHP_EOL;
                    }
                }
    		}
        }

        if ($data["databasespeedup"]['engine'] == 'redis') {
            $content .= PHP_EOL;
            $content .= "[engine_redis]". PHP_EOL;
            $content .= PHP_EOL;

            foreach($data["databasespeedup"] as $post_section => $post_value){
                if ($post_section == 'engine_redis') {
                    foreach ($post_value as $key => $value) {
                        if ($key == 'redis_password' && $value != '') {
                            $value = html_entity_decode($value);
                        }
                        $content .= $key."=".$value . PHP_EOL;
                    }
                }
    		}
        }

		//write it into file
		if (!$handle = fopen($filepath, 'w')) {
			return false;
		}

		$success = fwrite($handle, $content);
		fclose($handle);

		return $success;
	}
}
?>
