<?php
defined('ABSPATH')  || die('Unauthorized Access');
require 'vendor/autoload.php';

use Square\SquareClient;
use Square\Exceptions\ApiException;
use TicketEvolution\Client as TEvoClient;

if(!empty(esc_attr(get_option('ProductionAPIToken')) && !empty(esc_attr(get_option('ProductionAPISecret'))) && !empty(esc_attr(get_option('ProductionOfficeId'))))){
	$baseUrl     = 'https://api.ticketevolution.com';
	$environment = 'production';
	$apiVersion  = 'v9';
	$apiToken    = esc_attr(get_option('ProductionAPIToken'));
	$apiSecret   = esc_attr(get_option('ProductionAPISecret'));
	$office_id = esc_attr(get_option('ProductionOfficeId'));
} else {
	$baseUrl     = 'http://api.sandbox.ticketevolution.com';
	$environment = 'sandbox';
	$apiVersion  = 'v9';
	$apiToken    = esc_attr(get_option('SandboxAPIToken'));
	$apiSecret   = esc_attr(get_option('SandboxAPISecret'));
	$office_id = esc_attr(get_option('SandboxOfficeId'));
}

if(!empty(esc_attr(get_option('LiveSquareupAppId')) && !empty(esc_attr(get_option('LiveSquareupLocationId'))) && !empty(esc_attr(get_option('LiveSquareAccessToken'))))){
	$squareEnvironment = 'production';
	$squareupAppId = esc_attr(get_option('LiveSquareupAppId'));
	$squareupLocationId = esc_attr(get_option('LiveSquareupLocationId'));
	$squareAccessToken = esc_attr(get_option('LiveSquareAccessToken'));
} else {
	$squareEnvironment = 'sandbox';
	$squareupAppId = esc_attr(get_option('SandboxSquareupAppId'));
	$squareupLocationId = esc_attr(get_option('SandboxSquareupLocationId'));
	$squareAccessToken = esc_attr(get_option('SandboxSquareAccessToken'));
}

$resultPerPage = (int)esc_attr(get_option('SitePesultsPerPage'));
$website_url = isset($_SERVER['SERVER_NAME'])? "https://".sanitize_text_field(wp_unslash($_SERVER['SERVER_NAME'])) : "";
$plugin_dir_url = $website_url."/wp-content/plugins/live-events-ticketing";


// Users Login/Registration necessary scripts
add_action('wp_enqueue_scripts', 'aur_ajax_scripts');
function aur_ajax_scripts() {
    wp_enqueue_script('ajax-user-check', $GLOBALS['plugin_dir_url'] . '/js/ajax-user.js', array('jquery'), '1.0', true);
    wp_localize_script('ajax-user-check', 'ajax_user_params', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'security' => wp_create_nonce('ajax-user-check-nonce'),
    ));
    
    wp_localize_script('ajax-user-check', 'ajax_registration_params', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'security' => wp_create_nonce('ajax-user-registration-nonce'),
    ));
    
    wp_localize_script('ajax-user-check', 'ajax_address_create_param', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'security' => wp_create_nonce('ajax-address-create-nonce'),
    ));
    
    wp_localize_script('ajax-user-check', 'ajax_square_payment_params', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'security' => wp_create_nonce('ajax-square-payment-nonce'),
    ));
}

// Enqueue Square JavaScript SDK
/*function enqueue_square_sdk() {
    wp_enqueue_script('square-sdk', 'https://sandbox.web.squarecdn.com/v1/square.js', array(), null, true);
}
add_action('wp_enqueue_scripts', 'enqueue_square_sdk');*/

