<?php
/**
* Plugin Name: Live Events Ticketing
* Plugin URI: https://demo.skpsoft.com/
* Description: Launch Your own Live Events Ticketing Selling Platform
* Requires at least: 6.3
* Requires PHP: 7.4
* Version: 1.0
* Author: Waqar Javed
* Author URI: https://waqarmart.social/waqarjaved
* License: GPLv2 or later
* License URI: https://www.gnu.org/licenses/old-licenses/gpl-2.0.html
* Text Domain: live-events-ticketing
* 
* @package plugin-check
**/
 
defined('ABSPATH')  || die('Unauthorized Access');


require(dirname(__FILE__)."/vendor/autoload.php");
require(dirname(__FILE__)."/ajaxcall.php");
require(dirname(__FILE__)."/admin-license.php");
require(dirname(__FILE__)."/admin-panel-page-plugin.php");
require(dirname(__FILE__)."/customer-orders.php");
require(dirname(__FILE__)."/admin-orders.php");

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
	$squareJSFile = 'https://web.squarecdn.com/v1/square.js';
	//$squareJSFile = 'https://'.$GLOBALS['plugin_dir_url'] . '/js/square.js';
	$squareupAppId = esc_attr(get_option('LiveSquareupAppId'));
	$squareupLocationId = esc_attr(get_option('LiveSquareupLocationId'));
	$squareAccessToken = esc_attr(get_option('LiveSquareAccessToken'));
} else {
	$squareEnvironment = 'sandbox';
	$squareJSFile = 'https://sandbox.web.squarecdn.com/v1/square.js';
	//$squareJSFile = 'https://'.$GLOBALS['plugin_dir_url'] . '/js/sandbox-square.js';
	$squareupAppId = esc_attr(get_option('SandboxSquareupAppId'));
	$squareupLocationId = esc_attr(get_option('SandboxSquareupLocationId'));
	$squareAccessToken = esc_attr(get_option('SandboxSquareAccessToken'));
}

$resultPerPage = (int)esc_attr(get_option('SitePesultsPerPage'));
$website_url = isset($_SERVER['SERVER_NAME'])? "https://".sanitize_text_field(wp_unslash($_SERVER['SERVER_NAME'])) : "";
$plugin_dir_url = $website_url."/wp-content/plugins/live-events-ticketing";

function ticketevolution_enqueue_assets_scripts() {
    //wp_enqueue_style('bootstrap-css', $GLOBALS['plugin_dir_url'] .'/css/bootstrap.min.css' );
    //wp_enqueue_style('bootstrap-css', 'https://cdn.jsdelivr.net/npm/bootstrap@4.1.3/dist/css/bootstrap.min.css', array(), '4.1.3', false);
    //wp_deregister_script('jquery'); // Deregister any existing version
    //wp_register_script('jquery', $GLOBALS['plugin_dir_url'] .'/js/jquery-3.3.1.slim.min.js', array(), false, true);
    //wp_register_script('jquery', 'https://code.jquery.com/jquery-3.3.1.slim.min.js', array(), '3.3.1', true);
    //wp_enqueue_script('jquery');
    //wp_enqueue_script('popper.min.js', $GLOBALS['plugin_dir_url'] .'/js/popper.min.js', array(), '2.11.8', true);
    //wp_enqueue_script('popper-js', 'https://cdn.jsdelivr.net/npm/popper.js@1.14.3/dist/umd/popper.min.js', array(), '1.14.3', true);
    //wp_enqueue_script('bootstrap-js', $GLOBALS['plugin_dir_url'] .'/js/bootstrap.min.js', array(), '4.1.3', true);
    //wp_enqueue_script('bootstrap-js', 'https://cdn.jsdelivr.net/npm/bootstrap@4.1.3/dist/js/bootstrap.min.js', array('jquery', 'popper-js'), '4.1.3', true);
    wp_enqueue_script('tevomaps.js', $GLOBALS['plugin_dir_url'] . '/seatmaps-client/build/tevomaps.js', array(), '1.0.0', false);
}
add_action('wp_enqueue_scripts', 'ticketevolution_enqueue_assets_scripts', 50);

// Action when initialize on front site.
add_shortcode('ticketevolution_categories_list', 'callback_ticketevolution_categories_lists');
function callback_ticketevolution_categories_lists( $atts ) {
    
    if ( is_admin() ) {
		return '<p>This is where the shortcode [ticketevolution_categories_list] will show up.</p>';
	}

    $defaults = [
      'title'  => 'Events Listing Page'
    ];
      
    $atts = shortcode_atts(
        $defaults,
        $atts,
        'ticketevolution_categories_list'
    );
    $html = ticketevolution_enqueue_assets_scripts();
    /*$html = wp_enqueue_style( 'bootstrap.min.css', $GLOBALS['plugin_dir_url'] . '/css/bootstrap.min.css' );
    $html .= wp_enqueue_script('jquery-3.3.1.slim.min.js', $GLOBALS['plugin_dir_url'] . '/js/jquery-3.3.1.slim.min.js', array(), null, true);
    $html .= wp_enqueue_script('popper.min.js', $GLOBALS['plugin_dir_url'] . '/js/popper.min.js', array(), null, true);
    $html .= wp_enqueue_script('bootstrap.min.js', $GLOBALS['plugin_dir_url'] . '/js/bootstrap.min.js', array(), null, true);*/
    
    $html .= "<style>
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
            </style>";
	
    $apiClient = new TEvoClient([
        'baseUrl'     => $GLOBALS['baseUrl'],
        'apiVersion'  => $GLOBALS['apiVersion'],
        'apiToken'    => $GLOBALS['apiToken'],
        'apiSecret'   => $GLOBALS['apiSecret'],
    ]);
    
    try {
        if(isset($_REQUEST['_wpnonce'])){
            $nonce = isset($_REQUEST['_wpnonce'])? sanitize_text_field(wp_unslash($_REQUEST['_wpnonce'])) : '';
            if (!wp_verify_nonce($nonce, 'events_list')) {
                wp_die('Invalid nonce verification.');
            }
        }
        $pageNum = isset($_REQUEST['pageNum'])? (int) sanitize_text_field(wp_unslash($_REQUEST['pageNum'])) : 1;
		$pageTitle = isset($_REQUEST['pageTitle'])? sanitize_text_field(wp_unslash($_REQUEST['pageTitle'])) : '';
		$pageCategory = isset($_REQUEST['category'])? sanitize_text_field(wp_unslash($_REQUEST['category'])) : '';
		$pid = isset($_REQUEST['pid'])? sanitize_text_field(wp_unslash($_REQUEST['pid'])) : '';
		
		
		$parentArray = array();
		$eventsSearch = $apiClient->listCategories();
		
		if(isset($eventsSearch) && !empty($eventsSearch) && count($eventsSearch) > 0){
			$etotal_entries = $eventsSearch['total_entries'];
			$eper_page = $eventsSearch['per_page'];
			$categories = $eventsSearch['categories'];

            if($etotal_entries > 0){
				$html .= '<h3 class="darkpink" style="line-height: 1.3em; text-align:center;">
				<span class="black">'.$etotal_entries.'</span> CATEGORIES ';
				if($etotal_entries > $eper_page){
					$html .= '- SHOWING <span class="black">'.$eper_page.'</span> - PAGE <span class="black">'.$ecurrent_page.'</span>';
				}
				$html .= '</h3>';
				
				foreach($eventsSearch['categories'] as $eKey => $eValue){
					$parentArray['pname'][$eValue['parent']['id']] = $eValue['parent']['name'];
				}
				
				$html .= '<div class="container">';
				$html .= '<div class="row">';
				
				$html .= '<div class="col-12 text-left p-2 parent_categories">';
				$html .= '<ul>';
				$html .= '<li><a href="'.$GLOBALS['website_url'].'/categories">All Categories</a></li>';
				foreach($parentArray['pname'] as $eKey => $eValue){
					if(!empty($eValue)){
						$html .= '<li><a href="'.$GLOBALS['website_url'].'/categories?pid='.$eKey.'&category='.$eValue.'">'.$eValue.'</a></li>';
					}
				}
				$html .= '</ul>';
				$html .= '</div>';
				
				$html .= '<div class="col-4 text-left p-1" style="background: #ef457a !important;"><h4>Parent</h4></div><div class="col-8 text-left p-1" style="background: #ef457a !important;"><h4>Category</h4></div><div class="w-100"></div>';
				
				foreach($categories as $eKey => $eValue){
					$id = $eValue['id'];
					$name = $eValue['name'];
					$pid = $eValue['parent']['id'];
					$pname = (!empty($eValue['parent']['name']))?$eValue['parent']['name']:$pageCategory;
					$ppid = $eValue['parent']['parent']['id'];
					$ppname = $eValue['parent']['parent']['name'];
					
					$html .= '<div class="col-4 text-left p-2 row_border"><div class="event_date pink">'.$pname.'</div></div>';
					$html .= '<div class="col-6 text-left p-2 row_border"><div class="event-heading black">'.$name.'</div></div>';
					$html .= '<div class="col-2 text-left p-2 row_border"><a href="'.$GLOBALS['website_url'].'/category-events?categoryId='.$id.'&pageTitle='.$name.'" target="_blank" class="btn btn-sm mt-1 ticket-button"><span class="d-none d-md-block">Find Events</span> <span class="d-block d-md-none"><i class="fa fa-external-link"></i></span></a></div><div class="w-100"></div>';
				}
			} else {
				$html .= '<h3 class="darkpink" style="line-height: 1.3em; text-align: center;">NO RECORD FOUNDS!</h3>';
			}
        } else {
            $html .= '<h3 class="darkpink" style="line-height: 1.3em; text-align: center;">NO RECORD FOUNDS!</h3>';
        }
        $html .= '</div>';
        $html .= '</div>';
    	//$html .= '</table>';
    } catch (\Exception $e) {
        echo 'Message: ' . esc_html( $e->getMessage() );
    }
    
    return $html ; 
}


// Action when initialize on front site.
add_shortcode('ticketevolution_category_event_lists', 'callback_ticketevolution_category_event_lists');
function callback_ticketevolution_category_event_lists( $atts ) {
    
    if ( is_admin() ) {
		return '<p>This is where the shortcode [ticketevolution_category_event_lists] will show up.</p>';
	}

    $defaults = [
      'title'  => 'Events Listing Page'
    ];
      
    $atts = shortcode_atts(
        $defaults,
        $atts,
        'ticketevolution_category_event_lists'
    );
    
    $html = ticketevolution_enqueue_assets_scripts();
    /*$html = wp_enqueue_style( 'bootstrap.min.css', $GLOBALS['plugin_dir_url'] . '/css/bootstrap.min.css' );
    $html .= wp_enqueue_script('jquery-3.3.1.slim.min.js', $GLOBALS['plugin_dir_url'] . '/js/jquery-3.3.1.slim.min.js', array(), null, true);
    $html .= wp_enqueue_script('popper.min.js', $GLOBALS['plugin_dir_url'] . '/js/popper.min.js', array(), null, true);
    $html .= wp_enqueue_script('bootstrap.min.js', $GLOBALS['plugin_dir_url'] . '/js/bootstrap.min.js', array(), null, true);*/
    
    $html .= "<style>
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
            </style>";
	
    $apiClient = new TEvoClient([
        'baseUrl'     => $GLOBALS['baseUrl'],
        'apiVersion'  => $GLOBALS['apiVersion'],
        'apiToken'    => $GLOBALS['apiToken'],
        'apiSecret'   => $GLOBALS['apiSecret'],
    ]);
    
    try {
        if(isset($_REQUEST['_wpnonce'])){
            $nonce = isset($_REQUEST['_wpnonce'])? sanitize_text_field(wp_unslash($_REQUEST['_wpnonce'])) : '';
            if (!wp_verify_nonce($nonce, 'events_list')) {
                wp_die('Invalid nonce verification.');
            }
        }
        
		$pageNum = isset($_REQUEST['pageNum'])? (int) sanitize_text_field(wp_unslash($_REQUEST['pageNum'])) : 1;
		$categoryId = isset($_REQUEST['categoryId'])? sanitize_text_field(wp_unslash($_REQUEST['categoryId'])) : '';
        
		$eventsSearch = $apiClient->listEvents([
            'category_id' => $categoryId,
			'page'     => $pageNum,
            'per_page' => $GLOBALS['resultPerPage']
        ]);
		
		if(isset($eventsSearch) && !empty($eventsSearch) && count($eventsSearch) > 0){
			$etotal_entries = $eventsSearch['total_entries'];
			$eper_page = $eventsSearch['per_page'];
			$ecurrent_page = $eventsSearch['current_page'];
			$events = $eventsSearch['events'];

            if($etotal_entries > 0){
				$html .= '<h3 class="darkpink" style="line-height: 1.3em; text-align:center;">
				<span class="black">'.$etotal_entries.'</span> UPCOMING EVENTS ';
				if($etotal_entries > $eper_page){
					$html .= '- SHOWING <span class="black">'.$eper_page.'</span> - PAGE <span class="black">'.$ecurrent_page.'</span>';
				}
				$html .= '</h3>';
				$html .= '<div class="container">';
				$html .= '<div class="row">';
				$html .= '<div class="col-4 text-left p-1" style="background: #ef457a !important;"><h4>Dates</h4></div><div class="col-8 text-left p-1" style="background: #ef457a !important;"><h4>Events</h4></div><div class="w-100"></div>';
				//echo "<pre>"; print_r($events); die;
				foreach($events as $eKey => $eValue){
					$eventid = $eValue['id'];
					$ename = $eValue['name'];
					$eoccurs_at = strtotime($eValue['occurs_at']);
					$configurationid = $eValue['configuration']['id'];
					$evenueid = $eValue['venue']['id'];
					$evenuename = $eValue['venue']['name'];
					$evenuelocation = $eValue['venue']['location'];
					$event_date = gmdate('d M, y', $eoccurs_at);
					$event_time = gmdate('D • h:ia', $eoccurs_at);

					$html .= '<div class="col-4 text-left p-2 row_border"><div class="event_date pink">'.$event_date.'</div> <div class="event_time black">'.$event_time.'</div></div>';
					$html .= '<div class="col-6 text-left p-2 row_border"><div class="event-heading black">'.$ename.'</div><div><div class="event-subheading black">'.$evenuename.' • '.$evenuelocation.'</div></div></div>';
					$html .= '<div class="col-2 text-left p-2 row_border"><a href="'.$GLOBALS['website_url'].'/ticket-listing?eventId='.$eventid.'&venueId='.$evenueid.'&configurationId='.$configurationid.'&pageTitle='.$ename.'" target="_blank" class="btn btn-sm mt-1 ticket-button"><span class="d-none d-md-block">Find Tickets</span> <span class="d-block d-md-none"><i class="fa fa-external-link"></i></span></a></div><div class="w-100"></div>';
				}
				$html .= display_paginations($etotal_entries, $ecurrent_page, $GLOBALS['resultPerPage'], '&categoryId='.$categoryId);
			} else {
				$html .= '<h3 class="darkpink" style="line-height: 1.3em; text-align: center;">NO EVENT FOUNDS!</h3>';
			}
        } else {
            $html .= '<h3 class="darkpink" style="line-height: 1.3em; text-align: center;">NO RECORD FOUNDS!</h3>';
        }
        $html .= '</div>';
        $html .= '</div>';
    	//$html .= '</table>';
    } catch (\Exception $e) {
        echo 'Message: ' .esc_html( $e->getMessage() );
    }
    
    return $html ; 
}


