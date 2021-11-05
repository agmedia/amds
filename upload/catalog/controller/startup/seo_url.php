<?php
class ControllerStartupSeoUrl extends Controller {

    public $seo_url = [];

    public function index() {
        // Add rewrite to url class
        if ($this->config->get('config_seo_url')) {
            $this->url->addRewrite($this);
        }

        // Decode URL
        if (isset($this->request->get['_route_'])) {
            $parts = explode('/', $this->request->get['_route_']);

            // remove any empty arrays from trailing
            if (utf8_strlen(end($parts)) == 0) {
                array_pop($parts);
            }

            foreach ($parts as $part) {
                $sql = "SELECT * FROM " . DB_PREFIX . "seo_url WHERE keyword = '" . $this->db->escape($part) . "'";
                $query = $this->db->query($sql);

                if ($query->num_rows) {
                    $url = explode('=', $query->row['query']);

                    if ($url[0] == 'product_id') {
                        $this->request->get['product_id'] = $url[1];
                    }

                    if ($url[0] == 'category_id') {
                        if (!isset($this->request->get['path'])) {
                            $this->request->get['path'] = $url[1];
                        } else {
                            $this->request->get['path'] .= '_' . $url[1];
                        }
                    }

                    if ($url[0] == 'manufacturer_id') {
                        $this->request->get['manufacturer_id'] = $url[1];
                    }

                    if ($url[0] == 'information_id') {
                        $this->request->get['information_id'] = $url[1];
                    }

                    if ($url[0] == 'blog_id') {
                        $this->request->get['blog_id'] = $url[1];
                    }

                    if ($url[0] == 'blog_category_id') {
                        if (!isset($this->request->get['blogpath'])) {
                            $this->request->get['blogpath'] = $url[1];
                        } else {
                            $this->request->get['blogpath'] .= '_' . $url[1];
                        }}

                    if ($query->row['query'] && $url[0] != 'information_id' && $url[0] != 'manufacturer_id' && $url[0] != 'category_id' && $url[0] != 'blog_category_id' && $url[0] != 'blog_id' && $url[0] != 'product_id') {
                        $this->request->get['route'] = $query->row['query'];
                    }
                } else {
                    $this->request->get['route'] = 'error/not_found';

                    break;
                }
            }

            if (!isset($this->request->get['route'])) {
                if (isset($this->request->get['product_id'])) {
                    $this->request->get['route'] = 'product/product';

                } elseif (isset($this->request->get['blog_id'])) {
                    $this->request->get['route'] = 'extension/blog/blog';
                } elseif ($this->request->get['_route_'] ==  'extension_blog_home') {
                    $this->request->get['route'] = 'extension/blog/home';

                } elseif (isset($this->request->get['path'])) {
                    $this->request->get['route'] = 'product/category';

                } elseif (isset($this->request->get['blogpath'])) {
                    $this->request->get['route'] = 'extension/blog/category';

                } elseif (isset($this->request->get['manufacturer_id'])) {
                    $this->request->get['route'] = 'product/manufacturer/info';
                } elseif (isset($this->request->get['information_id'])) {
                    $this->request->get['route'] = 'information/information';
                }
            }
        }
    }

    public function rewrite($link) {
        $url_info = parse_url(str_replace('&amp;', '&', $link));
        $url = '';
        $data = array();

        parse_str($url_info['query'], $data);

        if (empty($this->seo_url)) {
            $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "seo_url");

            foreach ($query->rows as $row) {
                $this->seo_url[$row['query']] = $row['keyword'];
            }
        }


        foreach ($data as $key => $value) {
            if (isset($data['route'])) {
                if (($data['route'] == 'product/product' && $key == 'product_id') || (($data['route'] == 'product/manufacturer/info' || $data['route'] == 'product/product') && $key == 'manufacturer_id') || ($data['route'] == 'information/information' && $key == 'information_id') || ($data['route'] == 'extension/blog/blog' && $key == 'blog_id'))
                {
                    if (isset($this->seo_url[$key . '=' . $value])) {
                        $url .= '/' . $this->seo_url[$key . '=' . $value];

                        unset($data[$key]);
                    }

                } elseif ($key == 'route') {
                    if (isset($this->seo_url[$this->db->escape($value)])) {
                        $url .= '/' . $this->seo_url[$this->db->escape($value)];

                        unset($data[$key]);
                    } else if ($data['route'] == "common/home") {
                        $url .= '/';
                    }

                } elseif ($key == 'blogpath') {
                    $blog_categories = explode('_', $value);

                    foreach ($blog_categories as $category) {
                        if (isset($this->seo_url['blog_category_id=' . $category])) {
                            $url .= '/' . $this->seo_url['blog_category_id=' . $category];

                            unset($data[$key]);
                        } else {
                            $url = '';

                            break;
                        }

                        /*$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "seo_url WHERE `query` = 'blog_category_id=" . (int)$category . "'");
                        if ($query->num_rows) {
                            $url .= '/' . $query->row['keyword'];
                        } else {
                            $url = '';
                            break;
                        }*/
                    }

                } elseif (isset($data['route']) && $data['route'] == 'extension/blog/home') {
                    if (isset($this->seo_url['extension/blog/home'])) {
                        $url .= '/' . $this->seo_url['extension/blog/home'];

                        unset($data[$key]);
                    } else {
                        $url = '';
                    }

                    /*$blog_home = $this->db->query("SELECT * FROM " . DB_PREFIX . "seo_url WHERE `query` = 'extension/blog/home'");
                    if ($blog_home->num_rows) {
                        $url .= '/' . $blog_home->row['keyword'];
                    } else {
                        $url = '';
                    }*/

                } elseif ($key == 'path') {
                    $categories = explode('_', $value);

                    foreach ($categories as $category) {
                        if (isset($this->seo_url['category_id=' . $category])) {
                            $url .= '/' . $this->seo_url['category_id=' . $category];

                            unset($data[$key]);
                        } else {
                            $url = '';

                            break;
                        }
                    }

                    unset($data[$key]);
                }
            }
        }

        if ($url) {
            unset($data['route']);

            $query = '';

            if ($data) {
                foreach ($data as $key => $value) {
                    $query .= '&' . rawurlencode((string)$key) . '=' . rawurlencode((is_array($value) ? http_build_query($value) : (string)$value));
                }

                if ($query) {
                    $query = '?' . str_replace('&', '&amp;', trim($query, '&'));
                }
            }

            return $url_info['scheme'] . '://' . $url_info['host'] . (isset($url_info['port']) ? ':' . $url_info['port'] : '') . str_replace('/index.php', '', $url_info['path']) . $url . $query;
        } else {
            return $link;
        }
    }
}