// Handle Ajax registration
function aur_ajax_user_check() {
    check_ajax_referer('ajax-user-check-nonce', 'security');
    global $wpdb;

    $email = isset($_REQUEST['email'])? $wpdb->_real_escape(sanitize_text_field(wp_unslash($_REQUEST['email']))) : '';
    $account_check = isset($_REQUEST['account_check'])? $wpdb->_real_escape(sanitize_text_field(wp_unslash($_REQUEST['account_check']))) : '';
    
    if($account_check == "existing"){
        $password = isset($_REQUEST['password'])? $wpdb->_real_escape(sanitize_text_field(wp_unslash($_REQUEST['password']))) : '';
        $get_user_email =  get_user_by( 'email', $email );
        
        if ( in_array( 'subscriber', (array) $get_user_email->roles ) ) {
            $login = [];
            $login["user_login"] = $email;
            $login["user_password"] = $password;
            $varify = wp_signon($login, true);
            
            if(is_wp_error($varify)){
                echo wp_json_encode(array('success' => false, 'message' => "Invalid Credentials!"));
            } else {
                echo wp_json_encode(array('success' => true, 'message' => "signin"));
            }
        } else {
            echo wp_json_encode(array('success' => false, 'message' => "You are trying to access account of [".$get_user_email->roles[0]."]!"));
        }
    } else {
        if(strpos($email, ' ') !== FALSE){
            echo wp_json_encode(array('success' => false, 'message' => "Email has Space!"));
            wp_die();
        }
        if(empty($email)){
            echo wp_json_encode(array('success' => false, 'message' => "Email is required!"));
            wp_die();
        }
        if(!is_email($email)){
            echo wp_json_encode(array('success' => false, 'message' => "Email has no valid value!"));
            wp_die();
        }
        if(email_exists($email)){
            echo wp_json_encode(array('success' => false, 'message' => "Email already exists!"));
            wp_die();
        }
        if(username_exists($email)){
            echo wp_json_encode(array('success' => false, 'message' => "Email as username is already exist!"));
            wp_die();
        }
        
        echo wp_json_encode(array('success' => true, 'message' => "signup"));
    }
    
    wp_die();
}
add_action('wp_ajax_nopriv_ajax_user_check', 'aur_ajax_user_check'); // for users that are not logged in


