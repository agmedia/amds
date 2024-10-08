<?php
class ControllerExtensionModuleDigitalElephantFilterHelperUrl extends Controller
{
    private $storageUrlData = null;

    public function prototype()
    {
        return $this;
    }

    public function getUrlStringWithoutRoute()
    {
        $get_params = $this->request->server['QUERY_STRING'];
        $get_params = preg_replace('/&amp;/', '&', $get_params);

        $get_params_without_route = preg_replace('/route=(.)*&/U', '', $get_params);
        return $get_params_without_route;
    }

    public function deleteParamFromUrlString($url_string, $param_name) {
        $pattern = '/(&|\?)' . $param_name . '=(.)/U';
        $new_link = preg_replace($pattern,'', $url_string);
        return $new_link;
    }

    public function isAjaxRequest() {
        return (!empty($this->request->get['ajax_digitalElephantFilter']));
    }

    public function getUrlData()
    {
        if ($this->storageUrlData == null) {
            $data_url = array();

            if (isset($this->request->get['path'])) {

                $parts = explode('_', (string)$this->request->get['path']);

                $category_id = (int)array_pop($parts);

                $data_url['path'] = $this->request->get['path'];
            } else {
                $category_id = 0;

                $data_url['path'] = 0;
            }


            $data_url['category_id'] = $category_id;

            if (isset($this->request->get['price'])) {
                $data_url['price'] = $this->request->get['price'];
            } else {
                $data_url['price']['min'] = '';
                $data_url['price']['max'] = '';
            }

            $data_url['manufacturers'] = [];
            if (isset($this->request->get['manufacturers'])) {
                $data_url['manufacturers'] = $this->request->get['manufacturers'];
            }

            $data_url['category'] = [];
            if (isset($this->request->get['category'])) {
                $data_url['category'] = $this->request->get['category'];
            }

            $data_url['sub_categories'] = [];
            if (isset($this->request->get['category'])) {
                $data_url['sub_categories'] = $this->request->get['category']['categories'];
            }

            $data_url['option'] = [];
            if (isset($this->request->get['option'])) {
                $data_url['option'] = $this->request->get['option'];
            }

            $data_url['attribute'] = [];
            if (isset($this->request->get['attribute'])) {
                $data_url['attribute'] = $this->request->get['attribute'];
            }


            //sortiranje unutar poseba ponuda po sort_order, defaultno ostaje kako je i bilo (date_added).
            $categorySorts = array(
                'default'   => array('sort' => 'p.date_added', 'order' => 'DESC'),
                '1'       => array('sort' => 'p.sort_order', 'order' => 'ASC'),
                '8'       => array('sort' => 'p.sort_order', 'order' => 'ASC'),
                '12'       => array('sort' => 'p.sort_order', 'order' => 'ASC'),
                '92'       => array('sort' => 'p.sort_order', 'order' => 'ASC'),
                '108'       => array('sort' => 'p.sort_order', 'order' => 'ASC'),
                '138'       => array('sort' => 'p.sort_order', 'order' => 'ASC'),
                '22'       => array('sort' => 'p.sort_order', 'order' => 'ASC'),
                '52'       => array('sort' => 'p.sort_order', 'order' => 'ASC'),
                '10'       => array('sort' => 'p.sort_order', 'order' => 'ASC'),
                '33'       => array('sort' => 'p.sort_order', 'order' => 'ASC'),
                '17'       => array('sort' => 'p.sort_order', 'order' => 'ASC'),
                '84'       => array('sort' => 'p.sort_order', 'order' => 'ASC'),
                '104'       => array('sort' => 'p.sort_order', 'order' => 'ASC'),
                '139'       => array('sort' => 'p.sort_order', 'order' => 'ASC'),
                '45'       => array('sort' => 'p.sort_order', 'order' => 'ASC'),
                '56'       => array('sort' => 'p.sort_order', 'order' => 'ASC'),
                '129'       => array('sort' => 'p.sort_order', 'order' => 'ASC'),
                '80'       => array('sort' => 'p.sort_order', 'order' => 'ASC'),
                '54'       => array('sort' => 'p.sort_order', 'order' => 'ASC'),
                '35'       => array('sort' => 'p.sort_order', 'order' => 'ASC'),
                '69'       => array('sort' => 'p.sort_order', 'order' => 'ASC'),
                '14'       => array('sort' => 'p.sort_order', 'order' => 'ASC')

            );

            $default = $categorySorts['default'];
            if (isset($this->request->get['path'])) {
                $parts = explode('_', (string)$this->request->get['path']);
                $category_id = (int)array_pop($parts);
                $this->log->write($category_id);
                if (isset($categorySorts[$category_id])) {
                    $default = $categorySorts[$category_id];
                }
            }

            if (isset($this->request->get['sort'])) {
                $sort = $this->request->get['sort'];
            } else {
                $sort = $default['sort'];
            }

            if (isset($this->request->get['order'])) {
                $order = $this->request->get['order'];
            } else {
                $order = $default['order'];
            }



        /*    if (isset($this->request->get['sort'])) {
                $sort = $this->request->get['sort'];
            } else {
                $sort = 'p.date_added';
            }

            if (isset($this->request->get['order'])) {
                $order = $this->request->get['order'];
            } else {
                $order = 'DESC';
            }*/

            if (isset($this->request->get['page'])) {
                $page = $this->request->get['page'];
            } else {
                $page = 1;
            }

            if (isset($this->request->get['limit'])) {
                $limit = (int)$this->request->get['limit'];
            } else {

                if (VERSION >= '3.0.0.0') {
                    $limit = $this->config->get('theme_' . $this->config->get('config_theme') . '_product_limit');
                } else {
                    $limit = $this->config->get($this->config->get('config_theme') . '_product_limit');
                }

            }

            if (isset($this->request->get['filter'])) {
                $opencart_filter = $this->request->get['filter'];
            } else {
                $opencart_filter = '';
            }

            $path = 0;
            if (isset($this->request->get['path'])) {
                $path = $this->request->get['path'];
            }

            $data_url['path'] = $path;
            $data_url['sort'] = $sort;
            $data_url['page'] = $page;
            $data_url['limit'] = $limit;
            $data_url['order'] = $order;
            $data_url['start'] = ($page - 1) * $limit;
            $data_url['opencart_filter'] = $opencart_filter;

            $this->storageUrlData = $data_url;
        }

        return $this->storageUrlData;
    }
}