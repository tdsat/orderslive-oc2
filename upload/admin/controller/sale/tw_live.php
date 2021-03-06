<?php
define('TW_ORDERS_LIVE_VERSION',"1.1");

class ControllerSaleTwLive extends Controller {
	private $error = array();
	function __construct($registry) {
		parent::__construct($registry);
		//Models
		$this->load->model('sale/order');
		$this->load->model('customer/customer');
		$this->load->model('localisation/order_status');
		$this->load->model('tw/orderslive');

		//Language
		$this->load->language('sale/order');
		$this->load->language('sale/tw_live');
		$this->load->language('customer/customer');

		$this->processing_statuses = $this->config->get('config_processing_status');
		$this->complete_statuses = $this->config->get('config_complete_status');
	}
	
	private function loadTemplate($template,$data){
		return version_compare(VERSION,'2.2',"<") 
			?  $this->load->view($template.'.tpl',$data)
			:  $this->load->view($template,$data);
	}

	public function index() {
		$this->document->setTitle('LIVE!');
		$data = array();
		$data += $this->load->language('sale/tw_live');
		$data['locale'] = $this->language->get('code');

		$data['catalog'] = $this->request->server['HTTPS'] ? HTTPS_CATALOG : HTTP_CATALOG;
		$data['button_ip_add'] = $this->language->get('button_ip_add');
		$data['text_loading'] = $this->language->get('text_loading');
		// API login
		$this->load->model('user/api');

		$api_info = $this->model_user_api->getApi($this->config->get('config_api_id'));

		if ($api_info) {
			$data['api_id'] = $api_info['api_id'];
			$data['api_key'] = $api_info['key'];
			$data['api_ip'] = $this->request->server['REMOTE_ADDR'];
		} else {
			$data['api_id'] = '';
			$data['api_key'] = '';
			$data['api_ip'] = '';
		}

		//get last ten orders to display
		$last_ten = $this->model_sale_order->getOrders(['limit' => 10,'start' => 0,'sort'=>'o.date_modified','order'=>'DESC']);
		$order_tabs = [];
		$order_details = [];
		$order_data['text'] = $this->loadText();
		foreach ($last_ten as $o) {
			$order_data['order'] = $this->getOrder($o['order_id']);
			$order_details[] = $this->loadTemplate('sale/tw_order_live_info', $order_data);
			$order_tabs[]  = $this->loadTemplate('sale/tw_order_live_tab', $order_data);
		}
		//Setting Output
		$data['header'] = $this->load->controller('common/header');
		$data['footer'] = $this->load->controller('common/footer');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['order_tabs'] = $order_tabs;
		$data['order_details'] = $order_details;
		$data['token'] = $this->session->data['token'];

		foreach (new DirectoryIterator(DIR_APPLICATION.'view/sounds/tw/tworderslive') as $file) {
			if ($file->isFile()) {
				$data['sound_files'][] = array(
					'file' => $file->getFilename(),
					'name' => $file->getBasename('.'.$file->getExtension())
				);
			}
		}
		$this->response->setOutput($this->loadTemplate('sale/tw_order_live', $data));

		$data['sound_files'] = array_values(array_filter(scandir(DIR_APPLICATION.'view/sounds/tw/tworderslive'), function($file) {
			return !is_dir($file);
		}));
	}


	protected function getOrder($order_id) {

		$order_details = $this->getOrderInfo($order_id);
		$order = array(
			'details'	=>  $order_details,
			'customer'	=>	$this->getCustomer($order_details['customer_id']),
			'view_link'	=>	$this->url->link('sale/order/info', 'token=' . $this->session->data['token'] . '&order_id=' . $order_details['order_id'], true),
		);
		return $order;
	}