// Action when initialize on front site.
add_shortcode('ticketevolution_events_search_list', 'callback_ticketevolution_events_search_lists');
function callback_ticketevolution_events_search_lists( $atts ) {
    
    if ( is_admin() ) {
		return '<p>This is where the shortcode [ticketevolution_events_search_list] will show up.</p>';
	}

    $defaults = [
      'title'  => 'Events Listing Page'
    ];
      
    $atts = shortcode_atts(
        $defaults,
        $atts,
        'ticketevolution_events_search_list'
    );
    
    $html = ticketevolution_enqueue_assets_scripts();
    /*$html = wp_enqueue_style( 'bootstrap.min.css', $GLOBALS['plugin_dir_url'] . '/css/bootstrap.min.css' );
    $html .= wp_enqueue_script('jquery-3.3.1.slim.min.js', $GLOBALS['plugin_dir_url'] . '/js/jquery-3.3.1.slim.min.js', array(), null, true);
    $html .= wp_enqueue_script('popper.min.js', $GLOBALS['plugin_dir_url'] . '/js/popper.min.js', array(), null, true);
    $html .= wp_enqueue_script('bootstrap.min.js', $GLOBALS['plugin_dir_url'] . '/js/bootstrap.min.js', array(), null, true);*/
    
    $html .= "<style>
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
            </style>";
	
    $apiClient = new TEvoClient([
        'baseUrl'     => $GLOBALS['baseUrl'],
        'apiVersion'  => $GLOBALS['apiVersion'],
        'apiToken'    => $GLOBALS['apiToken'],
        'apiSecret'   => $GLOBALS['apiSecret'],
    ]);
    
    try {
        if(isset($_REQUEST['_wpnonce'])){
            $nonce = isset($_REQUEST['_wpnonce'])? sanitize_text_field(wp_unslash($_REQUEST['_wpnonce'])) : '';
            if (!wp_verify_nonce($nonce, 'events_list')) {
                wp_die('Invalid nonce verification.');
            }
        }
        $pageNum = isset($_REQUEST['pageNum'])? (int) sanitize_text_field(wp_unslash($_REQUEST['pageNum'])) : 1;
		$pageTitle = isset($_REQUEST['pageTitle'])? sanitize_text_field(wp_unslash($_REQUEST['pageTitle'])) : '';
		
		
		$eventsSearch = $apiClient->searchEvents([
            'q' => $pageTitle,
			'page'     => $pageNum,
            'per_page' => $GLOBALS['resultPerPage']
        ]);
		
		$etotal_entries = $eventsSearch['total_entries'];
		$eper_page = $eventsSearch['per_page'];
		$ecurrent_page = $eventsSearch['current_page'];
		$events = $eventsSearch['events'];
        
        if(isset($eventsSearch) && !empty($eventsSearch) && count($eventsSearch) > 0){
            if($etotal_entries > 0){
				$html .= '<h3 class="darkpink" style="line-height: 1.3em; text-align:center;">
				<span class="black">'.$etotal_entries.'</span> UPCOMING EVENTS ';
				if($etotal_entries > $eper_page){
					$html .= '- SHOWING <span class="black">'.$eper_page.'</span> - PAGE <span class="black">'.$ecurrent_page.'</span>';
				}
				$html .= '</h3>';
				$html .= '<div class="container">';
				$html .= '<div class="row">';
				$html .= '<div class="col-4 text-left p-1" style="background: #ef457a !important;"><h4>Dates</h4></div><div class="col-8 text-left p-1" style="background: #ef457a !important;"><h4>Events</h4></div><div class="w-100"></div>';
				//echo "<pre>"; print_r($events); die;
				foreach($events as $eKey => $eValue){
					$eventid = $eValue['id'];
					$ename = $eValue['name'];
					$eoccurs_at = strtotime($eValue['occurs_at']);
					$configurationid = $eValue['configuration']['id'];
					$evenueid = $eValue['venue']['id'];
					$evenuename = $eValue['venue']['name'];
					$evenuelocation = $eValue['venue']['location'];
					$event_date = gmdate('d M, y', $eoccurs_at);
					$event_time = gmdate('D • h:ia', $eoccurs_at);

					$html .= '<div class="col-4 text-left p-2 row_border"><div class="event_date pink">'.$event_date.'</div> <div class="event_time black">'.$event_time.'</div></div>';
					$html .= '<div class="col-6 text-left p-2 row_border"><div class="event-heading black">'.$ename.'</div><div><div class="event-subheading black">'.$evenuename.' • '.$evenuelocation.'</div></div></div>';
					$html .= '<div class="col-2 text-left p-2 row_border"><a href="'.$GLOBALS['website_url'].'/ticket-listing?eventId='.$eventid.'&venueId='.$evenueid.'&configurationId='.$configurationid.'&pageTitle='.$ename.'" target="_blank" class="btn btn-sm mt-1 ticket-button"><span class="d-none d-md-block">Find Tickets</span> <span class="d-block d-md-none"><i class="fa fa-external-link"></i></span></a></div><div class="w-100"></div>';
				}
				$html .= display_paginations($etotal_entries, $ecurrent_page, $GLOBALS['resultPerPage']);
			} else {
				$html .= '<h3 class="darkpink" style="line-height: 1.3em; text-align: center;">NO EVENT FOUNDS!</h3>';
			}
        } else {
            $html .= '<h3 class="darkpink" style="line-height: 1.3em; text-align: center;">NO RECORD FOUNDS!</h3>';
        }
        $html .= '</div>';
        $html .= '</div>';
    	//$html .= '</table>';
    } catch (\Exception $e) {
        echo 'Message: ' .esc_html( $e->getMessage() );
    }
    
    return $html ; 
}


// Action when initialize on front site.
add_shortcode('ticketevolution_venues_list', 'callback_ticketevolution_venues_list');
function callback_ticketevolution_venues_list( $atts ) {
    
    if ( is_admin() ) {
		return '<p>This is where the shortcode [ticketevolution_venues_list] will show up.</p>';
	}

    $defaults = [
      'title'  => 'Events Listing Page'
    ];
      
    $atts = shortcode_atts(
        $defaults,
        $atts,
        'ticketevolution_venues_list'
    );
    
    $html = ticketevolution_enqueue_assets_scripts();
    /*$html = wp_enqueue_style( 'bootstrap.min.css', $GLOBALS['plugin_dir_url'] . '/css/bootstrap.min.css' );
    $html .= wp_enqueue_script('jquery-3.3.1.slim.min.js', $GLOBALS['plugin_dir_url'] . '/js/jquery-3.3.1.slim.min.js', array(), null, true);
    $html .= wp_enqueue_script('popper.min.js', $GLOBALS['plugin_dir_url'] . '/js/popper.min.js', array(), null, true);
    $html .= wp_enqueue_script('bootstrap.min.js', $GLOBALS['plugin_dir_url'] . '/js/bootstrap.min.js', array(), null, true);*/
    
    $html .= "<style>
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
            </style>";
	
    $apiClient = new TEvoClient([
        'baseUrl'     => $GLOBALS['baseUrl'],
        'apiVersion'  => $GLOBALS['apiVersion'],
        'apiToken'    => $GLOBALS['apiToken'],
        'apiSecret'   => $GLOBALS['apiSecret'],
    ]);
    
    try {
        $venueSearch = $apiClient->listVenues([
            'name' => $atts['title']
        ]);
		$venues = $venueSearch['venues'];
        
        if(isset($_REQUEST['_wpnonce'])){
            $nonce = isset($_REQUEST['_wpnonce'])? sanitize_text_field(wp_unslash($_REQUEST['_wpnonce'])) : '';
            if (!wp_verify_nonce($nonce, 'events_list')) {
                wp_die('Invalid nonce verification.');
            }
        }
        $pageNum = isset($_REQUEST['pageNum'])? (int) sanitize_text_field(wp_unslash($_REQUEST['pageNum'])) : 1;
        
        if(isset($venues) && !empty($venues) && count($venues) > 0){
            foreach($venues as $key => $value){
                $venue_id = $value['id'];
                $eventLists = $apiClient->listEvents([
                    'venue_id' => $venue_id,
                    'page'     => $pageNum,
                    'per_page' => $GLOBALS['resultPerPage']
                ]);
                $etotal_entries = $eventLists['total_entries'];
                $eper_page = $eventLists['per_page'];
                $ecurrent_page = $eventLists['current_page'];
                $events = $eventLists['events'];
                
                if($etotal_entries > 0){
                    $html .= '<h3 class="darkpink" style="line-height: 1.3em; text-align:center;"><span class="black">'.$etotal_entries.'</span> UPCOMING EVENTS - SHOWING <span class="black">'.$eper_page.'</span> - PAGE <span class="black">'.$ecurrent_page.'</span></h3>';
                    $html .= '<div class="container">';
                    $html .= '<div class="row">';
                    $html .= '<div class="col-4 text-left p-1" style="background: #ef457a !important;"><h4>Dates</h4></div><div class="col-8 text-left p-1" style="background: #ef457a !important;"><h4>Events</h4></div><div class="w-100"></div>';
                    //echo "<pre>"; print_r($events); die;
                    foreach($events as $eKey => $eValue){
                        $eventid = $eValue['id'];
                        $ename = $eValue['name'];
                        $eoccurs_at = strtotime($eValue['occurs_at']);
                        $configurationid = $eValue['configuration']['id'];
                        $evenueid = $eValue['venue']['id'];
                        $evenuename = $eValue['venue']['name'];
                        $evenuelocation = $eValue['venue']['location'];
                        $event_date = gmdate('d M, y', $eoccurs_at);
                        $event_time = gmdate('D • h:ia', $eoccurs_at);
                        
                        $html .= '<div class="col-4 text-left p-2 row_border"><div class="event_date pink">'.$event_date.'</div> <div class="event_time black">'.$event_time.'</div></div>';
                        $html .= '<div class="col-6 text-left p-2 row_border"><div class="event-heading black">'.$ename.'</div><div><div class="event-subheading black">'.$evenuename.' • '.$evenuelocation.'</div></div></div>';
                        $html .= '<div class="col-2 text-left p-2 row_border"><a href="'.$GLOBALS['website_url'].'/ticket-listing?eventId='.$eventid.'&venueId='.$evenueid.'&configurationId='.$configurationid.'&pageTitle='.$ename.'" target="_blank" class="btn btn-sm mt-1 ticket-button"><span class="d-none d-md-block">Find Tickets</span> <span class="d-block d-md-none"><i class="fa fa-external-link"></i></span></a></div><div class="w-100"></div>';
                    }
                    $html .= display_paginations($etotal_entries, $ecurrent_page, $GLOBALS['resultPerPage']);
                } else {
                    $html .= '<h3 class="darkpink" style="line-height: 1.3em; text-align: center;">NO EVENT FOUNDS!</h3>';
                }
            }
        } else {
            $html .= '<h3 class="darkpink" style="line-height: 1.3em; text-align: center;">NO RECORD FOUNDS!</h3>';
        }
        $html .= '</div>';
        $html .= '</div>';
    	//$html .= '</table>';
    } catch (\Exception $e) {
        echo 'Message: ' .esc_html( $e->getMessage() );
    }
    
    return $html ; 
}


// Action when initialize on front site.
add_shortcode('ticketevolution_performers_list', 'callback_ticketevolution_performers_list');
function callback_ticketevolution_performers_list( $atts ) {
    
    if ( is_admin() ) {
		return '<p>This is where the shortcode [ticketevolution_performers_list] will show up.</p>';
	}

    $defaults = [
      'title'  => 'Events Listing Page'
    ];
      
    $atts = shortcode_atts(
        $defaults,
        $atts,
        'ticketevolution_performers_list'
    );
    
    $html = ticketevolution_enqueue_assets_scripts();
    /*$html = wp_enqueue_style( 'bootstrap.min.css', $GLOBALS['plugin_dir_url'] . '/css/bootstrap.min.css' );
    $html .= wp_enqueue_script('popper.min.js', $GLOBALS['plugin_dir_url'] . '/js/popper.min.js', array(), null, true);
    $html .= wp_enqueue_script('bootstrap.min.js', $GLOBALS['plugin_dir_url'] . '/js/bootstrap.min.js', array(), null, true);*/
    
    $html .= "<style>
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
            </style>";
	
    $apiClient = new TEvoClient([
        'baseUrl'     => $GLOBALS['baseUrl'],
        'apiVersion'  => $GLOBALS['apiVersion'],
        'apiToken'    => $GLOBALS['apiToken'],
        'apiSecret'   => $GLOBALS['apiSecret'],
    ]);
    
    try {
        $performerSearch = $apiClient->listPerformers([
            'name' => $atts['title']
        ]);
		$performers = $performerSearch['performers'];
		//echo "<pre>"; print_r($performers); die;
		if(isset($_REQUEST['_wpnonce'])){
            $nonce = isset($_REQUEST['_wpnonce'])? sanitize_text_field(wp_unslash($_REQUEST['_wpnonce'])) : '';
            if (!wp_verify_nonce($nonce, 'events_list')) {
                wp_die('Invalid nonce verification.');
            }
        }
        $pageNum = isset($_REQUEST['pageNum'])? (int) sanitize_text_field(wp_unslash($_REQUEST['pageNum'])) : 1;
        
        if(isset($performers) && !empty($performers) && count($performers) > 0){
            foreach($performers as $key => $value){
                $performer_id = $value['id'];
                $eventLists = $apiClient->listEvents([
                    'performer_id' => $performer_id,
                    'page'     => $pageNum,
                    'per_page' => $GLOBALS['resultPerPage']
                ]);
                $etotal_entries = $eventLists['total_entries'];
                $eper_page = $eventLists['per_page'];
                $ecurrent_page = $eventLists['current_page'];
                $events = $eventLists['events'];
                //echo "<pre>"; print_r($events);
                
                if($etotal_entries > 0){
                    $html .= '<h3 class="darkpink" style="line-height: 1.3em; text-align: center;"><span class="black">'.$etotal_entries.'</span> '.$atts['title'].' Schedule - Showing <span class="black">'.$eper_page.'</span> - Page <span class="black">'.$ecurrent_page.'</span></h3>';
                    $html .= '<div class="container">';
                    $html .= '<div class="row">';
                    $html .= '<div class="col-4 text-left p-1" style="background: #ef457a !important;"><h4>Dates</h4></div><div class="col-8 text-left p-1" style="background: #ef457a !important;"><h4>Events</h4></div><div class="w-100"></div>';
                    foreach($events as $eKey => $eValue){
                        $eventid = $eValue['id'];
                        $ename = $eValue['name'];
                        $eoccurs_at = strtotime($eValue['occurs_at']);
                        $configurationid = $eValue['configuration']['id'];
                        $evenueid = $eValue['venue']['id'];
                        $evenuename = $eValue['venue']['name'];
                        $evenuelocation = $eValue['venue']['location'];
                        $event_date = gmdate('d M, y', $eoccurs_at);
                        $event_time = gmdate('D • h:ia', $eoccurs_at);
                        //if($atts['title'] == "Formula 1" && str_contains($ename, "Formula 1 Miami Grand Prix")){
                            $html .= '<div class="col-4 text-left p-2 row_border"><div class="event_date pink">'.$event_date.'</div> <div class="event_time black">'.$event_time.'</div></div>';
                            $html .= '<div class="col-6 text-left p-2 row_border"><div class="event-heading black">'.$ename.'</div><div><div class="event-subheading black">'.$evenuename.' • '.$evenuelocation.'</div></div></div>';
                            $html .= '<div class="col-2 text-left p-2 row_border"><a href="'.$GLOBALS['website_url'].'/ticket-listing?eventId='.$eventid.'&venueId='.$evenueid.'&configurationId='.$configurationid.'&pageTitle='.$ename.'" target="_blank" class="btn btn-sm mt-1 ticket-button"><span class="d-none d-md-block">Find Tickets</span> <span class="d-block d-md-none"><i class="fa fa-external-link"></i></span></a></div><div class="w-100"></div>';
                        //}
                    }
                    $html .= display_paginations($etotal_entries, $ecurrent_page, $GLOBALS['resultPerPage']);
                } else {
                    $html .= '<h3 class="darkpink" style="line-height: 1.3em; text-align: center;">NO EVENT FOUNDS!</h3>';
                }
            }
        } else {
            $html .= '<h3 class="darkpink" style="line-height: 1.3em; text-align: center;">NO RECORD FOUNDS!</h3>';
        }
        $html .= '</div>';
        $html .= '</div>';
    	//$html .= '</table>';
    } catch (\Exception $e) {
        echo 'Message: ' .esc_html( $e->getMessage() );
    }
    
    return $html ; 
}

