<?php
class ControllerExtensionModuleFaq extends Controller {
	public function index($setting) {
		$this->load->language('extension/module/faq');
		$this->load->model('extension/faq');	
		$data['heading_title']=$setting['name'];
		$faqdisplay = $setting['faqdisplay'];
		$fcategory_id = $setting['faqcategory'];
		
		if($faqdisplay == 'faq'){
			
			if(!empty($fcategory_id)){
						$data['heading_title']='';
						$subfaqs=array();
							$filterdata=array(
							 'filter_fcategory_id' => $fcategory_id,
							);
							$faqs = $this->model_extension_faq->getfaqs($filterdata);
							foreach($faqs as $faq){
							  $subfaqs[]=array(
								'faq_id'	  => $faq['faq_id'],
								'name'		  => $faq['name'],
								'description' => html_entity_decode($faq['description'], ENT_QUOTES, 'UTF-8')
							  );
							}
							$categoryinfo = $this->model_extension_faq->getfCategory($fcategory_id);
							$data['results'][]=array(
							  'fcategory_id' => $categoryinfo['fcategory_id'],
							  'name'		 => $categoryinfo['name'],
							  'subfaqs'		 => $subfaqs
							);
			} else {
				if(!empty($setting['product_faq']))
				{
					$data['heading_title']='';
					foreach($setting['product_faq'] as $faq_id)
					{
					
					$faq = $this->model_extension_faq->getfaq($faq_id);
					if(isset($faq['faq_id']))
					{
					  $subfaqs[]=array(
						'faq_id'	  => $faq['faq_id'],
						'name'		  => $faq['name'],
						'description' => html_entity_decode($faq['description'], ENT_QUOTES, 'UTF-8')
					  );
					}
				}
				$data['results'][]=array(
				  'fcategory_id' => '1',
				  'name'		 => $this->language->get('heading_title'),
				  'subfaqs'		 => $subfaqs,
				);
				
			}
			}
		} else {
				if(!empty($setting['faq_category']))
				{
					$data['heading_title']='';
					if($setting['faqstatuscategory'])
					{
						foreach($setting['faq_category'] as $fcategory_id)	
						{
							$subfaqs=array();
							$filterdata=array(
							 'filter_fcategory_id' => $fcategory_id,
							);
							$faqs = $this->model_extension_faq->getfaqs($filterdata);
							foreach($faqs as $faq){
							  $subfaqs[]=array(
								'faq_id'	  => $faq['faq_id'],
								'name'		  => $faq['name'],
								'description' => html_entity_decode($faq['description'], ENT_QUOTES, 'UTF-8')
							  );
							}
							$categoryinfo = $this->model_extension_faq->getfCategory($fcategory_id);
							$data['results'][]=array(
							  'fcategory_id' => $categoryinfo['fcategory_id'],
							  'name'		 => $categoryinfo['name'],
							  'subfaqs'		 => $subfaqs
							);
						}
					}
					else
					{
						$data['heading_title']=$setting['name'];
						foreach($setting['faq_category'] as $fcategory_id)	
						{
							$subfaqs=array();
							$categoryinfo = $this->model_extension_faq->getfCategory($fcategory_id);
							$data['results'][]=array(
							  'fcategory_id' => $categoryinfo['fcategory_id'],
							  'name'		 => $categoryinfo['name'],
							  'subfaqs'		 => $subfaqs,
							  'href'		  => $this->url->link('extension/faq','&fcategory_id='.$fcategory_id),
							);
						}
					}
				
				}
			
		}
		//print_r($this->load->view('extension/module/faq', $data));die();
		return $this->load->view('extension/module/faq', $data);
	}
}