	protected function getOrderInfo($order_id) {
		$order_info = $this->model_sale_order->getOrder($order_id);
		if ($order_info) {
			$data['shipping'] = $this->url->link('sale/order/shipping', 'token=' . $this->session->data['token'] . '&order_id=' . (int)$order_id, true);
			$data['invoice'] = $this->url->link('sale/order/invoice', 'token=' . $this->session->data['token'] . '&order_id=' . (int)$order_id, true);
			$data['edit'] = $this->url->link('sale/order/edit', 'token=' . $this->session->data['token'] . '&order_id=' . (int)$order_id, true);
			$data['cancel'] = $this->url->link('sale/order', 'token=' . $this->session->data['token'], true);

			$data['order_id'] = $order_info['order_id'];
			$data['customer_id'] = $order_info['customer_id'];

			$data['store_id'] = $order_info['store_id'];
			$data['store_name'] = $order_info['store_name'];

			if ($order_info['store_id'] == 0) {
				$data['store_url'] = $this->request->server['HTTPS'] ? HTTPS_CATALOG : HTTP_CATALOG;
			} else {
				$data['store_url'] = $order_info['store_url'];
			}

			if ($order_info['invoice_no']) {
				$data['invoice_no'] = $order_info['invoice_prefix'] . $order_info['invoice_no'];
			} else {
				$data['invoice_no'] = '';
			}

			$data['order_date_added'] = date($this->language->get('date_format_short'), strtotime($order_info['date_added']));
			$data['order_time_added'] = date($this->language->get('time_format'), strtotime($order_info['date_added']));
			$data['order_date_modified'] = date($this->language->get('date_format_short'), strtotime($order_info['date_modified']));
			$data['order_time_modified'] = date($this->language->get('time_format'), strtotime($order_info['date_modified']));
			$data['order_datetime_added'] = $order_info['date_added'];
			$data['order_datetime_modified'] = $order_info['date_modified'];

			$data['firstname'] = $order_info['firstname'];
			$data['lastname'] = $order_info['lastname'];

			if ($order_info['customer_id']) {
				$data['customer'] = $this->url->link('customer/customer/edit', 'token=' . $this->session->data['token'] . '&customer_id=' . $order_info['customer_id'], true);
			} else {
				$data['customer'] = '';
			}

			$this->load->model('customer/customer_group');

			$customer_group_info = $this->model_customer_customer_group->getCustomerGroup($order_info['customer_group_id']);

			if ($customer_group_info) {
				$data['customer_group'] = $customer_group_info['name'];
			} else {
				$data['customer_group'] = '';
			}

			$data['email'] = $order_info['email'];
			$data['telephone'] = $order_info['telephone'];

			$data['shipping_method'] = $order_info['shipping_method'];
			$data['payment_method'] = $order_info['payment_method'];

			// Payment Address
			if ($order_info['payment_address_format']) {
				$format = $order_info['payment_address_format'];
			} else {
				$format = '{firstname} {lastname}' . "\n" . '{company}' . "\n" . '{address_1}' . "\n" . '{address_2}' . "\n" . '{city} {postcode}' . "\n" . '{zone}' . "\n" . '{country}';
			}

			$find = array(
				'{firstname}',
				'{lastname}',
				'{company}',
				'{address_1}',
				'{address_2}',
				'{city}',
				'{postcode}',
				'{zone}',
				'{zone_code}',
				'{country}'
			);

			$replace = array(
				'firstname' => $order_info['payment_firstname'],
				'lastname'  => $order_info['payment_lastname'],
				'company'   => $order_info['payment_company'],
				'address_1' => $order_info['payment_address_1'],
				'address_2' => $order_info['payment_address_2'],
				'city'      => $order_info['payment_city'],
				'postcode'  => $order_info['payment_postcode'],
				'zone'      => $order_info['payment_zone'],
				'zone_code' => $order_info['payment_zone_code'],
				'country'   => $order_info['payment_country']
			);

			$data['payment_address'] = str_replace(array("\r\n", "\r", "\n"), '<br />', preg_replace(array("/\s\s+/", "/\r\r+/", "/\n\n+/"), '<br />', trim(str_replace($find, $replace, $format))));

			// Shipping Address
			if ($order_info['shipping_address_format']) {
				$format = $order_info['shipping_address_format'];
			} else {
				$format = '{firstname} {lastname}' . "\n" . '{company}' . "\n" . '{address_1}' . "\n" . '{address_2}' . "\n" . '{city} {postcode}' . "\n" . '{zone}' . "\n" . '{country}';
			}

			$find = array(
				'{firstname}',
				'{lastname}',
				'{company}',
				'{address_1}',
				'{address_2}',
				'{city}',
				'{postcode}',
				'{zone}',
				'{zone_code}',
				'{country}'
			);

			$replace = array(
				'firstname' => $order_info['shipping_firstname'],
				'lastname'  => $order_info['shipping_lastname'],
				'company'   => $order_info['shipping_company'],
				'address_1' => $order_info['shipping_address_1'],
				'address_2' => $order_info['shipping_address_2'],
				'city'      => $order_info['shipping_city'],
				'postcode'  => $order_info['shipping_postcode'],
				'zone'      => $order_info['shipping_zone'],
				'zone_code' => $order_info['shipping_zone_code'],
				'country'   => $order_info['shipping_country']
			);

			$data['shipping_address'] = str_replace(array("\r\n", "\r", "\n"), '<br />', preg_replace(array("/\s\s+/", "/\r\r+/", "/\n\n+/"), '<br />', trim(str_replace($find, $replace, $format))));

			// Uploaded files
			$this->load->model('tool/upload');

			$data['products'] = array();

			$products = $this->model_sale_order->getOrderProducts($order_info['order_id']);

			foreach ($products as $product) {
				$option_data = array();

				$options = $this->model_sale_order->getOrderOptions($order_info['order_id'], $product['order_product_id']);

				foreach ($options as $option) {
					if ($option['type'] != 'file') {
						$option_data[] = array(
							'name'  => $option['name'],
							'value' => $option['value'],
							'type'  => $option['type']
						);
					} else {
						$upload_info = $this->model_tool_upload->getUploadByCode($option['value']);

						if ($upload_info) {
							$option_data[] = array(
								'name'  => $option['name'],
								'value' => $upload_info['name'],
								'type'  => $option['type'],
								'href'  => $this->url->link('tool/upload/download', 'token=' . $this->session->data['token'] . '&code=' . $upload_info['code'], true)
							);
						}
					}
				}

				$data['products'][] = array(
					'order_product_id' => $product['order_product_id'],
					'product_id'       => $product['product_id'],
					'name'    	 	   => $product['name'],
					'model'    		   => $product['model'],
					'option'   		   => $option_data,
					'quantity'		   => $product['quantity'],
					'price'    		   => $this->currency->format($product['price'] + ($this->config->get('config_tax') ? $product['tax'] : 0), $order_info['currency_code'], $order_info['currency_value']),
					'total'    		   => $this->currency->format($product['total'] + ($this->config->get('config_tax') ? ($product['tax'] * $product['quantity']) : 0), $order_info['currency_code'], $order_info['currency_value']),
					'href'     		   => $this->url->link('catalog/product/edit', 'token=' . $this->session->data['token'] . '&product_id=' . $product['product_id'], true)
				);
			}

			$data['vouchers'] = array();

			$vouchers = $this->model_sale_order->getOrderVouchers($order_info['order_id']);

			foreach ($vouchers as $voucher) {
				$data['vouchers'][] = array(
					'description' => $voucher['description'],
					'amount'      => $this->currency->format($voucher['amount'], $order_info['currency_code'], $order_info['currency_value']),
					'href'        => $this->url->link('sale/voucher/edit', 'token=' . $this->session->data['token'] . '&voucher_id=' . $voucher['voucher_id'], true)
				);
			}

			$data['totals'] = array();

			$totals = $this->model_sale_order->getOrderTotals($order_info['order_id']);

			foreach ($totals as $total) {
				$data['totals'][] = array(
					'title' => $total['title'],
					'text'  => $this->currency->format($total['value'], $order_info['currency_code'], $order_info['currency_value'])
				);
			}

			$data['comment'] = nl2br($order_info['comment']);

			$order_status_info = $this->model_localisation_order_status->getOrderStatus($order_info['order_status_id']);

			if ($order_status_info) {
				$data['order_status'] = $order_status_info['name'];
			} else {
				$data['order_status'] = '';
			}

			$data['order_status_id'] = $order_info['order_status_id'];

			$data['order_complete'] = in_array($order_info['order_status_id'],$this->complete_statuses);
			$data['order_processing'] = in_array($order_info['order_status_id'],$this->processing_statuses);

			$data['account_custom_field'] = $order_info['custom_field'];

			// Custom Fields
			$this->load->model('customer/custom_field');

			$data['account_custom_fields'] = array();

			$filter_data = array(
				'sort'  => 'cf.sort_order',
				'order' => 'ASC'
			);

			$custom_fields = $this->model_customer_custom_field->getCustomFields($filter_data);

			// Custom fields
			$data['payment_custom_fields'] = array();

			foreach ($custom_fields as $custom_field) {
				if ($custom_field['location'] == 'address' && isset($order_info['payment_custom_field'][$custom_field['custom_field_id']])) {
					if ($custom_field['type'] == 'select' || $custom_field['type'] == 'radio') {
						$custom_field_value_info = $this->model_customer_custom_field->getCustomFieldValue($order_info['payment_custom_field'][$custom_field['custom_field_id']]);

						if ($custom_field_value_info) {
							$data['payment_custom_fields'][] = array(
								'name'  => $custom_field['name'],
								'value' => $custom_field_value_info['name'],
								'sort_order' => $custom_field['sort_order']
							);
						}
					}

					if ($custom_field['type'] == 'checkbox' && is_array($order_info['payment_custom_field'][$custom_field['custom_field_id']])) {
						foreach ($order_info['payment_custom_field'][$custom_field['custom_field_id']] as $custom_field_value_id) {
							$custom_field_value_info = $this->model_customer_custom_field->getCustomFieldValue($custom_field_value_id);

							if ($custom_field_value_info) {
								$data['payment_custom_fields'][] = array(
									'name'  => $custom_field['name'],
									'value' => $custom_field_value_info['name'],
									'sort_order' => $custom_field['sort_order']
								);
							}
						}
					}

					if ($custom_field['type'] == 'text' || $custom_field['type'] == 'textarea' || $custom_field['type'] == 'file' || $custom_field['type'] == 'date' || $custom_field['type'] == 'datetime' || $custom_field['type'] == 'time') {
						$data['payment_custom_fields'][] = array(
							'name'  => $custom_field['name'],
							'value' => $order_info['payment_custom_field'][$custom_field['custom_field_id']],
							'sort_order' => $custom_field['sort_order']
						);
					}

					if ($custom_field['type'] == 'file') {
						$upload_info = $this->model_tool_upload->getUploadByCode($order_info['payment_custom_field'][$custom_field['custom_field_id']]);

						if ($upload_info) {
							$data['payment_custom_fields'][] = array(
								'name'  => $custom_field['name'],
								'value' => $upload_info['name'],
								'sort_order' => $custom_field['sort_order']
							);
						}
					}
				}
			}

			// Shipping
			$data['shipping_custom_fields'] = array();

			foreach ($custom_fields as $custom_field) {
				if ($custom_field['location'] == 'address' && isset($order_info['shipping_custom_field'][$custom_field['custom_field_id']])) {
					if ($custom_field['type'] == 'select' || $custom_field['type'] == 'radio') {
						$custom_field_value_info = $this->model_customer_custom_field->getCustomFieldValue($order_info['shipping_custom_field'][$custom_field['custom_field_id']]);

						if ($custom_field_value_info) {
							$data['shipping_custom_fields'][] = array(
								'name'  => $custom_field['name'],
								'value' => $custom_field_value_info['name'],
								'sort_order' => $custom_field['sort_order']
							);
						}
					}

					if ($custom_field['type'] == 'checkbox' && is_array($order_info['shipping_custom_field'][$custom_field['custom_field_id']])) {
						foreach ($order_info['shipping_custom_field'][$custom_field['custom_field_id']] as $custom_field_value_id) {
							$custom_field_value_info = $this->model_customer_custom_field->getCustomFieldValue($custom_field_value_id);

							if ($custom_field_value_info) {
								$data['shipping_custom_fields'][] = array(
									'name'  => $custom_field['name'],
									'value' => $custom_field_value_info['name'],
									'sort_order' => $custom_field['sort_order']
								);
							}
						}
					}

					if ($custom_field['type'] == 'text' || $custom_field['type'] == 'textarea' || $custom_field['type'] == 'file' || $custom_field['type'] == 'date' || $custom_field['type'] == 'datetime' || $custom_field['type'] == 'time') {
						$data['shipping_custom_fields'][] = array(
							'name'  => $custom_field['name'],
							'value' => $order_info['shipping_custom_field'][$custom_field['custom_field_id']],
							'sort_order' => $custom_field['sort_order']
						);
					}

					if ($custom_field['type'] == 'file') {
						$upload_info = $this->model_tool_upload->getUploadByCode($order_info['shipping_custom_field'][$custom_field['custom_field_id']]);

						if ($upload_info) {
							$data['shipping_custom_fields'][] = array(
								'name'  => $custom_field['name'],
								'value' => $upload_info['name'],
								'sort_order' => $custom_field['sort_order']
							);
						}
					}
				}
			}

			$data['ip'] = $order_info['ip'];
			$data['forwarded_ip'] = $order_info['forwarded_ip'];
			$data['user_agent'] = $order_info['user_agent'];
			$data['accept_language'] = $order_info['accept_language'];

			// Additional Tabs
			$data['tabs'] = array();

			if ($this->user->hasPermission('access', 'extension/payment/' . $order_info['payment_code'])) {
				if (is_file(DIR_CATALOG . 'controller/extension/payment/' . $order_info['payment_code'] . '.php')) {
					$content = $this->load->controller('extension/payment/' . $order_info['payment_code'] . '/order');
				} else {
					$content = null;
				}

				if ($content) {
					$this->load->language('extension/payment/' . $order_info['payment_code']);

					$data['tabs'][] = array(
						'code'    => $order_info['payment_code'],
						'title'   => $this->language->get('heading_title'),
						'content' => $content
					);
				}
			}

			$this->load->model('extension/extension');

			$extensions = $this->model_extension_extension->getInstalled('fraud');

			foreach ($extensions as $extension) {
				if ($this->config->get($extension . '_status')) {
					$this->load->language('extension/fraud/' . $extension);

					$content = $this->load->controller('extension/fraud/' . $extension . '/order');

					if ($content) {
						$data['tabs'][] = array(
							'code'    => $extension,
							'title'   => $this->language->get('heading_title'),
							'content' => $content
						);
					}
				}
			}

			// The URL we send API requests to
			$data['catalog'] = $this->request->server['HTTPS'] ? HTTPS_CATALOG : HTTP_CATALOG;

			$data['history'] = $this->getOrderHistory($order_id);
			return $data;
		} else {
			return new Action('error/not_found');
		}
	}

