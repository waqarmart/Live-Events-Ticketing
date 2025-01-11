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

$resultPerPage = (int)esc_attr(get_option('SitePesultsPerPage'));
$website_url = isset($_SERVER['SERVER_NAME'])? "https://".sanitize_text_field(wp_unslash($_SERVER['SERVER_NAME'])) : "";
$plugin_dir_url = $website_url."/wp-content/plugins/live-events-ticketing";


// Content for the custom page
function callback_events_ticket_orders() {
    wp_enqueue_style('bootstrap5-css', plugins_url('css/bootstrap.min.css', __FILE__), [], '5.3.3');
    wp_enqueue_style('fontawesome-css', plugins_url('css/fontawesome.min.6.4.2.css', __FILE__), [], '6.4.2');
    //wp_enqueue_script('jquery-37', plugins_url('js/jquery-3.7.1.min.js', __FILE__), array(), '3.7.1', true);
    wp_enqueue_script( 'jquery' );
    wp_enqueue_script('popper', plugins_url('js/popper.min.js', __FILE__), array(), '2.11.8', true);
    wp_enqueue_script('bootstrap5-js', plugins_url('js/bootstrap.min.js', __FILE__), ['jquery'], '5.3.3', true);
    
    //$html = '<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.1.3/dist/css/bootstrap.min.css" integrity="sha384-MCw98/SFnGE8fJT3GXwEOngsV7Zt27NXFoaoApmYm81iuXoPkFOJwJ8ERdknLPMO" crossorigin="anonymous">';
	//$html = '<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.4.2/css/fontawesome.min.css" integrity="sha384-BY+fdrpOd3gfeRvTSMT+VUZmA728cfF9Z2G42xpaRkUGu2i3DyzpTURDo5A6CaLK" crossorigin="anonymous">';
    //$html .= '<script src="https://code.jquery.com/jquery-3.3.1.slim.min.js" integrity="sha384-q8i/X+965DzO0rT7abK41JStQIAqVgRVzpbzo5smXKp4YfRvH+8abtTE1Pi6jizo" crossorigin="anonymous"></script>';
    //$html .= '<script src="https://cdn.jsdelivr.net/npm/popper.js@1.14.3/dist/umd/popper.min.js" integrity="sha384-ZMP7rVo3mIykV+2+9J3UJ46jBk0WLaUAdn689aCwoqbBJiSnjAK/l8WvCWPIPm49" crossorigin="anonymous"></script>';
    //$html .= '<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.1.3/dist/js/bootstrap.min.js" integrity="sha384-ChfqqxuZUCnJSK3+MXmPNIyE6ZbWh2IMqE241rYiqJxyMiZ6OW/JmZQ5stwEULTy" crossorigin="anonymous"></script>';
    $html = "<style>
            table { color: #000000; border: none; }
            .black {color: #000000 !important;}
            .darkpink {color: #ef457a !important;}
            .pink {color: #ce3197 !important;}
            .green {color: #086e88 !important;}
            .event-heading, .event_date { font-family: var(--e-global-typography-7a8c4c5-font-family), sans-serif; font-size: 18px !important; font-weight: bold; line-height: 25px !important; letter-spacing: 0.1px !important; }
            .event-subheading, .event_time { font-family: var(--e-global-typography-7a8c4c5-font-family), sans-serif; font-size: 14px !important; }
            .event-heading:hover { color: #000 !important; }
            .row_border {border-bottom: 1px solid #ef457a;}
            .ticket-button { font-family: var(--e-global-typography-7a8c4c5-font-family), sans-serif; font-size: 16px; background-color: #ef457a; padding: 2px 10px 5px !important; color: #ffffff !important; float: right; margin-right: 5px; cursor: pointer; border-radius: 5px; border: 1px solid #ef457a; }
            .ticket-button:hover { background-color: #ffffff; color: #000000 !important; }
            
            .pagination .page-link, .statuschange { cursor: pointer; /*background: #f1f1f1;*/ }
            .pagination .dots { padding: 6px 5px 0px; }
            .pagination .active { color: #fff; }
            .pagination .page-item { margin: 0 6px; }
			.parent_categories ul li { list-style-type: none; float: left; margin-right: 10px; margin-bottom: 10px; }
			.parent_categories ul li a {border: 1px solid #ccc; border-radius: 25px; padding: 5px 20px; background: #ffffff;}
			.parent_categories ul li a:hover { background: #ef457a; cursor: pointer; color: #ffffff; }
			#wpfooter p {display: none;}
            </style>";
	
    $apiClient = new TEvoClient([
        'baseUrl'     => $GLOBALS['baseUrl'],
        'apiVersion'  => $GLOBALS['apiVersion'],
        'apiToken'    => $GLOBALS['apiToken'],
        'apiSecret'   => $GLOBALS['apiSecret'],
    ]);
    
    try {
		$is_license_verified = get_option(PLUGIN_LICENSE_VERIFIED);
		if($is_license_verified){
		    if(isset($_REQUEST['_wpnonce'])){
                $nonce = isset($_REQUEST['_wpnonce'])? sanitize_text_field(wp_unslash($_REQUEST['_wpnonce'])) : '';
                if (!wp_verify_nonce($nonce, 'events_list')) {
                    wp_die('Invalid nonce verification.');
                }
            }
			$pageNum = isset($_REQUEST['pageNum'])? (int) sanitize_text_field(wp_unslash($_REQUEST['pageNum'])) : 1;

			$ordersList = $apiClient->listOrders([
				'page'     => $pageNum,
				'per_page' => $GLOBALS['resultPerPage']
			]);
			//echo "<pre>"; print_r($ordersList); die;
			if(isset($ordersList) && !empty($ordersList) && count($ordersList) > 0){
				$current_page = $ordersList['current_page'];
				$balance_sum = $ordersList['balance_sum'];
				$total_sum = $ordersList['total_sum'];
				$etotal_entries = $ordersList['total_entries'];
				$eper_page = $ordersList['per_page'];
				$orders = $ordersList['orders'];

				if($etotal_entries > 0){
					$html .= '<div class="row">';
					$html .= '<div class="col-12 text-center p-1"><h1><b>Orders List</b></h1></div>';
					$html .= '</div>';
					$html .= '<h3 class="darkpink" style="line-height: 1.3em; text-align:center;">
					<span class="black">'.$etotal_entries.'</span> ORDERS ';
					if($etotal_entries > $eper_page){
						$html .= '- SHOWING <span class="black">'.$eper_page.'</span> - PAGE <span class="black">'.$current_page.'</span>';
					}
					$html .= '</h3>';
					
					$html .= '<div class="table-responsive-sm">';
					$html .= '<table class="table">';
					$html .= '<thead class="table-dark">';
					$html .= '<tr>';
					$html .= '<th scope="col" style="font-size: 18px;">Order ID</th>';
					$html .= '<th scope="col" style="font-size: 18px;">Buyer</th>';
					$html .= '<th scope="col" style="font-size: 18px;">Email/Phone</th>';
					$html .= '<th scope="col" style="font-size: 18px;">Amount</th>';
					$html .= '<th scope="col" style="font-size: 18px;">Type</th>';
					$html .= '<th scope="col" style="font-size: 18px;">State</th>';
					$html .= '<th scope="col" style="font-size: 18px;">Date</th>';
					$html .= '<th scope="col" style="text-align: left; font-size: 18px; padding-left: 40px;">Action</th>';
					$html .= '</tr>';
					$html .= '</thead>';
					$html .= '<tbody>';
//echo '<pre>'; print_r($orders); die;
					foreach($orders as $eKey => $eValue){
						$id = $eValue['id'];
						$oid = $eValue['oid'];
						$created_at = $eValue['created_at'];
						$balance = $eValue['balance'];
						$fee = $eValue['fee'];
						$tax = $eValue['tax'];
						$total = $eValue['total'];
						$shipments_type = $eValue['shipments'][0]['type'];
						$shipments_state = $eValue['shipments'][0]['state'];
						$buyer_type = $eValue['buyer']['type'];
						$buyer_name = $eValue['buyer']['name'];
						$email_address = $eValue['buyer']['email_addresses'][0]['address'];
						$phone_numbers = $eValue['buyer']['phone_numbers'][0]['number'];
						
						//if($buyer_type == "Client"){
							$html .= '<tr>';
							$html .= '<th scope="row">'.$oid.'</th>';
							$html .= '<td>'.$buyer_name.'</td>';
							$html .= '<td>'.$email_address.' / '.$phone_numbers.'</td>';
							$html .= '<td>$'.$total.'</td>';
							$html .= '<td>'.$shipments_type.'</td>';
							$html .= '<td>'.$shipments_state.'</td>';
							$html .= '<td>'.gmdate("d M, Y", strtotime($created_at)).'</td>';
							$html .= '<td align="center"><a href="'.$GLOBALS['website_url'].'/wp-json/order/v1/download-file/?order_id='.$id.'" target="_blank" type="button" class="btn btn-secondary btn-sm">Download <i class="fa-solid fa-arrow-up-right-from-square"></i></a></td>';
							$html .= '</tr>';
						//}
					}
					$html .= '</tbody>';
					$html .= '</table>';
					$html .= '</div>';
					$html .= "<br>";
					$html .= display_paginations($etotal_entries, $current_page, $GLOBALS['resultPerPage'], '');
				} else {
					$html .= '<h3 class="darkpink" style="line-height: 1.3em; text-align: center;">NO RECORD FOUNDS!</h3>';
				}
			} else {
				$html .= '<h3 class="darkpink" style="line-height: 1.3em; text-align: center;">NO RECORD FOUNDS!</h3>';
			}
			$html .= '</div>';
			$html .= '</div>';
			//$html .= '</table>';
		} else {
			$html .= "<br><br><h1><b>Verify Your License Key First! <a href='admin.php?page=live-events-verify'>Click Here</a> to Verify!</b></h1>";
		}
    } catch (\Exception $e) {
        echo 'Message: ' . esc_html( $e->getMessage() );
    }
    
    echo $html; 
}