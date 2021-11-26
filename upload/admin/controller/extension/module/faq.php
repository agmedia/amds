<?php
class ControllerExtensionModuleFaq extends Controller {
	private $error = array();
public function install()
	{
	$this->load->model('extension/faq');
	$this->model_extension_faq->install();
	}	
	public function uninstall()
	{
	$this->load->model('extension/faq');
	$this->model_extension_faq->uninstall();
	}
	public function index() {
		$this->load->language('extension/module/faq');

		$this->document->setTitle($this->language->get('heading_title1'));

		$this->load->model('setting/module');

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
			if (!isset($this->request->get['module_id'])) {
				$this->model_setting_module->addModule('faq', $this->request->post);
			} else {
				$this->model_setting_module->editModule($this->request->get['module_id'], $this->request->post);
			}

			$this->cache->delete('product');

			$this->session->data['success'] = $this->language->get('text_success');

			$this->response->redirect($this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true));
		}

		$data['heading_title'] = $this->language->get('heading_title');

		$data['text_edit'] = $this->language->get('text_edit');
		$data['text_enabled'] = $this->language->get('text_enabled');
		$data['text_disabled'] = $this->language->get('text_disabled');
		$data['text_category'] = $this->language->get('text_category');
		$data['text_faq'] = $this->language->get('text_faq');
		$data['text_none'] = $this->language->get('text_none');
		$data['text_yes'] = $this->language->get('text_yes');
		$data['text_no'] = $this->language->get('text_no');

		$data['entry_name'] = $this->language->get('entry_name');
		$data['entry_status'] = $this->language->get('entry_status');
		$data['entry_display'] = $this->language->get('entry_display');
		$data['entry_category'] = $this->language->get('entry_category');
		$data['entry_selectcategory'] = $this->language->get('entry_selectcategory');
		$data['entry_faq'] = $this->language->get('entry_faq');

		$data['text_select'] = $this->language->get('text_select');
		$data['button_save'] = $this->language->get('button_save');
		$data['button_cancel'] = $this->language->get('button_cancel');
		$data['user_token'] = $this->session->data['user_token'];

		if (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		} else {
			$data['error_warning'] = '';
		}

		if (isset($this->error['name'])) {
			$data['error_name'] = $this->error['name'];
		} else {
			$data['error_name'] = '';
		}

		if (isset($this->request->post['selected'])) {
			$data['selected'] = (array)$this->request->post['selected'];
		} else {
			$data['selected'] = array();
		}

		$data['display'][] = array(
			'faqdisplay' 		=> $this->language->get('text_category'),
			'value'  		=> 'Category',
		);
		$data['display'][] = array(
			'faqdisplay' 		=> $this->language->get('text_faq'),
			'value'  		=> 'Faq',
		);

		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_extension'),
			'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true)
		);

		if (!isset($this->request->get['module_id'])) {
			$data['breadcrumbs'][] = array(
				'text' => $this->language->get('heading_title'),
				'href' => $this->url->link('extension/module/faq', 'user_token=' . $this->session->data['user_token'], true)
			);
		} else {
			$data['breadcrumbs'][] = array(
				'text' => $this->language->get('heading_title'),
				'href' => $this->url->link('extension/module/faq', 'user_token=' . $this->session->data['user_token'] . '&module_id=' . $this->request->get['module_id'], true)
			);
		}

		if (!isset($this->request->get['module_id'])) {
			$data['action'] = $this->url->link('extension/module/faq', 'user_token=' . $this->session->data['user_token'], true);
		} else {
			$data['action'] = $this->url->link('extension/module/faq', 'user_token=' . $this->session->data['user_token'] . '&module_id=' . $this->request->get['module_id'], true);
		}

		$data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true);

		if (isset($this->request->get['module_id']) && ($this->request->server['REQUEST_METHOD'] != 'POST')) {
			$module_info = $this->model_setting_module->getModule($this->request->get['module_id']);
		}

		if (isset($this->request->post['name'])) {
			$data['name'] = $this->request->post['name'];
		} elseif (!empty($module_info)) {
			$data['name'] = $module_info['name'];
		} else {
			$data['name'] = '';
		}
		if (isset($this->request->post['faqstatuscategory'])) {
			$data['faqstatuscategory'] = $this->request->post['faqstatuscategory'];
		} elseif (!empty($module_info['faqstatuscategory'])) {
			$data['faqstatuscategory'] = $module_info['faqstatuscategory'];
		} else {
			$data['faqstatuscategory'] = 0;
		}
		
		$filter_data = array(
			'sort'  => '',
			'order' => '',
			'start' => 0,
			'limit' => 10
		);

		$this->load->model('extension/category');
		$faqcategors = $this->model_extension_category->getCategories($filter_data);
		foreach ($faqcategors as $faqcat) {
			$data['faqcategories'][] = array(
				'fcategory_id' => $faqcat['fcategory_id'],
				'name'        => $faqcat['name']
			);
		}
		
		if (isset($this->request->post['faqcategory'])) {
			$data['faqcategory'] = $this->request->post['faqcategory'];
		} elseif (!empty($module_info)) {
			$data['faqcategory'] = $module_info['faqcategory'];
		} else {
			$data['faqcategory'] = '';
		}


		if (isset($this->request->post['status'])) {
			$data['status'] = $this->request->post['status'];
		} elseif (!empty($module_info)) {
			$data['status'] = $module_info['status'];
		} else {
			$data['status'] = '';
		}

