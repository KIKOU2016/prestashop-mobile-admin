<?php
/*
* 2007-2016 PrestaShop
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
*  @author PrestaShop SA <contact@prestashop.com>
*  @copyright  2007-2016 PrestaShop SA
*  @license    http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*/

/**
 * @since 1.5.0
 */
class ApimoduleOrdersModuleFrontController extends ModuleFrontController {
	public $ssl = true;
	public $display_column_left = false;
	public $header = false;
	public $errors = [];
	public $API_VERSION = 1.8;
	public $return = [];

	/**
	 * @see FrontController::initContent()
	 */
	public function initContent() {
		$this->return['status'] = false;
		if(isset($_GET['action']) && $this->valid()){

			$action = $_GET['action'];
			switch ($action){
				case 'list':$this->getOrdersList();break;
				case 'products':$this->getOrderProducts();break;
				case 'history':$this->getOrdersHistory();break;
				case 'info':$this->getOrdersInfo();break;
				case 'pad':$this->getPaymentAndDelivery();break;
				case 'status_update':$this->statusUpdate();break;
				case 'delivery_update':$this->changeOrderDelivery();break;
			}
		}
		$this->errors[] = "No action";
		header( 'Content-Type: application/json' );
		die( Tools::jsonEncode( $this->return ) );
	}

	private function valid() {
		$token = trim( Tools::getValue( 'token' ) );
		if ( empty( $token ) ) {
			$this->errors[] = 'You need to be logged!';
			return false;
		} else {
			$results = $this->getTokens( $token );
			if (!$results ) {
				$this->errors = 'Your token is no longer relevant!';
				return false;
			}
		}

		return true;
	}
	public function getTokens($token){
		$sql = "SELECT * FROM " . _DB_PREFIX_ . "apimodule_user_token
		        WHERE token = '".$token."'";

		if ($row = Db::getInstance()->getRow($sql)){
			return $row;
		}
		else{
			return false;
		}
	}


