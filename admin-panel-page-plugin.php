<?php
defined('ABSPATH')  || die('Unauthorized Access');
require 'vendor/autoload.php';

// Register settings
function register_site_settings() {
    // Register a setting and its sanitization callback
    register_setting('site_settings_group', 'SandboxAPIToken', 'sanitize_text_field');
    register_setting('site_settings_group', 'SandboxAPISecret', 'sanitize_text_field');
	register_setting('site_settings_group', 'SandboxOfficeId', 'sanitize_text_field');
	register_setting('site_settings_group', 'ProductionAPIToken', 'sanitize_text_field');
	register_setting('site_settings_group', 'ProductionAPISecret', 'sanitize_text_field');
	register_setting('site_settings_group', 'ProductionOfficeId', 'sanitize_text_field');
	
	register_setting('site_settings_group', 'SitePesultsPerPage', 'sanitize_text_field');
	
	register_setting('site_settings_group', 'SandboxSquareupAppId', 'sanitize_text_field');
	register_setting('site_settings_group', 'SandboxSquareAccessToken', 'sanitize_text_field');
	register_setting('site_settings_group', 'SandboxSquareupLocationId', 'sanitize_text_field');
	register_setting('site_settings_group', 'LiveSquareupAppId', 'sanitize_text_field');
	register_setting('site_settings_group', 'LiveSquareAccessToken', 'sanitize_text_field');
	register_setting('site_settings_group', 'LiveSquareupLocationId', 'sanitize_text_field');
}
add_action('admin_init', 'register_site_settings');

