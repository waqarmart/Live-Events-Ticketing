<?php
defined('ABSPATH')  || die('Unauthorized Access');
require 'vendor/autoload.php';

define('PLUGIN_NAME', 'live-events-ticketing');
define('PLUGIN_OPTION_KEY', PLUGIN_NAME . '_license_key');
define('PLUGIN_LICENSE_VERIFIED', PLUGIN_NAME . '_license_verified');
define('PLUGIN_LICENSE_API_URL', 'https://waqarmart.digital/api/verify'); //'https://sandbox.bailey.sh/v3/market/author/sale' {_token | purchase_code} {ukjod-kn21s-y8err-qhl5y}
define('PLUGIN_API_HEADER', 'oGoJRjmSkLKlIzwPhOzXxjPchlI8QLe2'); 
//'cFAKETOKENabcREPLACEMExyzcdefghj'

// Add menu page 
function live_events_verify() {
	add_menu_page(
        'Plugin Verification',          // Page title
        'Live Events',          // Menu title
        'manage_options',       // Capability required to see the menu
        'live-events-verify',      	// Menu slug (unique identifier)
        'callback_live_events_verify', // Callback function to render the main menu page
        'dashicons-admin-tools',   // Icon for the menu
        6                          // Menu position (optional)
    );
	
	add_submenu_page(
        'live-events-verify',       // Parent menu slug
        'Plugin Settings',             	// Page title
        'Settings',             	// Menu title
        'manage_options',     		// Capability required to see the submenu
        'live-events-settings',   	// Submenu slug (unique identifier)
        'callback_events_ticket_settings' // Callback function to render the submenu page
    );
	
    add_submenu_page(
        'live-events-verify',   // Parent menu slug
        'Plugin Orders',             	// Page title
        'Orders',             	// Menu title
        'manage_options',     	// Capability required to see the submenu
        'live-events-orders',   // Submenu slug (unique identifier)
        'callback_events_ticket_orders' // Callback function to render the submenu page
    );
}
add_action('admin_menu', 'live_events_verify');


// Content for the custom page
function callback_live_events_verify() {
    wp_enqueue_style('bootstrap5-css', plugins_url('css/bootstrap.min.css', __FILE__), [], '5.3.3');
    //wp_register_script('jquery-37', plugins_url('js/jquery-3.7.1.min.js', __FILE__), array(), '3.7.1', true);
    wp_enqueue_script( 'jquery' );
    wp_enqueue_script('bootstrap5-js', plugins_url('js/bootstrap.min.js', __FILE__), ['jquery'], '5.3.3', true);
    
	try {
		$html = "";
		$msg_license = "";
		if (isset($_REQUEST['submit'])) {
		    if(isset($_REQUEST['_wpnonce'])){
                $nonce = isset($_REQUEST['_wpnonce'])? sanitize_text_field(wp_unslash($_REQUEST['_wpnonce'])) : '';
                if (!wp_verify_nonce($nonce, 'events_list')) {
                    wp_die('Invalid nonce verification.');
                }
            }
            
			$license_key = isset($_REQUEST['license_key'])? sanitize_text_field(wp_unslash($_REQUEST['license_key'])) : "";
			$is_verified = verify_waqarmartdigital_license($license_key);
            //echo "<pre>"; print_r($is_verified); die;
			
			// Show a success or error message
			if ($is_verified) {
			    update_option(PLUGIN_OPTION_KEY, $license_key);
    			update_option(PLUGIN_LICENSE_VERIFIED, $is_verified);
    			
				$html .= '<div class="updated"><p>License verified successfully!</p></div>';
			} else {
				$html .= '<div class="error"><p>Failed to verify the license key. Please check the key and try again.</p></div>';
			}
		}

		// Get the saved license key
		$license_key = get_option(PLUGIN_OPTION_KEY);
		$is_license_verified = get_option(PLUGIN_LICENSE_VERIFIED);
		if($is_license_verified){
			$msg_license = "<h1 style=\"color:green; font-weight:bold;\">Your License Verified Successfully!</h1><br><br><br>";
		}
		// Render the settings form
		$html .= '<div class="wrap">';
		$html .= $msg_license;
		$html .= '<h1>Live Events Ticketing Plugin License Verification</h1>';
		$html .= '<h4>Purchase Plugin License From <a href="https://waqarmart.digital/item/wordpress-plugin-live-events-ticketing-selling-plugin" target="_blank">Waqarmart.digital</a></h4>';
		$html .= '<form method="post">';
		$html .= '<table class="form-table">';
		$html .= '<tr>';
		$html .= '<th scope="row">License Key</th>';
		$html .= '<td><input type="text" name="license_key" value="' . esc_attr($license_key) . '" class="regular-text" required /></td>';
		$html .= '</tr>';
		$html .= '</table>';
		$html .= '<p><input type="submit" name="submit" class="button button-primary" value="Save Settings" /></p>';
		$html .= '</form>';
		$html .= '</div>';

    } catch (\Exception $e) {
        $html .= 'Message: ' . esc_html( $e->getMessage() );
    }
    
    echo $html;
}

function verify_waqarmartdigital_license($license_key) {
	try {
		$response = wp_remote_post(
			PLUGIN_LICENSE_API_URL, array(
             'body' => array( 'purchase_code' => trim($license_key) ),
            )
		);
		//echo "<pre>"; print_r($response); die;
		// Check if the response was successful
		if (is_wp_error($response)) {
			return false;
		}

		// Decode the JSON response
		$response_body = json_decode(wp_remote_retrieve_body($response), true);
        //echo "<pre>"; print_r($response_body); die;
		// Check if the sale information was returned
		if (isset($response_body) && $response_body > 0) {
			return true;
		} else {
			return false;
		}
	} catch (\Exception $e) {
        echo 'Message: ' . esc_html( $e->getMessage() );
    }
}