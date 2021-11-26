<?php
class ControllerExtensionFaqcategory extends Controller {
	public function index() {
		$this->load->language('extension/faqcategory');
		$this->load->model('extension/faq');

		$this->document->setTitle($this->language->get('heading_title'));

		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/home')
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('extension/faq')
		);

		$this->load->model('tool/image');
		$data['placeholder'] = $this->model_tool_image->resize('no_image.png', 100, 100);

		

		// New 
		if(isset($this->request->get['faq_id']))
		{
		$data['faq_id']=$this->request->get['faq_id'];
		}
		else{
		$data['faq_id']=0;
		}

		// New 
		$data['text_readmore'] = $this->language->get('text_readmore');
		$data['heading_title'] = $this->language->get('heading_title');
		$data['heading_payment'] = $this->language->get('heading_payment');
		$url='';
		$data['results']=array();
		
		$categoies = $this->model_extension_faq->getFaqcategories($data);
		/*$module = $this->model_extension_faq->getModules($data);
		//print_r($module);*/
		
		foreach($categoies as $category){
			
			if ($category['image']) {
				$data['thumb'] = $this->model_tool_image->resize($category['image'], $this->config->get('theme_' . $this->config->get('config_theme') . '_image_category_width'), $this->config->get('theme_' . $this->config->get('config_theme') . '_image_category_height'));
			} else {
				$data['thumb'] = '';
			}
			if ($category['image']) {
				$image = $this->model_tool_image->resize($category['image'], $this->config->get('theme_' . $this->config->get('config_theme') . '_image_thumb_width'), $this->config->get('theme_' . $this->config->get('config_theme') . '_image_thumb_height'));
			} else {
				$image = $this->model_tool_image->resize('placeholder.png', $this->config->get('theme_' . $this->config->get('config_theme') . '_image_thumb_width'), $this->config->get('theme_' . $this->config->get('config_theme') . '_image_thumb_height'));
			}

			$data['results'][] = array(
				'fcategory_id' => $category['fcategory_id'],
				'name' => $category['name'],
				'meta_description' => html_entity_decode($category['meta_description'], ENT_QUOTES, 'UTF-8'),
				'thumb' 		=>$image,
				'href'        => $this->url->link('extension/faq', '&fcategory_id=' . $category['fcategory_id'] . $url )
			);	
		}

		$data['column_left'] = $this->load->controller('common/column_left');
		$data['column_right'] = $this->load->controller('common/column_right');
		$data['content_top'] = $this->load->controller('common/content_top');
		$data['content_bottom'] = $this->load->controller('common/content_bottom');
		$data['footer'] = $this->load->controller('common/footer');
		$data['header'] = $this->load->controller('common/header');

		$this->response->setOutput($this->load->view('extension/faqcategory', $data));
	}
	
	public function search(){
		$this->load->language('extension/faqcategory');
		$this->load->model('extension/faq');

		$this->document->setTitle($this->language->get('heading_title'));

		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/home')
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('extension/faq')
		);
		
		if(isset($this->request->get['fsearch'])){
			$search = $this->request->get['fsearch'];
		}else{
			$search = '';
		}
		
		$data['search'] = $search;

		$data['text_readmore'] = $this->language->get('text_readmore');
		$data['text_search'] = $this->language->get('text_search');
		$data['heading_title'] = $this->language->get('heading_title');
		$data['heading_payment'] = $this->language->get('heading_payment');
		$data['results']=array();
		$filterdata=array(
		 'filter_name' => trim($search),
		);
		$faqs = $this->model_extension_faq->getfaqs($filterdata);
		foreach($faqs as $faq){
			$category_info = $this->model_extension_faq->getfCategory($faq['fcategory_id']);
			if($category_info){
				$categoiesname = $category_info['name'];
			}else{
				$categoiesname = '';
			}
			
			$data['results'][]=array(
			  'faq_id'	  	  => $faq['faq_id'],
			  'name'	 	  => $faq['name'],
			  'categoiesname' => $categoiesname,
			  'description'   => html_entity_decode($faq['description'], ENT_QUOTES, 'UTF-8'),
			  'href'		  => $this->url->link('extension/faq/fullview','&faq_id='.$faq['faq_id']),
			);
		}
		
		
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['column_right'] = $this->load->controller('common/column_right');
		$data['content_top'] = $this->load->controller('common/content_top');
		$data['content_bottom'] = $this->load->controller('common/content_bottom');
		$data['footer'] = $this->load->controller('common/footer');
		$data['header'] = $this->load->controller('common/header');
		
		$this->response->setOutput($this->load->view('extension/faqserach', $data));
	}
	
	public function fullview(){
		$this->load->language('extension/faqcategory');
		$this->load->model('extension/faq');
		
		if($this->request->get['faq_id']){
		  $faq_id = $this->request->get['faq_id'];
		}else{
		  $faq_id = 0;
		}
		
		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/home')
		);
		
		$faq_info = $this->model_extension_faq->getfaq($faq_id);
		if($faq_info){
		    $category_info = $this->model_extension_faq->getfCategory($faq_info['fcategory_id']);
			if($category_info){
				$this->document->setTitle($category_info['meta_title']);
				$this->document->setDescription($category_info['meta_description']);
				$this->document->setKeywords($category_info['meta_keyword']);
			}
			
			$data['breadcrumbs'][] = array(
				'text' => $faq_info['name'],
				'href' => $this->url->link('extension/faq/fullview', 'faq_id=' .  $faq_id)
			);

			$data['heading_title'] = $faq_info['name'];

			$data['button_continue'] = $this->language->get('button_continue');

			$data['description'] = html_entity_decode($faq_info['description'], ENT_QUOTES, 'UTF-8');

			
			$data['continue'] = $this->url->link('extension/faq/search','','SSL');

			$data['column_left'] = $this->load->controller('common/column_left');
			$data['column_right'] = $this->load->controller('common/column_right');
			$data['content_top'] = $this->load->controller('common/content_top');
			$data['content_bottom'] = $this->load->controller('common/content_bottom');
			$data['footer'] = $this->load->controller('common/footer');
			$data['header'] = $this->load->controller('common/header');
			
			$this->response->setOutput($this->load->view('extension/faqview', $data));
		}else{
			$data['breadcrumbs'][] = array(
				'text' => $this->language->get('text_error'),
				'href' => $this->url->link('extension/information', 'information_id=' . $information_id)
			);

			$this->document->setTitle($this->language->get('text_error'));

			$data['heading_title'] = $this->language->get('text_error');

			$data['text_error'] = $this->language->get('text_error');

			$data['button_continue'] = $this->language->get('button_continue');

			$data['continue'] = $this->url->link('extension/faq/search','','SSL');
			
			$this->response->addHeader($this->request->server['SERVER_PROTOCOL'] . ' 404 Not Found');

			$data['column_left'] = $this->load->controller('common/column_left');
			$data['column_right'] = $this->load->controller('common/column_right');
			$data['content_top'] = $this->load->controller('common/content_top');
			$data['content_bottom'] = $this->load->controller('common/content_bottom');
			$data['footer'] = $this->load->controller('common/footer');
			$data['header'] = $this->load->controller('common/header');

			$this->response->setOutput($this->load->view('error/not_found', $data));
		}
	}
}