function aur_ajax_register_user() {
    check_ajax_referer('ajax-user-registration-nonce', 'security');
    global $wpdb;
    
    $address = [];
    $account_check = isset($_REQUEST['account_check'])? $wpdb->_real_escape(sanitize_text_field(wp_unslash($_REQUEST['account_check']))) : '';
    
    if($account_check == "new"){
        $address['fullname'] = $fullname = isset($_REQUEST['fullname'])? $wpdb->_real_escape(sanitize_text_field(wp_unslash($_REQUEST['fullname']))) : '';
        $address['company'] = $company = isset($_REQUEST['company'])? $wpdb->_real_escape(sanitize_text_field(wp_unslash($_REQUEST['company']))) : '';
        $address['mobile'] = $mobile = isset($_REQUEST['mobile'])? $wpdb->_real_escape(sanitize_text_field(wp_unslash($_REQUEST['mobile']))) : '';
        $address['line1'] = $line1 = isset($_REQUEST['line1'])? $wpdb->_real_escape(sanitize_text_field(wp_unslash($_REQUEST['line1']))) : '';
        $address['line2'] = $line2 = isset($_REQUEST['line2'])? $wpdb->_real_escape(sanitize_text_field(wp_unslash($_REQUEST['line2']))) : '';
        $address['city'] = $city = isset($_REQUEST['city'])? $wpdb->_real_escape(sanitize_text_field(wp_unslash($_REQUEST['city']))) : '';
        $address['zip'] = $zip = isset($_REQUEST['zip'])? $wpdb->_real_escape(sanitize_text_field(wp_unslash($_REQUEST['zip']))) : '';
        $address['state'] = $state = isset($_REQUEST['state'])? $wpdb->_real_escape(sanitize_text_field(wp_unslash($_REQUEST['state']))) : '';
        $address['country'] = $country = isset($_REQUEST['country'])? $wpdb->_real_escape(sanitize_text_field(wp_unslash($_REQUEST['country']))) : '';
        $address['delivery_method'] = $delivery_method = isset($_REQUEST['delivery_method'])? $wpdb->_real_escape(sanitize_text_field(wp_unslash($_REQUEST['delivery_method']))) : '';
        
        
        $email = isset($_REQUEST['email'])? $wpdb->_real_escape(sanitize_text_field(wp_unslash($_REQUEST['email']))) : '';
        $password = isset($_REQUEST['password'])? $wpdb->_real_escape(sanitize_text_field(wp_unslash($_REQUEST['password']))) : '';
        $password_confirm = isset($_REQUEST['password_confirm'])? $wpdb->_real_escape(sanitize_text_field(wp_unslash($_REQUEST['password_confirm']))) : '';
        
        $venue_id = isset($_REQUEST['venue_id'])? $wpdb->_real_escape(sanitize_text_field(wp_unslash($_REQUEST['venue_id']))) : '';
        $event_id = isset($_REQUEST['event_id'])? $wpdb->_real_escape(sanitize_text_field(wp_unslash($_REQUEST['event_id']))) : '';
        $listing_id = isset($_REQUEST['listing_id'])? $wpdb->_real_escape(sanitize_text_field(wp_unslash($_REQUEST['listing_id']))) : '';
        $configuration_id = isset($_REQUEST['configuration_id'])? $wpdb->_real_escape(sanitize_text_field(wp_unslash($_REQUEST['configuration_id']))) : '';
        $ticket_qty = isset($_REQUEST['ticket_qty'])? $wpdb->_real_escape(sanitize_text_field(wp_unslash($_REQUEST['ticket_qty']))) : '';
        
        if(empty($fullname)){
            echo wp_json_encode(array('success' => false, 'message' => "Full Name is required!"));
            wp_die();
        }
        if(empty($mobile)){
            echo wp_json_encode(array('success' => false, 'message' => "Mobile Number is required!"));
            wp_die();
        }
        if(empty($line1)){
            echo wp_json_encode(array('success' => false, 'message' => "Address Line1 is required!"));
            wp_die();
        }
        if(empty($city)){
            echo wp_json_encode(array('success' => false, 'message' => "City is required!"));
            wp_die();
        }
        if(empty($zip)){
            echo wp_json_encode(array('success' => false, 'message' => "Zip is required!"));
            wp_die();
        }
        if(empty($state)){
            echo wp_json_encode(array('success' => false, 'message' => "State is required!"));
            wp_die();
        }
        if(empty($country)){
            echo wp_json_encode(array('success' => false, 'message' => "Country is required!"));
            wp_die();
        }
        if(empty($password)){
            echo wp_json_encode(array('success' => false, 'message' => "Password is required!"));
            wp_die();
        }
        if(empty($password_confirm)){
            echo wp_json_encode(array('success' => false, 'message' => "Confirm Password is required!"));
            wp_die();
        }
        if($password != $password_confirm){
            echo wp_json_encode(array('success' => false, 'message' => "Password is mismatched with confirm password!"));
            wp_die();
        }
        
        try {
                $user_id = wp_create_user($email, $password, $email);
                if (!is_wp_error($user_id)) {
                    update_user_meta( $user_id, 'nickname', $fullname );
                    $login = [];
                    $login["user_login"] = $email;
                    $login["user_password"] = $password;
                    $varify = wp_signon($login, true);
                    
                    $apiClient = new TEvoClient([
                        'baseUrl'     => $GLOBALS['baseUrl'],
                        'apiVersion'  => $GLOBALS['apiVersion'],
                        'apiToken'    => $GLOBALS['apiToken'],
                        'apiSecret'   => $GLOBALS['apiSecret'],
                    ]);
                    
                    $client_data = array(array(
                        'name' => $fullname
                    ));
                    $createClient = $apiClient->createClients([
                        "clients" => $client_data
                    ]);
                    $created_client_id = (int)$createClient['clients'][0]['id']; 
                    
                    $email_array = array(array(
                        'address' => $email
                    ));
                    $createEmail = $apiClient->createClientEmailAddresses([
                        'client_id' => $created_client_id,
                        'email_addresses' => $email_array
                    ]);
                    
                    $phone_array = array(array(
                        'number' => $mobile
                    ));
                    $createPhone = $apiClient->createClientPhoneNumbers([
                        'client_id' => $created_client_id,
                        'phone_numbers' => $phone_array
                    ]);
                    
                    $address_array = array(array(
                        'name' => $fullname,
                        'company' => $company,
                        'street_address' => $line1,
                        'extended_address' => $line2,
                        'locality' => $city,
                        'region' => $state,
                        'postal_code' => $zip,
                        'country_code' => $country,
                        'primary' => true,
                        'is_primary_shipping' => true,
                        'is_primary_billing' => true,
                    ));
                    $createAddress = $apiClient->createClientAddresses([
                        'client_id' => $created_client_id,
                        'addresses' => $address_array
                    ]);
                    
                    
                    $listing_ob = $apiClient->showListing([
                        'id' => (int)$listing_id
                    ]);
                    
                    $ticket_group_id = $listing_ob['id'];
                    $retail_price = $listing_ob['retail_price'];
                    $wholesale_price = $listing_ob['wholesale_price'];
                    $format = $listing_ob['format'];
                    $office_id = $GLOBALS['office_id'];
                    
                    $client_ob = $apiClient->showClient([
                        'client_id' => $created_client_id
                    ]);
                    
                    $client_id = $client_ob['id'];
                    $client_name = $client_ob['name'];
                    $client_company = $client_ob['company'];
                    $client_primary_shipping_address_id = $client_ob['primary_shipping_address']['id'];
                    $client_primary_billing_address_id = $client_ob['primary_billing_address']['id'];
                    $client_primary_email_address_id = $client_ob['primary_email_address']['id'];
                    $client_primary_phone_number_id = $client_ob['primary_phone_number']['id'];
                    
                    update_user_meta( $user_id, 'c_api_clientid', $client_id );
                    //update_user_meta( $user_id, 'c_api_orderid', $order_id );
                    echo wp_json_encode(array('success' => true, 'message' => 'user_created'));
                }
            } catch (Exception $e) {
                echo wp_json_encode(array('success' => false, 'message' => 'Error: '.$e->getMessage()));
            }
    } else {
        
    }
    
    wp_die();
}
add_action('wp_ajax_ajax_register_user', 'aur_ajax_register_user');
add_action('wp_ajax_nopriv_ajax_register_user', 'aur_ajax_register_user'); // for users that are not logged in


