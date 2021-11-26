<?php
class ControllerExtensionFaqcategoryPro extends Controller {
	public function index() {
		$this->load->language('extension/faqcategory');
		$this->load->model('extension/faq');
		$data['results']=array();
		$subfaqs=array();
		$data['tmdfaqstatus'] = $this->config->get('tmdfaqsetting_status');
		$data['displaycategory'] = $this->config->get('tmdfaqsetting_displaycategory');
		$data['faqposition'] = $this->config->get('tmdfaqsetting_faqposition');
		if(isset($this->request->get['product_id'])) {
			$faqcat_infos = $this->model_extension_faq->getTmdFaqCategory($this->request->get['product_id']);
			foreach ($faqcat_infos as $result) {
				$category_infos = $this->model_extension_faq->getfCategory($result['fcategory_id']);
				$faq_infos = $this->model_extension_faq->getTmdfcategoies($result['fcategory_id']);
				foreach($faq_infos as $faq){
				  $subfaqs[]=array(
					'faq_id'	  => $faq['faq_id'],
					'name'		  => $faq['name'],
					'description' => html_entity_decode($faq['description'], ENT_QUOTES, 'UTF-8'),
					'href'		  => $this->url->link('extension/faq/fullview','&faq_id='.$faq['faq_id']),
				  );
				}
				if($category_infos){
					$data['results'][] = array(
						'fcategory_id' 		=> $category_infos['fcategory_id'],
						'name' 				=> $category_infos['name'],
						'meta_description' 	=> html_entity_decode($category_infos['meta_description'], ENT_QUOTES, 'UTF-8'),
						'subfaqs' 	=> $subfaqs,
					);
				}
			}
		}
		return $this->load->view('extension/faqcategorypro', $data);
	}
}