	protected function getCustomer($customer_id) {
		$customer_info = $this->model_customer_customer->getCustomer($customer_id);

		$this->load->model('customer/customer_group');

		$data['customer_groups'] = $this->model_customer_customer_group->getCustomerGroups();


		$data = array_merge($data,$customer_info);

		// Custom Fields
		$this->load->model('customer/custom_field');

		$data['custom_fields'] = array();

		$filter_data = array(
			'sort'  => 'cf.sort_order',
			'order' => 'ASC'
		);

		$custom_fields = $this->model_customer_custom_field->getCustomFields($filter_data);

		foreach ($custom_fields as $custom_field) {
			$data['custom_fields'][] = array(
				'custom_field_id'    => $custom_field['custom_field_id'],
				'custom_field_value' => $this->model_customer_custom_field->getCustomFieldValues($custom_field['custom_field_id']),
				'name'               => $custom_field['name'],
				'value'              => $custom_field['value'],
				'type'               => $custom_field['type'],
				'location'           => $custom_field['location'],
				'sort_order'         => $custom_field['sort_order']
			);
		}
		if(isset($customer_info['custom_field']))
			$data['account_custom_field'] = json_decode($customer_info['custom_field'], true);

		$this->load->model('localisation/country');

		$data['addresses'] = $this->model_customer_customer->getAddresses($customer_id);

		$data['customer_histories'] = $customer_id != 0 ? $this->getCustomerHistory($customer_id) : '';

		return $data;
	}