	/**
	 * @api {get} /index.php?action=list&fc=module&module=apimodule&controller=orders  getOrders
	 * @apiName GetOrders
	 * @apiGroup Orders
	 *
	 * @apiParam {Token} token your unique token.
	 * @apiParam {Number} page number of the page.
	 * @apiParam {Number} limit limit of the orders for the page.
	 * @apiParam {Array} filter array of the filter params.
	 * @apiParam {String} filter[fio] full name of the client.
	 * @apiParam {Number} filter[order_status_id] unique id of the order.
	 * @apiParam {Number} filter[min_price] min price of order.
	 * @apiParam {Number} filter[max_price] max price of order.
	 * @apiParam {Date} filter[date_min] min date adding of the order.
	 * @apiParam {Date} filter[date_max] max date adding of the order.
	 *
	 * @apiSuccess {Number} version  Current API version.
	 * @apiSuccess {Array} orders  Array of the orders.
	 * @apiSuccess {Array} statuses  Array of the order statuses.
	 * @apiSuccess {Number} order_id  ID of the order.
	 * @apiSuccess {Number} order_number  Number of the order.
	 * @apiSuccess {String} fio     Client's FIO.
	 * @apiSuccess {String} status  Status of the order.
	 * @apiSuccess {String} currency_code  Default currency of the shop.
	 * @apiSuccess {String} order[currency_code] currency of the order.
	 * @apiSuccess {Number} total  Total sum of the order.
	 * @apiSuccess {Date} date_added  Date added of the order.
	 * @apiSuccess {Date} total_quantity  Total quantity of the orders.
	 *
	 *
	 *
	 *
	 * @apiSuccessExample Success-Response:
	 *     HTTP/1.1 200 OK
	 * {
	 *   "Response"
	 *   {
	 *      "orders":
	 *      {
	 *            {
	 *             "order_id" : "1",
	 *             "order_number" : "1",
	 *             "fio" : "Anton Kiselev",
	 *             "status" : "Сделка завершена",
	 *             "total" : "106.00",
	 *             "date_added" : "2016-12-09 16:17:02",
	 *             "currency_code": "RUB"
	 *             },
	 *            {
	 *             "order_id" : "2",
	 *             "order_number" : "2",
	 *             "fio" : "Vlad Kochergin",
	 *             "status" : "В обработке",
	 *             "total" : "506.00",
	 *             "date_added" : "2016-10-19 16:00:00",
	 *             "currency_code": "RUB"
	 *             }
	 *       },
	 *       "statuses" :
	 *                  {
	 *                         {
	 *                             "name": "Отменено",
	 *                             "id_order_state": "7",
	 *                             "id_lang": "1"
	 *                         },
	 *                         {
	 *                             "name": "Сделка завершена",
	 *                             "id_order_state": "5",
	 *                             "id_lang": "1"
	 *                          },
	 *                          {
	 *                              "name": "Ожидание",
	 *                              "id_order_state": "1",
	 *                              "id_lang": "1"
	 *                           }
	 *                    }
	 *       "currency_code": "RUB",
	 *       "total_quantity": 50,
	 *       "total_sum": "2026.00",
	 *       "max_price": "1405.00"
	 *   },
	 *   "Status" : true,
	 *   "version": 1.0
	 * }
	 * @apiErrorExample Error-Response:
	 *
	 * {
	 *      "version": 1.0,
	 *      "Status" : false
	 *
	 * }
	 *
	 *
	 */
	public function getOrdersList(){
		$page = trim( Tools::getValue( 'page' ) );
		$limit = trim( Tools::getValue( 'limit' ) );
		$filter = trim( Tools::getValue( 'filter' ) );
		$platform = trim( Tools::getValue( 'platform' ) );
		$order_status_id = trim( Tools::getValue( 'order_status_id' ) );
		$fio = trim( Tools::getValue( 'fio' ) );
		$min_price = trim( Tools::getValue( 'min_price' ) );
		$max_price = trim( Tools::getValue( 'max_price' ) );
		$date_min = trim( Tools::getValue( 'date_min' ) );
		$date_max = trim( Tools::getValue( 'date_max' ) );

		if (isset($page) && (int)$page!= 0 && isset($limit) && (int)$limit != 0) {
			$page = ($page - 1) * $limit;
			$limit = $limit;
		} else {
			$page = 0;
			$limit = 9999;
		}

		if (isset($filter)) {
			$orders = $this->getOrders(array('filter' => $filter, 'page' => $page, 'limit' => $limit));
		} elseif (!empty($platform) && $platform == 'android') {
			$filter = [];
			$filter['order_status_id'] = !empty($order_status_id)?$order_status_id:'';
			$filter['fio'] = !empty($fio)?$fio:'';
			$filter['min_price'] = !empty($min_price)?$min_price:1;
			$filter['max_price'] = !empty($max_price)?$max_price:$this->getMaxOrderPrice();
			$filter['date_min'] = !empty($date_min)?$date_min:1;
			$filter['date_max'] = !empty($date_max)?$date_max:1;

			$orders = $this->getOrders(array('filter' => $filter, 'page' => $page, 'limit' => $limit));

		} else {
			$orders = $this->getOrders(array('page' => $page, 'limit' => $limit));
		}
		$response = [];
		$orders_to_response = [];

		$sum = 0;
		$quantity = 0;

		foreach ($orders as $order) {
			$sum = $sum + $order['total_paid'];
			$quantity++;
			$data['order_number'] = $order['id_order'];
			$data['order_id'] = $order['id_order'];
			$data['fio'] = $order['firstname'] . ' ' . $order['lastname'];

			$data['status'] = $order['id_order_state'];

			$data['total'] = number_format($order['total_paid'], 2, '.', '');
			$data['date_added'] = $order['date_add'];
			$data['currency_code'] = Context::getContext()->currency->iso_code;
			$orders_to_response[] = $data;

		}

		$this->return['response']['total_quantity'] = $quantity;
		$this->return['response']['currency_code'] = Context::getContext()->currency->iso_code;
		$this->return['response']['total_sum'] = number_format($sum, 2, '.', '');
		$this->return['response']['orders'] = $orders_to_response;
		$this->return['response']['max_price'] = $this->getMaxOrderPrice();
		$statuses = $this->OrderStatusList();
		$this->return['response']['statuses'] = $statuses;
		$this->return['response']['api_version'] = $this->API_VERSION;

		$this->return['version'] = $this->API_VERSION;

		$this->return['status'] = true;

		$this->return['errors'] = $this->errors;
		header('Content-Type: application/json');
		die(Tools::jsonEncode($this->return));

	}