// Create Client Primary Address
function aur_ajax_address_create() {
    check_ajax_referer('ajax-address-create-nonce', 'security');
    global $wpdb;
    try {
        $address['client_id'] = $client_id = isset($_REQUEST['existing_client_id'])? $wpdb->_real_escape(sanitize_text_field(wp_unslash($_REQUEST['existing_client_id']))) : '';
        $address['fullname'] = $fullname = isset($_REQUEST['fullname'])? $wpdb->_real_escape(sanitize_text_field(wp_unslash($_REQUEST['fullname']))) : '';
        $address['line1'] = $line1 = isset($_REQUEST['line1'])? $wpdb->_real_escape(sanitize_text_field(wp_unslash($_REQUEST['line1']))) : '';
        $address['line2'] = $line2 = isset($_REQUEST['line2'])? $wpdb->_real_escape(sanitize_text_field(wp_unslash($_REQUEST['line2']))) : '';
        $address['city'] = $city = isset($_REQUEST['city'])? $wpdb->_real_escape(sanitize_text_field(wp_unslash($_REQUEST['city']))) : '';
        $address['zip'] = $zip = isset($_REQUEST['zip'])? $wpdb->_real_escape(sanitize_text_field(wp_unslash($_REQUEST['zip']))) : '';
        $address['state'] = $state = isset($_REQUEST['state'])? $wpdb->_real_escape(sanitize_text_field(wp_unslash($_REQUEST['state']))) : '';
        $address['country'] = $country = isset($_REQUEST['country'])? $wpdb->_real_escape(sanitize_text_field(wp_unslash($_REQUEST['country']))) : '';
        
        $apiClient = new TEvoClient([
            'baseUrl'     => $GLOBALS['baseUrl'],
            'apiVersion'  => $GLOBALS['apiVersion'],
            'apiToken'    => $GLOBALS['apiToken'],
            'apiSecret'   => $GLOBALS['apiSecret'],
        ]);
        
        $address_array = array(array(
            'name' => $fullname,
            'street_address' => $line1,
            'extended_address' => $line2,
            'locality' => $city,
            'region' => $state,
            'postal_code' => $zip,
            'country_code' => $country,
            'primary' => false,
            'is_primary_shipping' => false,
            'is_primary_billing' => true,
        ));
        $createAddress = $apiClient->createClientAddresses([
            'client_id' => (int)$client_id,
            'addresses' => $address_array
        ]);
        
        echo wp_json_encode(array('success' => true, 'message' => "billing_address_created"));
    } catch (Exception $e) {
        echo wp_json_encode(array('success' => false, 'message' => 'Error: '.$e->getMessage()));
    }
    wp_die();
}
add_action('wp_ajax_ajax_address_create', 'aur_ajax_address_create');
add_action('wp_ajax_nopriv_ajax_address_create', 'aur_ajax_address_create'); // for users that are not logged in