	public function getCustomerHistory($customer_id) {
		$data['text_no_results'] = $this->language->get('text_no_results');

		$data['column_date_added'] = $this->language->get('column_date_added');
		$data['column_comment'] = $this->language->get('column_comment');

		if (isset($this->request->get['page'])) {
			$page = $this->request->get['page'];
		} else {
			$page = 1;
		}

		$data['histories'] = array();

		$results = $this->model_customer_customer->getHistories($customer_id, ($page - 1) * 10, 10);

		foreach ($results as $result) {
			$data['histories'][] = array(
				'comment'    => $result['comment'],
				'date_added' => date($this->language->get('date_format_short'), strtotime($result['date_added']))
			);
		}

		$history_total = $this->model_customer_customer->getTotalHistories($customer_id);

		$pagination = new Pagination();
		$pagination->total = $history_total;
		$pagination->page = $page;
		$pagination->limit = 10;
		$pagination->url = $this->url->link('customer/customer/history', 'token=' . $this->session->data['token'] . '&customer_id=' .$customer_id. '&page={page}', true);

		$data['pagination'] = $pagination->render();

		$data['results'] = sprintf($this->language->get('text_pagination'), ($history_total) ? (($page - 1) * 10) + 1 : 0, ((($page - 1) * 10) > ($history_total - 10)) ? $history_total : ((($page - 1) * 10) + 10), $history_total, ceil($history_total / 10));

		return $this->loadTemplate('customer/customer_history', $data);
	}