	/**
	 * @api {get} /index.php?action=products&fc=module&module=apimodule&controller=orders  getOrderProducts
	 * @apiName getOrderProducts
	 * @apiGroup Orders
	 *
	 * @apiParam {Token} token your unique token.
	 * @apiParam {ID} order_id unique order id.
	 *
	 * @apiSuccess {Number} version  Current API version.
	 * @apiSuccess {Url} image  Picture of the product.
	 * @apiSuccess {Number} quantity  Quantity of the product.
	 * @apiSuccess {String} name     Name of the product.
	 * @apiSuccess {String} model  Model of the product.
	 * @apiSuccess {Number} Price  Price of the product.
	 * @apiSuccess {Number} total_order_price  Total sum of the order.
	 * @apiSuccess {Number} total_price  Sum of product's prices.
	 * @apiSuccess {Number} shipping_price  Cost of the shipping.
	 * @apiSuccess {Number} total  Total order sum.
	 * @apiSuccess {Number} product_id  unique product id.
	 *
	 * @apiSuccessExample Success-Response:
	 *     HTTP/1.1 200 OK
	 * {
	 *      "response":
	 *          {
	 *              "products": [
	 *              {
	 *                  "image" : "http://opencart/image/catalog/demo/htc_touch_hd_1.jpg",
	 *                  "name" : "HTC Touch HD",
	 *                  "model" : "Product 1",
	 *                  "quantity" : 3,
	 *                  "price" : 100.00,
	 *                  "product_id" : 90
	 *              },
	 *              {
	 *                  "image" : "http://opencart/image/catalog/demo/iphone_1.jpg",
	 *                  "name" : "iPhone",
	 *                  "model" : "Product 11",
	 *                  "quantity" : 1,
	 *                  "price" : 500.00,
	 *                  "product_id" : 97
	 *               }
	 *            ],
	 *            "total_order_price":
	 *              {
	 *                   "total_discount": 0,
	 *                   "total_price": 2250,
	 *                   "shipping_price": 35,
	 *                   "total": 2285
	 *               }
	 *
	 *         },
	 *      "status": true,
	 *      "version": 1.0
	 * }
	 *
	 *
	 * @apiErrorExample Error-Response:
	 *
	 *     {
	 *          "error": "Can not found any products in order with id = 10",
	 *          "version": 1.0,
	 *          "Status" : false
	 *     }
	 *
	 */
	public function getOrderProducts()
	{
		$id = trim( Tools::getValue( 'order_id' ) );
		$this->return['status'] = false;
		if (!empty($id)) {
			$order = new Order($id);
			$products = $order->getProducts();

			if (count($products) > 0) {
				$data               = array();
				$total_discount_sum = 0;
				$shipping_price        = 0;
				$total_price        = 0;
				foreach ( $products as $product ):
					$array = [];
					if (!empty($product['image'])) {
						$image = Image::getCover($product['product_id']);
						$imagePath = Link::getImageLink($product->link_rewrite, $image['id_image'], 'home_default');
						$array['image'] = $imagePath;
					}

					if (!empty($product['product_name'])) {
						$array['name'] = strip_tags( htmlspecialchars_decode( $product['product_name'] ) );
					}
					if (!empty($product['model'])){
						$array['model'] = $product['model'];
					}
					if (!empty($product['quantity'])){
						$quantity = number_format( $product['quantity'], 2, '.', '' );
						$array['quantity'] = $quantity;
					}else{
						$quantity = 1;
					}
					if (!empty($product['price'])){
						$array['price'] = number_format( $product['price'], 2, '.', '' );
					}
					$array['product_id'] = $product['product_id'];

					$array['discount_price'] = $product['product_quantity_discount'];
					$array['discount']       = $product['quantity_discount'];

					$total_discount_sum += $product['product_quantity_discount'];

					$total_price += $product['price'] * $quantity;

					$shipping_price += $product['additional_shipping_cost'];

					$data['products'][] = $array;
				endforeach;

				$data['total_order_price'] = array(
					'total_discount' => $total_discount_sum,
					'total_price' => $total_price,
					'shipping_price' => +number_format($shipping_price, 2, '.', ''),
					'total' => $total_price + $shipping_price
				);

				$this->return['response'] = $data;
				$this->return['status'] = true;

			} else {
				$this->return['errors'][] = 'Can not found any products in order with id = ' . $id;

			}
		} else {

			$this->return['errors'][] = 'You have not specified ID';
		}

		$this->return['version'] = $this->API_VERSION;
		header('Content-Type: application/json');
		die(Tools::jsonEncode($this->return));
	}

