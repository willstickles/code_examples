<?php

/* Toggle Debug During Development */
//define( 'WP_DEBUG', TRUE );

/* Check to see if the plugin is being accessed directly
 * If so, send a 403 Forbidden response
 */
if ( ! function_exists( 'add_action' ) ) {

    header( 'Status: 403 Forbidden' );
    header( 'HTTP/1.1 403 Forbidden' );

    exit();

}

/** Set up the class */
if ( ! class_exists( 'SFAPI' ) ) {

    class SFAPI {


        /** Constructor */
        function __construct() {
            global $debug;

            $return = '';

            if ( ! empty( $debug ) ) {

                $this->debug = $debug;

            } else {

                $this->debug = FALSE;

            }

            $post_info = file_get_contents( "php://input" );

            if ( $post_info ) {

                $post_data = json_decode( $post_info );

                echo '<pre>' . time();
                print_r( $post_data );
                echo '</pre>';

                foreach ( $post_data as $data ) {

                    if ( $this->debug ) {

                        echo '<pre>';
                        print_r( $this->debug );
                        echo '</pre>';

                    }

                    $auth_response = $this->authorize_token( $data->attributes->access_token );

                    $response = json_decode( $auth_response );

                    if ( $response->error ) {

                        $this->error( "<p>$response->error</p><p>$response->error_description</p>" );
                        return FALSE;

                    }

                    switch ( $data->attributes->type ) {

                        case 'Product2':
                            $return = $this->update_product( $data );
                            break;
                        case 'Account':
                            $return = $this->update_account( $data );
                            break;
                        case 'Order__c':
                            $return = $this->update_order( $data );
                            break;

                    }

                    if ( $return ) {
                        $this->return_status( 'Success' );
                    } else {
                        $this->error( 'Failure on product: ' . $data->Id );
                    }

                }

            } else {

                $this->error( 'Accessed incorrectly. Missing POST Vars' );

            }

            self::_hooks();

        }

        /** Action Hooks */
        function _hooks() {
        }

        /**************************************************************************************************************
         * prepare()
         *
         * Recursive function that converts input provided by API call into a mapped array
         *
         * @param mixed $val
         * @return array
         */
        function prepare( $val ) {

            switch ( $val ) {

                case is_object( $val ):

                    echo "Is object";
                    $val = get_object_vars( $val );
                    array_map( array( 'SFAPI', 'prepare' ), $val );

                    break;

                case is_array( $val ):

                    echo "is array";
                    array_map( array( 'SFAPI', 'prepare' ), $val );

                    break;

                default:

                    echo "Other";
                    htmlentities( trim( $val ) );

                    break;

            }


            return $val;
        }

        /**
         * Update Product information
         */
        function update_product( $data ) {

	        if( $data->Family == 'DSC' ){
		        return FALSE;
	        }

            if ( ! $data->ProductCode ) {

                $this->error( 'You must send ProductCode' );
                return FALSE;

            }

            $args = array(
                'post_type'  => 'product',
                'post_status'    => array( 'publish', 'draft'),
                'meta_key'  =>  '_sku',
                'meta_query' => array(
                    array(
                        'key'   => '_sku',
                        'value' => $data->ProductCode
                    )
                )
            );

            $getProductId = new WP_Query( $args );

            $product_id = $getProductId->post->ID;

            if ( ! WC()->product_factory->get_product( $product_id ) ) {
                $response = $this->create_new_product( $data );
                $this->return_status( $response );
                return TRUE;
            }

            if ( ! wp_is_post_revision( $product_id ) ) {

                // unhook this function so it doesn't loop infinitely
                remove_action( 'save_post', array( $this, 'update_product' ) );

                $product_info = array(
                    'ID'           =>   $product_id,
                    'post_title'   =>   $data->Name,
//                    'post_content' =>   $data->description
                );

                // update the post, which calls save_post again
                wp_update_post( $product_info );

                // re-hook this function
                add_action( 'save_post', array( $this, 'update_product' ) );

            }

            $formatted_sf_product_id = substr( $data->Id, 0, 15);

            update_post_meta( $product_id, '_regular_price', $data->List_Price__c );
            update_post_meta( $product_id, '_sale_price', $data->Sale_Price__c );
            update_post_meta( $product_id, '_weight', $data->Item_Weight__c );
            update_post_meta( $product_id, '_sku', $data->ProductCode );
            update_post_meta( $product_id, 'salesforce_id', $formatted_sf_product_id );
            update_post_meta( $product_id, 'salesforce_product_family', $data->Family );
            update_post_meta( $product_id, 'salesforce_last_modified_by_id', substr( $data->LastModifiedById, 0, 15 ) );

            if ( $data->Family == 'KOM' ) {

                $product_type = 'subscription';

                $subscription_signup_fee      = ( $data->Subscription_Sign_Up_Fee__c ) ? $data->Subscription_Sign_Fee__c : 0.00;
                $subscription_period          = ( $data->Subscription_Period_c ) ? $data->Subscription_Period_c : "month";
                $subscription_period_interval = ( $data->Subscription_Period_Interval__c ) ? $data->Subscription_Period_Interval__c : 1;
                $subscription_length          = ( $data->Subscription_Length__c ) ? $data->Subscription_Length__c : 0;

                update_post_meta( $product_id, '_subscription_price', $data->List_Price__c );
                update_post_meta( $product_id, '_subscription_sign_up_fee', $subscription_signup_fee );
                update_post_meta( $product_id, '_subscription_period', $subscription_period );
                update_post_meta( $product_id, '_subscription_period_interval', $subscription_period_interval );
                update_post_meta( $product_id, '_subscription_length', $subscription_length );

            } else {
                $product_type = 'simple';
            }
            $this->return_status( "Updated Product ID: $product_id" );
            return TRUE;

        }

        /** Create new product in Woocommerce */
        function create_new_product( $data ) {

            global $wp_error;

            $args = array(
                'meta_key'   => 'sf_account_id',
                'meta_value' => $data->CreatedById,
                'fields'     => 'ID'
            );

            $user_query = new WP_User_Query( $args );

            if ( $user_query->total_users > 0 ) {

                foreach ( $user_query->results as $author ) {
                    $user_id = $author;
                }

            } else {
                $user_id = '6';
            }

            $post = array(
                'post_author'  => $user_id,
                'post_status'  => 'draft',
                'post_title'   => $data->Name,
                'post_parent'  => '',
                'post_type'    => 'product'
            );

            //Create post
            $post_id = wp_insert_post( $post, $wp_error );

            $virtual = ( strtolower( $data->Product_Type__c ) == 'digital' ) ? 'yes' : 'no';

            update_post_meta( $post_id, '_visibility', 'visible' );
            update_post_meta( $post_id, '_stock_status', 'instock' );
            update_post_meta( $post_id, 'total_sales', '0' );
            update_post_meta( $post_id, '_downloadable', '' );
            update_post_meta( $post_id, '_virtual', $virtual );
            update_post_meta( $post_id, '_regular_price', $data->List_Price__c );
            update_post_meta( $post_id, '_sale_price', $data->Sale_Price__c );
            update_post_meta( $post_id, '_purchase_note', $data->Notes__c );
            update_post_meta( $post_id, '_featured', "no" );
            update_post_meta( $post_id, '_weight', "" );
            update_post_meta( $post_id, '_length', "" );
            update_post_meta( $post_id, '_width', "" );
            update_post_meta( $post_id, '_height', "" );
            update_post_meta( $post_id, '_sku', $data->ProductCode );
            update_post_meta( $post_id, '_product_attributes', array() );
            update_post_meta( $post_id, '_sale_price_dates_from', "" );
            update_post_meta( $post_id, '_sale_price_dates_to', "" );
            update_post_meta( $post_id, '_price', $data->List_Price__c );
            update_post_meta( $post_id, '_sold_individually', "" );
            update_post_meta( $post_id, '_manage_stock', "no" );
            update_post_meta( $post_id, '_backorders', "no" );
            update_post_meta( $post_id, '_stock', "" );
            update_post_meta( $post_id, 'salesforce_id', substr( $data->Id, 0, 15) );

            if ( $data->Family == 'KOM' ) {

                $product_type = 'subscription';

                $subscription_signup_fee      = ( $data->Subscription_Sign_Up_Fee__c ) ? $data->Subscription_Sign_Fee__c : 0.00;
                $subscription_period          = ( $data->Subscription_Period_c ) ? $data->Subscription_Period_c : "month";
                $subscription_period_interval = ( $data->Subscription_Period_Interval__c ) ? $data->Subscription_Period_Interval__c : 1;
                $subscription_length          = ( $data->Subscription_Length__c ) ? $data->Subscription_Length__c : 0;

                update_post_meta( $post_id, '_subscription_price', $data->List_Price__c );
                update_post_meta( $post_id, '_subscription_sign_up_fee', $subscription_signup_fee );
                update_post_meta( $post_id, '_subscription_period', $subscription_period );
                update_post_meta( $post_id, '_subscription_period_interval', $subscription_period_interval );
                update_post_meta( $post_id, '_subscription_length', $subscription_length );

            } else {
                $product_type = 'simple';
            }

            // Product type
            wp_set_object_terms( $post_id, $product_type, 'product_type' );

            return "New product ID: $post_id created.";

        }

        /**
         * Update Account information
         */
        function update_account( $data ) {

            if ( ! $data->SQL_ID_number__c ) {
                return FALSE;
            }

            $user_info = get_user_by( 'id', $data->SQL_ID_number__c );

            $account_id = $user_info->ID;

            $user_data = array(
                'ID'         => $account_id,
                'user_email' => $data->PersonEmail,
	            'user_login'    =>  $data->User_Name__c,
                'display_name'  => $data->FirstName . ' ' . $data->LastName,
            );

            $user_id = wp_update_user( $user_data ); // update user information

//	        if ( is_wp_error( $user_id ) ){
//
//		        // Account doesn't exist. Create new user.
//		        wp_insert_user( $user_data ); // update user information
//
//	        }

	        $k1_start = strtotime( $data->Kelby_Start_Date__c . '00:00:00' );

            $k1_end   = strtotime( $data->Kelby_End_Date__c. '00:00:00' );

            /*
             * Update user metadata. If field doesn't exist, it will create it.
             */
            update_user_meta( $account_id, 'first_name', $data->FirstName );
            update_user_meta( $account_id, 'last_name', $data->LastName );
            update_user_meta( $account_id, 'occupation', $data->Occupation__c );
            update_user_meta( $account_id, 'auto_renewal', $data->Auto_Renewal__c );
            update_user_meta( $account_id, '_virtual', $data->Electronic_Delivery__c );
            update_user_meta( $account_id, 'kelby_start_date', date( 'Y-m-d H:i:s', $k1_start ) );
            update_user_meta( $account_id, 'kelby_end_date', date( 'Y-m-d H:i:s', $k1_end ) );
            update_user_meta( $account_id, 'monthly_renewal', $data->Monthly_Renewal__c );

            /*
             * Billing information from Salesforce Account Object
             */
            update_user_meta( $account_id, 'billing_country', $data->BillingCountry );
            update_user_meta( $account_id, 'billing_address_1', $data->BillingStreet );
            update_user_meta( $account_id, 'billing_city', $data->BillingCity );
            update_user_meta( $account_id, 'billing_state', $data->BillingState );
            update_user_meta( $account_id, 'billing_postcode', $data->BillingPostalCode );
            update_user_meta( $account_id, 'billing_phone', $data->PersonHomePhone );

            /*
             * Shipping information from Salesforce Account Object
             */
            update_user_meta( $account_id, 'shipping_address_1', $data->ShippingStreet );
            update_user_meta( $account_id, 'shipping_city', $data->ShippingCity );
            update_user_meta( $account_id, 'shipping_state', $data->ShippingState );
            update_user_meta( $account_id, 'shipping_postcode', $data->ShippingPostalCode );
            update_user_meta( $account_id, 'shipping_country', $data->ShippingCountry );
            update_user_meta( $account_id, 'sf_account_id', substr( $data->Id, 0, 15) );

            return TRUE;
        }

        /**
         * Update Order Shipping information
         */
        function update_order( $data ) {

            $args = array(
                'meta_key'   => 'sf_account_id',
                'meta_value' => $data->Account__c,
                'fields'     => 'ID'
            );

            $user_query = new WP_User_Query( $args );

            $account_id = $user_query->results[0]->ID;

            foreach ( $user_query->results as $results ) {
                $account_id = $results;
            }

            /*
             * Billing information from SalesForce Order__c Object
             */
            update_user_meta( $account_id, 'billing_country', $data->Billing_Country__c );
            update_user_meta( $account_id, 'billing_first_name', $data->Billing_First_Name__c );
            update_user_meta( $account_id, 'billing_last_name', $data->Billing_Last_Name__c );
            update_user_meta( $account_id, 'billing_company', $data->Billing_Company__c );
            update_user_meta( $account_id, 'billing_address_1', $data->Billing_Street__c );
            update_user_meta( $account_id, 'billing_city', $data->Billing_City__c );
            update_user_meta( $account_id, 'billing_state', $data->Billing_State__c );
            update_user_meta( $account_id, 'billing_postcode', $data->Billing_Postal__c );
            update_user_meta( $account_id, 'billing_email', $data->Billing_Email__c );
            update_user_meta( $account_id, 'billing_phone', $data->Billing_Contact_Phone__c );

            /*
             * Shipping information from SalesForce Order__c Object
             */
            update_user_meta( $account_id, 'shipping_country', $data->Shipping_Country__c );
            update_user_meta( $account_id, 'shipping_first_name', $data->Shipping_First_Name__c );
            update_user_meta( $account_id, 'shipping_last_name', $data->Shipping_Last_Name__c );
            update_user_meta( $account_id, 'shipping_company', $data->Shipping_Company__c );
            update_user_meta( $account_id, 'shipping_address_1', $data->Shipping_Street__c );
            update_user_meta( $account_id, 'shipping_city', $data->Shipping_City__c );
            update_user_meta( $account_id, 'shipping_state', $data->Shipping_State__c );
            update_user_meta( $account_id, 'shipping_postcode', $data->Shipping_Postal__c );
            update_user_meta( $account_id, 'shipping_email', $data->Shipping_Email__c );
            update_user_meta( $account_id, 'shipping_total_cost', $data->Total_Shipping_Cost__c );
            update_user_meta( $account_id, 'shipping_phone', $data->Shipping_Contact_Phone__c );

            return TRUE;
        }

        function authorize_token( $access_token ) {

            $curl = curl_init( site_url() . '/oauth/request_access' );
            curl_setopt( $curl, CURLOPT_HTTPHEADER, array( 'Authorization: Bearer ' . $access_token ) );
            curl_setopt( $curl, CURLOPT_FOLLOWLOCATION, 1 );
            curl_setopt( $curl, CURLOPT_RETURNTRANSFER, TRUE );
            $auth = curl_exec( $curl );

            return $auth;

        }

        /**************************************************************************************************************
         * error()
         *
         * Output error message.
         *
         * @param string $msg
         * @return json
         */
        function error( $msg = '' ) {
            $data = array
            (
                'error' => $msg
            );

            $this->output( $data );
        }

        /**
         * output()
         *
         * Output data in json format.
         *
         */
        function output( $data = '' ) {

            if ( $this->debug ) {
                print_r( $data );
            }

            echo json_encode( $data );
        }

        function return_status( $msg = '' ) {

            $data = array( 'status' => $msg );

            $this->output( $data );
        }

    }

} else {

    /** Exit with a message that the Salesforce API class has been set up */
    _e( 'Salesforce API has already been set up.', 'sf-api' );

}

/**
 * Create a new SFAPI
 * This has been done in the theme page-api.php
 */
//$sfapi = new SFAPI();