	protected function getOrderHistory($order_id) {
		$data['text_no_results'] = $this->language->get('text_no_results');

		$data['column_date_added'] = $this->language->get('column_date_added');
		$data['column_status'] = $this->language->get('column_status');
		$data['column_notify'] = $this->language->get('column_notify');
		$data['column_comment'] = $this->language->get('column_comment');

		if (isset($this->request->get['page'])) {
			$page = $this->request->get['page'];
		} else {
			$page = 1;
		}

		$data['histories'] = array();

		$results = $this->model_sale_order->getOrderHistories($order_id, ($page - 1) * 10, 10);

		foreach ($results as $result) {
			$data['histories'][] = array(
				'notify'     => $result['notify'] ? $this->language->get('text_yes') : $this->language->get('text_no'),
				'status'     => $result['status'],
				'comment'    => nl2br($result['comment']),
				'date_added' => date($this->language->get('date_format_short'), strtotime($result['date_added']))
			);
		}

		$history_total = $this->model_sale_order->getTotalOrderHistories($order_id);

		$pagination = new Pagination();
		$pagination->total = $history_total;
		$pagination->page = $page;
		$pagination->limit = 10;
		$pagination->url = $this->url->link('sale/order/history', 'token=' . $this->session->data['token'] . '&order_id=' . $order_id . '&page={page}', true);

		$data['pagination'] = $pagination->render();

		$data['results'] = sprintf($this->language->get('text_pagination'), ($history_total) ? (($page - 1) * 10) + 1 : 0, ((($page - 1) * 10) > ($history_total - 10)) ? $history_total : ((($page - 1) * 10) + 10), $history_total, ceil($history_total / 10));

		return $this->loadTemplate('sale/order_history', $data);
	}