	/**
	 * @api {get} /index.php?action=history&fc=module&module=apimodule&controller=orders  getOrderHistory
	 * @apiName getOrderHistory
	 * @apiGroup Orders
	 *
	 * @apiParam {Number} order_id unique order ID.
	 * @apiParam {Token} token your unique token.
	 *
	 * @apiSuccess {Number} version  Current API version.
	 * @apiSuccess {String} name     Status of the order.
	 * @apiSuccess {Number} order_status_id  ID of the status of the order.
	 * @apiSuccess {Date} date_added  Date of adding status of the order.
	 * @apiSuccess {String} comment  Some comment added from manager.
	 * @apiSuccess {Array} statuses  Statuses list for order.
	 *
	 * @apiSuccessExample Success-Response:
	 *     HTTP/1.1 200 OK
	 *       {
	 *           "response":
	 *               {
	 *                   "orders":
	 *                      {
	 *                          {
	 *                              "name": "Отменено",
	 *                              "order_status_id": "7",
	 *                              "date_added": "2016-12-13 08:27:48.",
	 *                              "comment": "Some text"
	 *                          },
	 *                          {
	 *                              "name": "Сделка завершена",
	 *                              "order_status_id": "5",
	 *                              "date_added": "2016-12-25 09:30:10.",
	 *                              "comment": "Some text"
	 *                          },
	 *                          {
	 *                              "name": "Ожидание",
	 *                              "order_status_id": "1",
	 *                              "date_added": "2016-12-01 11:25:18.",
	 *                              "comment": "Some text"
	 *                           }
	 *                       },
	 *                   "statuses" :
	 *                  {
	 *                         {
	 *                             "name": "Отменено",
	 *                             "id_order_state": "7",
	 *                             "id_lang": "1"
	 *                         },
	 *                         {
	 *                             "name": "Сделка завершена",
	 *                             "id_order_state": "5",
	 *                             "id_lang": "1"
	 *                          },
	 *                          {
	 *                              "name": "Ожидание",
	 *                              "id_order_state": "1",
	 *                              "id_lang": "1"
	 *                           }
	 *                    }
	 *               },
	 *           "status": true,
	 *           "version": 1.0
	 *       }
	 * @apiErrorExample Error-Response:
	 *
	 *     {
	 *          "error": "Can not found any statuses for order with id = 5",
	 *          "version": 1.0,
	 *          "Status" : false
	 *     }
	 */

	public function getOrdersHistory(){
		$id = trim( Tools::getValue( 'order_id' ) );
		if (!empty($id)) {
			$order = new Order($id);
			$history = $order->getHistory();
			$data = array();
			$response = [];
			$statuses = $this->OrderStatusList();
			$statusArray = [];
			foreach ($statuses as $one):
				$statusArray[$one['id_order_state']] = $one['name'];
			endforeach;

			if (!empty($history)) {
				foreach ( $history as $item ):
					$statusId = $item['id_order_state'];
					$data['name'] = $statusArray[$statusId];
					$data['order_status_id'] =$statusId;
					$data['date_added'] = $item['date_add'];
					$data['comment'] ='';
					$response['orders'][] = $data;
				endforeach;

				$response['statuses'] = $statuses;

				$this->return['status'] = true;
				$this->return['response'] = $response;

			} else {

				$this->return['status']  = false;
				$this->return['errors']  = 'Can not found any statuses for order with id = ' . $id;

			}
		} else {
			$this->return['status']  = false;
			$this->return['errors']  = 'You have not specified ID';
		}
		$this->return['version'] = $this->API_VERSION;
		header( 'Content-Type: application/json' );
		die( Tools::jsonEncode( $this->return ) );
	}

