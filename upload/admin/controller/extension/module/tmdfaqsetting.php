<?php
class ControllerExtensionModuleTmdFaqsetting extends Controller {
	private $error = array();

	public function index() {
		$this->load->language('extension/module/tmdfaqsetting');

		$this->document->setTitle($this->language->get('heading_title1'));

		$this->load->model('setting/setting');

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {

			if(isset($this->request->post['tmdfaqsetting_status'])) {

				$status=$this->request->post['tmdfaqsetting_status'];
			}
			
			$postdata['module_tmdfaqsetting_status']=$status;

			$this->model_setting_setting->editSetting('module_tmdfaqsetting',$postdata);
			
			$this->model_setting_setting->editSetting('tmdfaqsetting', $this->request->post);

			$this->session->data['success'] = $this->language->get('text_success');

			$this->response->redirect($this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true));
		}

		$data['heading_title'] = $this->language->get('heading_title');

		$data['text_edit'] = $this->language->get('text_edit');
		$data['text_yes'] = $this->language->get('text_yes');
		$data['text_no'] = $this->language->get('text_no');
		$data['text_enabled'] = $this->language->get('text_enabled');
		$data['text_disabled'] = $this->language->get('text_disabled');

		$data['entry_faqposition'] = $this->language->get('entry_faqposition');
		$data['text_out_tab'] = $this->language->get('text_out_tab');
		$data['text_in_tab'] = $this->language->get('text_in_tab');
		$data['entry_status'] = $this->language->get('entry_status');
		$data['text_yes'] = $this->language->get('text_yes');
		$data['text_no'] = $this->language->get('text_no');
		$data['entry_displaycategory'] = $this->language->get('entry_displaycategory');

		$data['button_save'] = $this->language->get('button_save');
		$data['button_cancel'] = $this->language->get('button_cancel');

		if (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		} else {
			$data['error_warning'] = '';
		}

		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_extension'),
			'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('extension/module/tmdfaqsetting', 'user_token=' . $this->session->data['user_token'], true)
		);

		$data['action'] = $this->url->link('extension/module/tmdfaqsetting', 'user_token=' . $this->session->data['user_token'], true);

		$data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true);

		if (isset($this->request->post['tmdfaqsetting_admin'])) {
			$data['tmdfaqsetting_admin'] = $this->request->post['tmdfaqsetting_admin'];
		} else {
			$data['tmdfaqsetting_admin'] = $this->config->get('tmdfaqsetting_admin');
		}

		if (isset($this->request->post['tmdfaqsetting_status'])) {
			$data['tmdfaqsetting_status'] = $this->request->post['tmdfaqsetting_status'];
		} else {
			$data['tmdfaqsetting_status'] = $this->config->get('tmdfaqsetting_status');
		}

		if (isset($this->request->post['tmdfaqsetting_displaycategory'])) {
			$data['tmdfaqsetting_displaycategory'] = $this->request->post['tmdfaqsetting_displaycategory'];
		} else {
			$data['tmdfaqsetting_displaycategory'] = $this->config->get('tmdfaqsetting_displaycategory');
		}

		if (isset($this->request->post['tmdfaqsetting_faqposition'])) {
			$data['tmdfaqsetting_faqposition'] = $this->request->post['tmdfaqsetting_faqposition'];
		} else {
			$data['tmdfaqsetting_faqposition'] = $this->config->get('tmdfaqsetting_faqposition');
		}

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/module/tmdfaqsetting', $data));
	}

	protected function validate() {
		if (!$this->user->hasPermission('modify', 'extension/module/tmdfaqsetting')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		return !$this->error;
	}
}