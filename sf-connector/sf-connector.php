<?php if ( ! defined( 'ABSPATH' ) ) { exit; }

/*
Plugin Name: WooCommerce to Salesforce Connector
Version: 2.0
Description: Send Custom Data to Salesforce
Author: Will Stickles
Author URI:
Plugin URI:
*/

/* Check to see if the plugin is being accessed directly
 * If so, send a 403 Forbidden response
 */
if ( ! function_exists( 'add_action' ) ) {

	header( 'Status: 403 Forbidden' );
	header( 'HTTP/1.1 403 Forbidden' );
	exit();
}

if ( ! array_key_exists( 'WC_Salesforce', $GLOBALS ) ) {

	/** Set up the class */
	if ( ! class_exists( 'WC_Salesforce' ) ) {

		class WC_Salesforce {

			/** Set the minimum required version and the exit message if it isn't met */
			private $minimum_version = '3.9';
			private $minimum_message = 'Woocommerce to SalesForce requires WordPress 3.9 or greater.<a href="http://codex.wordpress.org/Upgrading_WordPress">Click here to upgrade.</a>';
			private $nonce;

			/** Constructor */
			function __construct() {

				/* Get the current wp version */
				global $wp_version;

				if ( version_compare( $wp_version, $this->minimum_version, '<' ) ) {
					exit( $this->minimum_message );
				}

				define( "SOAP_CLIENT_BASEDIR", plugin_dir_path( __FILE__ ) . "soapclient" );

				require_once( SOAP_CLIENT_BASEDIR . '/SforceEnterpriseClient.php' );
				require_once( SOAP_CLIENT_BASEDIR . '/SforceHeaderOptions.php' );

				require_once( 'includes/userAuth.php' );
				require_once( 'includes/class-wc-api-client.php' );

				/*
				 * Consumer Key: ck_47876b52c435b42139d5a04e35cc5163
				 * Consumer Secret: cs_846efbaf28c28bd23de06b940a69a95f
				 */

				$consumer_key    = ''; // Add your own Consumer Key here
				$consumer_secret = ''; // Add your own Consumer Secret here
				$store_url       = ''; // Add the home URL to the store you want to connect to here
				$sf_dev			 = 'https://dev.salesforce.com'; // Company saleforce sandbox url
				$sf_production	 = 'https://live.salesforce.com'; // Company salesforce production url

				$this->is_admin = FALSE;
				$this->doing_ajax = FALSE;

				// Initialize the class
				$wc_api = new WC_API_Client( $consumer_key, $consumer_secret, $store_url );

				if ( function_exists( 'is_kt_dev' ) ) {
					$this->env = ( is_kt_dev() ) ? 'dev' : 'production';

					$this->sf_account = ( $this->env == 'dev' ) ? 'sf_dev_account_id' : 'sf_account_id';
					$this->sf_domain = ( $this->env == 'dev' ) ? $sf_dev : $sf_production;

				} else {
					$this->env = 'live';
				}

				self::_hooks();
				self::_includes();

			}

			/** Action Hooks */
			function _hooks() {

				if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
					$this->doing_ajax = TRUE;
				}

				if ( is_admin() ){
					$this->is_admin = TRUE;
				}

				/* Register required admin assets ( JS & CSS ) */
				add_action( 'admin_enqueue_scripts', array( $this, 'register_admin_assets' ) );
				add_action( 'admin_menu', array( $this, 'register_sf_connector_admin_page') );

				/* Register required public assets ( JS & CSS ) */
				add_action( 'wp_enqueue_scripts', array( $this, 'register_public_assets' ) );

				/**
				 * Actions for creating and updating accounts
				 */
				add_action( 'woocommerce_created_customer', array( $this, 'create_account' ), 10, 1 );
				add_action( 'user_register', array( $this, 'create_account' ), 10, 1 );

				/**
				 * AJAX Call to update customer orders in SalesForce
				 */
				add_action( 'kelbyone_completed_order', array( $this, 'call_jquery' ),10 , 1 );
				add_action( 'wp_ajax_update_salesforce_orders', array( $this, 'order_item_updates' ) );
				add_action( 'wp_ajax_get_records_to_update', array( $this, 'get_records_to_update' ) );
				add_action( 'wp_ajax_create_salesforce_order', array( $this, '_create_order' ) );
				add_action( 'wp_ajax_no_priv_create_salesforce_order', array( $this, '_create_order' ) );

				if ( !$this->doing_ajax && $this->is_admin ){
					add_action( 'woocommerce_order_status_completed', array( $this, '_create_order' ), 10, 1 );

					$update_actions = array(
						'woocommerce_order_status_pending_to_processing',
						'woocommerce_order_status_pending_to_completed',
						'woocommerce_order_status_pending_to_on-hold',
						'woocommerce_order_status_failed_to_processing',
						'woocommerce_order_status_failed_to_completed',
//						'woocommerce_order_status_completed'

					);
					foreach ( $update_actions as $action ) {
//						add_action( $action, array( $this, '_update_order_status'), 10, 2  );
					}
				}

				$renewal_actions = array(
					'scheduled_subscription_payment',
					'scheduled_subscription_expiration',
					'cancelled_subscription',
					'activated_subscription',
					'subscription_end_of_prepaid_term',
					'subscription_expired',
					'subscription_put_on-hold',
					'subscription_trial_end',
					'processed_subscription_payment',
					'processed_subscription_payments_for_order',
					'processed_subscription_payment_failure',
					'updated_users_subscriptions',
				);
				foreach( $renewal_actions as $renewal ){
//					add_action( $renewal, array( $this, '_send_membership_info' ) );
				}



			}

			function _includes(){
				include( plugin_dir_path( __FILE__) . 'includes/functions.php' );
			}

			function register_admin_assets(){
				/* Register and enqueue the js */
				wp_register_script( 'sf-connector-admin_js', plugins_url( '/assets/js/admin.js' , __FILE__ ) );
				wp_enqueue_script( 'sf-connector-admin_js' );

				wp_enqueue_script( 'jquery-ui-datepicker' );

				wp_enqueue_style( 'datepicker_css', '//code.jquery.com/ui/1.11.2/themes/smoothness/jquery-ui.css', '', '', '' );
			}

			function register_public_assets(){

				if ( is_page('checkout' ) ){
					wp_register_script( 'sf-connector-checkout_js', plugins_url( '/assets/js/checkout.js' , __FILE__ ) );
					wp_enqueue_script( 'sf-connector-checkout_js' );
				}

			}

			function register_sf_connector_admin_page(){
				add_menu_page( 'Product Updates', 'Product Updates', 'Administrator', 'sf-connector', array( $this, 'display_sf_connector' ), '', 90 );
			}

			function display_sf_connector(){
				include( 'views/admin_view.php');
			}

			function call_jquery( $order ){
				$order_id = $order->id;
//				echo "<script type='text/javascript' src=". site_url().'/wp-content/plugins/sf-connector/assets/js/checkout.js'.">var order_id = ".$order_id."</script>";
				echo '<input id="order_id" name="order_id" type="hidden" value="'.$order_id.'"/>';
			}

			function _create_order( $order ){

				$sForceConnection = '';

				try {

					// Sets order id from the thankyou page ajax callback
					if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
						$order = $_POST['order_id'];
					}

					$is_updated = get_post_meta( $order, 'sf_updated', true );

					/** In case only an order id is passed. It will get the order from WooCommerce */
					if ( is_numeric( $order ) ){
						$order = new WC_Order( $order );
					}

					if ( $is_updated ){
						$order_note = sprintf( __( 'Order #%s already exists in salesforce. Salesforce will not be updated.' ), $order->id );
						$order->add_order_note( $order_note );
						return FALSE;
					}

					$sForceConnection = $this->authenticate_user(); // Authenticate user through SF Toolkit
					$date = date( 'c' );
					$user_id = $order->get_user_id();

					$sf_account_id = get_user_meta( $user_id, $this->sf_account, true );

					if ( $sf_account_id ) {
						$sf_account_id = $this->_update_sf_account_id( $user_id, $sForceConnection );
					} else {
						$this->create_account( $user_id, $sForceConnection );
						$sf_account_id = get_user_meta( $user_id, $this->sf_account, true );
					}
					$sOrderObject = $this->_create_sf_order_object( $order, $sf_account_id, $user_id );

					if ( $this->env == 'dev' ){
						foreach ( $sOrderObject as $key => $value ) {
							$this->wc_logs( 'OrderObject', $key . ' => ' . $value );
						}
					}

					$response = $sForceConnection->create( array( $sOrderObject ), 'Order__c' );

					if ( $response[0]->success = 1 ){

						$order_note = sprintf( __( 'Order #%s - SF ID# <a href="'.$this->sf_domain.'/%s" target="_blank" >%s</a> successfully created in SalesForce.' ), $order->id, substr( $response[0]->id, 0, 15 ), substr( $response[0]->id, 0, 15 ) );
						$order->add_order_note( $order_note );

						$sf_order_id = $response[0]->id; // Order Id returned from SalesForce.
						update_post_meta( $order->id, 'sf_order_id', substr( $sf_order_id, 0, 15 ) );
						update_post_meta( $order->id, 'sf_updated', TRUE );

						/**
						 * Get all order items and send to salesforce.
						 *
						 * NOTE: Must be done now when order object is still instantiated.
						 */

						$response = $this->_sf_order_item_updates( $order, $sf_order_id, $sf_account_id, $sForceConnection );

						if ( $response ){
							$order_note = sprintf( __( 'Order #%s - Line items successfully sent to SalesForce.' ), $order->id );
							$order->add_order_note( $order_note );
						} else{
							$order_note = sprintf( __( 'There was a problem sending line items for Order #%s to salesforce.' ), $order->id );
							$order->add_order_note( $order_note );
						}

					} else {
						$order_note = sprintf( __( 'There was a problem creating Order #%s in SalesForce.' ), $order->id, $response[0]->id );
						$order->add_order_note( $order_note );
					}

					/**
					 * Check for coupon codes used for this order.
					 */
					$coupons = $order->get_used_coupons();

					if ( $coupons ) {

						$couponResponse = $this->_create_coupon_object( $coupons, $sf_account_id );

						if ( $this->env == 'dev' ){
							foreach ( $couponResponse as $key => $value ) {
								$this->wc_logs( 'CouponObject', $key . ' => ' . $value );
							}
						}

					}

					if( $this->env == 'dev' ){
						/** Create the Order_Items__c Object under the SalesForce Order_Id */
						if ( $response[0]->success != 1 ) {
							foreach ( $response as $key => $value ) {
								$this->wc_logs( 'OrderObjectResponse', $key . ' => ' . $value );
							}
							return TRUE;

						}
					}

					echo json_encode( array( 'status' => 'success' ) );

					die();

				} catch ( Exception $e ){
					$this->wc_logs( $sForceConnection->getLastRequest(), $e->getMessage() );
				}

			}

			/** Authenticate User */
			function authenticate_user() {

				$mySforceConnection = new SforceEnterpriseClient();

				if ( $this->env == 'dev' ) {
					$mySoapClient = $mySforceConnection->createConnection( SOAP_CLIENT_BASEDIR . '/enterprise.wsdl.xml' );
				} else {
					$mySoapClient = $mySforceConnection->createConnection( SOAP_CLIENT_BASEDIR . '/enterprise.production.wsdl.xml' );
				}

				$mylogin = $mySforceConnection->login( $this->USERNAME, $this->PASSWORD );

				return $mySforceConnection;

			}// end authenticate_user()

			/** Query Object */
			function sf_query( $query, $sForceConnection ) {

				try {

					$sForceConnection = $this->authenticate_user();

					$response = $sForceConnection->query( ( $query ) );

					return $response->records;

				} catch ( Exception $e ) {

					$this->wc_logs( $sForceConnection->getLastRequest(), $e->getMessage() );

				}

			}// end sf_query()

			/** Create an account in SalesForce when a user is created from Admin User area */
			function create_account( $user_id, $sForceConnection ){

				try {

					$sForceConnection = ( $sForceConnection ) ? $sForceConnection : $this->authenticate_user(); //Connect to SalesForce via PHP Toolkit
					$user             = get_userdata( $user_id ); // Get ECOM User data from WP Database
					$user_meta        = get_user_meta( $user_id );// Get ECOM User Meta from WP Database
					$account_type     = '01250000000DzAvAAK'; // SalesForce Personal account type Id.

					// Create SalesForce Account Object
					$sAccountObject                   = new stdclass();
					$sAccountObject->SQL_ID_number__c = $user->ID;
					$sAccountObject->User_Name__c     = $user->user_login;
					$sAccountObject->RecordTypeId     = $account_type;

					$sAccountObject->FirstName         = $user_meta['first_name'][0];
					$sAccountObject->LastName          = $user_meta['last_name'][0];
					$sAccountObject->PersonHomePhone   = $user->billing_phone;
					$sAccountObject->PersonEmail       = $user->user_email;

					$sAccountObject->BillingStreet     = $user->billing_address_1;
					$sAccountObject->BillingCity       = $user->billing_city;
					$sAccountObject->BillingPostalCode = $user->billing_postcode;

					// Check to see if USER Account metadata contains the meta_key sf_account_id and the meta_value is not empty
					if ( ! $user_meta[ $this->sf_account ] || $user_meta[ $this->sf_account ][0] == '' ) {

						/** Check to see if the SQL ID in SalesForce matches User Data */
						$sf_account_id = $this->_update_sf_account_id( $user_id, $sForceConnection );

						/* Check for a returned record */
						if ( ! $sf_account_id ) {

							// No matching user record so create new account
							$response  = $sForceConnection->create( array( $sAccountObject ), 'Account' );

							if ( $response[0]->success != 1 ) {
								$this->wc_logs( 'Create Account -> Account', $response );
							} else {
								$record_id = substr( $response[0]->id, 0, 15 );
								// Update user record with new SalesForce Id
								update_user_meta( $user->ID, $this->sf_account, $record_id );
							}

						}

					}

					return true;

				} catch ( Exception $e ) {

					$this->wc_logs( $sForceConnection->getLastRequest(), $e->getMessage() );

				}


			}// end create_account()

			/** update salesforce object.
			 * uses: $object
			 */
			function update_profile( $user_id, $sForceConnection ) {

				try{

					$sForceConnection = ( $sForceConnection ) ? $sForceConnection : $this->authenticate_user();

					$user = get_userdata( $user_id );

					$sf_account_id = $this->_update_sf_account_id( $user_id, $sForceConnection );

					if ( ! $sf_account_id ) {
						$this->create_account( $user_id, $sForceConnection );
					}

					$sUpdateAccountObject = new stdclass();

					$sUpdateAccountObject->Id               = $sf_account_id;
					$sUpdateAccountObject->SQL_ID_number__c = $user->ID;
					$sUpdateAccountObject->User_Name__c     = $user->user_login;
					$sUpdateAccountObject->FirstName        = $user->user_firstname;
					$sUpdateAccountObject->LastName         = $user->user_lastname;
					$sUpdateAccountObject->PersonHomePhone  = $user->billing_phone;
					$sUpdateAccountObject->PersonEmail      = $user->user_email;

					// Authorize.net Customer Profile ID
					if ( $auth_customer_profile_id = get_user_meta( $user_id, '_wc_authorize_net_cim_profile_id', true ) ) {
						$sUpdateAccountObject->Authorize_net_Customer_Profile_ID__c = $auth_customer_profile_id;
					}

					$referrer = ( $_POST['_wp_http_referrer'] ) ? $_POST['_wp_http_referrer'] : '';

					switch ( $referrer ) {

						case '/my-account/edit-address/billing/':

							if ( $this->env == 'dev' ) {
								$sUpdateAccountObject->BillingStateCode = convert_state( $_POST['billing_state'], 'abbrev' );
							} else {
								$sUpdateAccountObject->BillingState = convert_state( $_POST['billing_state'], 'abbrev' );
							}

							$sUpdateAccountObject->BillingPostalCode = $_POST['billing_postcode'];

							break;

						case '/my-account/edit-address/shipping/':

							if ( $this->env == 'dev' ) {
								$sUpdateAccountObject->ShippingStateCode = convert_state( $_POST['shipping_state'], 'abbrev' );
							} else {
								$sUpdateAccountObject->ShippingState = convert_state( $_POST['shipping_state'], 'abbrev' );
							}

							$sUpdateAccountObject->ShippingPostalCode = $_POST['shipping_postcode'];

							break;

						default:

							$billing_state_abbr  = convert_state( $user->billing_state, 'abbrev' );
							$shipping_state_abbr = convert_state( $user->shipping_state, 'abbrev' );

							$sUpdateAccountObject->BillingStreet = $user->billing_address_1;
							$sUpdateAccountObject->BillingCity   = $user->billing_city;

							if ( $this->env == 'dev' ) {
								$sUpdateAccountObject->BillingStateCode = $billing_state_abbr;
							} else {
								$sUpdateAccountObject->BillingState = $billing_state_abbr;
							}

//						$sObject1->BillingPostalCode  = $user->billing_postcode;
//
							$sUpdateAccountObject->ShippingStreet = $user->shipping_address_1;
							$sUpdateAccountObject->ShippingCity   = $user->shipping_city;

							if ( $this->env == 'dev' ) {
								$sUpdateAccountObject->ShippingStateCode = $shipping_state_abbr;
							} else {
								$sUpdateAccountObject->ShippingState = $shipping_state_abbr;
							}

							$sUpdateAccountObject->ShippingPostalCode = $user->shipping_postcode;

							break;

					}

					$response = $sForceConnection->update( array( $sUpdateAccountObject ), 'Account' );

					if( $this->env == 'dev' ){
						/** Create the Order_Items__c Object under the SalesForce Order_Id */
						if ( $response[0]->success != 1 ) {
							foreach ( $response as $key => $value ) {
								$this->wc_logs( 'AccountResponse', $key . ' => ' . $value );
							}
							return $user_id;

						}
					}

					return $user_id;

				} catch ( Exception $e ) {

					$this->wc_logs( $sForceConnection->getLastRequest(), $e->getMessage() );

				}

				return $user_id;

			}// end update_profile()

			function _create_sf_order_object( $order, $sf_account_id, $user_id ){

				$order = new WC_Order( $order );

				$user_meta = get_user_meta( $order->get_user_id() );

				$billing_first_name = $user_meta['billing_first_name'][0];
				$billing_last_name = $user_meta['billing_last_name'][0];
				$billing_phone = $order->billing_phone;
				$billing_address_1 = $order->billing_address_1;
				$billing_city = $order->billing_city;
				$billing_state = $order->billing_state;
				$billing_postcode = $order->billing_postcode;
				$billing_company = $order->billing_company;
				$billing_country = $order->billing_country;

				$shipping_first_name = $user_meta['shipping_first_name'][0];
				$shipping_last_name = $user_meta['shipping_last_name'][0];
				$shipping_phone = $order->shipping_phone;
				$shipping_address_1 = $order->shipping_address_1;
				$shipping_city = $order->shipping_city;
				$shipping_state = $order->shipping_state;
				$shipping_postcode = $order->shipping_postcode;
				$shipping_company = $order->shipping_company;
				$shipping_country = $order->shipping_country;

				$tax          = $order->order_tax;
				$shipping_tax = $order->order_shipping_tax;
				$date         = date( 'c', strtotime( $order->post->post_date_gmt ) );
				$shipping_method = $order->get_shipping_method();
				$last_name = ( $user_meta['last_name'][0] ) ? ucwords( $user_meta['last_name'][0] ) : $user_id;
				$order_name = $last_name . '-' . $order->id;
				$order_status = $order->get_status();

				/**
				 * @var  $sObject
				 * Description: Create an object of order information to be sent to SalesForce. The order needs to be created
				 * in SalesForce and the order ID created for the current users account. After the Order ID is returned from
				 * SalesForce, we can send the order information to the Order Items Object.
				 */
				$sOrderObject                            = new stdclass();
				$sOrderObject->RecordTypeId              = '01250000000E4RJ';
				$sOrderObject->Account__c                = substr( $sf_account_id, 0, 15);
				$sOrderObject->Name                      = $order_name;
				$sOrderObject->Order__c                  = $order->id;
				$sOrderObject->Billing_First_Name__c     = $billing_first_name;
				$sOrderObject->Billing_Last_Name__c      = $billing_last_name;
				$sOrderObject->Billing_Contact_Phone__c  = $billing_phone;
				$sOrderObject->Billing_Street__c         = $billing_address_1;
				$sOrderObject->Billing_City__c           = $billing_city;
				$sOrderObject->Billing_State__c          = $billing_state;
				$sOrderObject->Billing_Postal__c         = $billing_postcode;
				$sOrderObject->Billing_Email__c          = $order->billing_email;
				$sOrderObject->Billing_Company__c        = $billing_company;
				$sOrderObject->Billing_Country__c        = $billing_country;
				$sOrderObject->Shipping_First_Name__c    = $shipping_first_name;
				$sOrderObject->Shipping_Last_Name__c     = $shipping_last_name;
				$sOrderObject->Shipping_Contact_Phone__c = $billing_phone;
				$sOrderObject->Shipping_Street__c        = $shipping_address_1;
				$sOrderObject->Shipping_City__c          = $shipping_city;
				$sOrderObject->Shipping_State__c         = $shipping_state;
				$sOrderObject->Shipping_Postal__c        = $shipping_postcode;
				$sOrderObject->Shipping_Email__c         = $order->billing_email;
				$sOrderObject->Shipping_Company__c       = $shipping_company;
				$sOrderObject->Shipping_Country__c       = $shipping_country;
				$sOrderObject->Payment_Method__c         = $order->payment_method;
				$sOrderObject->Credit_Card_Info__c       = get_post_meta( $order->id, '_wc_authorize_net_cim_card_type', true );
				$sOrderObject->Shipping_Method__c        = $shipping_method;
				if ( $_POST['authorize-net-cim-cc-number'] ) {
					$sOrderObject->CC_Last_four_digits__c = (string) cc_last_four( $_POST['authorize-net-cim-cc-number'] );
				}
				$sOrderObject->WC_Authorize_Net_Customer_Profile_ID__c = get_user_meta( $user_id, '_wc_authorize_net_cim_profile_id', true );
				$sOrderObject->Order_Date__c                           = $date;
				$sOrderObject->Order_Status__c                         = $order_status;
				$sOrderObject->Total_Shipping_Cost__c                  = round( $order->order_shipping, 2 );
				$sOrderObject->Total_Tax__c                            = round( $tax + $shipping_tax, 2 );
				$sOrderObject->Order_Total__c                          = $order->get_total();
				$sOrderObject->Total_Discounts__c                      = $order->get_order_discount();

				return $sOrderObject;

			}// end _create_sf_order_object

			function _create_coupon_object( $coupons, $sf_order_id ){

				try{
					$sForceConnection = $this->authenticate_user(); // Authenticate user through SF Toolkit

					$couponObject = new stdclass();
					$couponObject->Order__c = substr( $sf_order_id, 0, 15 );


					foreach ( $coupons as $coupon ) {

						$coupon_data = new WC_Coupon( $coupon );

						$code      = $coupon_data->code;
						$post_info = get_post( $coupon_data->id );

						$couponObject->Name             = $code;
						$couponObject->Quantity__c      = '1';
						$couponObject->Product__c       = $post_info->post_excerpt;
						$couponObject->Unit_Price__c    = $coupon_data->amount;
						$couponObject->Line_Item_Tax__c = '0.00';

						$response = $sForceConnection->create( array( $couponObject ), 'Order_Item__c' ); // Send Order items object to SalesForce.

						return $response;

					}
				} catch ( Exception $e ){
					$this->wc_logs( $sForceConnection->getLastRequest(), $e->getMessage() );
				}

			}

			function get_records_to_update( $sForceConnection){

				if ( defined( 'DOING_AJAX' ) && DOING_AJAX )
				{
					global $wpdb;

					try {

						$startdate  = date( 'Y-m-d H:i:s', strtotime( $_POST['startdate'] . ' 00:00:00' ) );
						$enddate  = date( 'Y-m-d H:i:s', strtotime( $_POST['enddate'] . ' 23:59:59' ));

						$post_per_page = ( $_POST['limit'] ) ? $_POST['limit'] : -1;

						$query =  $wpdb->prepare("SELECT kt_posts.ID FROM kt_postmeta INNER JOIN kt_posts ON kt_posts.ID = kt_postmeta.post_id WHERE kt_posts.post_type = %s AND kt_postmeta.meta_key = %s AND kt_postmeta.meta_value >= %s AND kt_postmeta.meta_value <= %s and kt_posts.post_status = %s" , 'shop_order', '_completed_date', $startdate, $enddate, 'wc-completed');

						$results = $wpdb->get_results( $query );
						if ( $_POST['count'] == 'yes' ){
							echo json_encode( array('count'=>count($results)));
							die();
						} else {
							foreach ( $results as $value ){
								$order_array[] = $value->ID;
							}
							echo json_encode( $order_array );
							die();
						}
						exit();
					} catch ( Exception $e ){
						echo $e;
					}

				}// end DOING_AJAX

			}

			function _sf_order_item_updates( $order, $sf_order_id, $sf_account_id, $sForceConnection ){

				global $wpdb;

				$date = date( 'c' );

				$user_id = $order->get_user_id();

				$sForceConnection = ( $sForceConnection) ? $sForceConnection : $this->authenticate_user(); // Authenticate user through SF Toolkit

				$sOrderItemObject = new stdclass();
				$sOrderItemObject->Order__c = substr( $sf_order_id, 0, 15 );

				$items = $order->get_items();

				foreach ( $items as $product ) { // Loop through order items

					$item = get_post_meta( $product['product_id'] );

					$sf_product_id     = get_post_meta( $product['product_id'], 'salesforce_id', true );
					$sf_product_family = get_post_meta( $product['product_id'], 'salesforce_product_family', true );

					$print_addon = get_the_terms( $product['product_id'], 'product_cat' );

					$addon = array();

					foreach ( $print_addon as $key => $value ){
						$addon = $value->slug;
					}

					if ( is_array( $addon ) ){
						$is_addon = ( in_array( 'magazine_print_addon', $addon ) ) ? TRUE : FALSE;
					} else {
						$is_addon = ( $addon == 'magazine_print_addon' ) ? TRUE : FALSE;
					}

					if ( $is_addon === TRUE ){

						$sAccountObject = new stdClass();
						$sAccountObject->Print_Delivery__c = true;
						$sAccountObject->Id  = $sf_account_id;

						$account_response = $sForceConnection->update( array( $sAccountObject ), 'Account' );

						if ( $account_response[0]->success != 1 ) {
							foreach ( $account_response as $key => $value ) {
								$this->wc_logs( 'AddonResponse', $key . ' => ' . $value );
							}
						}


					}

					if ( $is_subscription = WC_Subscriptions_Product::is_subscription( $product['product_id'] ) ) {

						if ( $sf_product_family && $sf_product_family == 'MAG' ) {

							$subscription_key = WC_Subscriptions_Manager::get_subscription_key( $order->id, $product['product_id'] );

							$mag_subscription = $this->send_mag_subscription_to_salesforce( $sf_account_id, $sf_product_id, $date, $product, $subscription_key );

						} /*else {
//							$send_membership_info = $this->send_membership_info( $order->id, $user_id, $sf_order_id );
						}*/

					}

					$sOrderItemObject->Name                 = $product['name'];
					$sOrderItemObject->Quantity__c          = $product['qty'];
					$sOrderItemObject->Product__c           = $sf_product_id;
					$sOrderItemObject->Unit_Price__c        = number_format( (float) $product['line_total'], 2, '.', '' );
					$sOrderItemObject->Shippable_Product__c = true;
					$sOrderItemObject->Line_Item_Tax__c     = number_format( (float) $product['line_tax'], 2, '.', '' );

					if ( $this->env == 'dev' ){
						foreach ( $sOrderItemObject as $key => $value ){
							$this->wc_logs( 'OrderItemObject', $key.' => '.$value );
						}
					}

					$response = $sForceConnection->create( array( $sOrderItemObject ), 'Order_Item__c' ); // Send Order items object to SalesForce

					if ( $this->env == 'dev' ){
						if ( $response[0]->success != 1 ) {
							foreach ( $response as $key => $value ) {
								$this->wc_logs( 'OrderItemResponse', $key . ' => ' . $value );
							}
						}
					}

				}// end order items loop.

				return TRUE;
			}// End order_items_update()

			function _check_for_existing_account( $user_id, $order_id, $sForceConnection ){

				$sForceConnection = ( $sForceConnection ) ? $sForceConnection : $this->authenticate_user();

				try{

					$query = "SELECT Id FROM Order WHERE Order__c = '". $order_id ."'";

					$result = $this->sf_query( $query, $sForceConnection );

					return $result;

				} catch ( Exception $e ) {

					$this->wc_logs( $sForceConnection->getLastRequest(), $e->getMessage() );

				}

			}

			function _check_salesforce_for_order( $order_id, $sForceConnection ){

				$sForceConnection = ( $sForceConnection ) ? $sForceConnection : $this->authenticate_user();

				try{

//					$query = "SELECT Id FROM Order WHERE Id"

				} catch ( Exception $e ) {

					$this->wc_logs( $sForceConnection->getLastRequest(), $e->getMessage() );

				}

			}

			function send_mag_subscription_to_salesforce( $sf_account_id, $sf_product_id, $date, $product, $subscription_key ) {

				$sForceConnect = $this->authenticate_user(); // Authenticate user through SF Toolkit

				$subscription_info = WC_Subscriptions_Manager::get_subscription( $subscription_key );

				$SubscriptionObject = new stdClass();

				$SubscriptionObject->Name       = $product['name'];
				$SubscriptionObject->Account__c = $sf_account_id;

				if ( get_post_meta( $sf_product_id, '_virtual', true ) == 'yes' ) {
					$delivery_method = 'E-Delivery';
				} else {
					$delivery_method = 'Paper';
				}

				$SubscriptionObject->Delivery_Method__c         = $delivery_method;
				$SubscriptionObject->Subscription_Order_Date__c = $date;

				if ( $subscription_info['expiry_date'] > 0 ) {
					$SubscriptionObject->Expiration_Date__c = date( 'c', strtotime( $subscription_info['expiry_date'] ) );
				}

				$subscription_response = $sForceConnect->create( array( $SubscriptionObject ), 'Subscription__c' );

				return $subscription_response;

			}

			function send_membership_info( $order_id = null, $user_id = null, $sf_order_id = null, $post = null ) {

				$post_type = get_post_type( $order_id );

				if ( $post_type != 'shop_order' ) {
					return false;
				}

				$sForceConnect = $this->authenticate_user(); // Authenticate user through SF Toolkit

				if ( $user_id != null ) {
					$current_user_id = $user_id;
				} else {
					$current_user_id = get_current_user_id();
				}

				$sf_account_id = get_user_meta( $user_id, $this->sf_account, true );

				if ( $sf_order_id != null ) {
					$salesforce_order_id = $sf_order_id;
				} else {
					$salesforce_order_id = wc_get_order_item_meta( $order_id, '_sf_order_id', true );
				}

				if ( $order_id != null ) {
					$current_order_id = $order_id;
				} else {
					$order            = new WC_Order( $post->ID );
					$current_order_id = $order->id;
				}

				$sAccountUpdate = $this->_create_membership_update_object( $current_user_id, $current_order_id, $sf_account_id, $salesforce_order_id );

				$sForceConnect->update( array( $sAccountUpdate ), 'Account' );

				return true;

			}

			/** Account Object for membership updates */
			function _create_membership_update_object( $user_id, $order_id, $sf_account_id, $salesforce_order_id ) {

				$subscriptionUpdate = new stdclass();

				$sForceConnect = $this->authenticate_user(); // Authenticate user through SF Toolkit

				$subscription_key = ( $_GET['subscription'] ) ? $_GET['subscription'] : WC_Subscriptions_Manager::get_subscription_key( $order_id );

				$subscription_info = WC_Subscriptions_Manager::get_subscription( $subscription_key );

				$subscript_manage = array(

					'subscription'                      => $subscription_info,
					'calc_next_payment_date'            => WC_Subscriptions_Manager::calculate_next_payment_date( $subscription_key, $user_id ),
					'calc_subscription_expiration_date' => WC_Subscriptions_Manager::calculate_subscription_expiration_date( $subscription_key, $user_id ),
					'calc_trial_exp_date'               => WC_Subscriptions_Manager::calculate_trial_expiration_date( $subscription_key, $user_id ),
					'next_payment_date'                 => WC_Subscriptions_Manager::get_next_payment_date( $subscription_key, $user_id ),
					'status_to_display'                 => WC_Subscriptions_Manager::get_status_to_display( $subscription_key, $user_id ),
					'subscription_expiration_date'      => WC_Subscriptions_Manager::get_subscription_expiration_date( $subscription_key, $user_id ),
					'completed_payment_count'           => WC_Subscriptions_Manager::get_subscriptions_completed_payment_count( $subscription_key ),
					'failed_payment_count'              => WC_Subscriptions_Manager::get_subscriptions_failed_payment_count( $subscription_key, $user_id ),
					'trial_exp_date'                    => WC_Subscriptions_Manager::get_trial_expiration_date( $subscription_key, $user_id )

				);

				// Authorize.net Customer Profile ID
				if ( $auth_customer_profile_id = get_user_meta( $user_id, '_wc_authorize_net_cim_profile_id', true ) ) {
					$subscriptionUpdate->Authorize_net_Customer_Profile_ID__c = $auth_customer_profile_id;
				}

				$subscription_start_date      = ( $subscription_info['start_date'] > 0 ) ? date( 'c', strtotime( $subscription_info['start_date'] ) ) : '';
				$subscription_expiry_date        = ( $subscription_info['expiry_date'] > 0 ) ? date( 'c', strtotime( $subscription_info['expiry_date'] ) ) : '';
				$membership_end_date          = ( $subscription_info['end_date'] > 0 ) ? date( 'c', strtotime( $subscription_info['end_date'] ) ) : '';
				$last_membership_trial_expiry = ( $subscription_info['trial_expiry_date'] > 0 ) ? date( 'c', $subscription_info['trial_expiry_date'] ) : '';
				$last_payment_date            = ( strtotime( $subscription_info['last_payment_date'] ) > 0 ) ? strtotime( $subscription_info['last_payment_date'] ) : '';

				// Update Account information
				$subscriptionUpdate->Id = $sf_account_id;
				if ( $subscription_start_date != '' ) {
					$subscriptionUpdate->Kelby_Start_Date__c = $subscription_start_date;
				}
				if ( $subscription_expiry_date != '' ) {
					$subscriptionUpdate->Kelby_End_Date__c = $subscription_expiry_date;
				}
				if ( $membership_end_date != '' ) {
					$subscriptionUpdate->Membership_Expiration_Date__c = $membership_end_date;
				}
				if ( $last_membership_trial_expiry != '' ) {
					$subscriptionUpdate->Last_Membership_Trial_Expiry__c = $last_membership_trial_expiry;
				}
				if ( $last_payment_date != '' ) {
					$subscriptionUpdate->Last_Membership_Payment__c = date( 'c', $last_payment_date );
				}

				$groups = $this->_get_user_groups( $user_id );

				$subscriptionUpdate->Membership_Status__c               = $subscription_info['status'];
				$subscriptionUpdate->Last_Membership_Order__c           = $salesforce_order_id;
				$subscriptionUpdate->Last_Membership_Product__c         = get_post_meta( $subscription_info['product_id'], 'salesforce_id', true );
				$subscriptionUpdate->Membership_Failed_Payment_Count__c = $subscription_info['failed_payments'];
				$subscriptionUpdate->Membership_Payment_Count__c        = $subscript_manage['completed_payment_count'];
				$subscriptionUpdate->Membership_Groups__c               = implode( ', ', $groups );

				if ( $subscription_info['period'] == 'month' ) {
					$subscriptionUpdate->Monthly_Renewal__c = true;
				}

				if ( $subscription_info['expiry_date'] == 0 ) {
					$subscriptionUpdate->Auto_Renewal__c = true;
				}

				$completed_payments = $subscription_info['completed_payments'];

				if ( $completed_payments != 0 ) {

					$sMembership = $this->_send_membership_updates( $completed_payments, $sf_account_id, $order_id );

					$sForceConnect->create( array( $sMembership ), 'Membership_Payment__c' );

				}

				return $subscriptionUpdate;

			}

			function _get_user_groups( $user_id ){
				global $wpdb;

				$return = array();
				$groups = kelbyone_fetch_user_groups( $user_id );
				$group_table = _groups_get_tablename( "group" );

				foreach ( $groups as $group_id ){

					$group_names = $wpdb->get_results( $wpdb->prepare( "SELECT name FROM $group_table WHERE group_id = %d" , $group_id ) );
					$return[] =  $group_names[0]->name;

				}

				return $return;

			}

			function _get_user_orders( $user_id, $status = 'complete' ) {

				if ( ! $user_id ) {
					return false;
				}

				//Karey Adds...Sorry man...see me and I will explain
				$order_ids = Kelby()->orders->get_customer_order_ids( $user_id );
				$order_ids = implode( ',', $order_ids );

				$args = array(
					'numberposts' => - 1,
					//'meta_key'      =>  '_customer_user',
					//'meta_value'    =>  $user_id,
					'includes'    => $order_ids, //Karey Adds
					'post_type'   => 'shop_order',
					'post_status' => 'publish',
					'tax_query'   => array(
						array(
							'taxonomy' => 'shop_order_status',
							'field'    => 'slug',
							'terms'    => $status
						)
					)
				);

				$posts = get_posts( $args );
				//get the post ids as order ids
				$orders = wp_list_pluck( $posts, 'ID' );

				return $orders;
			}

			function _send_membership_updates( $completed_payments, $sf_account_id, $order_id ) {

				$order = new WC_Order( $order_id );

				$sf_order_id = $order->get_items( $order_id );

				foreach ( $sf_order_id as $key => $value ) {
					$salesforce_id = $value['sf_order_id'];
				}

				$sMembership = new stdClass();

				foreach ( $completed_payments as $key => $date ) {
					$payment_date                 = strtotime( $date );
					$sf_date                      = date( 'c', $payment_date );
					$sMembership->Name            = $order_id . '-' . $date;
					$sMembership->Account__c      = $sf_account_id;
					$sMembership->Payment_Date__c = $sf_date;
					$sMembership->Order__c        = $salesforce_id;
				}

				return $sMembership;

			}

			function _update_sf_account_id( $user_id, $sForceConnection ) {

				$id = '';

				try{

					$sForceConnection = ( $sForceConnection ) ? $sForceConnection : $this->authenticate_user();

					// Check to see if a user ID is passed. If not get current user's id.
					if ( ! $user_id ){
						$user_id = get_current_user();
					}

					$sf_account_id = get_user_meta( $user_id, $this->sf_account, true ) ;

						$query = "SELECT Id FROM Account WHERE SQL_ID_Number__c = '" . $user_id . "'";
						$sAccountIdQuery = $this->sf_query( $query, $sForceConnection );

						foreach ( $sAccountIdQuery as $user ){
							$id = substr( $user->Id, 0, 15);

							if ( $id != $sf_account_id ){
								update_user_meta( $user_id, $this->sf_account, $id );
							}

						}

						return $id;


				} catch ( Exception $e ) {

					$this->wc_logs( $sForceConnection->getLastRequest(), $e->getMessage() );

				}// End catch

				return $id;

			}// end _update_sf_account_id

			function _update_order_status( $redirect, $order ){

				$sForceConnection = '';

				try {
					$sForceConnection = $this->authenticate_user(); // Authenticate user through SF Toolkit

					if ( is_numeric( $order ) ){
						$order = new WC_Order( $order );
					}

					$order_status = $order->get_status();
					$user_id = $order->get_user_id();

					$updateOrderStatus = new stdClass();

					$updateOrderStatus->Id = substr( get_post_meta( $order->id, 'sf_order_id', true ), 0, 15 );
					$updateOrderStatus->Payment_Method__c         = $order->payment_method;
					$updateOrderStatus->Credit_Card_Info__c       = get_post_meta( $order->id, '_wc_authorize_net_cim_card_type', true );
					$updateOrderStatus->Paid__c                   = true;
					if ( $_POST['authorize-net-cim-cc-number'] ) {
						$updateOrderStatus->CC_Last_four_digits__c = (string) cc_last_four( $_POST['authorize-net-cim-cc-number'] );
					}
					$updateOrderStatus->WC_Authorize_Net_Customer_Profile_ID__c = get_user_meta( $user_id, '_wc_authorize_net_cim_profile_id', true );
					$updateOrderStatus->Order_Status__c                         = $order_status;

					$response = $sForceConnection->update( array( $updateOrderStatus ), 'Order__c' );

					return $redirect;

				} catch ( Exception $e ) {

					$this->wc_logs( $sForceConnection->getLastRequest(), $e->getMessage() );

				}

			}

			function wc_logs( $handle, $error ){
				$error_log = new WC_Logger();

				if ( $error ){
					$error_log->add( $handle, $error );
				}
			}

			function sf_send_error() {
				printf(
					'<div class="error"><p>%s</p></div>',
					__( 'Sorry, there was an error sending your data to SalesForce.' )
				);
			}
		}

	} else {

		/** Exit with a message that the PostTypeFactory class has been set up */
		_e( 'Woocommerce to SalesForce has already been set up.', 'wc-salesforce' );

	}

	/** Create a new WC_SalesForce */
	$GLOBALS['WC_Salesforce'] = new WC_Salesforce();

}

/** If WC_Salesforce has been set, register the activation and deactivation hooks */
if ( isset( $WC_Salesforce ) ) {

	register_activation_hook( __FILE__, array( $WC_Salesforce, 'install' ) );
	register_deactivation_hook( __FILE__, array( $WC_Salesforce, 'uninstall' ) );

}