	/**
	 * @api {get} /index.php?action=info&fc=module&module=apimodule&controller=orders  getOrderInfo
	 * @apiName getOrderInfo
	 * @apiGroup Orders
	 *
	 * @apiParam {Number} order_id unique order ID.
	 * @apiParam {Token} token your unique token.
	 *
	 * @apiSuccess {Number} version  Current API version.
	 * @apiSuccess {Number} order_number  Number of the order.
	 * @apiSuccess {String} fio     Client's FIO.
	 * @apiSuccess {String} status  Status of the order.
	 * @apiSuccess {String} email  Client's email.
	 * @apiSuccess {Number} phone  Client's phone.
	 * @apiSuccess {Number} total  Total sum of the order.
	 * @apiSuccess {currency_code} status  Default currency of the shop.
	 * @apiSuccess {Date} date_added  Date added of the order.
	 * @apiSuccess {Array} statuses  Statuses list for order.
	 *
	 * @apiSuccessExample Success-Response:
	 *     HTTP/1.1 200 OK
	 * {
	 *      "response" :
	 *          {
	 *              "order_number" : "6",
	 *              "currency_code": "RUB",
	 *              "fio" : "Anton Kiselev",
	 *              "email" : "client@mail.ru",
	 *              "telephone" : "056 000-11-22",
	 *              "date_added" : "2016-12-24 12:30:46",
	 *              "total" : "1405.00",
	 *              "status" : "Сделка завершена",
	 *              "statuses" :
	 *                  {
	 *                         {
	 *                             "name": "Отменено",
	 *                             "id_order_state": "7",
	 *                             "id_lang": "1"
	 *                         },
	 *                         {
	 *                             "name": "Сделка завершена",
	 *                             "id_order_state": "5",
	 *                             "id_lang": "1"
	 *                          },
	 *                          {
	 *                              "name": "Ожидание",
	 *                              "id_order_state": "1",
	 *                              "id_lang": "1"
	 *                           }
	 *                    }
	 *          },
	 *      "status" : true,
	 *      "version": 1.0
	 * }
	 *
	 * @apiErrorExample Error-Response:
	 *
	 *     {
	 *       "error" : "Can not found order with id = 5",
	 *       "version": 1.0,
	 *       "Status" : false
	 *     }
	 */
	public function getOrdersInfo(){
		$id = trim( Tools::getValue( 'order_id' ) );

		$this->return['status']  = false;
		if (!empty($id)) {
			$order = new Order($id);
			/*echo "<pre>";
			print_r($order);
			echo "</pre>";
			die();*/
			$data = array();
			$statuses = $this->OrderStatusList();
			$statusArray = [];
			foreach ($statuses as $one):
				$statusArray[$one['id_order_state']] = $one['name'];
			endforeach;

			$customer = new Customer($order->id_customer);

			if ($order) {
				$data['order_number'] = $order->id_order;

				if (isset($customer->firstname) && isset($customer->lastname)) {
					$data['fio'] = $customer->firstname . ' ' . $customer->lastname;
				}
				if (isset($customer->email)) {
					$data['email'] = $customer->email;
				} else {
					$data['email'] = '';
				}
				/*if (isset($customer->telephone)) {
					$data['telephone'] = $customer->telephone;
				} else {
					$data['telephone'] = '';
				}*/
				$data['telephone'] = '';

				$data['date_add'] = $order->date_add;

				if (isset($order->total_paid)) {
					$data['total'] = number_format($order->total_paid, 2, '.', '');;
				}
				if (isset($order->current_state)) {
					$data['status'] = $statusArray[$order->current_state];
				} else {
					$data['status'] = '';
				}

				$data['statuses'] = $statuses;
				$data['currency_code'] = Context::getContext()->currency->iso_code;;

				$this->return['status']  = true;
				$this->return['response']  = $data;

			} else {

				$this->return['errors']  = 'Can not found order with id = ' . $id;
			}
		} else {

			$this->return['errors']  = 'You have not specified ID';
		}

		$this->return['version'] = $this->API_VERSION;
		header( 'Content-Type: application/json' );
		die( Tools::jsonEncode( $this->return ) );
	}