	public function check($timestamp = 0){
		//This is the timestamp of the latest product the cliend browser has
		//It is 0 when the window/tab opens for the first time
		$timestamp = isset($this->request->get['timestamp']) 
			? (int) $this->request->get['timestamp']
			: 0;
		$json = array(
			'orders' => array(),
			'order_count' => 0,
			'new_timestamp' => 0,
			'previous_timestamp' => $timestamp
		);
		// If this is the first request, just send a new timestamp
		if ($timestamp == 0 ){
			$order = $this->model_tw_orderslive->getLatestOrder();
			$json['new_timestamp'] = strtotime($order['date_modified']);
		} else {
			$orders = $this->model_tw_orderslive->getOrdersNewerThan($timestamp);

			$order_data['text'] = $this->loadText();
			$new_timestamp = $timestamp;
			foreach($orders as $o){
				//Set new timestamp to date of most recently changed order;
				$order_modified_timestamp = strtotime($o['date_modified']);
				if($order_modified_timestamp > $timestamp) $new_timestamp = $order_modified_timestamp;
				$order_data['order'] = $this->getOrder($o['order_id']);
				$json['orders'][] = [
					'order_id'      => $o['order_id'],
					'order_data'    => $this->loadTemplate('sale/tw_order_live_info', $order_data),
					'order_tab'     => $this->loadTemplate('sale/tw_order_live_tab', $order_data),
					'timestamp' 	=> $order_modified_timestamp
				];
			}
			$json['order_count'] = count($orders);
	
			//Send the timestamp of the most recently added/changed order
			$json['previous_timestamp'] = $timestamp;
			$json['new_timestamp'] = $new_timestamp;
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	public function more($page = 1){
		if(!isset($this->request->get['page'])) return; 

		$page = (int)$this->request->get['page'];

		$orders = $this->model_tw_orderslive->getMoreOrders($page);
		$order_data['text'] = $this->loadText();

		$json = array(
			'orders' => array(),
			'order_count' => 0,
			'page' => 0
		);

		foreach($orders as $o){
			//Set new timestamp to date of most recently changed order;
			$order_modified_timestamp = strtotime($o['date_modified']);
			$order_data['order'] = $this->getOrder($o['order_id']);
			$json['orders'][] = [
				'order_id'      => $o['order_id'],
				'order_data'    => $this->loadTemplate('sale/tw_order_live_info', $order_data),
				'order_tab'     => $this->loadTemplate('sale/tw_order_live_tab', $order_data),
				'timestamp' 	=> $order_modified_timestamp
			];
		}
		$json['page'] = count($orders)  == 10 ? $page + 1 : 0;
		
		$json['order_count'] = count($orders);

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}


	public function refresh(){
		if(isset($this->request->get['order_id'])){
			$order_id = (int)$this->request->get['order_id'];
			$order_data['text'] = $this->loadText();

			if($order_id) {
				$order_data['order'] = $this->getOrder($order_id);
				$json['timestamp'] = strtotime($order_data['order']['details']['order_datetime_modified']);
				$json['order_id'] = $order_id;
				$json['order_data']  = $this->loadTemplate('sale/tw_order_live_info', $order_data);
				$json['order_tab']  = $this->loadTemplate('sale/tw_order_live_tab', $order_data);
			}
			$this->response->addHeader('Content-Type: application/json');
			$this->response->setOutput(json_encode($json));
		}
	}
	
	protected function loadText(){
		$data = array();
		//Language
		$data += $this->load->language('sale/order');
		$data += $this->load->language('customer/customer');
		$data['order_statuses'] = $this->model_localisation_order_status->getOrderStatuses();
		$data['text_ip_add'] = sprintf($this->language->get('text_ip_add'), $this->request->server['REMOTE_ADDR']);
		
		return $data;
	}

	public function debug(){
		$this->load->model('tw/orderslive');
		$order = $this->model_tw_orderslive->getOrdersNewerThan(1542382825);
		d($order,strtotime($order['date_added']),strtotime($order['date_modified']));
	}
}