// Content for the custom page
function callback_events_ticket_settings() {
    wp_enqueue_style('bootstrap5-css', plugins_url('css/bootstrap.min.css', __FILE__), [], '5.3.3');
    //wp_register_script('jquery-37', plugins_url('js/jquery-3.7.1.min.js', __FILE__), array(), '3.7.1', true);
    wp_enqueue_script( 'jquery' );
    wp_enqueue_script('bootstrap5-js', plugins_url('js/bootstrap.min.js', __FILE__), ['jquery'], '5.3.3', true);
?>



<script type="text/javascript">
	jQuery( document ).ready(function($) {
		$('#nav-tab-credentials a').on('click', function (e) {
			e.preventDefault();
			var TabTriggerEl = $(this);
            var tab = new bootstrap.Tab(TabTriggerEl);
            tab.show();
			//$(this).tab('show');
		});
		
		$('#nav-tab-squareup a').on('click', function (e) {
			e.preventDefault();
			var TabTriggerEl = $(this);
            var tab = new bootstrap.Tab(TabTriggerEl);
            tab.show();
			//$(this).tab('show');
		});
	});
</script>
<?php 
	$is_license_verified = get_option(PLUGIN_LICENSE_VERIFIED);
	if($is_license_verified){
?>
<form name="ticketevolution" method="post" action="options.php">
	<div class="container pt-5">
		<fieldset style="border: 2px solid #000; border-radius: 5px; border-style: dashed; margin-bottom: 20px; padding: 10px;">
			<div class="row">
				<div class="col-sm">
					<h2>TicketEvolution API Settings</h2>
					<p>To get API credentials, You need to create an account of <a href="https://www.ticketevolution.com/contact-us/?prefill=1" target="_blank">ticketevolution.com</a>. Contact support to create account.</p>
				</div>
			</div>
			<div class="row">
				<div class="col-sm">
					<?php settings_fields('site_settings_group'); ?>
					<?php do_settings_sections('site_settings_group'); ?>
					
					<nav>
						<div class="nav nav-tabs" id="nav-tab-credentials" role="tablist">
							<a class="nav-item nav-link active" id="ticketevolution-sandbox-tab" data-toggle="tab" href="#ticketevolution-sandbox" role="tab" aria-controls="ticketevolution-sandbox" aria-selected="true">SandBox</a>
							<a class="nav-item nav-link" id="ticketevolution-production-tab" data-toggle="tab" href="#ticketevolution-production" role="tab" aria-controls="ticketevolution-production" aria-selected="false">Production</a>
						</div>
					</nav>
					<div class="tab-content" id="nav-tabContent">
						<div class="tab-pane fade show active p-4" id="ticketevolution-sandbox" role="tabpanel" aria-labelledby="ticketevolution-sandbox-tab">
							<div class="form-group row">
								<label for="SandboxAPIToken" class="col-sm-2 col-form-label"><strong>API Token</strong></label>
								<div class="col-sm-10">
									<input type="text" class="form-control-plaintext" name="SandboxAPIToken" id="SandboxAPIToken" value="<?php echo esc_attr(get_option('SandboxAPIToken')); ?>">
								</div>
							</div>
							<div class="form-group row">
								<label for="SandboxAPISecret" class="col-sm-2 col-form-label"><strong>API Secret</strong></label>
								<div class="col-sm-10">
									<input type="text" class="form-control-plaintext" name="SandboxAPISecret" id="SandboxAPISecret" value="<?php echo esc_attr(get_option('SandboxAPISecret')); ?>">
								</div>
							</div>
							<div class="form-group row">
								<label for="SandboxOfficeId" class="col-sm-2 col-form-label"><strong>Sandbox Office Id</strong></label>
								<div class="col-sm-10">
									<input type="text" class="form-control-plaintext" name="SandboxOfficeId" id="SandboxOfficeId" value="<?php echo esc_attr(get_option('SandboxOfficeId')); ?>">
								</div>
							</div>
						</div>
						<div class="tab-pane fade p-4" id="ticketevolution-production" role="tabpanel" aria-labelledby="ticketevolution-production-tab">
							<div class="form-group row">
								<label for="ProductionAPIToken" class="col-sm-2 col-form-label"><strong>API Token</strong></label>
								<div class="col-sm-10">
									<input type="text" class="form-control-plaintext" name="ProductionAPIToken" id="ProductionAPIToken" value="<?php echo esc_attr(get_option('ProductionAPIToken')); ?>">
								</div>
							</div>
							<div class="form-group row">
								<label for="ProductionAPISecret" class="col-sm-2 col-form-label"><strong>API Secret</strong></label>
								<div class="col-sm-10">
									<input type="text" class="form-control-plaintext" name="ProductionAPISecret" id="ProductionAPISecret" value="<?php echo esc_attr(get_option('ProductionAPISecret')); ?>">
								</div>
							</div>
							<div class="form-group row">
								<label for="ProductionOfficeId" class="col-sm-2 col-form-label"><strong>Production Office Id</strong></label>
								<div class="col-sm-10">
									<input type="text" class="form-control-plaintext" name="ProductionOfficeId" id="ProductionOfficeId" value="<?php echo esc_attr(get_option('ProductionOfficeId')); ?>">
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</fieldset>

		<fieldset style="border: 2px solid #000; border-radius: 5px; border-style: dashed; margin-bottom: 20px; padding: 10px;">
			<div class="row">
				<div class="col-sm">
					<h3>TicketEvolution Other Settings</h3>
					<div class="form-group row">
						<label for="resultPerPage" class="col-sm-2 col-form-label"><strong>Domain</strong></label>
						<div class="col-sm-10">
							<?php echo esc_html( $GLOBALS['website_url'] ); ?>
						</div>
					</div>
					<div class="form-group row">
						<label for="SitePesultsPerPage" class="col-sm-2 col-form-label"><strong>Result Per Page</strong></label>
						<div class="col-sm-10">
							<input type="text" class="form-control-plaintext" name="SitePesultsPerPage" id="SitePesultsPerPage" value="<?php echo esc_attr(get_option('SitePesultsPerPage')); ?>">
						</div>
					</div>
				</div>
			</div>
		</fieldset>

		<fieldset style="border: 2px solid #000; border-radius: 5px; border-style: dashed; margin-bottom: 20px; padding: 10px;">
			<div class="row">
				<div class="col-sm">
					<h3>Squareup API Settings</h3>
					<nav>
						<div class="nav nav-tabs" id="nav-tab-squareup" role="tablist">
							<a class="nav-item nav-link active" id="nav-squareup-sandbox" data-toggle="tab" href="#squareup-sandbox" role="tab" aria-controls="squareup-sandbox" aria-selected="true">SandBox</a>
							<a class="nav-item nav-link" id="nav-squareup-live" data-toggle="tab" href="#squareup-live" role="tab" aria-controls="squareup-live" aria-selected="false">Production</a>
						</div>
					</nav>
					<div class="tab-content" id="nav-tabContent">
						<div class="tab-pane fade show active p-4" id="squareup-sandbox" role="tabpanel" aria-labelledby="nav-home-tab">
							<div class="form-group row">
								<label for="SandboxSquareupAppId" class="col-sm-2 col-form-label"><strong>App ID</strong></label>
								<div class="col-sm-10">
									<input type="text" class="form-control-plaintext" name="SandboxSquareupAppId" id="SandboxSquareupAppId" value="<?php echo esc_attr(get_option('SandboxSquareupAppId')); ?>">
								</div>
							</div>
							<div class="form-group row">
								<label for="SandboxSquareAccessToken" class="col-sm-2 col-form-label"><strong>Access Token</strong></label>
								<div class="col-sm-10">
									<input type="text" class="form-control-plaintext" name="SandboxSquareAccessToken" id="SandboxSquareAccessToken" value="<?php echo esc_attr(get_option('SandboxSquareAccessToken')); ?>">
								</div>
							</div>
							<div class="form-group row">
								<label for="SandboxSquareupLocationId" class="col-sm-2 col-form-label"><strong>Location Id</strong></label>
								<div class="col-sm-10">
									<input type="text" class="form-control-plaintext" name="SandboxSquareupLocationId" id="SandboxSquareupLocationId" value="<?php echo esc_attr(get_option('SandboxSquareupLocationId')); ?>">
								</div>
							</div>
						</div>
						<div class="tab-pane fade p-4" id="squareup-live" role="tabpanel" aria-labelledby="nav-profile-tab">
							<div class="form-group row">
								<label for="LiveSquareupAppId" class="col-sm-2 col-form-label"><strong>App ID</strong></label>
								<div class="col-sm-10">
									<input type="text" class="form-control-plaintext" name="LiveSquareupAppId" id="LiveSquareupAppId" value="<?php echo esc_attr(get_option('LiveSquareupAppId')); ?>">
								</div>
							</div>
							<div class="form-group row">
								<label for="LiveSquareAccessToken" class="col-sm-2 col-form-label"><strong>Access Token</strong></label>
								<div class="col-sm-10">
									<input type="text" class="form-control-plaintext" name="LiveSquareAccessToken" id="LiveSquareAccessToken" value="<?php echo esc_attr(get_option('LiveSquareAccessToken')); ?>">
								</div>
							</div>
							<div class="form-group row">
								<label for="LiveSquareupLocationId" class="col-sm-2 col-form-label"><strong>Location Id</strong></label>
								<div class="col-sm-10">
									<input type="text" class="form-control-plaintext" name="LiveSquareupLocationId" id="LiveSquareupLocationId" value="<?php echo esc_attr(get_option('LiveSquareupLocationId')); ?>">
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</fieldset>
		
		<button type="submit" name="TicketEvolutionBtn" class="btn btn-primary"> Save Changes </button>
	</div>
</form>

    <?php
} else {
		echo "<br><br><h1><b>Verify Your License Key First! <a href='admin.php?page=live-events-verify'>Click Here</a> to Verify!</b></h1>";
}
	
}