	/**
	 * @api {get} /index.php?action=pad&fc=module&module=apimodule&controller=orders  getOrderPaymentAndDelivery
	 * @apiName getOrderPaymentAndDelivery
	 * @apiGroup Orders
	 *
	 * @apiParam {Number} order_id unique order ID.
	 * @apiParam {Token} token your unique token.
	 *
	 * @apiSuccess {Number} version  Current API version.
	 * @apiSuccess {String} payment_method     Payment method.
	 * @apiSuccess {String} shipping_method  Shipping method.
	 * @apiSuccess {String} shipping_address  Shipping address.
	 * @apiSuccess {String} shipping_phone  Shipping phone.
	 * @apiSuccess {String} shipping_phone_mobile  Shipping phone mobile.
	 * @apiSuccess {String} payment_address  Payment address.
	 * @apiSuccess {String} payment_phone  Payment phone.
	 * @apiSuccess {String} payment_phone_mobile  Payment mobile phone.
	 *
	 * @apiSuccessExample Success-Response:
	 *     HTTP/1.1 200 OK
	 *
	 *      {
	 *          "response":
	 *              {
	 *                  "payment_method" : "Оплата при доставке",
	 *                  "shipping_method" : "Доставка с фиксированной стоимостью доставки",
	 *                  "shipping_address" : "проспект Карла Маркса 1, Днепропетровск, Днепропетровская область, Украина."
	 *                  "shipping_phone" : "123-123-123."
	 *                  "shipping_phone_mobile" : "132-123-123"
	 *                  "payment_address" : "проспект Карла Маркса 1, Днепропетровск, Днепропетровская область, Украина."
	 *                  "payment_phone" : "132-123-123"
	 *                  "payment_phone_mobile" : "132-123-123"
	 *              },
	 *          "status": true,
	 *          "version": 1.0
	 *      }
	 * @apiErrorExample Error-Response:
	 *
	 *    {
	 *      "error": "Can not found order with id = 90",
	 *      "version": 1.0,
	 *      "Status" : false
	 *   }
	 *
	 */
	public function getPaymentAndDelivery()
	{
		$id = trim( Tools::getValue( 'order_id' ) );

		$this->return['status']  = false;
		if (!empty($id)) {
			$order = new Order($id);
			$address_delivery = new Address(intval($order->id_address_delivery));
			$address_payment = new Address(intval($order->id_address_invoice));
			$id_carrier = $order->id_carrier;
			$carriers = new Carrier($id_carrier);
			foreach ($carriers->delay as $one):
				$shipping_method = $one;
			endforeach;
//			echo "<pre>";
//			print_r($order);
//			print_r($carriers);
//			print_r($address_delivery);
//			print_r($address_payment);
//			echo "</pre>";
//			die();
			$data = array();
			$statuses = $this->OrderStatusList();
			$statusArray = [];
			foreach ($statuses as $one):
				$statusArray[$one['id_order_state']] = $one['name'];
			endforeach;


			if ($order) {

				$data['shipping_address'] = '';
				$data['payment_address'] = '';

				if (!empty($order->payment)) {
					$data['payment_method'] = $order->payment;
				}
				if (!empty($shipping_method)) {
					$data['shipping_method'] = $shipping_method;
				}
				if (!empty($address_delivery->country)) {
					$data['shipping_address'] .= $address_delivery->country." ";
				}
				if (!empty($address_delivery->alias)) {
					$data['shipping_address'] .= $address_delivery->alias." ";
				}
				if (!empty($address_delivery->address1)) {
					$data['shipping_address'] .= $address_delivery->address1." ";
				}
				if (!empty($address_delivery->address2)) {
					$data['shipping_address'] .= $address_delivery->address2." ";
				}
				if (!empty($address_delivery->postcode)) {
					$data['shipping_address'] .= $address_delivery->postcode." ";
				}
				if (!empty($address_delivery->city)) {
					$data['shipping_address'] .= $address_delivery->city." ";
				}
				if (!empty($address_delivery->phone)) {
					$data['shipping_phone'] .= $address_delivery->phone;
				}
				if (!empty($address_delivery->phone_mobile)) {
					$data['shipping_phone_mobile'] .= $address_delivery->phone_mobile;
				}

				if (!empty($address_payment->country)) {
					$data['payment_address'] .= $address_payment->country." ";
				}
				if (!empty($address_payment->alias)) {
					$data['payment_address'] .= $address_payment->alias." ";
				}
				if (!empty($address_payment->address1)) {
					$data['payment_address'] .= $address_payment->address1." ";
				}
				if (!empty($address_payment->address2)) {
					$data['payment_address'] .= $address_payment->address2." ";
				}
				if (!empty($address_payment->postcode)) {
					$data['payment_address'] .= $address_payment->postcode." ";
				}
				if (!empty($address_payment->city)) {
					$data['payment_address'] .= $address_payment->city." ";
				}
				if (!empty($address_payment->phone)) {
					$data['payment_phone'] .= $address_payment->phone;
				}
				if (!empty($address_payment->phone_mobile)) {
					$data['payment_phone_mobile'] .= $address_payment->phone_mobile;
				}

				$this->return['status']  = true;
				$this->return['response']  = $data;

			} else {
				$this->return['errors']  = 'Can not found order with id = ' . $id;
					}
		} else {
			$this->return['errors']  = 'You have not specified ID';
		}

		$this->return['version'] = $this->API_VERSION;
		header( 'Content-Type: application/json' );
		die( Tools::jsonEncode( $this->return ) );
	}

