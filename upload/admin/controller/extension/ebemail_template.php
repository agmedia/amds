<?php
class ControllerExtensionEbemailTemplate extends Controller {
	private $error = array();

	public function index() {
		$this->load->language('extension/ebemail_template');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('extension/ebemail_template');

		$this->getList();
	}

	public function add() {
		$this->load->language('marketing/coupon');
		$this->load->language('extension/ebemail_template');

		$this->document->setTitle($this->language->get('heading_title'));
		
		$this->load->model('extension/ebemail_template');

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateForm()) {
			$this->model_extension_ebemail_template->addEmailTemplate($this->request->post);

			$this->session->data['success'] = $this->language->get('text_success');

			$url = '';

			if (isset($this->request->get['sort'])) {
				$url .= '&sort=' . $this->request->get['sort'];
			}

			if (isset($this->request->get['order'])) {
				$url .= '&order=' . $this->request->get['order'];
			}

			if (isset($this->request->get['page'])) {
				$url .= '&page=' . $this->request->get['page'];
			}

			$this->response->redirect($this->url->link('extension/ebemail_template', 'user_token=' . $this->session->data['user_token'] . $url, true));
		}

		$this->getForm();
	}

	public function edit() {
		$this->load->language('marketing/coupon');
		$this->load->language('extension/ebemail_template');

		$this->document->setTitle($this->language->get('heading_title'));
		
		$this->load->model('extension/ebemail_template');

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateForm()) {
			$this->model_extension_ebemail_template->editEmailTemplate($this->request->get['abandonedcart_email_template_id'], $this->request->post);

			$this->session->data['success'] = $this->language->get('text_success');

			$url = '';

			if (isset($this->request->get['sort'])) {
				$url .= '&sort=' . $this->request->get['sort'];
			}

			if (isset($this->request->get['order'])) {
				$url .= '&order=' . $this->request->get['order'];
			}

			if (isset($this->request->get['page'])) {
				$url .= '&page=' . $this->request->get['page'];
			}

			$this->response->redirect($this->url->link('extension/ebemail_template', 'user_token=' . $this->session->data['user_token'] . $url, true));
		}

		$this->getForm();
	}

	public function delete() {
		$this->load->language('extension/ebemail_template');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('extension/ebemail_template');

		if (isset($this->request->post['selected']) && $this->validateDelete()) {
			foreach ($this->request->post['selected'] as $abandonedcart_email_template_id) {
				$this->model_extension_ebemail_template->deleteEmailTemplate($abandonedcart_email_template_id);
			}

			$this->session->data['success'] = $this->language->get('text_success');

			$url = '';

			if (isset($this->request->get['sort'])) {
				$url .= '&sort=' . $this->request->get['sort'];
			}

			if (isset($this->request->get['order'])) {
				$url .= '&order=' . $this->request->get['order'];
			}

			if (isset($this->request->get['page'])) {
				$url .= '&page=' . $this->request->get['page'];
			}

			$this->response->redirect($this->url->link('extension/ebemail_template', 'user_token=' . $this->session->data['user_token'] . $url, true));
		}

		$this->getList();
	}

	protected function getList() {
		if (isset($this->request->get['sort'])) {
			$sort = $this->request->get['sort'];
		} else {
			$sort = 'id.title';
		}

		if (isset($this->request->get['order'])) {
			$order = $this->request->get['order'];
		} else {
			$order = 'ASC';
		}

		if (isset($this->request->get['page'])) {
			$page = $this->request->get['page'];
		} else {
			$page = 1;
		}

		$url = '';

		if (isset($this->request->get['sort'])) {
			$url .= '&sort=' . $this->request->get['sort'];
		}

		if (isset($this->request->get['order'])) {
			$url .= '&order=' . $this->request->get['order'];
		}

		if (isset($this->request->get['page'])) {
			$url .= '&page=' . $this->request->get['page'];
		}

		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('extension/ebemail_template', 'user_token=' . $this->session->data['user_token'] . $url, true)
		);

		$data['back_module'] = $this->url->link('extension/module/recover_carts', 'user_token=' . $this->session->data['user_token'] . $url, true);
		$data['add'] = $this->url->link('extension/ebemail_template/add', 'user_token=' . $this->session->data['user_token'] . $url, true);
		$data['delete'] = $this->url->link('extension/ebemail_template/delete', 'user_token=' . $this->session->data['user_token'] . $url, true);

		$data['abandonedcart_email_templates'] = array();

		$filter_data = array(
			'sort'  => $sort,
			'order' => $order,
			'start' => ($page - 1) * $this->config->get('config_limit_admin'),
			'limit' => $this->config->get('config_limit_admin')
		);

		$abandonedcart_email_template_total = $this->model_extension_ebemail_template->getTotalEmailTemplates();

		$results = $this->model_extension_ebemail_template->getEmailTemplates($filter_data);

		foreach ($results as $result) {
			$data['abandonedcart_email_templates'][] = array(
				'abandonedcart_email_template_id' => $result['abandonedcart_email_template_id'],
				'title'          => $result['title'],
				'sort_order'     => $result['sort_order'],
				'edit'           => $this->url->link('extension/ebemail_template/edit', 'user_token=' . $this->session->data['user_token'] . '&abandonedcart_email_template_id=' . $result['abandonedcart_email_template_id'] . $url, true)
			);
		}

		$data['heading_title'] = $this->language->get('heading_title');

		$data['text_list'] = $this->language->get('text_list');
		$data['text_no_results'] = $this->language->get('text_no_results');
		$data['text_confirm'] = $this->language->get('text_confirm');

		$data['column_title'] = $this->language->get('column_title');
		$data['column_sort_order'] = $this->language->get('column_sort_order');
		$data['column_action'] = $this->language->get('column_action');

		$data['button_add'] = $this->language->get('button_add');
		$data['button_edit'] = $this->language->get('button_edit');
		$data['button_delete'] = $this->language->get('button_delete');
		$data['button_back_module'] = $this->language->get('button_back_module');

		if (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		} else {
			$data['error_warning'] = '';
		}

		if (isset($this->session->data['success'])) {
			$data['success'] = $this->session->data['success'];

			unset($this->session->data['success']);
		} else {
			$data['success'] = '';
		}

		if (isset($this->request->post['selected'])) {
			$data['selected'] = (array)$this->request->post['selected'];
		} else {
			$data['selected'] = array();
		}

		$url = '';

		if ($order == 'ASC') {
			$url .= '&order=DESC';
		} else {
			$url .= '&order=ASC';
		}

		if (isset($this->request->get['page'])) {
			$url .= '&page=' . $this->request->get['page'];
		}

		$data['sort_title'] = $this->url->link('extension/ebemail_template', 'user_token=' . $this->session->data['user_token'] . '&sort=id.title' . $url, true);
		$data['sort_sort_order'] = $this->url->link('extension/ebemail_template', 'user_token=' . $this->session->data['user_token'] . '&sort=i.sort_order' . $url, true);

		$url = '';

		if (isset($this->request->get['sort'])) {
			$url .= '&sort=' . $this->request->get['sort'];
		}

		if (isset($this->request->get['order'])) {
			$url .= '&order=' . $this->request->get['order'];
		}

		$pagination = new Pagination();
		$pagination->total = $abandonedcart_email_template_total;
		$pagination->page = $page;
		$pagination->limit = $this->config->get('config_limit_admin');
		$pagination->url = $this->url->link('extension/ebemail_template', 'user_token=' . $this->session->data['user_token'] . $url . '&page={page}', true);

		$data['pagination'] = $pagination->render();

		$data['results'] = sprintf($this->language->get('text_pagination'), ($abandonedcart_email_template_total) ? (($page - 1) * $this->config->get('config_limit_admin')) + 1 : 0, ((($page - 1) * $this->config->get('config_limit_admin')) > ($abandonedcart_email_template_total - $this->config->get('config_limit_admin'))) ? $abandonedcart_email_template_total : ((($page - 1) * $this->config->get('config_limit_admin')) + $this->config->get('config_limit_admin')), $abandonedcart_email_template_total, ceil($abandonedcart_email_template_total / $this->config->get('config_limit_admin')));

		$data['sort'] = $sort;
		$data['order'] = $order;

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/ebcart/email_template_list', $data));
	}

	protected function getForm() {
		
		$data['heading_title'] = $this->language->get('heading_title');

		$data['text_form'] = !isset($this->request->get['abandonedcart_email_template_id']) ? $this->language->get('text_add') : $this->language->get('text_edit');
		$data['text_default'] = $this->language->get('text_default');
		$data['text_enabled'] = $this->language->get('text_enabled');
		$data['text_disabled'] = $this->language->get('text_disabled');
		$data['text_yes'] = $this->language->get('text_yes');
		$data['text_no'] = $this->language->get('text_no');
		$data['text_percent'] = $this->language->get('text_percent');
		$data['text_amount'] = $this->language->get('text_amount');

		$data['entry_demensional'] = $this->language->get('entry_demensional');
		$data['entry_title'] = $this->language->get('entry_title');
		$data['entry_description'] = $this->language->get('entry_description');
		$data['entry_sort_order'] = $this->language->get('entry_sort_order');
		$data['entry_status'] = $this->language->get('entry_status');
		$data['entry_subject'] = $this->language->get('entry_subject');
		$data['entry_name'] = $this->language->get('entry_name');
		$data['entry_type'] = $this->language->get('entry_type');
		$data['entry_discount'] = $this->language->get('entry_discount');
		$data['entry_total'] = $this->language->get('entry_total');
		$data['entry_product'] = $this->language->get('entry_product');
		$data['entry_category'] = $this->language->get('entry_category');
		$data['entry_uses_total'] = $this->language->get('entry_uses_total');
		$data['entry_vaild'] = $this->language->get('entry_vaild');
		$data['entry_uses_customer'] = $this->language->get('entry_uses_customer');
		$data['entry_limit'] = $this->language->get('entry_limit');
		$data['entry_featured_product'] = $this->language->get('entry_featured_product');
		$data['tab_coupon'] = $this->language->get('tab_coupon');
		$data['tab_product'] = $this->language->get('tab_product');
		$data['tab_shortcode'] = $this->language->get('tab_shortcode');
		$data['tab_support'] = $this->language->get('tab_support');
		
		$data['help_uses_total'] = $this->language->get('help_uses_total');
		$data['help_type'] = $this->language->get('help_type');
		$data['help_total'] = $this->language->get('help_totals');
		$data['help_product'] = $this->language->get('help_product');
		$data['help_category'] = $this->language->get('help_category');
		$data['help_vaild'] = $this->language->get('help_vaild');
		$data['help_uses_customer'] = $this->language->get('help_uses_customer');
		$data['help_fproduct'] = $this->language->get('help_fproduct');
		
		$data['text_shortcuts'] = $this->language->get('text_shortcuts');

		$data['button_save'] = $this->language->get('button_save');
		$data['button_cancel'] = $this->language->get('button_cancel');

		$data['tab_general'] = $this->language->get('tab_general');
		$data['tab_data'] = $this->language->get('tab_data');

		if (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		} else {
			$data['error_warning'] = '';
		}

		if (isset($this->error['title'])) {
			$data['error_title'] = $this->error['title'];
		} else {
			$data['error_title'] = array();
		}

		if (isset($this->error['subject'])) {
			$data['error_subject'] = $this->error['subject'];
		} else {
			$data['error_subject'] = array();
		}

		if (isset($this->error['description'])) {
			$data['error_description'] = $this->error['description'];
		} else {
			$data['error_description'] = array();
		}
		
		if (isset($this->error['name'])) {
			$data['error_name'] = $this->error['name'];
		} else {
			$data['error_name'] = '';
		}

		$url = '';

		if (isset($this->request->get['sort'])) {
			$url .= '&sort=' . $this->request->get['sort'];
		}

		if (isset($this->request->get['order'])) {
			$url .= '&order=' . $this->request->get['order'];
		}

		if (isset($this->request->get['page'])) {
			$url .= '&page=' . $this->request->get['page'];
		}

		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('extension/ebemail_template', 'user_token=' . $this->session->data['user_token'] . $url, true)
		);

		if (!isset($this->request->get['abandonedcart_email_template_id'])) {
			$data['action'] = $this->url->link('extension/ebemail_template/add', 'user_token=' . $this->session->data['user_token'] . $url, true);
		} else {
			$data['action'] = $this->url->link('extension/ebemail_template/edit', 'user_token=' . $this->session->data['user_token'] . '&abandonedcart_email_template_id=' . $this->request->get['abandonedcart_email_template_id'] . $url, true);
		}

		$data['cancel'] = $this->url->link('extension/ebemail_template', 'user_token=' . $this->session->data['user_token'] . $url, true);

		if (isset($this->request->get['abandonedcart_email_template_id']) && ($this->request->server['REQUEST_METHOD'] != 'POST')) {
			$abandonedcart_ebemail_template_info = $this->model_extension_ebemail_template->getEmailTemplate($this->request->get['abandonedcart_email_template_id']);
			$email_coupon_info = $this->model_extension_ebemail_template->getEmailCoupon($this->request->get['abandonedcart_email_template_id']);
		}

		$data['user_token'] = $this->session->data['user_token'];

		$this->load->model('localisation/language');

		$data['languages'] = $this->model_localisation_language->getLanguages();

		if (isset($this->request->post['abandonedcart_email_template_description'])) {
			$data['abandonedcart_email_template_description'] = $this->request->post['abandonedcart_email_template_description'];
		} elseif (isset($this->request->get['abandonedcart_email_template_id'])) {
			$data['abandonedcart_email_template_description'] = $this->model_extension_ebemail_template->getEmailTemplateDescriptions($this->request->get['abandonedcart_email_template_id']);
		} else {
			$data['abandonedcart_email_template_description'] = array();
		}

		
		
		

		if (isset($this->request->post['sort_order'])) {
			$data['sort_order'] = $this->request->post['sort_order'];
		} elseif (!empty($abandonedcart_ebemail_template_info)) {
			$data['sort_order'] = $abandonedcart_ebemail_template_info['sort_order'];
		} else {
			$data['sort_order'] = '';
		}
		
		if (isset($this->request->post['coupon_name'])) {
			$data['coupon_name'] = $this->request->post['coupon_name'];
		} elseif(isset($email_coupon_info['coupon_name'])){
			$data['coupon_name'] = $email_coupon_info['coupon_name'];
		} else {
			$data['coupon_name'] = '';
		}
		
		if (isset($this->request->post['coupon_status'])) {
			$data['coupon_status'] = $this->request->post['coupon_status'];
		} elseif (!empty($email_coupon_info)) {
			$data['coupon_status'] = $email_coupon_info['coupon_status'];
		} else {
			$data['coupon_status'] = true;
		}
		
		if (isset($this->request->post['coupon_type'])) {
			$data['coupon_type'] = $this->request->post['coupon_type'];
		}elseif(isset($email_coupon_info['coupon_type'])){
			$data['coupon_type'] = $email_coupon_info['coupon_type'];
		}  else {
			$data['coupon_type'] = '';
		}
		
		if (isset($this->request->post['coupon_contion'])) {
			$data['coupon_contion'] = $this->request->post['coupon_contion'];
		} elseif (!empty($email_coupon_info['coupon_contion'])) {
			$data['coupon_contion'] = $email_coupon_info['coupon_contion'];
		} else {
			$data['coupon_contion'] = false;
		}
		
		if (isset($this->request->post['coupon_discount'])) {
			$data['coupon_discount'] = $this->request->post['coupon_discount'];
		} elseif(isset($email_coupon_info['coupon_discount'])){
			$data['coupon_discount'] = $email_coupon_info['coupon_discount'];
		} else {
			$data['coupon_discount'] = '';
		}
		
		if (isset($this->request->post['coupon_total'])) {
			$data['coupon_total'] = $this->request->post['coupon_total'];
		} elseif(isset($email_coupon_info['coupon_total'])){
			$data['coupon_total'] = $email_coupon_info['coupon_total'];
		} else {
			$data['coupon_total'] = '';
		}
		
		if (isset($this->request->post['coupon_uses_total'])) {
			$data['coupon_uses_total'] = $this->request->post['coupon_uses_total'];
		} elseif(isset($email_coupon_info['coupon_uses_total'])){
			$data['coupon_uses_total'] = $email_coupon_info['coupon_uses_total'];
		} else {
			$data['coupon_uses_total'] = '';
		}
		
		if (isset($this->request->post['coupon_vaild'])) {
			$data['coupon_vaild'] = $this->request->post['coupon_vaild'];
		} elseif(isset($email_coupon_info['coupon_vaild'])){
			$data['coupon_vaild'] = $email_coupon_info['coupon_vaild'];
		} else {
			$data['coupon_vaild'] = '';
		}
		
		if (isset($this->request->post['coupon_uses_customer'])) {
			$data['coupon_uses_customer'] = $this->request->post['coupon_uses_customer'];
		} elseif(isset($email_coupon_info['coupon_uses_customer'])){
			$data['coupon_uses_customer'] = $email_coupon_info['coupon_uses_customer'];
		} else {
			$data['coupon_uses_customer'] = '';
		}
		
		if (isset($this->request->post['coupon_product'])) {
			$products = $this->request->post['coupon_product'];
		} elseif(isset($email_coupon_info['coupon_product'])){
			$products = (!empty($email_coupon_info['coupon_product']) ? json_decode($email_coupon_info['coupon_product']) : array());
		} else{
			$products = array();
		}
		
		$this->load->model('catalog/product');

		$data['coupon_product'] = array();

		foreach ($products as $product_id) {
			$product_info = $this->model_catalog_product->getProduct($product_id);

			if ($product_info) {
				$data['coupon_product'][] = array(
					'product_id' => $product_info['product_id'],
					'name'       => $product_info['name']
				);
			}
		}
		
		if (isset($this->request->post['coupon_category'])) {
			$categories = $this->request->post['coupon_category'];
		} elseif(isset($email_coupon_info['coupon_category'])){
			$categories = (!empty($email_coupon_info['coupon_category']) ? json_decode($email_coupon_info['coupon_category']) : array());
		} else {
			$categories = array();
		}
		
		
		$this->load->model('catalog/category');

		$data['coupon_category'] = array();

		foreach ($categories as $category_id) {
			$category_info = $this->model_catalog_category->getCategory($category_id);

			if ($category_info) {
				$data['coupon_category'][] = array(
					'category_id' => $category_info['category_id'],
					'name'        => ($category_info['path'] ? $category_info['path'] . ' &gt; ' : '') . $category_info['name']
				);
			}
		}
		
		
		///Featured Products

		$data['fproducts'] = array();

		if (!empty($this->request->post['featured_product'])) {
			$featured_product = $this->request->post['featured_product'];
		} elseif (!empty($abandonedcart_ebemail_template_info['featured_product'])) {
			$featured_product = (!empty($abandonedcart_ebemail_template_info['featured_product']) ? json_decode($abandonedcart_ebemail_template_info['featured_product']) : array());
		} else {
			$featured_product = array();
		}

		foreach ($featured_product as $product_id) {
			$product_info = $this->model_catalog_product->getProduct($product_id);

			if ($product_info) {
				$data['fproducts'][] = array(
					'product_id' => $product_info['product_id'],
					'name'       => $product_info['name']
				);
			}
		}

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/ebcart/email_template_form', $data));
	}

	protected function validateForm() {
		if (!$this->user->hasPermission('modify', 'extension/ebemail_template')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		foreach ($this->request->post['abandonedcart_email_template_description'] as $language_id => $value) {
			if ((utf8_strlen($value['title']) < 3) || (utf8_strlen($value['title']) > 255)) {
				$this->error['title'][$language_id] = $this->language->get('error_title');
			}

			if ((utf8_strlen($value['subject']) < 3) || (utf8_strlen($value['title']) > 255)) {
				$this->error['subject'][$language_id] = $this->language->get('error_subject');
			}
			
			if (utf8_strlen($value['description']) < 10) {
				$this->error['description'][$language_id] = $this->language->get('error_description');
			}
		}
		
		
		if($this->request->post['coupon_status']){
			if ((utf8_strlen($this->request->post['coupon_name']) < 3) || (utf8_strlen($this->request->post['coupon_name']) > 128)) {
				$this->error['name'] = $this->language->get('error_name');
			}
		}
		

		if ($this->error && !isset($this->error['warning'])) {
			$this->error['warning'] = $this->language->get('error_warning');
		}

		return !$this->error;
	}

	protected function validateDelete() {
		if (!$this->user->hasPermission('modify', 'extension/ebemail_template')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		return !$this->error;
	}
}