// Action when initialize on front site.
add_shortcode('ticketevolution_ticket_listing', 'callback_ticketevolution_ticket_listing');
function callback_ticketevolution_ticket_listing( $atts ) {
    
    if ( is_admin() ) {
		return '<p>This is where the shortcode [ticketevolution_ticket_listing] will show up.</p>';
	}

    $defaults = [
      'title'  => 'Live Events Ticketing List Page'
    ];
      
    $atts = shortcode_atts(
        $defaults,
        $atts,
        'ticketevolution_ticket_listing'
    );
    
    $html = ticketevolution_enqueue_assets_scripts();
    //$html = wp_enqueue_style('bootstrap-css', plugins_url('css/bootstrap.min.css', __FILE__)); //wp_enqueue_style( 'bootstrap.min.css', $GLOBALS['plugin_dir_url'] . '/css/bootstrap.min.css' );
    //$html .= wp_enqueue_script('jquery'); //wp_enqueue_script('jquery-3.3.1.slim.min.js', $GLOBALS['plugin_dir_url'] . '/js/jquery-3.3.1.slim.min.js', array(), null, true);
    //$html .= '<script src="'.$GLOBALS['plugin_dir_url'].'/js/popper.min.js"></script>'; //wp_enqueue_script('popper.min.js', $GLOBALS['plugin_dir_url'] . '/js/popper.min.js', array(), null, true);
    //$html .= wp_enqueue_script('bootstrap-js', plugins_url('js/bootstrap.min.js', __FILE__), array('jquery'), '4.1.3', true);
    //$html .= wp_enqueue_script('tevomaps.js', $GLOBALS['plugin_dir_url'] . '/seatmaps-client/build/tevomaps.js', array('jquery'), microtime(), true);
    
    $html .= "<style>
            table { color: #000000; border: none; }
            .black {color: #000000 !important;}
            .darkgray {color: #04092c;}
            .darkpink {color: #ef457a !important;}
            .pink {color: #ce3197 !important;}
            .green {color: #086e88 !important;}
            .event-heading, .event_date { font-family: var(--e-global-typography-7a8c4c5-font-family), sans-serif; font-size: 16px !important; font-weight: bold; line-height: 25px !important; letter-spacing: 0.1px !important; }
            .event-subheading, .event_time { font-family: var(--e-global-typography-7a8c4c5-font-family), sans-serif; font-size: 14px !important; }
            .event-heading:hover { color: #000 !important; }
            .row_border {border-bottom: 1px solid #ef457a;}
            .ticket-button { font-family: var(--e-global-typography-7a8c4c5-font-family), sans-serif; font-size: 16px; background-color: #ef457a; padding: 2px 10px 5px !important; color: #ffffff !important; float: right; margin-right: 5px; cursor: pointer; border-radius: 5px; border: 1px solid #ef457a; }
            .ticket-button:hover { background-color: #ffffff; color: #000000 !important; }
            button.ticket-button { padding: 11px !important; }
            button.ticket-button span { color: #ffffff; }
            button.ticket-button span:hover, button.ticket-button span:active { background: #ffffff; color: #ef457a;}
            .elementor-1631 .elementor-section:nth-child(2) .elementor-container, .elementor-1631 .elementor-section:nth-child(2) .elementor-container section {max-width: 100%; important;}
            .myseatmaps {height: 1000px;  border: 5px solid #f1f1f1;}
            .myticketslist {height: 1000px;  border: 5px solid #f1f1f1; overflow-x: scroll;}
            .ticket_highlight {background: #A3CA3A; margin: 2px 0px; cursor:pointer;}
            
            @media only screen and (max-width: 850px) {
                .myseatmaps {height: 600px;}
            }
            @media only screen and (max-width: 600px) {
                .myseatmaps {height: 300px;}
            }
            </style>";
	
    $apiClient = new TEvoClient([
        'baseUrl'     => $GLOBALS['baseUrl'],
        'apiVersion'  => $GLOBALS['apiVersion'],
        'apiToken'    => $GLOBALS['apiToken'],
        'apiSecret'   => $GLOBALS['apiSecret'],
    ]);
    
    try {
        if(isset($_REQUEST['_wpnonce'])){
            $nonce = isset($_REQUEST['_wpnonce'])? sanitize_text_field(wp_unslash($_REQUEST['_wpnonce'])) : '';
            if (!wp_verify_nonce($nonce, 'events_list')) {
                wp_die('Invalid nonce verification.');
            }
        }
		$venueId = isset($_REQUEST['venueId'])? sanitize_text_field(wp_unslash($_REQUEST['venueId'])) : '';
		$configurationId = isset($_REQUEST['configurationId'])? sanitize_text_field(wp_unslash($_REQUEST['configurationId'])) : '';
		$eventId = isset($_REQUEST['eventId'])? (int) sanitize_text_field(wp_unslash($_REQUEST['eventId'])) : '';
		
        $eventDetail = $apiClient->showEvent([
            'event_id' => $eventId
        ]);
        $eventName = $eventDetail['name'];
        $eventDate = $eventDetail['occurs_at'];
        $eventChartM = $eventDetail['configuration']['seating_chart']['medium'];
        //echo '<pre>'; print_r($eventDetail);
        
        $venueDetail = $apiClient->showVenue([
            'venue_id' => $eventDetail['venue']['id']
        ]);
        $venueName = $venueDetail['name'];
        $venueAddress = $venueDetail['address']['street_address'];
        $venueLocation = $venueDetail['address']['location'];
        $venuePostal = $venueDetail['address']['postal_code'];
        $venueFullAddress = $venueAddress.', '.$venueLocation.', '.$venuePostal;
        
        $listingSearch = $apiClient->listings([
            'event_id' => $eventId
        ]);
        $total_entries = $listingSearch["total_entries"];
        $ticket_groups = $listingSearch["ticket_groups"];
        $ticketGroups = [];
        $tickets_qty = "";
        $html .= '<div class="container-fluid"><div class="row">';
        $html .= '<div class="myseatmaps col-lg-8" id="my-map"></div>';
        $html .= '<div class="myticketslist col-lg-4">';
        $html .= '<div class="container">';
        $html .= '<div class="row">';
        
        //echo '<pre>'; print_r($ticket_groups);
        foreach($ticket_groups as $key => $value){
            $id = $value['id'];
            $available_quantity = $value['available_quantity'];
            $eticket = $value['eticket'];
            $wholesale_price = $value['wholesale_price'];
            $public_notes = $value['public_notes'];
            $format = $value['format'];
            $quantity = $value['quantity'];
            $row = $value['row'];
            $type = $value['type'];
            $tickets_qty_list = $value['splits'];
            $ticketGroups[$key]['tevo_section_name'] = $tevo_section_name = $value['tevo_section_name'];
            $ticketGroups[$key]['retail_price'] = $retail_price = $value['retail_price'];
            if(isset($tickets_qty_list[0]) && $tickets_qty_list[0] == end($tickets_qty_list)){ $tickets_qty = $tickets_qty_list[0]; }
            if(isset($tickets_qty_list[0]) && $tickets_qty_list[0] != end($tickets_qty_list)){ $tickets_qty = $tickets_qty_list[0].' - '.end($tickets_qty_list); }
            
            $html .= "<div class='col-2 text-left p-2 row_border ticket_listings ".str_replace(' ', '-', $tevo_section_name)."' data-section-name='".$tevo_section_name."' data-object='".wp_json_encode($value)."'><div class='event_date pink'>$".$retail_price."</div> <div class='event_time black'>each</div></div>";
            $html .= "<div class='col-8 text-left p-2 row_border ticket_listings ".str_replace(' ', '-', $tevo_section_name)."' data-section-name='".$tevo_section_name."' data-object='".wp_json_encode($value)."'><div class='event-heading black'>".ucwords($tevo_section_name)." • Row ".$row."</div><div><div class='event-subheading black'>".ucwords($type).' - '.$tickets_qty." tickets</div></div></div>";
            $html .= "<div class='col-2 text-left p-2 row_border ticket_listings ".str_replace(' ', '-', $tevo_section_name)."' data-section-name='".$tevo_section_name."' data-object='".wp_json_encode($value)."'><a class='btn btn-sm mt-1 ticket-button'><span class='d-none d-md-block'>Book</span> <span class='d-block d-md-none'><i class='fa fa-forward'></i></span></a></div>";
            $html .= '<div class="w-100"></div>';
        }
        $html .= "<div class='col-12 ticket_detail' style='display:none;'>Loading...</div>";
        
        $html .= '</div>';
        $html .= '</div>';
        $html .= '</div></div></div>';
        
        if(!empty($venueId) && !empty($configurationId)){
        $ticketGroups = wp_json_encode($ticketGroups);
        //echo "<pre>"; print_r($ticket_groups);
        //$html .= '<script src="'.$GLOBALS['plugin_dir_url'].'/seatmaps-client/build/tevomaps.js"></script>';
        $html .= "<script>
                  // create a new seatmap
                  var seatmap = new Tevomaps({
                    venueId: '".$venueId."',
                    configurationId: '".$configurationId."',
                    ticketGroups: ".$ticketGroups.",
                    onSelection: function (sectionIds) {
                        $('.ticket_listings').hide();
                        $('.ticket_detail').hide();
                        sectionIds.forEach(function(item) {
                            str = item.replace(/\s+/g, '-');
                            $('.'+str).show();
                        });
                        if(sectionIds.length == 0){
                            $('.ticket_listings').show();
                        }
                    }
                  });
                
                  // turn element with ID of 'my-map' into a seatmap for config 1046
                  var seatmapApi = seatmap.build('my-map');
                  $('.ticket_listings').on({
                        mouseenter: function () {
                            attrclass = $(this).attr('data-section-name');
                            strclass = attrclass.replace(/\s+/g, '-');
                            $('.'+strclass).addClass('ticket_highlight');
                            seatmapApi.highlightSection(attrclass);
                        },
                        mouseleave: function () {
                            attrclass = $(this).attr('data-section-name');
                            strclass = attrclass.replace(/\s+/g, '-');
                            $('.'+strclass).removeClass('ticket_highlight');
                            seatmapApi.unhighlightSection(attrclass);
                        }
                  });
                  
                  $('.ticket_listings').on('click', function(){
                        var html_tag = '';
                        attrclass = $(this).attr('data-section-name');
                        dataobject = $(this).attr('data-object');
                        obj = JSON.parse(dataobject);
                        listing_id = obj.id;
                        tevo_section_name = obj.tevo_section_name;
                        row = obj.row;
                        retail_price = obj.retail_price;
                        splits = obj.splits;
                        format = obj.format;
                        instant_delivery = obj.instant_delivery;
                        public_notes = obj.public_notes;
                        
                        $('.ticket_listings').hide();
                        $('.ticket_detail').show();
                        
                        html_tag += '<a class=\'btn btn-sm mt-1 float-none position-absolute mt-3 ticket-button ticket_button_return\' title=\'Click here to go back\'><span><i class=\'fa fa-backward\'></i></span></a>';
                        if('$eventChartM' != 'null'){
                            html_tag += '<img src=\"$eventChartM\" width=\"100%\">';
                        } else {
                            html_tag += '<img src=\"https://placehold.co/200x200?text=No Map-Image\" width=\"100%\">';
                        }
                        section_name = tevo_section_name.toLowerCase().replace(/\b[a-z]/g, function(letter) {
                            return letter.toUpperCase();
                        });
                        html_tag += '<form name=\"ticket_checkout_form\" method=\"get\" action=\"/ticket-checkout\">';
                        html_tag += '<div class=\"row\">';
                        html_tag += '<div class=\"col-8\"><h5 class=\"black\">'+ section_name +'</h5><label class=\"black\">Row '+ row +'</label></div>';
                        html_tag += '<div class=\"col-4\"><h5 class=\"black text-right\">$'+ retail_price +' <small>/ea</small></h5></div>';
                        html_tag += '</div>';
                        var format_title = '';
                        var format_detail = '';
                        var dropbox = '<select name=\"ticket_qty\" style=\"width: 100%;padding: 5px 10px;border: 1px solid #ef457a;\">';
                        splits.forEach(function(item) {
                            str = (item > 1)? 's' : '';
                            dropbox +=  '<option value=\"'+item+'\">'+item+' Ticket'+str+'</option>';
                        });
                        dropbox += '</select>';
                        html_tag += '<div class=\"row\">';
                        html_tag += '<div class=\"col-6\">'+ dropbox +'</div>';
                        html_tag += '<div class=\"col-6\"><button type=\'submit\' class=\'btn btn-sm ticket-button\'><span>Checkout <i class=\'fa fa-forward\'></i></span></button></div>';
                        html_tag += '<input type=\"hidden\" name=\"listingId\" value=\"'+ listing_id +'\">';
                        html_tag += '<input type=\"hidden\" name=\"eventId\" value=\"$eventId\">';
                        html_tag += '<input type=\"hidden\" name=\"venueId\" value=\"$venueId\">';
                        html_tag += '<input type=\"hidden\" name=\"configurationId\" value=\"$configurationId\">';
                        html_tag += '<input type=\"hidden\" name=\"pageTitle\" value=\"'+ section_name +' • Row '+ row +'\">';
                        html_tag += '</div>';
                        html_tag += '</form>';
                        var format_title = '';
                        var format_detail = '';
                        
                        if(format == 'TM_mobile'){
                            format_title = 'TM Mobile (Mobile Transfer)';
                            format_detail = 'The tickets purchased will be transferred to the consumer\'s email address. The consumer must then accept the transfer and then be prepared to show the tickets on their mobile device in order to gain entry to the event.';
                        }
                        if(format == 'Flash_seats'){
                            format_title = 'Flash Seats™ (Mobile Transfer)';
                            format_detail = 'The tickets purchased will be transferred to the consumer\'s email address. The consumer must then accept the transfer and then be prepared to show the tickets on their mobile device in order to gain entry to the event.';
                        }
                        if(format == 'Eticket'){
                            format_title = 'Eticket';
                            format_detail = 'These tickets are generally PDF files that the consumer will download and print out to take to the event and generally include a barcode that must be scanned in order to gain entry to the event. Sometimes the seller will provide a PDF of the mobile entry ticket images. These must be displayed on a mobile phone so the barcodes can be scanned for entry. The venue will not allow entry if these are printed on paper.';
                        }
                        if(format == 'Physical'){
                            format_title = 'Physical';
                            format_detail = 'The traditional paper or “hard” ticket that has existed for dozens of years. Physical tickets usually include a barcode that must be scanned in order to gain entry to the event. Physical tickets are generally shipped via FedEx, but in some cases may need to be picked up by the consumer at the venue’s Will Call or a location near the venue. The seller will provide the exact location after the order is accepted.';
                        }
                        if(format == 'Paperless'){
                            format_title = 'Paperless';
                            format_detail = 'For some events, no actual tickets are issued and instead the credit card that originally purchased the tickets must be scanned in order to gain entry to the event. In most cases a gift card with instructions will be sent to the consumer via FedEx and the consumer will use that gift card to gain entry to the event. The gift card does not need to be returned after the event and may be discarded. In some cases it may be too late to ship the gift card required for entry and the Client may need to be picked up by the consumer at the venue’s Will Call or a location near the venue. The seller will provide the exact location after the order is accepted. In other cases the consumer may need to meet a representative of the company selling the tickets at the venue and the representative will walk the consumer through the process of entering the event.';
                        }
                        html_tag += '<div class=\"row\">';
                        html_tag += '<div class=\"col-12 mt-3\">';
                        html_tag += '<h5 class=\"black\">'+format_title+'</h5>';
                        html_tag += '<p class=\"darkgray\" align=\"justify\">'+format_detail+'</p>';
                        
                        let datetime = formatUTCDateTime('$eventDate');
                        
                        html_tag += '<h5 class=\"black\">$eventName</h5>';
                        html_tag += '<p class=\"darkgray\">'+ datetime +'</p>';
                        
                        html_tag += '<h5 class=\"black\">$venueName</h5>';
                        html_tag += '<p class=\"darkgray\">$venueFullAddress</p>';
                        
                        html_tag += '<h5 class=\"black\">Notes</h5>';
                        html_tag += '<p class=\"darkgray\">'+ public_notes +'</p>';
                        html_tag += '</div>';
                        html_tag += '</div>';
                        $('.ticket_detail').html(html_tag);
                  });
                  
                function formatUTCDateTime(utcdate) {
                    const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
                    const days = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
                    let msec = Date.parse(utcdate);
                    const date = new Date(msec);
                    let month = months[date.getMonth()];
                    let day = days[date.getDay()];
                    let dat = date.getDate();
                    let year = date.getFullYear();
                    let hours = date.getHours();
                    let minutes = date.getMinutes();
                    var ampm = hours >= 12 ? 'pm' : 'am';
                    hours = hours % 12;
                    hours = hours ? hours : 12; // the hour '0' should be '12'
                    minutes = minutes < 10 ? '0'+minutes : minutes;
                    fulldate = day +', '+ month +' '+ dat +', '+ year;
                    var strTime = fulldate +' at '+ hours + ':' + minutes + '' + ampm;
                    return strTime;
                }
                
                $('body').on('click', '.ticket_button_return', function(){
                    $('.ticket_detail').hide();
                    $('.ticket_listings').show();
                });
                </script>
                ";
        }
    } catch (\Exception $e) {
        echo 'Message: ' .esc_html( $e->getMessage() );
    }
    
    return $html ; 
}


// Action when initialize on front site.
add_shortcode('ticketevolution_ticket_checkout', 'callback_ticketevolution_ticket_checkout');
function callback_ticketevolution_ticket_checkout( $atts ) {
    
    if ( is_admin() ) {
		return '<p>This is where the shortcode [ticketevolution_ticket_checkout] will show up.</p>';
	}

    $defaults = [
      'title'  => 'Events Listing Page'
    ];
      
    $atts = shortcode_atts(
        $defaults,
        $atts,
        'ticketevolution_ticket_checkout'
    );
    
    //$html = wp_enqueue_style( 'bootstrap.min.css', $GLOBALS['plugin_dir_url'] . '/css/bootstrap.min.css' );
    //$html .= wp_enqueue_script('jquery-3.7.1.min.js', $GLOBALS['plugin_dir_url'] . '/js/jquery-3.7.1.min.js', array(), null, true);
    //$html .= wp_enqueue_script('bootstrap.min.js', $GLOBALS['plugin_dir_url'] . '/js/bootstrap.min.js', array(), null, true);
    
    $html = "<style>
            table { color: #000000; border: none; }
            .black {color: #000000 !important;}
            .darkgray {color: #04092c;}
            .darkpink {color: #ef457a !important;}
            .pink {color: #ce3197 !important;}
            .green {color: #086e88 !important;}
            .link {color: #ce3197 !important;}
            .link:hover {color: #000000 !important;}
            .event-heading, .event_date { font-family: var(--e-global-typography-7a8c4c5-font-family), sans-serif; font-size: 16px !important; font-weight: bold; line-height: 25px !important; letter-spacing: 0.1px !important; }
            .event-subheading, .event_time { font-family: var(--e-global-typography-7a8c4c5-font-family), sans-serif; font-size: 14px !important; }
            .event-heading:hover { color: #000 !important; }
            .row_border {border-bottom: 1px solid #ef457a;}
            .ticket-button { font-family: var(--e-global-typography-7a8c4c5-font-family), sans-serif; font-size: 16px; background-color: #ef457a; padding: 2px 10px 5px !important; color: #ffffff !important; float: right; margin-right: 5px; cursor: pointer; border-radius: 5px; border: 1px solid #ef457a; }
            .ticket-button:hover { background-color: #ffffff; color: #000000 !important; }
            .elementor-1631 .elementor-section:nth-child(2) .elementor-container, .elementor-1631 .elementor-section:nth-child(2) .elementor-container section {max-width: 100%; important;}
            .myseatmaps {min-height: 700px;  border: 5px solid #f1f1f1; padding-bottom: 30px;}
            .myticketslist {min-height: 700px;  border: 5px solid #f1f1f1; overflow-x: scroll;}
            @media only screen and (max-width: 850px) {
                .myseatmaps {height: 600px;}
            }
            @media only screen and (max-width: 600px) {
                .myseatmaps {height: 300px;}
            }
            .ticket_highlight {background: #A3CA3A; margin: 2px 0px; cursor:pointer;}
            .stepwizard-step p { margin-top: 0px; color:#666; }
            .stepwizard-row { display: table-row; }
            .stepwizard { display: table; width: 100%; position: relative; }
            .stepwizard-step button[disabled] {}
            .stepwizard-step .btn-success {background: #c36;}
            .stepwizard-step .btn-complete {background: #c36; -ms-transform: scaleX(-1) rotate(-35deg); /* IE 9 */ -webkit-transform: scaleX(-1) rotate(-35deg); /* Chrome, Safari, Opera */ transform: scaleX(-1) rotate(-35deg);}
            .stepwizard .btn.disabled, .stepwizard .btn[disabled], .stepwizard fieldset[disabled] .btn { opacity:1 !important; color:#bbb; }
            .stepwizard-row:before { top: 14px; left: 0; bottom: 0; position: absolute; content:' '; width: 100%; height: 1px; background-color: #ccc; z-index: 0; }
            .stepwizard-step { display: table-cell; text-align: center; position: relative; }
            .btn-circle { width: 30px; height: 30px; text-align: center; padding: 6px 0; font-size: 12px; line-height: 1.428571429; border-radius: 15px; background: #9E9E9E; color: #ffffff !important; }
            .nextBtn {color: #fff !important;}
            .nextBtn:hover { color: #ef457a !important; }
            .panel-title, .control-label { color: #333333 !important; width: 100%; }
            .form-group input[type=email], .form-group input[type=password], .form-group input[type=text], .form-group select { color: #333333 !important; border: 1px solid #d3d3dc !important; width: 100%; border-radius: 0.25rem; font-size: 1rem; height: 2.6rem; }
            .form-group input[type=radio] { border: 1px solid #EF457A !important; margin-top: 5px; height: 1.5rem; }
            
            .sq-card-iframe-container { height: 50px !important; }
            .buyer-inputs { display: flex; gap: 20px; justify-content: space-between; border: none; margin: 0; padding: 0; }
            #card-container { margin-top: 45px; min-height: 90px; }
            #gift-card-container { margin-top: 45px; min-height: 90px; }
            @media screen and (max-width: 500px) { #card-container { min-height: 140px; } }
            #ach-button { margin-top: 20px; }
            #landing-page-layout { width: 80%; margin: 150px auto; max-width: 1000px; }
            #its-working { color: #737373; }
            #example-container { width: 100%; border: 1px solid #b3b3b3; padding: 48px; margin: 32px 0; border-radius: 12px; }
            #example-list {display: flex; flex-direction: column; gap: 15px; }
            #customer-input { margin-bottom: 40px; }
            #card-input { margin-top: 0; margin-bottom: 40px; }
            button:disabled { background-color: rgba(0, 0, 0, 0.05); color: rgba(0, 0, 0, 0.3); }
            #payment-status-container { display: flex; align-items: center; justify-content: center; border: 1px solid rgba(0, 0, 0, 0.05); box-sizing: border-box; border-radius: 50px; margin: 0 auto; width: 225px; height: 48px; visibility: hidden; }
            #payment-status-container.missing-credentials { width: 350px; }
            #payment-status-container.is-success:before {
              content: ''; background-color: #00b23b; width: 16px; height: 16px; margin-right: 16px;
              -webkit-mask: url(\"data:image/svg+xml,%3Csvg width='16' height='16' viewBox='0 0 16 16' fill='none' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath fill-rule='evenodd' clip-rule='evenodd' d='M8 16C12.4183 16 16 12.4183 16 8C16 3.58172 12.4183 0 8 0C3.58172 0 0 3.58172 0 8C0 12.4183 3.58172 16 8 16ZM11.7071 6.70711C12.0968 6.31744 12.0978 5.68597 11.7093 5.29509C11.3208 4.90422 10.6894 4.90128 10.2973 5.28852L11 6C10.2973 5.28852 10.2973 5.28853 10.2973 5.28856L10.2971 5.28866L10.2967 5.28908L10.2951 5.29071L10.2886 5.29714L10.2632 5.32224L10.166 5.41826L9.81199 5.76861C9.51475 6.06294 9.10795 6.46627 8.66977 6.90213C8.11075 7.4582 7.49643 8.07141 6.99329 8.57908L5.70711 7.29289C5.31658 6.90237 4.68342 6.90237 4.29289 7.29289C3.90237 7.68342 3.90237 8.31658 4.29289 8.70711L6.29289 10.7071C6.68342 11.0976 7.31658 11.0976 7.70711 10.7071L11.7071 6.70711Z' fill='black' fill-opacity='0.9'/%3E%3C/svg%3E\");
              mask: url(\"data:image/svg+xml,%3Csvg width='16' height='16' viewBox='0 0 16 16' fill='none' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath fill-rule='evenodd' clip-rule='evenodd' d='M8 16C12.4183 16 16 12.4183 16 8C16 3.58172 12.4183 0 8 0C3.58172 0 0 3.58172 0 8C0 12.4183 3.58172 16 8 16ZM11.7071 6.70711C12.0968 6.31744 12.0978 5.68597 11.7093 5.29509C11.3208 4.90422 10.6894 4.90128 10.2973 5.28852L11 6C10.2973 5.28852 10.2973 5.28853 10.2973 5.28856L10.2971 5.28866L10.2967 5.28908L10.2951 5.29071L10.2886 5.29714L10.2632 5.32224L10.166 5.41826L9.81199 5.76861C9.51475 6.06294 9.10795 6.46627 8.66977 6.90213C8.11075 7.4582 7.49643 8.07141 6.99329 8.57908L5.70711 7.29289C5.31658 6.90237 4.68342 6.90237 4.29289 7.29289C3.90237 7.68342 3.90237 8.31658 4.29289 8.70711L6.29289 10.7071C6.68342 11.0976 7.31658 11.0976 7.70711 10.7071L11.7071 6.70711Z' fill='black' fill-opacity='0.9'/%3E%3C/svg%3E\");
            }
            #payment-status-container.is-success:after { content: 'Payment successful'; font-size: 14px; line-height: 16px; }
            #payment-status-container.is-failure:before {
              content: ''; background-color: #cc0023; width: 16px; height: 16px; margin-right: 16px;
              -webkit-mask: url(\"data:image/svg+xml,%3Csvg width='16' height='16' viewBox='0 0 16 16' fill='none' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath fill-rule='evenodd' clip-rule='evenodd' d='M8 16C12.4183 16 16 12.4183 16 8C16 3.58172 12.4183 0 8 0C3.58172 0 0 3.58172 0 8C0 12.4183 3.58172 16 8 16ZM5.70711 4.29289C5.31658 3.90237 4.68342 3.90237 4.29289 4.29289C3.90237 4.68342 3.90237 5.31658 4.29289 5.70711L6.58579 8L4.29289 10.2929C3.90237 10.6834 3.90237 11.3166 4.29289 11.7071C4.68342 12.0976 5.31658 12.0976 5.70711 11.7071L8 9.41421L10.2929 11.7071C10.6834 12.0976 11.3166 12.0976 11.7071 11.7071C12.0976 11.3166 12.0976 10.6834 11.7071 10.2929L9.41421 8L11.7071 5.70711C12.0976 5.31658 12.0976 4.68342 11.7071 4.29289C11.3166 3.90237 10.6834 3.90237 10.2929 4.29289L8 6.58579L5.70711 4.29289Z' fill='black' fill-opacity='0.9'/%3E%3C/svg%3E%0A\");
              mask: url(\"data:image/svg+xml,%3Csvg width='16' height='16' viewBox='0 0 16 16' fill='none' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath fill-rule='evenodd' clip-rule='evenodd' d='M8 16C12.4183 16 16 12.4183 16 8C16 3.58172 12.4183 0 8 0C3.58172 0 0 3.58172 0 8C0 12.4183 3.58172 16 8 16ZM5.70711 4.29289C5.31658 3.90237 4.68342 3.90237 4.29289 4.29289C3.90237 4.68342 3.90237 5.31658 4.29289 5.70711L6.58579 8L4.29289 10.2929C3.90237 10.6834 3.90237 11.3166 4.29289 11.7071C4.68342 12.0976 5.31658 12.0976 5.70711 11.7071L8 9.41421L10.2929 11.7071C10.6834 12.0976 11.3166 12.0976 11.7071 11.7071C12.0976 11.3166 12.0976 10.6834 11.7071 10.2929L9.41421 8L11.7071 5.70711C12.0976 5.31658 12.0976 4.68342 11.7071 4.29289C11.3166 3.90237 10.6834 3.90237 10.2929 4.29289L8 6.58579L5.70711 4.29289Z' fill='black' fill-opacity='0.9'/%3E%3C/svg%3E%0A\");
            }
            #payment-status-container.is-failure:after { content: 'Payment failed'; font-size: 14px; line-height: 16px; }
            #payment-status-container.missing-credentials:before {
              content: ''; background-color: #cc0023; width: 16px; height: 16px; margin-right: 16px;
              -webkit-mask: url(\"data:image/svg+xml,%3Csvg width='16' height='16' viewBox='0 0 16 16' fill='none' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath fill-rule='evenodd' clip-rule='evenodd' d='M8 16C12.4183 16 16 12.4183 16 8C16 3.58172 12.4183 0 8 0C3.58172 0 0 3.58172 0 8C0 12.4183 3.58172 16 8 16ZM5.70711 4.29289C5.31658 3.90237 4.68342 3.90237 4.29289 4.29289C3.90237 4.68342 3.90237 5.31658 4.29289 5.70711L6.58579 8L4.29289 10.2929C3.90237 10.6834 3.90237 11.3166 4.29289 11.7071C4.68342 12.0976 5.31658 12.0976 5.70711 11.7071L8 9.41421L10.2929 11.7071C10.6834 12.0976 11.3166 12.0976 11.7071 11.7071C12.0976 11.3166 12.0976 10.6834 11.7071 10.2929L9.41421 8L11.7071 5.70711C12.0976 5.31658 12.0976 4.68342 11.7071 4.29289C11.3166 3.90237 10.6834 3.90237 10.2929 4.29289L8 6.58579L5.70711 4.29289Z' fill='black' fill-opacity='0.9'/%3E%3C/svg%3E%0A\");
              mask: url(\"data:image/svg+xml,%3Csvg width='16' height='16' viewBox='0 0 16 16' fill='none' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath fill-rule='evenodd' clip-rule='evenodd' d='M8 16C12.4183 16 16 12.4183 16 8C16 3.58172 12.4183 0 8 0C3.58172 0 0 3.58172 0 8C0 12.4183 3.58172 16 8 16ZM5.70711 4.29289C5.31658 3.90237 4.68342 3.90237 4.29289 4.29289C3.90237 4.68342 3.90237 5.31658 4.29289 5.70711L6.58579 8L4.29289 10.2929C3.90237 10.6834 3.90237 11.3166 4.29289 11.7071C4.68342 12.0976 5.31658 12.0976 5.70711 11.7071L8 9.41421L10.2929 11.7071C10.6834 12.0976 11.3166 12.0976 11.7071 11.7071C12.0976 11.3166 12.0976 10.6834 11.7071 10.2929L9.41421 8L11.7071 5.70711C12.0976 5.31658 12.0976 4.68342 11.7071 4.29289C11.3166 3.90237 10.6834 3.90237 10.2929 4.29289L8 6.58579L5.70711 4.29289Z' fill='black' fill-opacity='0.9'/%3E%3C/svg%3E%0A\");
            }
            #payment-status-container.missing-credentials:after { content: 'applicationId and/or locationId is incorrect'; font-size: 14px; line-height: 16px; }
            #payment-status-container.is-success.store-card-message:after { content: 'Store card successful'; }
            #payment-status-container.is-failure.store-card-message:after { content: 'Store card failed'; }
            #afterpay-button { height: 40px; }
            </style>
            ";
                                    
    $apiClient = new TEvoClient([
        'baseUrl'     => $GLOBALS['baseUrl'],
        'apiVersion'  => $GLOBALS['apiVersion'],
        'apiToken'    => $GLOBALS['apiToken'],
        'apiSecret'   => $GLOBALS['apiSecret'],
    ]);
    
    try {
        if(isset($_REQUEST['_wpnonce'])){
            $nonce = isset($_REQUEST['_wpnonce'])? sanitize_text_field(wp_unslash($_REQUEST['_wpnonce'])) : '';
            if (!wp_verify_nonce($nonce, 'events_list')) {
                wp_die('Invalid nonce verification.');
            }
        }
		$venueId = isset($_REQUEST['venueId'])? sanitize_text_field(wp_unslash($_REQUEST['venueId'])) : '';
		$configurationId = isset($_REQUEST['configurationId'])? sanitize_text_field(wp_unslash($_REQUEST['configurationId'])) : '';
		$eventId = isset($_REQUEST['eventId'])? (int) sanitize_text_field(wp_unslash($_REQUEST['eventId'])) : '';
		$listingId = isset($_REQUEST['listingId'])? (int) sanitize_text_field(wp_unslash($_REQUEST['listingId'])) : '';
		$ticketQty = isset($_REQUEST['ticket_qty'])? sanitize_text_field(wp_unslash($_REQUEST['ticket_qty'])) : '';
		
        if(!empty($eventId)){
            $eventDetail = $apiClient->showEvent([
                'event_id' => $eventId
            ]);
            $eventName = $eventDetail['name'];
            $eventDate = $eventDetail['occurs_at'];
            $eventChartM = $eventDetail['configuration']['seating_chart']['medium'];
            
            $venueDetail = $apiClient->showVenue([
                'venue_id' => $eventDetail['venue']['id']
            ]);
            $venueName = $venueDetail['name'];
            $occursAt = $venueDetail['occurs_at'];
            $venueAddress = $venueDetail['address']['street_address'];
            $venueLocation = $venueDetail['address']['location'];
            $venuePostal = $venueDetail['address']['postal_code'];
            $venueFullAddress = $venueAddress.', '.$venueLocation.', '.$venuePostal;
            
            $listingSearch = $apiClient->showListing([
                'id' => $listingId
            ]);
            
            $c_api_clientid = "";
            $c_api_orderid = "";
            $primary_billing_add = "";
            $primary_shipping_add = "";
            $signin_tab_display = "block";
            $delivery_tab_display = "none";
            $active_tab_class = "btn-default";
            $primary_shipping_id = "";
            $primary_billing_id = "";
            $client_first_name = "";
            $client_last_name = "";
            $primary_email = "";
            $primary_number = "";
            $primary_billing_street = "";
            $primary_billing_extended = "";
            $primary_billing_locality = "";
            $primary_billing_region = "";
            $primary_billing_country = "";
            $logged_user_type = "customer";
            
            if(is_user_logged_in()){
                $active_tab_class = "btn-success";
                $signin_tab_display = "none";
                $delivery_tab_display = "block";
                $logged_user_id = get_current_user_id();
                $logged_user_type = (user_has_role($logged_user_id, 'subscriber'))? "customer" : "admin";
                
                if($logged_user_type == "customer"){
                    $c_api_clientid = get_user_meta( $logged_user_id, 'c_api_clientid', true );
                    //$c_api_orderid = get_user_meta( $logged_user_id, 'c_api_orderid', true );
                    $client_ob = $apiClient->showClient([
                        'client_id' => (int)$c_api_clientid
                    ]);
                    
                    $primary_shipping_id = $client_ob['primary_shipping_address']['id'];
                    $primary_shipping_name = $client_ob['primary_shipping_address']['name'];
                    $primary_shipping_street = $client_ob['primary_shipping_address']['street_address'];
                    $primary_shipping_extended = $client_ob['primary_shipping_address']['extended_address'];
                    $primary_shipping_locality = $client_ob['primary_shipping_address']['locality'];
                    $primary_shipping_region = $client_ob['primary_shipping_address']['region'];
                    $primary_shipping_postal = $client_ob['primary_shipping_address']['postal_code'];
                    $primary_shipping_country = $client_ob['primary_shipping_address']['country_code'];
                    $primary_email = $client_ob['primary_email_address']['address'];
                    $primary_country_code = $client_ob['primary_phone_number']['country_code'];
                    $primary_number = $client_ob['primary_phone_number']['number'];
                    
                    if(!empty($primary_shipping_name)){ $primary_shipping_add .= '<b>'.$primary_shipping_name.'</b>'.'<br>'; }
                    if(!empty($primary_shipping_street)){ $primary_shipping_add .= $primary_shipping_street; }
                    if(!empty($primary_shipping_extended)){ $primary_shipping_add .= ', '.$primary_shipping_extended; }
                    if(!empty($primary_shipping_locality)){ $primary_shipping_add .= ', '.$primary_shipping_locality; }
                    if(!empty($primary_shipping_region)){ $primary_shipping_add .= ', '.$primary_shipping_region; }
                    if(!empty($primary_shipping_postal)){ $primary_shipping_add .= ', '.$primary_shipping_postal; }
                    if(!empty($primary_shipping_country)){ $primary_shipping_add .= ', '.$primary_shipping_country; }
                    if(!empty($primary_email)){ $primary_shipping_add .= '<br>'.$primary_email; }
                    if(!empty($primary_number)){ $primary_shipping_add .= '<br>+'.$primary_country_code.$primary_number; }
                    
                    $primary_billing_id = $client_ob['primary_billing_address']['id'];
                    $primary_billing_name = $client_ob['primary_billing_address']['name'];
                    $primary_billing_street = $client_ob['primary_billing_address']['street_address'];
                    $primary_billing_extended = $client_ob['primary_billing_address']['extended_address'];
                    $primary_billing_locality = $client_ob['primary_billing_address']['locality'];
                    $primary_billing_region = $client_ob['primary_billing_address']['region'];
                    $primary_billing_postal = $client_ob['primary_billing_address']['postal_code'];
                    $primary_billing_country = $client_ob['primary_billing_address']['country_code'];
                    
                    if(!empty($primary_billing_name)){ $primary_billing_add .= '<b>'.$primary_billing_name.'</b>'.'<br>'; }
                    if(!empty($primary_billing_street)){ $primary_billing_add .= $primary_billing_street; }
                    if(!empty($primary_billing_extended)){ $primary_billing_add .= ', '.$primary_billing_extended; }
                    if(!empty($primary_billing_locality)){ $primary_billing_add .= ', '.$primary_billing_locality; }
                    if(!empty($primary_billing_region)){ $primary_billing_add .= ', '.$primary_billing_region; }
                    if(!empty($primary_billing_postal)){ $primary_billing_add .= ', '.$primary_billing_postal; }
                    if(!empty($primary_billing_country)){ $primary_billing_add .= ', '.$primary_billing_country; }
                    if(!empty($primary_email)){ $primary_billing_add .= '<br>'.$primary_email; }
                    if(!empty($primary_number)){ $primary_billing_add .= '<br>+'.$primary_country_code.$primary_number; }
                    
                    $name_parts = explode(" ",$primary_shipping_name);
                    $client_first_name = (isset($name_parts[0]))? $name_parts[0] : '';
                    $client_last_name = (isset($name_parts[1]))? $name_parts[1] : '';
                }
            }
            $html .= '<div class="container-fluid"><div class="row">';
            $html .= '<div class="myseatmaps col-lg-8 pt-5">';
            
            if($logged_user_type == "customer"){
            $html .= '<div class="stepwizard">
                            <div class="stepwizard-row setup-panel">
                                <div class="stepwizard-step col-xs-3" style="display:'.$signin_tab_display.';">
                                    <a href="#step-1" type="button" class="btn btn-success signin_step btn-circle">1</a>
                                    <p><small>Login or Register</small></p>
                                </div>
                                <div class="stepwizard-step col-xs-3"> 
                                    <a href="#step-2" type="button" class="btn '.$active_tab_class.' delivery_step btn-circle" disabled="disabled">2</a>
                                    <p><small>Delivery</small></p>
                                </div>
                                <div class="stepwizard-step col-xs-3"> 
                                    <a href="#step-3" type="button" class="btn btn-default billing_step btn-circle" disabled="disabled">3</a>
                                    <p><small>Payment & Billing</small></p>
                                </div>
                                <div class="stepwizard-step col-xs-3"> 
                                    <a href="#step-4" type="button" class="btn btn-default order_step btn-circle" disabled="disabled">4</a>
                                    <p><small>Place Order</small></p>
                                </div>
                            </div>
                        </div>
                        
                        <form name="ticket_checkout" id="formsteps" role="form">
                            <div class="panel panel-primary setup-content p-4" id="step-1" style="display:'.$signin_tab_display.';">
                                <div class="panel-body signin_section">
                                    <div class="form-group">
                                        <label class="control-label">Email Address</label>
                                        <input maxlength="100" type="email" name="email_address" id="email_address" required="required" class="form-control" placeholder="Email Address" />
                                    </div>
                                    <div class="form-group">
                                        <label class="control-label"><input type="radio" name="account_check" class="account_check" value="new" checked /> Create a new account</label>
                                    </div>
                                    <div class="form-group">
                                        <label class="control-label"><input type="radio" name="account_check" class="account_check" value="existing" /> Sign into an existing account</label>
                                    </div>
                                    <div class="form-group password_field" style="display:none;">
                                        <label class="control-label">Password</label>
                                        <input maxlength="100" type="password" name="password" id="password" required="required" class="form-control" placeholder="Password" />
                                    </div>
                                    <img class="ajax_loader_img" src="'. $GLOBALS['plugin_dir_url'] .'/ajax-loader.gif" style="width: 100px;float: right; display:none;" />
                                    <button id="signin_button" class="btn btn-primary nextBtn" style="float: right;" type="button">Continue Next</button>
                                </div>
                            </div>
                            
                            <div class="panel panel-primary setup-content" id="step-2" style="display:'.$delivery_tab_display.';">
                                <div class="panel-heading">
                                     <h3 class="panel-title">Delivery Address</h3>
                                </div>
                                <div class="panel-body shipping-address" style="display:'.$delivery_tab_display.';">
                                     <div class="">
                                        <label class="control-label">Default Shipping Address *</label>
                                    </div>
                                    <div class="form-group" style="border: 2px dotted #EF457A; padding: 10px; background: #f1f1f1;">
                                        <input class="form-check-input" type="radio" name="primary_shipping_radio" id="primary_shipping_radio" checked value="'.$primary_shipping_id.'" style="margin-left: 0px; margin-top: 0px;">
                                        <label class="form-check-label control-label" for="primary_shipping_radio" style="margin-left: 25px;font-size: 18px; line-height: 25px; font-weight: 400;">
                                            '.$primary_shipping_add.'
                                        </label>
                                    </div>
                                </div>
                                <div class="panel-body" style="display:'.$signin_tab_display.';">
                                    <div class="form-group">
                                        <label class="control-label">Full Name *</label>
                                        <input type="text" id="deliveryFullName" required="required" class="form-control" placeholder="Enter Full Name" />
                                    </div>
                                    <div class="form-group">
                                        <label class="control-label">Company (Optional)</label>
                                        <input type="text" id="deliveryCompanyName" class="form-control" placeholder="Enter Company Name" maxlength="50" />
                                    </div>
                                    <div class="form-group">
                                        <label class="control-label">Mobile Phone *</label>
                                        <input type="text" id="deliveryMobileNumber" required="required" class="form-control" placeholder="Enter Mobile Phone" />
                                    </div>
                                    <div class="form-group">
                                        <label class="control-label">Address line 1 *</label>
                                        <input type="text" id="deliveryLine1" required="required" class="form-control" placeholder="Enter Address line 1" />
                                    </div>
                                    <div class="form-group">
                                        <label class="control-label">Address line 2 (Optional)</label>
                                        <input type="text" id="deliveryLine2" class="form-control" placeholder="Enter Address line 2" />
                                    </div>
                                    <div class="form-row">
                                        <div class="form-group col-md-6">
                                            <label class="control-label" for="deliveryCity">City *</label>
                                            <input type="text" class="form-control" id="deliveryCity" required="required">
                                        </div>
                                        <div class="form-group col-md-6">
                                            <label class="control-label" for="deliveryZip">Zip *</label>
                                            <input type="text" class="form-control" id="deliveryZip" required="required">
                                        </div>
                                    </div>
                                    <div class="form-row">
                                        <div class="form-group col-md-6">
                                            <label class="control-label" for="deliveryState">State *</label>
                                            <select id="deliveryState" class="form-control" required="required">
                                                <option value="">Select your state</option><option value="AL">Alabama</option><option value="AK">Alaska</option><option value="AZ">Arizona</option><option value="AR">Arkansas</option><option value="CA">California</option><option value="CO">Colorado</option><option value="CT">Connecticut</option><option value="DE">Delaware</option><option value="DC">Washington, D.C.</option><option value="FL">Florida</option><option value="GA">Georgia</option><option value="HI">Hawaii</option><option value="ID">Idaho</option><option value="IL">Illinois</option><option value="IN">Indiana</option><option value="IA">Iowa</option><option value="KS">Kansas</option><option value="KY">Kentucky</option><option value="LA">Louisiana</option><option value="ME">Maine</option><option value="MD">Maryland</option><option value="MA">Massachusetts</option><option value="MI">Michigan</option><option value="MN">Minnesota</option><option value="MS">Mississippi</option><option value="MO">Missouri</option><option value="MT">Montana</option><option value="NE">Nebraska</option><option value="NV">Nevada</option><option value="NH">New Hampshire</option><option value="NJ">New Jersey</option><option value="NM">New Mexico</option><option value="NY">New York</option><option value="NC">North Carolina</option><option value="ND">North Dakota</option><option value="OH">Ohio</option><option value="OK">Oklahoma</option><option value="OR">Oregon</option><option value="PA">Pennsylvania</option><option value="PR">Puerto Rico</option><option value="RI">Rhode Island</option><option value="SC">South Carolina</option><option value="SD">South Dakota</option><option value="TN">Tennessee</option><option value="TX">Texas</option><option value="UT">Utah</option><option value="VT">Vermont</option><option value="VA">Virginia</option><option value="VI">Virgin Islands</option><option value="WA">Washington</option><option value="WV">West Virginia</option><option value="WI">Wisconsin</option><option value="WY">Wyoming</option>
                                            </select>
                                        </div>
                                        <div class="form-group col-md-6">
                                            <label class="control-label" for="deliveryCountry">Country *</label>
                                            <select id="deliveryCountry" class="form-control" required="required">
                                                <option value="">Select your country</option><option value="AF">Afghanistan</option><option value="AX">Åland Islands</option><option value="AL">Albania</option><option value="DZ">Algeria</option><option value="AS">American Samoa</option><option value="AD">Andorra</option><option value="AO">Angola</option><option value="AI">Anguilla</option><option value="AQ">Antarctica</option><option value="AG">Antigua and Barbuda</option><option value="AR">Argentina</option><option value="AM">Armenia</option><option value="AW">Aruba</option><option value="AU">Australia</option><option value="AT">Austria</option><option value="AZ">Azerbaijan</option><option value="BS">Bahamas</option><option value="BH">Bahrain</option><option value="BD">Bangladesh</option><option value="BB">Barbados</option><option value="BY">Belarus</option><option value="BE">Belgium</option><option value="BZ">Belize</option><option value="BJ">Benin</option><option value="BM">Bermuda</option><option value="BT">Bhutan</option><option value="BO">Bolivia, Plurinational State of</option><option value="BQ">Bonaire, Sint Eustatius and Saba</option><option value="BA">Bosnia and Herzegovina</option><option value="BW">Botswana</option><option value="BV">Bouvet Island</option><option value="BR">Brazil</option><option value="IO">British Indian Ocean Territory</option><option value="BN">Brunei Darussalam</option><option value="BG">Bulgaria</option><option value="BF">Burkina Faso</option><option value="BI">Burundi</option><option value="CV">Cabo Verde</option><option value="KH">Cambodia</option><option value="CM">Cameroon</option><option value="CA">Canada</option><option value="KY">Cayman Islands</option><option value="CF">Central African Republic</option><option value="TD">Chad</option><option value="CL">Chile</option><option value="CN">China</option><option value="CX">Christmas Island</option><option value="CC">Cocos (Keeling) Islands</option><option value="CO">Colombia</option><option value="KM">Comoros</option><option value="CG">Congo</option><option value="CD">Congo, Democratic Republic of the</option><option value="CK">Cook Islands</option><option value="CR">Costa Rica</option><option value="HR">Croatia</option><option value="CU">Cuba</option><option value="CW">Curaçao</option><option value="CY">Cyprus</option><option value="CZ">Czechia</option><option value="CI">Côte d`Ivoire</option><option value="DK">Denmark</option><option value="DJ">Djibouti</option><option value="DM">Dominica</option><option value="DO">Dominican Republic</option><option value="EC">Ecuador</option><option value="EG">Egypt</option><option value="SV">El Salvador</option><option value="GQ">Equatorial Guinea</option><option value="ER">Eritrea</option><option value="EE">Estonia</option><option value="SZ">Eswatini</option><option value="ET">Ethiopia</option><option value="FK">Falkland Islands (Malvinas)</option><option value="FO">Faroe Islands</option><option value="FJ">Fiji</option><option value="FI">Finland</option><option value="FR">France</option><option value="GF">French Guiana</option><option value="PF">French Polynesia</option><option value="TF">French Southern Territories</option><option value="GA">Gabon</option><option value="GM">Gambia</option><option value="GE">Georgia</option><option value="DE">Germany</option><option value="GH">Ghana</option><option value="GI">Gibraltar</option><option value="GR">Greece</option><option value="GL">Greenland</option><option value="GD">Grenada</option><option value="GP">Guadeloupe</option><option value="GU">Guam</option><option value="GT">Guatemala</option><option value="GG">Guernsey</option><option value="GN">Guinea</option><option value="GW">Guinea-Bissau</option><option value="GY">Guyana</option><option value="HT">Haiti</option><option value="HM">Heard Island and McDonald Islands</option><option value="VA">Holy See</option><option value="HN">Honduras</option><option value="HK">Hong Kong</option><option value="HU">Hungary</option><option value="IS">Iceland</option><option value="IN">India</option><option value="ID">Indonesia</option><option value="IR">Iran, Islamic Republic of</option><option value="IQ">Iraq</option><option value="IE">Ireland</option><option value="IM">Isle of Man</option><option value="IL">Israel</option><option value="IT">Italy</option><option value="JM">Jamaica</option><option value="JP">Japan</option><option value="JE">Jersey</option><option value="JO">Jordan</option><option value="KZ">Kazakhstan</option><option value="KE">Kenya</option><option value="KI">Kiribati</option><option value="KP">Korea, Democratic People`s Republic of</option><option value="KR">Korea, Republic of</option><option value="KW">Kuwait</option><option value="KG">Kyrgyzstan</option><option value="LA">Lao People`s Democratic Republic</option><option value="LV">Latvia</option><option value="LB">Lebanon</option><option value="LS">Lesotho</option><option value="LR">Liberia</option><option value="LY">Libya</option><option value="LI">Liechtenstein</option><option value="LT">Lithuania</option><option value="LU">Luxembourg</option><option value="MO">Macao</option><option value="MG">Madagascar</option><option value="MW">Malawi</option><option value="MY">Malaysia</option><option value="MV">Maldives</option><option value="ML">Mali</option><option value="MT">Malta</option><option value="MH">Marshall Islands</option><option value="MQ">Martinique</option><option value="MR">Mauritania</option><option value="MU">Mauritius</option><option value="YT">Mayotte</option><option value="MX">Mexico</option><option value="FM">Micronesia, Federated States of</option><option value="MD">Moldova, Republic of</option><option value="MC">Monaco</option><option value="MN">Mongolia</option><option value="ME">Montenegro</option><option value="MS">Montserrat</option><option value="MA">Morocco</option><option value="MZ">Mozambique</option><option value="MM">Myanmar</option><option value="NA">Namibia</option><option value="NR">Nauru</option><option value="NP">Nepal</option><option value="NL">Netherlands</option><option value="NC">New Caledonia</option><option value="NZ">New Zealand</option><option value="NI">Nicaragua</option><option value="NE">Niger</option><option value="NG">Nigeria</option><option value="NU">Niue</option><option value="NF">Norfolk Island</option><option value="MK">North Macedonia</option><option value="MP">Northern Mariana Islands</option><option value="NO">Norway</option><option value="OM">Oman</option><option value="PK">Pakistan</option><option value="PW">Palau</option><option value="PS">Palestine, State of</option><option value="PA">Panama</option><option value="PG">Papua New Guinea</option><option value="PY">Paraguay</option><option value="PE">Peru</option><option value="PH">Philippines</option><option value="PN">Pitcairn</option><option value="PL">Poland</option><option value="PT">Portugal</option><option value="PR">Puerto Rico</option><option value="QA">Qatar</option><option value="RO">Romania</option><option value="RU">Russian Federation</option><option value="RW">Rwanda</option><option value="RE">Réunion</option><option value="BL">Saint Barthélemy</option><option value="SH">Saint Helena, Ascension and Tristan da Cunha</option><option value="KN">Saint Kitts and Nevis</option><option value="LC">Saint Lucia</option><option value="MF">Saint Martin (French part)</option><option value="PM">Saint Pierre and Miquelon</option><option value="VC">Saint Vincent and the Grenadines</option><option value="WS">Samoa</option><option value="SM">San Marino</option><option value="ST">Sao Tome and Principe</option><option value="SA">Saudi Arabia</option><option value="SN">Senegal</option><option value="RS">Serbia</option><option value="SC">Seychelles</option><option value="SL">Sierra Leone</option><option value="SG">Singapore</option><option value="SX">Sint Maarten (Dutch part)</option><option value="SK">Slovakia</option><option value="SI">Slovenia</option><option value="SB">Solomon Islands</option><option value="SO">Somalia</option><option value="ZA">South Africa</option><option value="GS">South Georgia and the South Sandwich Islands</option><option value="SS">South Sudan</option><option value="ES">Spain</option><option value="LK">Sri Lanka</option><option value="SD">Sudan</option><option value="SR">Suriname</option><option value="SJ">Svalbard and Jan Mayen</option><option value="SE">Sweden</option><option value="CH">Switzerland</option><option value="SY">Syrian Arab Republic</option><option value="TW">Taiwan, Province of China</option><option value="TJ">Tajikistan</option><option value="TZ">Tanzania, United Republic of</option><option value="TH">Thailand</option><option value="TL">Timor-Leste</option><option value="TG">Togo</option><option value="TK">Tokelau</option><option value="TO">Tonga</option><option value="TT">Trinidad and Tobago</option><option value="TN">Tunisia</option><option value="TR">Turkey</option><option value="TM">Turkmenistan</option><option value="TC">Turks and Caicos Islands</option><option value="TV">Tuvalu</option><option value="UG">Uganda</option><option value="UA">Ukraine</option><option value="AE">United Arab Emirates</option><option value="GB">United Kingdom</option><option value="UM">United States Minor Outlying Islands</option><option value="US">United States</option><option value="UY">Uruguay</option><option value="UZ">Uzbekistan</option><option value="VU">Vanuatu</option><option value="VE">Venezuela, Bolivarian Republic of</option><option value="VN">Vietnam</option><option value="VG">Virgin Islands, British</option><option value="VI">Virgin Islands, U.S.</option><option value="WF">Wallis and Futuna</option><option value="EH">Western Sahara</option><option value="YE">Yemen</option><option value="ZM">Zambia</option><option value="ZW">Zimbabwe</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <div class="panel-heading" style="display:'.$signin_tab_display.';">
                                     <h3 class="panel-title">Create Password</h3>
                                </div>
                                <div class="panel-body" style="display:'.$signin_tab_display.';">
                                    <div class="form-group">
                                        <label class="control-label">Password *</label>
                                        <input type="password" id="userPassword" required="required" class="form-control" placeholder="Enter Password" />
                                    </div>
                                    <div class="form-group">
                                        <label class="control-label">Confirm Password *</label>
                                        <input type="password" id="userConfirmPassword" required="required" class="form-control" placeholder="Enter Confirm Password" />
                                    </div>
                                </div>
                                <div class="panel-heading delivery_method_block">
                                     <h3 class="panel-title">Delivery Method</h3>
                                </div>
                                <div class="panel-body delivery_method_block">
                                    <div class="form-group">
                                        <label class="control-label">Choose Delivery Method</label>
                                        <select id="deliveryMethod" class="form-control">';
                                            if($listingSearch["format"] == "TM_mobile"){
                                                $html .= '<option value="TMMobile">TM Mobile Entry (Mobile Transfer)</option>';
                                            }
                                            if($listingSearch["format"] == "Flash_seats"){
                                                $html .= '<option value="FlashSeats">Flash Seats™ (Mobile Transfer)</option>';
                                            }
                                            if($listingSearch["format"] == "Eticket"){
                                                $html .= '<option value="Eticket">Print at Home (E-ticket)</option>';
                                            }
                                            if($listingSearch["format"] == "Physical"){
                                                $html .= '<option value="FedEx">Physical (Fedex 2 Day)</option>';
                                                $html .= '<option value="LocalPickup">Physical (Local Pickup)</option>';
                                            }
                                            if($listingSearch["format"] == "Paperless"){
                                                $html .= '<option value="FedEx">Physical (Fedex 2 Day)</option>';
                                                $html .= '<option value="LocalPickup">Physical (Local Pickup)</option>';
                                            }
                                            if($listingSearch["format"] == "Guest_list"){
                                                $html .= '<option value="GuestList">Guest list</option>';
                                            }
                            $html .= '</select>
                                    </div>
                                </div>
                                
                                <input type="hidden" name="venueId" id="OrderVenueId" value="'.$venueId.'" />
                                <input type="hidden" name="eventId" id="OrderEventId" value="'.$eventId.'" />
                                <input type="hidden" name="listingId" id="OrderListingId" value="'.$listingId.'" />
                                <input type="hidden" name="ticketQty" id="OrderTicketQty" value="'.$ticketQty.'" />
                                <input type="hidden" name="configurationId" id="OrderConfigurationId" value="'.$configurationId.'" />
                                <button id="delivery_button" class="btn btn-primary nextBtn pull-right" type="button">Continue Next</button>
                                <img class="ajax_loader_img" src="'.$GLOBALS['plugin_dir_url'].'/ajax-loader.gif" style="width: 100px;float: right; display:none;" />
                            </div>
                            
                            <div class="panel panel-primary setup-content" id="step-3" style="display:none;">
                                <div class="panel-heading">
                                     <h3 class="panel-title">Payment Type & Billing</h3>
                                </div>
                                <div class="panel-body">
                                    <div class="panel-body">
                                         <div class="">
                                            <label class="control-label">Payment Types</label>
                                        </div>
                                        <div class="form-group" style="border: 2px dotted #EF457A; padding: 10px; background: #f1f1f1;">
                                            <input class="form-check-input" type="radio" name="payment_type_radio" id="payment_type_radio" checked value="cc_payment" style="margin-left: 0px; margin-top: 4px;">
                                            <label class="form-check-label control-label" for="payment_type_radio" style="margin-left: 25px;font-size: 18px; line-height: 25px; font-weight: 400;">
                                                Pay by credit card
                                            </label>
                                        </div>
                                    </div>
                                    <div class="panel-body shipping-address">
                                         <div class="">
                                            <label class="control-label">Default Billing Address *</label>
                                        </div>
                                        <div class="form-group" style="border: 2px dotted #EF457A; padding: 10px; background: #f1f1f1;">
                                            <input class="form-check-input" type="radio" name="primary_billing_radio" id="primary_billing_radio" checked value="'.$primary_billing_id.'" style="margin-left: 0px; margin-top: 0px;">
                                            <label class="form-check-label control-label" for="primary_billing_radio" style="margin-left: 25px;font-size: 18px; line-height: 25px; font-weight: 400;">
                                                '.$primary_billing_add.'
                                            </label>
                                            <a id="edit_billing_address" class="btn btn-primary" style="background: #189DD8; color: #fff;">Create New Billing Address</a>
                                        </div>
                                    </div>
                                    <div class="panel-body" id="display_billing_address_form" style="border: 2px dotted #EF457A; padding: 10px; background: rgba(241, 241, 241, 0.42); display:none;" >
                                        <div class="form-group">
                                            <input class="form-check-input" type="radio" name="create_billing_radio" id="create_billing_radio" checked value="Yes" style="margin-left: 0px; margin-top: 3px;">
                                            <label class="form-check-label control-label" for="create_billing_radio" style="margin-left: 25px;font-size: 18px; line-height: 25px; font-weight: 400;">
                                                Create Billing Address
                                            </label>
                                        </div>
                                        <div class="form-group">
                                            <label class="control-label">Full Name *</label>
                                            <input type="text" id="billingFullName" required="required" class="form-control" placeholder="Enter Full Name" />
                                        </div>
                                        <div class="form-group">
                                            <label class="control-label">Address line 1 *</label>
                                            <input type="text" id="billingLine1" required="required" class="form-control" placeholder="Enter Address line 1" />
                                        </div>
                                        <div class="form-group">
                                            <label class="control-label">Address line 2 (Optional)</label>
                                            <input type="text" id="billingLine2" class="form-control" placeholder="Enter Address line 2" />
                                        </div>
                                        <div class="form-row">
                                            <div class="form-group col-md-6">
                                                <label class="control-label" for="billingCity">City *</label>
                                                <input type="text" class="form-control" id="billingCity" required="required">
                                            </div>
                                            <div class="form-group col-md-6">
                                                <label class="control-label" for="billingZip">Zip *</label>
                                                <input type="text" class="form-control" id="billingZip" required="required">
                                            </div>
                                        </div>
                                        <div class="form-row">
                                            <div class="form-group col-md-6">
                                                <label class="control-label" for="billingState">State *</label>
                                                <select id="billingState" class="form-control" required="required">
                                                    <option value="">Select your state</option><option value="AL">Alabama</option><option value="AK">Alaska</option><option value="AZ">Arizona</option><option value="AR">Arkansas</option><option value="CA">California</option><option value="CO">Colorado</option><option value="CT">Connecticut</option><option value="DE">Delaware</option><option value="DC">Washington, D.C.</option><option value="FL">Florida</option><option value="GA">Georgia</option><option value="HI">Hawaii</option><option value="ID">Idaho</option><option value="IL">Illinois</option><option value="IN">Indiana</option><option value="IA">Iowa</option><option value="KS">Kansas</option><option value="KY">Kentucky</option><option value="LA">Louisiana</option><option value="ME">Maine</option><option value="MD">Maryland</option><option value="MA">Massachusetts</option><option value="MI">Michigan</option><option value="MN">Minnesota</option><option value="MS">Mississippi</option><option value="MO">Missouri</option><option value="MT">Montana</option><option value="NE">Nebraska</option><option value="NV">Nevada</option><option value="NH">New Hampshire</option><option value="NJ">New Jersey</option><option value="NM">New Mexico</option><option value="NY">New York</option><option value="NC">North Carolina</option><option value="ND">North Dakota</option><option value="OH">Ohio</option><option value="OK">Oklahoma</option><option value="OR">Oregon</option><option value="PA">Pennsylvania</option><option value="PR">Puerto Rico</option><option value="RI">Rhode Island</option><option value="SC">South Carolina</option><option value="SD">South Dakota</option><option value="TN">Tennessee</option><option value="TX">Texas</option><option value="UT">Utah</option><option value="VT">Vermont</option><option value="VA">Virginia</option><option value="VI">Virgin Islands</option><option value="WA">Washington</option><option value="WV">West Virginia</option><option value="WI">Wisconsin</option><option value="WY">Wyoming</option>
                                                </select>
                                            </div>
                                            <div class="form-group col-md-6">
                                                <label class="control-label" for="billingCountry">Country *</label>
                                                <select id="billingCountry" class="form-control" required="required">
                                                    <option value="">Select your country</option><option value="AF">Afghanistan</option><option value="AX">Åland Islands</option><option value="AL">Albania</option><option value="DZ">Algeria</option><option value="AS">American Samoa</option><option value="AD">Andorra</option><option value="AO">Angola</option><option value="AI">Anguilla</option><option value="AQ">Antarctica</option><option value="AG">Antigua and Barbuda</option><option value="AR">Argentina</option><option value="AM">Armenia</option><option value="AW">Aruba</option><option value="AU">Australia</option><option value="AT">Austria</option><option value="AZ">Azerbaijan</option><option value="BS">Bahamas</option><option value="BH">Bahrain</option><option value="BD">Bangladesh</option><option value="BB">Barbados</option><option value="BY">Belarus</option><option value="BE">Belgium</option><option value="BZ">Belize</option><option value="BJ">Benin</option><option value="BM">Bermuda</option><option value="BT">Bhutan</option><option value="BO">Bolivia, Plurinational State of</option><option value="BQ">Bonaire, Sint Eustatius and Saba</option><option value="BA">Bosnia and Herzegovina</option><option value="BW">Botswana</option><option value="BV">Bouvet Island</option><option value="BR">Brazil</option><option value="IO">British Indian Ocean Territory</option><option value="BN">Brunei Darussalam</option><option value="BG">Bulgaria</option><option value="BF">Burkina Faso</option><option value="BI">Burundi</option><option value="CV">Cabo Verde</option><option value="KH">Cambodia</option><option value="CM">Cameroon</option><option value="CA">Canada</option><option value="KY">Cayman Islands</option><option value="CF">Central African Republic</option><option value="TD">Chad</option><option value="CL">Chile</option><option value="CN">China</option><option value="CX">Christmas Island</option><option value="CC">Cocos (Keeling) Islands</option><option value="CO">Colombia</option><option value="KM">Comoros</option><option value="CG">Congo</option><option value="CD">Congo, Democratic Republic of the</option><option value="CK">Cook Islands</option><option value="CR">Costa Rica</option><option value="HR">Croatia</option><option value="CU">Cuba</option><option value="CW">Curaçao</option><option value="CY">Cyprus</option><option value="CZ">Czechia</option><option value="CI">Côte d`Ivoire</option><option value="DK">Denmark</option><option value="DJ">Djibouti</option><option value="DM">Dominica</option><option value="DO">Dominican Republic</option><option value="EC">Ecuador</option><option value="EG">Egypt</option><option value="SV">El Salvador</option><option value="GQ">Equatorial Guinea</option><option value="ER">Eritrea</option><option value="EE">Estonia</option><option value="SZ">Eswatini</option><option value="ET">Ethiopia</option><option value="FK">Falkland Islands (Malvinas)</option><option value="FO">Faroe Islands</option><option value="FJ">Fiji</option><option value="FI">Finland</option><option value="FR">France</option><option value="GF">French Guiana</option><option value="PF">French Polynesia</option><option value="TF">French Southern Territories</option><option value="GA">Gabon</option><option value="GM">Gambia</option><option value="GE">Georgia</option><option value="DE">Germany</option><option value="GH">Ghana</option><option value="GI">Gibraltar</option><option value="GR">Greece</option><option value="GL">Greenland</option><option value="GD">Grenada</option><option value="GP">Guadeloupe</option><option value="GU">Guam</option><option value="GT">Guatemala</option><option value="GG">Guernsey</option><option value="GN">Guinea</option><option value="GW">Guinea-Bissau</option><option value="GY">Guyana</option><option value="HT">Haiti</option><option value="HM">Heard Island and McDonald Islands</option><option value="VA">Holy See</option><option value="HN">Honduras</option><option value="HK">Hong Kong</option><option value="HU">Hungary</option><option value="IS">Iceland</option><option value="IN">India</option><option value="ID">Indonesia</option><option value="IR">Iran, Islamic Republic of</option><option value="IQ">Iraq</option><option value="IE">Ireland</option><option value="IM">Isle of Man</option><option value="IL">Israel</option><option value="IT">Italy</option><option value="JM">Jamaica</option><option value="JP">Japan</option><option value="JE">Jersey</option><option value="JO">Jordan</option><option value="KZ">Kazakhstan</option><option value="KE">Kenya</option><option value="KI">Kiribati</option><option value="KP">Korea, Democratic People`s Republic of</option><option value="KR">Korea, Republic of</option><option value="KW">Kuwait</option><option value="KG">Kyrgyzstan</option><option value="LA">Lao People`s Democratic Republic</option><option value="LV">Latvia</option><option value="LB">Lebanon</option><option value="LS">Lesotho</option><option value="LR">Liberia</option><option value="LY">Libya</option><option value="LI">Liechtenstein</option><option value="LT">Lithuania</option><option value="LU">Luxembourg</option><option value="MO">Macao</option><option value="MG">Madagascar</option><option value="MW">Malawi</option><option value="MY">Malaysia</option><option value="MV">Maldives</option><option value="ML">Mali</option><option value="MT">Malta</option><option value="MH">Marshall Islands</option><option value="MQ">Martinique</option><option value="MR">Mauritania</option><option value="MU">Mauritius</option><option value="YT">Mayotte</option><option value="MX">Mexico</option><option value="FM">Micronesia, Federated States of</option><option value="MD">Moldova, Republic of</option><option value="MC">Monaco</option><option value="MN">Mongolia</option><option value="ME">Montenegro</option><option value="MS">Montserrat</option><option value="MA">Morocco</option><option value="MZ">Mozambique</option><option value="MM">Myanmar</option><option value="NA">Namibia</option><option value="NR">Nauru</option><option value="NP">Nepal</option><option value="NL">Netherlands</option><option value="NC">New Caledonia</option><option value="NZ">New Zealand</option><option value="NI">Nicaragua</option><option value="NE">Niger</option><option value="NG">Nigeria</option><option value="NU">Niue</option><option value="NF">Norfolk Island</option><option value="MK">North Macedonia</option><option value="MP">Northern Mariana Islands</option><option value="NO">Norway</option><option value="OM">Oman</option><option value="PK">Pakistan</option><option value="PW">Palau</option><option value="PS">Palestine, State of</option><option value="PA">Panama</option><option value="PG">Papua New Guinea</option><option value="PY">Paraguay</option><option value="PE">Peru</option><option value="PH">Philippines</option><option value="PN">Pitcairn</option><option value="PL">Poland</option><option value="PT">Portugal</option><option value="PR">Puerto Rico</option><option value="QA">Qatar</option><option value="RO">Romania</option><option value="RU">Russian Federation</option><option value="RW">Rwanda</option><option value="RE">Réunion</option><option value="BL">Saint Barthélemy</option><option value="SH">Saint Helena, Ascension and Tristan da Cunha</option><option value="KN">Saint Kitts and Nevis</option><option value="LC">Saint Lucia</option><option value="MF">Saint Martin (French part)</option><option value="PM">Saint Pierre and Miquelon</option><option value="VC">Saint Vincent and the Grenadines</option><option value="WS">Samoa</option><option value="SM">San Marino</option><option value="ST">Sao Tome and Principe</option><option value="SA">Saudi Arabia</option><option value="SN">Senegal</option><option value="RS">Serbia</option><option value="SC">Seychelles</option><option value="SL">Sierra Leone</option><option value="SG">Singapore</option><option value="SX">Sint Maarten (Dutch part)</option><option value="SK">Slovakia</option><option value="SI">Slovenia</option><option value="SB">Solomon Islands</option><option value="SO">Somalia</option><option value="ZA">South Africa</option><option value="GS">South Georgia and the South Sandwich Islands</option><option value="SS">South Sudan</option><option value="ES">Spain</option><option value="LK">Sri Lanka</option><option value="SD">Sudan</option><option value="SR">Suriname</option><option value="SJ">Svalbard and Jan Mayen</option><option value="SE">Sweden</option><option value="CH">Switzerland</option><option value="SY">Syrian Arab Republic</option><option value="TW">Taiwan, Province of China</option><option value="TJ">Tajikistan</option><option value="TZ">Tanzania, United Republic of</option><option value="TH">Thailand</option><option value="TL">Timor-Leste</option><option value="TG">Togo</option><option value="TK">Tokelau</option><option value="TO">Tonga</option><option value="TT">Trinidad and Tobago</option><option value="TN">Tunisia</option><option value="TR">Turkey</option><option value="TM">Turkmenistan</option><option value="TC">Turks and Caicos Islands</option><option value="TV">Tuvalu</option><option value="UG">Uganda</option><option value="UA">Ukraine</option><option value="AE">United Arab Emirates</option><option value="GB">United Kingdom</option><option value="UM">United States Minor Outlying Islands</option><option value="US">United States</option><option value="UY">Uruguay</option><option value="UZ">Uzbekistan</option><option value="VU">Vanuatu</option><option value="VE">Venezuela, Bolivarian Republic of</option><option value="VN">Vietnam</option><option value="VG">Virgin Islands, British</option><option value="VI">Virgin Islands, U.S.</option><option value="WF">Wallis and Futuna</option><option value="EH">Western Sahara</option><option value="YE">Yemen</option><option value="ZM">Zambia</option><option value="ZW">Zimbabwe</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="form-row">
                                            <div class="form-group col-md-12">
                                                <input type="hidden" id="existing_client_id" value="'.$c_api_clientid.'">
                                                <button id="billing_address_create_btn" class="btn btn-primary nextBtn pull-left" type="button">Save</button>
                                                <img class="ajax_loader_img" src="'.$GLOBALS['plugin_dir_url'].'/ajax-loader.gif" style="width: 100px;float: left; display:none;" />
                                            </div>
                                        </div>
                                    </div>
                                    <br/>
                                    
                                    <button id="billing_button" class="btn btn-primary nextBtn pull-right" type="button">Continue Next</button>
                                    <img class="ajax_loader_img" src="'.$GLOBALS['plugin_dir_url'].'/ajax-loader.gif" style="width: 100px;float: right; display:none;" />
                                </div>
                            </div>
                        </form>
                            
                        <div class="panel panel-primary setup-content" id="step-4" style="display:none;">';
                        
                        $square_client = new SquareClient([
                          'accessToken' => $GLOBALS['squareAccessToken'],
                          'environment' => $GLOBALS['squareEnvironment']
                        ]);
                        $square_retrive_card = "";
                        $saved_square_card_id = "";
                        $card_heading = 'Pay With Card';
                        if(isset($logged_user_id) && !empty($logged_user_id)){
                            $saved_square_card_id = get_user_meta( $logged_user_id, 'square_card_id', true );
                            $square_retrive_card = $square_client->getCardsApi()->retrieveCard($saved_square_card_id);
                            $card_heading = ($square_retrive_card->isSuccess())? 'Your Card On File' : 'Pay With Card';
                        }
                        $html .= '
                            <div class="panel-heading">
                                 <h3 class="panel-title">'.$card_heading.'</h3>
                            </div>
                            <div class="panel-body">
                                <div class="form-group">
                                    <form id="payment-form">';
                                        if (!empty($square_retrive_card) && !empty($saved_square_card_id)) {
                                            //echo "<pre>"; print_r($square_retrive_card);
                                            $cardId = $square_retrive_card->getResult()->getCard()->getId();
                                            $cardBrand = $square_retrive_card->getResult()->getCard()->getCardBrand();
                                            $last4 = $square_retrive_card->getResult()->getCard()->getLast4();
                                            $expMonth = $square_retrive_card->getResult()->getCard()->getExpMonth();
                                            $expYear = $square_retrive_card->getResult()->getCard()->getExpYear();
                                            $cardholderName = $square_retrive_card->getResult()->getCard()->getCardholderName();
                                            $html .= '
                                                <div class="form-group" style="border: 2px dotted #EF457A; padding: 10px; background: #f1f1f1;">
                                                    <input class="form-check-input" type="radio" name="saved_card_onfile_radio" id="saved_card_onfile_radio" checked value="'.$cardId.'" style="margin-left: 0px; margin-top: 0px;">
                                                    <label class="form-check-label control-label" for="saved_card_onfile_radio" style="margin-left: 25px;font-size: 18px; line-height: 25px; font-weight: 400;">
                                                        <b>'.$cardholderName.'</b><br>'.$cardBrand.'<br> <b>Ending:</b> '.$last4.'<br> <b>Expiry:</b> '.$expMonth.', '.$expYear.'
                                                    </label>
                                                </div>
                                                <div id="card-container" style="display:none;"></div>
                                                <button id="card-button" type="button" class="btn btn-primary nextBtn saved_card_button">Pay $'. ($listingSearch["retail_price"]*$ticketQty) .'</button>
                                                <div id="payment-status-msg" style="font-size: 20px;border: 1px solid #EF457A;padding: 5px;text-align: left; color: #000; margin-top: 10px; display:none;"></div>
                                            ';
                                        } else {
                                            $html .= '    
                                            <div id="card-container"></div>
                                            <label class="control-label" id="card-container-label" for="square_save_card"><input type="checkbox" name="square_save_card" id="square_save_card" value="1" checked /> Save this card for future use.</label>
                                            <button id="card-button" type="button" class="btn btn-primary nextBtn">Pay $'. ($listingSearch["retail_price"]*$ticketQty) .'</button>
                                            <div id="payment-status-msg" style="font-size: 20px;border: 1px solid #EF457A;padding: 5px;text-align: left; color: #000; margin-top: 10px; display:none;"></div>
                                            ';
                                        }
                                        
                                    $html .= '
                                        <input type="hidden" name="ticket_amount" id="ticket_amount" value="'.$listingSearch["retail_price"].'" />
                                        <input type="hidden" name="ticket_qty" id="ticket_qty" value="'.$ticketQty.'" />
                                        <img class="ajax_loader_img" src="'. $GLOBALS['plugin_dir_url'] .'/ajax-loader.gif" style="width: 100px;float: left; display:none;" />
                                    </form>
                                    
                                </div>
                            </div>
                        </div>';
            } else {
                $html .= 'Please Login as customer to proceed with checkout!';
            }
            $html .= '</div>';
            
            $html .= '<div class="myticketslist col-lg-4 pt-3">';
            $html .= '<div class="container">';
            $html .= '<div class="row">';
            $html .= '<div class="col-12 ticket_detail">';
            if(is_user_logged_in()){
                if(isset($_REQUEST['_wpnonce'])){
                    $nonce = isset($_REQUEST['_wpnonce'])? sanitize_text_field(wp_unslash($_REQUEST['_wpnonce'])) : '';
                    if (!wp_verify_nonce($nonce, 'events_list')) {
                        wp_die('Invalid nonce verification.');
                    }
                }
        		$ticket_qty = isset($_REQUEST['ticket_qty'])? sanitize_text_field(wp_unslash($_REQUEST['ticket_qty'])) : '';
        		$listingId = isset($_REQUEST['listingId'])? sanitize_text_field(wp_unslash($_REQUEST['listingId'])) : '';
        		$eventId = isset($_REQUEST['eventId'])? (int) sanitize_text_field(wp_unslash($_REQUEST['eventId'])) : '';
        		$venueId = isset($_REQUEST['venueId'])? sanitize_text_field(wp_unslash($_REQUEST['venueId'])) : '';
        		$configurationId = isset($_REQUEST['configurationId'])? sanitize_text_field(wp_unslash($_REQUEST['configurationId'])) : '';
        		$pageTitle = isset($_REQUEST['pageTitle'])? sanitize_text_field(wp_unslash($_REQUEST['pageTitle'])) : '';
        		
                $usrobj = wp_get_current_user();
                $customLogoutUrl = home_url('ticket-checkout/?ticket_qty='.$ticket_qty.'&listingId='.$listingId.'&eventId='.$eventId.'&venueId='.$venueId.'&configurationId='.$configurationId.'&pageTitle='.$pageTitle);
                $html .= '<div class="black">';
                $html .= '<h5 class="black">Hi '. $usrobj->data->user_login .'! (<a href="'. wp_logout_url($customLogoutUrl) .'" class="link">Logout</a>) - <a href="'. home_url('orders-history') .'">View Orders</a></h5>';
                $html .= '</div>';
            }
            $html .= '<h5 class="black">'.$eventName.'</h5>';
            $html .= '<label class="black" style="font-weight: bold;">'.$venueName.'</label>';
            $html .= '<p class="black">'.$venueFullAddress.'</p>';
            $html .= '<h5 class="black">Ticket Details</h5>';
            $html .= '<p class="black"><b>Name:</b> '.ucwords($listingSearch["tevo_section_name"]).'</p>';
            $html .= '<p class="black"><b>Section:</b> '.$listingSearch["section"].'</p>';
            $html .= '<p class="black"><b>Row:</b> '.$listingSearch["row"].'</p>';
            $html .= '<p class="black"><small style="color:cccccc;"><b>Note:</b> '.$listingSearch["public_notes"].'</small></p>';
            $html .= '<p class="black"><b>Price:</b> $'.$listingSearch["retail_price"].' each</p>';
            $html .= '<p class="black"><b>Quantity:</b> '.$ticketQty.'</p>';
            if($listingSearch["format"] == "TM_mobile"){
                $html .= '<p class="black"><b>TM Mobile Entry (Mobile Transfer):</b><br>When the seller transfers the tickets to you, you will receive an email instructing you how to claim the tickets on your phone. Please note that you will need to use an iOS or Android mobile device to gain entry to your event.</p>';
            }
            if($listingSearch["format"] == "Flash_seats"){
                $html .= '<p class="black"><b>Flash Seats™ (Mobile Transfer):</b><br>When the seller transfers the tickets to you, you will receive an email instructing you how to claim the tickets on your phone. Please note that you will need to use an iOS or Android mobile device to gain entry to your event.</p>';
            }
            if($listingSearch["format"] == "Eticket"){
                $html .= '<p class="black"><b>Print at Home (E-ticket):</b><br>When the seller transfers the tickets to you, you will receive an email instructing you how to claim the tickets on your phone. Please note that you will need to use an iOS or Android mobile device to gain entry to your event.</p>';
            }
            if($listingSearch["format"] == "Physical"){
                $html .= '<p class="black"><b>Physical (FedEx):</b><br>When using FedEx as the shipment type, We will use shipment information to ship the tickets directly to the buyer using the shipping address provided.</p>';
                $html .= '<p class="black"><b>Physical (Local Pickup):</b><br>If it is too late to ship the tickets or the tickets are not able to be shipped for another reason your Buer will need to pick up the tickets near the venue. We will provide Buyer\'s information to the Seller and the location and the seller\'s representative’s name and phone number in case they need to contact each other.</p>';
            }
            if($listingSearch["format"] == "Paperless"){
                $html .= '<p class="black"><b>Physical (FedEx):</b><br>When using FedEx as the shipment type, We will use shipment information to ship the tickets directly to the buyer using the shipping address provided.</p>';
                $html .= '<p class="black"><b>Physical (Local Pickup):</b><br>If it is too late to ship the tickets or the tickets are not able to be shipped for another reason your Buer will need to pick up the tickets near the venue. We will provide Buyer\'s information to the Seller and the location and the seller\'s representative’s name and phone number in case they need to contact each other.</p>';
            }
            if($listingSearch["format"] == "Guest_list"){
                $html .= '<p class="black"><b>Guest List Ticket:</b><br>Guest List is used for exclusive parties such as some Super Bowl parties. The Guest’s name(s) will be added to a guest list and the guest must show a government issued photo ID in order to gain entry to the event.</p>';
            }
			 
            $html .= '</div>';
            $html .= '</div>';
            $html .= '</div>';
            $html .= '</div></div></div>';
            $html .= '<script type="text/javascript" src="'.$GLOBALS['squareJSFile'].'" id="squareJSFile-js"></script>';
            $html .= "<script>";
            $html .= "
						  
            const appId = '". $GLOBALS['squareupAppId'] ."';
            const locationId = '". $GLOBALS['squareupLocationId'] ."';
            
            jQuery(document).ready(function ($){
                $('body').on('change', '.account_check', function(){
                    if($(this).val() == 'new'){
                        $('.password_field').hide();
                    } else {
                        $('.password_field').show();
                    }
                });
            });

            // Required in SCA Mandated Regions: Learn more at https://developer.squareup.com/docs/sca-overview
            async function verifyBuyer(payments, token) {
                const verificationDetails = {
                  amount: '". ($listingSearch["retail_price"]*$ticketQty) ."',
                  billingContact: {
                    givenName: '". $client_first_name ."',
                    familyName: '". $client_last_name ."',
                    email: '".$primary_email ."',
                    phone: '".$primary_number ."',
                    addressLines: ['".$primary_billing_street."', '".$primary_billing_extended."'],
                    city: '". $primary_billing_locality ."',
                    state: '". $primary_billing_region ."',
                    countryCode: '". $primary_billing_country ."',
                  },
                  currencyCode: 'USD',
                  intent: 'CHARGE',
                };
                const verificationResults = await payments.verifyBuyer(
                  token,
                  verificationDetails,
                );
                return verificationResults.token;
            }
            ";
            $html .= "</script>";
        }
        
    } catch (\Exception $e) {
        echo 'Message: ' .esc_html( $e->getMessage() );
    }
    
    return $html ; 
}

function get_the_user_ip() {
    if ( ! empty( $_SERVER['HTTP_CLIENT_IP'] ) ) {
        //check ip from share internet
        $ip = sanitize_text_field(wp_unslash($_SERVER['HTTP_CLIENT_IP']));
    } elseif ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
        //to check ip is pass from proxy
        $ip = sanitize_text_field(wp_unslash($_SERVER['HTTP_X_FORWARDED_FOR']));
    } else {
        $ip = (isset($_SERVER['REMOTE_ADDR']))? sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR'])) : "";
    }
    
    return apply_filters( 'wpb_get_ip', $ip );
}

function user_has_role($user_id, $role_name){
    $user_meta = get_userdata($user_id);
    $user_roles = $user_meta->roles;
    return in_array($role_name, $user_roles);
}

// Dynamic Heading
add_shortcode('event_listing_title', 'callback_event_listing_title');
function callback_event_listing_title( $atts ) {
    if(isset($_REQUEST['_wpnonce'])){
        $nonce = isset($_REQUEST['_wpnonce'])? sanitize_text_field(wp_unslash($_REQUEST['_wpnonce'])) : '';
        if (!wp_verify_nonce($nonce, 'events_list')) {
            wp_die('Invalid nonce verification.');
        }
    }
	$title = isset($_REQUEST['pageTitle'])? sanitize_text_field(wp_unslash($_REQUEST['pageTitle'])) : '';
    
    return '<div class="heading-section-title  display-inline-block"><h2 class="heading-title">'.$title.'</h2></div>';
}

add_action('init', 'almonte_session_start');
function almonte_session_start() {
	if( !session_id() ){
		session_start();
	}
}

if (!function_exists('display_paginations')) {
    function display_paginations($total_pages, $page, $num_results_on_page, $queryParam="") {
        $result = "";
        if(isset($_REQUEST['_wpnonce'])){
            $nonce = isset($_REQUEST['_wpnonce'])? sanitize_text_field(wp_unslash($_REQUEST['_wpnonce'])) : '';
            if (!wp_verify_nonce($nonce, 'events_list')) {
                wp_die('Invalid nonce verification.');
            }
        }
        
        $_wpnonce = wp_create_nonce('events_list');
		$action = "listing";
		$params = "&action=".$action."&_wpnonce=".$_wpnonce;
		
		if( is_admin() && ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX ) ) {
			unset($_SESSION['pagination_full_url']);
		}
		
		if(!empty($_SESSION['pagination_full_url'])){
		    $full_url = sanitize_text_field(wp_unslash($_SESSION['pagination_full_url']));
		} else {
		    $REQUEST_URI = isset($_SERVER['REQUEST_URI'])? sanitize_text_field(wp_unslash($_SERVER['REQUEST_URI'])) : "";
		    $website_url = isset($GLOBALS['website_url'])? sanitize_text_field(wp_unslash($GLOBALS['website_url'])) : "";
			$_SESSION['pagination_full_url'] = $full_url = "https://".$website_url.$REQUEST_URI."&";
			$full_url = sanitize_text_field(wp_unslash($_SESSION['pagination_full_url']));
			
		}
		$search_field = isset($_REQUEST['search_field'])? sanitize_text_field(wp_unslash($_REQUEST['search_field'])) : "";
        $page_pram = isset($search_field)? "&search_field=".$search_field.$params : '';
		
        if(ceil($total_pages / $num_results_on_page) > 0){
            $result .= '<ul class="pagination">';
            if ($page > 1){
                $result .= '<li class="paginate_button page-item previous" data-page="'. ($page-1) .'"><a class="page-link" href="'.$full_url.'pageNum='.($page-1).$page_pram.'"><i class="fa fa-angle-left"></i></a></li>';
            }
            if($page > 3){
                $result .= '<li class="paginate_button page-item start" data-page="1"><a class="page-link" href="'.$full_url.'pageNum=1">1</a></li>';
                $result .= '<li class="dots";>- - -</li>';
            }
            if($page-2 > 0){
                $result .= '<li class="paginate_button page-item" data-page="'. ($page-2) .'"><a class="page-link" href="'.$full_url.'pageNum='.($page-2).$page_pram.'">'. ($page-2) .'</a></li>';
            }
            if($page-1 > 0){
                $result .= '<li class="paginate_button page-item" data-page="'. ($page-1) .'"><a class="page-link" href="'.$full_url.'pageNum='.($page-1).$page_pram.'">'. ($page-1) .'</a></li>';
            }
            $result .= '<li class="paginate_button page-item active" data-page="'. $page .'"><span class="page-link">'. $page .'</span></li>';
            if($page+1 < ceil($total_pages / $num_results_on_page)+1){
                $result .= '<li class="paginate_button page-item" data-page="'. ($page+1) .'"><a class="page-link" href="'.$full_url.'pageNum='.($page+1).$page_pram.'">'. ($page+1) .'</a></li>';
            }
            if ($page+2 < ceil($total_pages / $num_results_on_page)+1){
                $result .= '<li class="paginate_button page-item" data-page="'. ($page+2) .'"><a class="page-link" href="'.$full_url.'pageNum='.($page+2).$page_pram.'">'. ($page+2) .'</a></li>';
            }
            if($page < ceil($total_pages / $num_results_on_page)-2){
                $result .= '<li class="dots";>- - -</li>';
                $result .= '<li class="paginate_button page-item end" data-page="'. ceil($total_pages / $num_results_on_page) .'"><a class="page-link" href="'.$full_url.'pageNum='.ceil($total_pages / $num_results_on_page).$page_pram.'">'. ceil($total_pages / $num_results_on_page) .'</a></li>';
            }
            if ($page < ceil($total_pages / $num_results_on_page)){
                $result .= '<li class="paginate_button page-item next" data-page="'. ($page+1) .'"><a class="page-link" href="'.$full_url.'pageNum='.($page+1).$page_pram.'"><i class="fa fa-angle-right"></i></a></li>';
            }
            $result .= '</ul>';
        }
        return $result;
    }
}