	/**
	 * @api {get} index.php?action=status_update&fc=module&module=apimodule&controller=orders  statusUpdate
	 * @apiName update Order Status
	 * @apiGroup Orders
	 *
	 * @apiParam {Number} order_id unique order ID.
	 * @apiParam {Token} token your unique token.
	 *
	 * @apiSuccess {Number} version  Current API version.
	 * @apiSuccess {String} status_id    Status id.
	 * @apiSuccess {String} order_id   Order id.
	 * @apiSuccess {String} inform    Inform.
	 * @apiSuccessExample Success-Response:
	 *     HTTP/1.1 200 OK
	 *
	 *      {
	 *          "status": true,
	 *          "version": 1.0
	 *      }
	 * @apiErrorExample Error-Response:
	 *
	 *    {
	 *      "error": "Can not found order with id = 90",
	 *      "version": 1.0,
	 *      "Status" : false
	 *   }
	 *
	 */
	public function statusUpdate()
	{
		$statusId = trim( Tools::getValue( 'status_id' ) );
		$orderId = trim( Tools::getValue( 'order_id' ) );
		$inform = trim( Tools::getValue( 'inform' ) );

		$this->return['status']  = false;
		if (!empty($statusId) && !empty($orderId)) {

			$sql = "SELECT id_order_history FROM " . _DB_PREFIX_ . "order_history as oh WHERE oh.order_id = '" . $orderId."'";

			if ($row = Db::getInstance()->getRow($sql)) {

				$insert = Db::getInstance()->insert('order_history', array(
					'id_employee' => 1,
					'id_order'      => $orderId,
					'id_order_state'      => $statusId,
					'date_add'      => date('Y-m:d H:i:s')
				));
				$insert_id = Db::getInstance()->Insert_ID();
				if ( $inform == true ) {
					$sql = "SELECT c.email, c.firstname  FROM " . _DB_PREFIX_ . "customer AS c
				        INNER JOIN " . _DB_PREFIX_ . "orders as o ON c.id_customer = o.id_customer                    
				        WHERE o.id_order = " . $orderId;

					if($data = Db::getInstance()->getRow($sql)) {
						$order = new Order($orderId);
						$order_state = new OrderState($statusId);
						$history = new OrderHistory($insert_id);

						$templateVars = array();

						$history->sendEmail($order, $templateVars);
					}
				}
				$this->return['status']  = true;
			}
			$this->return['errors'][]  = "Can not found order with id = ' . $orderId";
		}
		$this->return['errors'][]  = "You have not specified order Id or status Id";
		$this->return['version'] = $this->API_VERSION;
		header( 'Content-Type: application/json' );
		die( Tools::jsonEncode( $this->return ) );

	}

	/**
	 * @api {get} index.php?action=delivery_update&fc=module&module=apimodule&controller=orders  changeOrderDelivery
	 * @apiName update Order Delivery
	 * @apiGroup Orders
	 *
	 * @apiParam {String} address New shipping address.
	 * @apiParam {String} city New shipping city.
	 * @apiParam {Number} order_id unique order ID.
	 * @apiParam {Token} token your unique token.
	 *
	 * @apiSuccess {Number} version  Current API version.
	 * @apiSuccess {Boolean} response Status of change address.
	 *
	 * @apiSuccessExample Success-Response:
	 *     HTTP/1.1 200 OK
	 *   {
	 *         "status": true,
	 *         "version": 1.0
	 *    }
	 * @apiErrorExample Error-Response:
	 *
	 *     {
	 *       "error": "Can not change address",
	 *       "version": 1.0,
	 *       "Status" : false
	 *     }
	 *
	 */
	public function changeOrderDelivery()
	{
		$order_id = trim( Tools::getValue( 'order_id' ) );
		$address = trim( Tools::getValue( 'address' ) );
		$city = trim( Tools::getValue( 'city' ) );
		$order = new Order($order_id);

		$sql = "UPDATE " . _DB_PREFIX_ . "address SET address1 = '" . $address . "'";
		if ($city !== false) {
			$sql .= " , shipping_city = '" . $city . "'";
		}
		$sql .= " WHERE id_address = '" . $order->id_address_delivery . "'";
		Db::getInstance()->ExecuteS( $sql );

		return true;
	}

