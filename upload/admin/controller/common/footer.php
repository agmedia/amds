<?php
class ControllerCommonFooter extends Controller {
	public function index() {
		$this->load->language('common/footer');

		if ($this->user->isLogged()) {
			$data['text_version'] = sprintf($this->language->get('text_version'), VERSION);
		} else {
			$data['text_version'] = '';
		}
		
	//	$data['link'] = $this->url->link('extension/module/luceed_sync', 'user_token=' . $this->session->data['user_token'], true);

		return $this->load->view('common/footer', $data);
	}
}