// Create Client Primary Address
function aur_ajax_square_payment() {
    check_ajax_referer('ajax-square-payment-nonce', 'security');
    global $wpdb;
    try {
        
        $apiClient = new TEvoClient([
            'baseUrl'     => $GLOBALS['baseUrl'],
            'apiVersion'  => $GLOBALS['apiVersion'],
            'apiToken'    => $GLOBALS['apiToken'],
            'apiSecret'   => $GLOBALS['apiSecret'],
        ]);
        
        $logged_user_id = get_current_user_id();
        $logged_user = wp_get_current_user();
        $logged_user_email = $logged_user->user_email;
        $c_api_clientid = get_user_meta( $logged_user_id, 'c_api_clientid', true );
        
        $client_ob = $apiClient->showClient([
            'client_id' => (int)$c_api_clientid
        ]);
        
        $primary_name = $client_ob['primary_shipping_address']['name'];
        $name_parts = explode(" ",$primary_name);
        $first_name = (isset($name_parts[0]))? $name_parts[0] : '';
        $last_name = (isset($name_parts[1]))? $name_parts[1] : '';
        $primary_email = $client_ob['primary_email_address']['address'];
        $primary_country_code = $client_ob['primary_phone_number']['country_code'];
        $primary_phone = $client_ob['primary_phone_number']['number'];
        $primary_phone_number = "+".$primary_country_code.$primary_phone;
        
        $billing_address = new \Square\Models\Address();
        $billing_address->setAddressLine1($client_ob['primary_shipping_address']['street_address']);
        $billing_address->setAddressLine2($client_ob['primary_shipping_address']['extended_address']);
        $billing_address->setLocality($client_ob['primary_shipping_address']['locality']);
        $billing_address->setAdministrativeDistrictLevel1($client_ob['primary_shipping_address']['region']);
        $billing_address->setPostalCode($client_ob['primary_shipping_address']['postal_code']);
        $billing_address->setCountry($client_ob['primary_shipping_address']['country_code']);
        
        $shipping_address = new \Square\Models\Address();
        $shipping_address->setAddressLine1($client_ob['primary_billing_address']['street_address']);
        $shipping_address->setAddressLine2($client_ob['primary_billing_address']['extended_address']);
        $shipping_address->setLocality($client_ob['primary_billing_address']['locality']);
        $shipping_address->setAdministrativeDistrictLevel1($client_ob['primary_billing_address']['region']);
        $shipping_address->setPostalCode($client_ob['primary_billing_address']['postal_code']);
        $shipping_address->setCountry($client_ob['primary_billing_address']['country_code']);
        
        $square_client = new SquareClient([
          'accessToken' => $GLOBALS['squareAccessToken'],
          'environment' => $GLOBALS['squareEnvironment']
        ]);
        
        $saved_square_card_id = get_user_meta( $logged_user_id, 'square_card_id', true );
        $saved_square_customer_id = get_user_meta( $logged_user_id, 'square_customer_id', true );
        $saved_square_sourceId = get_user_meta( $logged_user_id, 'square_source_id', true );
        
        /********Start Payment Creation*********/
        $locationId = isset($_REQUEST['locationId'])? sanitize_text_field(wp_unslash($_REQUEST['locationId'])) : '';
        $ticket_venueid = isset($_REQUEST['ticket_venueid'])? sanitize_text_field(wp_unslash($_REQUEST['ticket_venueid'])) : '';
        $ticket_eventid = isset($_REQUEST['ticket_eventid'])? sanitize_text_field(wp_unslash($_REQUEST['ticket_eventid'])) : '';
        $ticket_listingId = isset($_REQUEST['ticket_listingId'])? sanitize_text_field(wp_unslash($_REQUEST['ticket_listingId'])) : '';
        $delivery_method = isset($_REQUEST['delivery_method'])? sanitize_text_field(wp_unslash($_REQUEST['delivery_method'])) : '';
        $SquareSaveCard = isset($_REQUEST['SquareSaveCard'])? sanitize_text_field(wp_unslash($_REQUEST['SquareSaveCard'])) : '';
        $ticketAmount = isset($_REQUEST['ticket_amount'])? sanitize_text_field(wp_unslash($_REQUEST['ticket_amount'])) : '';
        $ticket_qty = isset($_REQUEST['ticket_qty'])? sanitize_text_field(wp_unslash($_REQUEST['ticket_qty'])) : '';
        $totalAmount = $ticketAmount*$ticket_qty;
        $totalAmount_in_cents = (int)($totalAmount * 100);
        $wp_order_array['total_amount'] = $totalAmount;
        $wp_order_array['order_amount'] = $ticketAmount;
        $wp_order_array['order_qty'] = $ticket_qty;
        $message = "";
        
        
        /********Save API ORDER********/
        $listing_ob = $apiClient->showListing([
            'id' => (int)$ticket_listingId
        ]);
        
        $ticket_group_id = $listing_ob['id'];
        $retail_price = $listing_ob['retail_price'];
        $wholesale_price = $listing_ob['wholesale_price'];
        $format = $listing_ob['format'];
        $office_id = $GLOBALS['office_id'];
        
        $client_id = $client_ob['id'];
        $client_name = $client_ob['name'];
        $client_company = $client_ob['company'];
        $client_primary_shipping_address_id = $client_ob['primary_shipping_address']['id'];
        $client_primary_billing_address_id = $client_ob['primary_billing_address']['id'];
        $client_primary_email_address_id = $client_ob['primary_email_address']['id'];
        $client_primary_phone_number_id = $client_ob['primary_phone_number']['id'];
        
        $order_array = array(array(
            "client_id" => $client_id,
            "billing_address_id" => $client_primary_billing_address_id,
            "created_by_ip_address" => get_the_user_ip(),
            "internal_notes" => "Double check the payment made and approve order manually.",
            "payments" => array(array(
                "type" => "offline"
            )),
            "seller_id" => (int)$office_id,
            "shipped_items" => array(array(
              "phone_number_id" => $client_primary_phone_number_id,
              "email_address_id" => $client_primary_email_address_id,
              "address_id" => $client_primary_shipping_address_id,
              "items" => array(array(
                  "price" => $retail_price,
                  "quantity" => $ticket_qty,
                  "ticket_group_id" => $ticket_group_id,
                  //"wholesale_price" => $wholesale_price
               )),
              "ship_to_name" => $client_name,
              "type" => $delivery_method
            )),
            "shipping" => 0,
            "tax" => 0
        ));
        $createOrder = $apiClient->createOrders([
            'orders' => $order_array
        ]);
        //echo "<pre>"; print_r($createOrder); die;
        $wp_order_array['order_id'] = $order_id = $createOrder['orders'][0]['id'];
        /********Save API ORDER********/
        
        
        try {
            $amount_money = new \Square\Models\Money();
            $amount_money->setAmount($totalAmount_in_cents);
            $amount_money->setCurrency('USD');
            
            if(isset($saved_square_card_id) && !empty($saved_square_card_id)){
                //cnon:CBASEMPqaldobr8reY0HlxrjycM
                //uniqid()
                $cardonfile = new \Square\Models\CreatePaymentRequest($saved_square_card_id, uniqid());
                $cardonfile->setAmountMoney($amount_money);
                $cardonfile->setCustomerId($saved_square_customer_id);
                $cardonfile->setLocationId($locationId);
                $cardonfile->setAutocomplete(true);
                $payment = $square_client->getPaymentsApi()->createPayment($cardonfile);
                
                if (!$payment->isSuccess()) {
                    $errors = $payment->getErrors();
                    $getCode = $errors[0]->getCode();
                    $getDetail = $errors[0]->getDetail();
                    echo wp_json_encode(array('success' => false, 'message' => '<b>Code:</b> ['.$getCode.'].<br> <b>Error:</b> '.$getDetail)); die;
                }
                
                $wp_order_array['square_payment_id'] = $paymentId = $payment->getResult()->getPayment()->getId();
            } else {
                $sourceId = isset($_REQUEST['sourceId'])? sanitize_text_field(wp_unslash($_REQUEST['sourceId'])) : '';
                $verificationToken = isset($_REQUEST['verificationToken'])? sanitize_text_field(wp_unslash($_REQUEST['verificationToken'])) : '';
                $idempotencyKey = uniqid(); //$_REQUEST['idempotencyKey'];
                
                $paymentBody = new \Square\Models\CreatePaymentRequest($sourceId, $idempotencyKey);
                $paymentBody->setAmountMoney($amount_money);
                $paymentBody->setAutocomplete(true);
                $paymentBody->setReferenceId($logged_user_id);
                $paymentBody->setLocationId($locationId);
                $paymentBody->setVerificationToken($verificationToken);
                $payment = $square_client->getPaymentsApi()->createPayment($paymentBody);
                
                if (!$payment->isSuccess()) {
                    $errors = $payment->getErrors();
                    $getCode = $errors[0]->getCode();
                    $getDetail = $errors[0]->getDetail();
                    echo wp_json_encode(array('success' => false, 'message' => '<b>Code:</b> ['.$getCode.'].<br> <b>Error:</b> '.$getDetail)); die;
                }
                
                $wp_order_array['square_payment_id'] = $paymentId = $payment->getResult()->getPayment()->getId();
                //add_user_meta( $logged_user_id, 'square_paymentId', $paymentId );
                
                if(isset($SquareSaveCard) && !empty($SquareSaveCard) && $SquareSaveCard == "true"){
                    if(empty($saved_square_customer_id)){
                        $customersApi = $square_client->getCustomersApi();
                        $createCustomerRequest = new \Square\Models\CreateCustomerRequest();
                        $createCustomerRequest->setGivenName($first_name);
                        $createCustomerRequest->setFamilyName($last_name);
                        $createCustomerRequest->setAddress($shipping_address);
                        $createCustomerRequest->setEmailAddress($primary_email);
                        $createCustomerRequest->setPhoneNumber($primary_phone_number);
                        $createCustomerRequest->setReferenceId($logged_user_id);
                        $customer = $customersApi->createCustomer($createCustomerRequest);
                        
                        if (!$customer->isSuccess()) {
                            $errors = $createdCard->getErrors();
                            $getCode = $errors[0]->getCode();
                            $getDetail = $errors[0]->getDetail();
                            $message .= '<br>But unfortunately your info is not saved for future use, due to:<br> <b>Code:</b> ['.$getCode.'].<br> <b>Error:</b> '.$getDetail;
                        } else {
                            $saved_square_customer_id = $customerId = $customer->getResult()->getCustomer()->getId();
                            update_user_meta( $logged_user_id, 'square_customer_id', $customerId );
                        }
                    }
                    
                    $card = new \Square\Models\Card();
                    $card->setCardholderName($primary_name);
                    $card->setBillingAddress($billing_address);
                    $card->setCustomerId($saved_square_customer_id);
                    $card->setReferenceId($logged_user_id);
                    $customerCard = new \Square\Models\CreateCardRequest($sourceId, $paymentId, $card );
                    $createdCard = $square_client->getCardsApi()->createCard($customerCard);
                    
                    if (!$createdCard->isSuccess()) {
                        $errors = $createdCard->getErrors();
                        $getCode = $errors[0]->getCode();
                        $getDetail = $errors[0]->getDetail();
                        $message .= '<br>But unfortunately this card is not saved for future use, due to:<br> <b>Code:</b> ['.$getCode.'].<br> <b>Error:</b> '.$getDetail;
                    } else {
                        $cardId = $createdCard->getResult()->getCard()->getId();
                        add_user_meta( $logged_user_id, 'square_card_id', $cardId );
                        add_user_meta( $logged_user_id, 'square_source_id', $sourceId );
                    }
                }
            }
        } catch (\Square\Exceptions\ApiException $e) {
            echo wp_json_encode(array('success' => false, 'message' => 'Server Error: '.$e->getMessage())); die;
        }
        /********End Payment Creation*********/
        /********Start Saving Order values*********/
        
        add_user_meta( $logged_user_id, 'c_api_orderid', $wp_order_array );
        
        /********End Saving Order values*********/
        
        echo wp_json_encode(array('success' => true, 'message' => "Thank You! your payment is successful and ticket order placed. Once we confirm the order, you'll receive confirmation email within 24 hours.<br>".$message));
    } catch (Exception $e) {
        echo wp_json_encode(array('success' => false, 'message' => 'Error: '.$e->getMessage()));
    }
    wp_die();
}
add_action('wp_ajax_ajax_square_payment', 'aur_ajax_square_payment');
add_action('wp_ajax_nopriv_ajax_square_payment', 'aur_ajax_square_payment'); // for users that are not logged in