	public function OrderStatusList()
	{
		$sql = "SELECT id_order_state,id_lang, name FROM " . _DB_PREFIX_ . "order_state_lang WHERE id_lang = 1 ";
				$results = Db::getInstance()->ExecuteS( $sql );
		return $results;
	}
	public function getMaxOrderPrice()
	{
		$sql = "SELECT MAX(total_paid) AS total FROM `" . _DB_PREFIX_ . "orders` as o
		            INNER JOIN " . _DB_PREFIX_ . "order_history as oh ON o.id_order=oh.id_order 
		            WHERE oh.id_order_state != '0'";
		$total = 0;
		if ($row = Db::getInstance()->getRow($sql)){
			$total = number_format($row['total'], 2, '.','');
		}
		return $total;
	}
	public function getOrders( $data = array() ) {

		$sql = "SELECT o.id_order,o.date_add,o.total_paid, oh.id_order_state, c.firstname, c.lastname FROM " . _DB_PREFIX_ . "orders AS o 
					INNER JOIN " . _DB_PREFIX_ . "order_history as oh ON o.id_order=oh.id_order 
					INNER JOIN " . _DB_PREFIX_ . "customer as c ON c.id_customer=o.id_customer  ";
		if (isset($data['filter'])) {
			if (isset($data['filter']['order_status_id']) &&
			            (int)($data['filter']['order_status_id']) != 0 &&
			            $data['filter']['order_status_id'] != '') {
				$sql .= " WHERE oh.id_order_state = " . (int)$data['filter']['order_status_id'];
			} else {
				$sql .= " WHERE oh.id_order_state != 0 ";
			}
			if (isset($data['filter']['fio']) && $data['filter']['fio'] != '') {
				$params = [];
				$newparam = explode(' ', $data['filter']['fio']);

				foreach ($newparam as $key => $value) {
					if ($value == '') {
						unset($newparam[$key]);
					} else {
						$params[] = $value;
					}
				}

				$sql .= " AND ( c.firstname LIKE '%" . $params[0] . "%' OR o.lastname LIKE '%" . $params[0] . "%'";

				foreach ($params as $param) {
					if ($param != $params[0]) {
						$sql .= " OR o.firstname LIKE '%" . $params[0] . "%' 
									OR o.lastname LIKE '%" . $param . "%'";
					};
				}
				$sql .= " ) ";
			}
			if (isset($data['filter']['min_price']) && isset($data['filter']['max_price']) && $data['filter']['max_price'] != ''  && $data['filter']['min_price'] != 0) {
				$sql .= " AND o.total > " . $data['filter']['min_price'] . " AND o.total <= " . $data['filter']['max_price'];
			}
			if (isset($data['filter']['date_min']) && $data['filter']['date_min'] != '') {
				$date_min = date('y-m-d', strtotime($data['filter']['date_min']));
				$sql .= " AND DATE_FORMAT(o.date_add,'%y-%m-%d') > '" . $date_min . "'";
			}
			if (isset($data['filter']['date_max']) && $data['filter']['date_max'] != '') {
				$date_max = date('y-m-d', strtotime($data['filter']['date_max']));
				$sql .= " AND DATE_FORMAT(o.date_add,'%y-%m-%d') < '" . $date_max . "'";
			}


		} else {
			$sql .= " WHERE oh.id_order_state != 0 ";
		}
		$sql .= " GROUP BY o.id_order ORDER BY o.id_order DESC";

		$sql .= " LIMIT " . (int)$data['limit'] . " OFFSET " . (int)$data['page'];

		$results = Db::getInstance()->ExecuteS( $sql );

		return $results;
	}

}