// 2 August 2018 //
		if (isset($this->request->post['faqdisplay'])) {
			$data['faqdisplay'] = $this->request->post['faqdisplay'];
		} elseif (!empty($module_info)) {
			$data['faqdisplay'] = $module_info['faqdisplay'];
		} else {
			$data['faqdisplay'] = 'category';
		}


		$this->load->model('extension/category');

		if (isset($this->request->post['faq_category'])) {
			$categoryname = $this->request->post['faq_category'];
		} elseif(!empty($module_info['faq_category'])) {
			$categoryname = $module_info['faq_category'];
		} else {
			$categoryname =array();
		}

		$data['categories'] = array();

		foreach ($categoryname as $fcategory_id) {
			$category_info = $this->model_extension_category->getCategory($fcategory_id);

				if ($category_info) {
				$data['categories'][] = array(
					'fcategory_id' => $category_info['fcategory_id'],
					'name'       => $category_info['name']
				);
			}
		
		}

		$this->load->model('extension/faq');

		if (isset($this->request->post['product_faq'])) {
			$faqname = $this->request->post['product_faq'];
		} elseif(!empty($module_info['product_faq'])) {
			$faqname = $module_info['product_faq'];
		} else {
			$faqname =array();
		}

		$data['faqs'] = array();

		foreach ($faqname as $faq_id) {
			$faq_info = $this->model_extension_faq->getfaq($faq_id);

				if ($faq_info) {
				$data['faqs'][] = array(
					'faq_id' => $faq_info['faq_id'],
					'name'       => $faq_info['name']
				);
			}
		
		}

// 2 August 2018 //
		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/module/faq', $data));
	}

	protected function validate() {
		if (!$this->user->hasPermission('modify', 'extension/module/faq')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		if ((utf8_strlen($this->request->post['name']) < 3) || (utf8_strlen($this->request->post['name']) > 64)) {
			$this->error['name'] = $this->language->get('error_name');
		}

		return !$this->error;
	}

// 2 August 2018 //
	public function autocomplete() {
		$json = array();

		if (isset($this->request->get['filter_name'])) {
			$this->load->model('extension/category');

			$filter_data = array(
				'filter_name' => $this->request->get['filter_name'],
				'sort'        => 'name',
				'order'       => 'ASC',
				'start'       => 0,
				'limit'       => 5
			);

			$results = $this->model_extension_category->getCategories($filter_data);

			foreach ($results as $result) {
				$json[] = array(
					'fcategory_id' => $result['fcategory_id'],
					'name'        => strip_tags(html_entity_decode($result['name'], ENT_QUOTES, 'UTF-8'))
				);
			}
		}

		$sort_order = array();

		foreach ($json as $key => $value) {
			$sort_order[$key] = $value['name'];
		}

		array_multisort($sort_order, SORT_ASC, $json);

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	public function faqautocomplete() {
		$json = array();

		if (isset($this->request->get['filter_name'])) {
			$this->load->model('extension/faq');

			$filter_data = array(
				'filter_name' => $this->request->get['filter_name'],
				'sort'        => 'name',
				'order'       => 'ASC',
				'start'       => 0,
				'limit'       => 5
			);

			$results = $this->model_extension_faq->getFAQs($filter_data);

			foreach ($results as $result) {
				$json[] = array(
					'faq_id' => $result['faq_id'],
					'name'        => strip_tags(html_entity_decode($result['name'], ENT_QUOTES, 'UTF-8'))
				);
			}
		}

		$sort_order = array();

		foreach ($json as $key => $value) {
			$sort_order[$key] = $value['name'];
		}

		array_multisort($sort_order, SORT_ASC, $json);

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

// 2 August 2018 //

}