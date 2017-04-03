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
class ApimoduleStatisticModuleFrontController extends ModuleFrontController {
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
		$filter                 = trim( Tools::getValue( 'filter' ) );
		if ( $this->valid() && ! empty( $filter  )) {
				$this->getStat( $filter );
		}
		header( 'Content-Type: application/json' );
		die( Tools::jsonEncode( $this->return ) );
	}

	private function valid() {
		$token = trim( Tools::getValue( 'token' ) );
		if ( ! empty( $token ) ) {
			$this->errors[] = 'You need to be logged!';
			return false;
		} else {
			$results = $this->getTokens( $token );
			if ( $results ) {
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

	public function getStat( $filter ) {
		$clients = $this->getTotalCustomers( array( 'filter' => $filter ) );
		$orders  = $this->getTotalOrders( array( 'filter' => $filter ) );

		if ( $clients === false && $orders === false ) {

			$this->return['error']  = 'Unknown filter set';
			$this->return['status'] = false;
			header( 'Content-Type: application/json' );
			die( Tools::jsonEncode( $this->return ) );


		} else {
			$clients_for_time = [];
			$orders_for_time  = [];
			if ( $filter == 'month' ) {
				$days  = cal_days_in_month( CAL_GREGORIAN, date( 'm' ), date( 'Y' ) );
				$hours = $days;
				for ( $i = 1; $i <= $days; $i ++ ) {
					$b = 0;
					$o = 0;
					if ( ! empty( $clients ) ) {
						foreach ( $clients as $value ) {
							$day = strtotime( $value['date_add'] );
							$day = date( "d", $day );

							if ( $day == $i ) {
								$b = $b + 1;
							}
						}
					}
					$clients_for_time[] = $b;

					if ( ! empty( $orders ) ) {
						foreach ( $orders as $value ) {

							$day = strtotime( $value['date_add'] );
							$day = date( "d", $day );

							if ( $day == $i ) {
								$o = $o + 1;
							}
						}
					}
					$orders_for_time[] = $o;
				}
			} elseif ( $filter == 'day' ) {
				$hours = range( 0, 23 );

				for ( $i = 0; $i <= 23; $i ++ ) {
					$b = 0;
					$o = 0;
					if ( ! empty( $clients ) ) {
						foreach ( $clients as $value ) {
							$hour = strtotime( $value['date_add'] );
							$hour = date( "h", $hour );

							if ( $hour == $i ) {
								$b = $b + 1;
							}
						}
					}
					$clients_for_time[] = $b;
					if ( ! empty( $orders ) ) {
						foreach ( $orders as $value ) {

							$day = strtotime( $value['date_add'] );
							$day = date( "h", $day );

							if ( $day == $i ) {
								$o = $o + 1;
							}
						}
					}
					$orders_for_time[] = $o;
				}

			} elseif ( $filter == 'week' ) {
				$hours = range( 1, 7 );

				for ( $i = 1; $i <= 7; $i ++ ) {
					$b = 0;
					$o = 0;
					if ( ! empty( $clients ) ) {
						foreach ( $clients as $value ) {
							$date = strtotime( $value['date_add'] );

							$f = date( "N", $date );

							if ( $f == $i ) {
								$b = $b + 1;
							}
						}
					}
					$clients_for_time[] = $b;
					if ( ! empty( $orders ) ) {
						foreach ( $orders as $val ) {

							$day = strtotime( $val['date_add'] );
							$day = date( "N", $day );

							if ( $day == $i ) {
								$o = $o + 1;
							}
						}
					}
					$orders_for_time[] = $o;
				}

			} elseif ( $filter == 'year' ) {
				$hours = range( 1, 12 );

				for ( $i = 1; $i <= 12; $i ++ ) {
					$b = 0;
					$o = 0;
					if(!empty($clients)) {
						foreach ( $clients as $value ) {
							$date = strtotime( $value['date_add'] );

							$f = date( "m", $date );

							if ( $f == $i ) {
								$b = $b + 1;
							}
						}
					}
					$clients_for_time[] = $b;
					if ( ! empty( $orders ) ) {
						foreach ( $orders as $val ) {

							$day = strtotime( $val['date_add'] );
							$day = date( "m", $day );

							if ( $day == $i ) {
								$o = $o + 1;
							}
						}
					}
					$orders_for_time[] = $o;
				}
			}

			$data['xAxis']   = $hours;
			$data['clients'] = $clients_for_time;
			$data['orders']  = $orders_for_time;
		}

		$sale_total = $this->getTotalSales();

		$data['total_sales'] = number_format( $sale_total, 2, '.', '' );
		$sale_year_total     = $this->getTotalSales( array( 'this_year' => true ) );


		$data['sale_year_total'] = number_format( $sale_year_total, 2, '.', '' );
		$orders_total            = $this->getTotalOrders();
		$data['orders_total']    = $orders_total[0]['COUNT(*)'];
		$clients_total           = $this->getTotalCustomers();
		$data['clients_total']   = $clients_total[0]['COUNT(*)'];
		$data['currency_code']   = Context::getContext()->currency->iso_code;

		$this->return['error']    = $this->errors;
		$this->return['status']   = true;
		$this->return['version']  = $this->API_VERSION;
		$this->return['response'] = $data;
		header( 'Content-Type: application/json' );
		die( Tools::jsonEncode( $this->return ) );
	}

	public function getTotalOrders( $data = array() ) {

		$sql = "SELECT date_add FROM `" . _DB_PREFIX_ . "orders` as o
		            INNER JOIN " . _DB_PREFIX_ . "order_history as oh ON o.id_order=oh.id_order 
		            WHERE oh.id_order_state > '0'";
		if ( isset( $data['filter'] ) ) {
			if ( $data['filter'] == 'day' ) {
				$sql .= " AND DATE(o.date_add) = DATE(NOW())";
			} elseif ( $data['filter'] == 'week' ) {
				$date_start = strtotime( '-' . date( 'w' ) . ' days' );
				$sql .= "AND DATE(o.date_add) >= DATE('" . date( 'Y-m-d', $date_start ) . "') ";

			} elseif ( $data['filter'] == 'month' ) {
				$sql .= "AND DATE(o.date_add) >= '" . date( 'Y' ) . '-' . date( 'm' ) . '-1' . "' ";

			} elseif ( $data['filter'] == 'year' ) {
				$sql .= "AND YEAR(o.date_add) = YEAR(NOW())";
			} else {
				return false;
			}
		}
		//echo $sql;
		$results = Db::getInstance()->ExecuteS( $sql );

		return $results;
	}

	public function getTotalCustomers( $data = array() ) {

		if ( isset( $data['filter'] ) ) {
			$sql = "SELECT date_add FROM `" . _DB_PREFIX_ . "customer` ";
			if ( $data['filter'] == 'day' ) {
				$sql .= " WHERE DATE(date_add) = DATE(NOW())";
			} elseif ( $data['filter'] == 'week' ) {
				$date_start = strtotime( '-' . date( 'w' ) . ' days' );
				$sql .= "WHERE DATE(date_add) >= DATE('" . date( 'Y-m-d', $date_start ) . "') ";
			} elseif ( $data['filter'] == 'month' ) {
				$sql .= "WHERE DATE(date_add) >= '" . date( 'Y' ) . '-' . date( 'm' ) . '-1' . "' ";
			} elseif ( $data['filter'] == 'year' ) {
				$sql .= "WHERE YEAR(date_add) = YEAR(NOW()) ";
			} else {
				return false;
			}
		} else {
			$sql = "SELECT COUNT(*) FROM `" . _DB_PREFIX_ . "customer` ";
		}
		//echo $sql;
		$results = Db::getInstance()->ExecuteS( $sql );

		return $results;
	}

	public function getTotalSales( $data = array() ) {

		$sql = "SELECT SUM(total_paid) AS total FROM `" . _DB_PREFIX_ . "orders` as o
		            INNER JOIN " . _DB_PREFIX_ . "order_history as oh ON o.id_order=oh.id_order 
		            WHERE oh.id_order_state > '0'";

		if ( ! empty( $data['this_year'] ) ) {
			$sql .= " AND DATE_FORMAT(o.date_add,'%Y') = DATE_FORMAT(NOW(),'%Y')";
		}

		$results = Db::getInstance()->ExecuteS( $sql );

		return $results['total'];

	}

}