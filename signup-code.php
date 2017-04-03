<?php
/*
Plugin Name: Signup Code
Plugin URI: http://premium.wpmudev.org/project/signup-code
Description: Limit who can sign up for a blog or user account at your site by requiring a special code that you can easily configure yourself
Author: WPMU DEV
Version: 1.0.3.3
Author URI: http://premium.wpmudev.org
Network: true
WDP ID: 98
Text Domain: signup_code
*/

/*
Copyright 2007-2009 Incsub (http://incsub.com)
Author - S H Mohanjith (Incsub)
Contributors - Andrew Billits (Incsub)

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License (Version 2 - GPLv2) as published by
the Free Software Foundation.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/

//------------------------------------------------------------------------//
//---Hook-----------------------------------------------------------------//
//------------------------------------------------------------------------//
add_action('init', 'signup_code_init');
add_action('network_admin_menu', 'signup_code_plug_pages');
add_action('signup_extra_fields', 'signup_code_field_wpmu');
add_action('bp_after_account_details_fields', 'signup_code_field_bp');
add_filter('wpmu_validate_user_signup', 'signup_code_filter_wpmu');
add_filter('bp_signup_validate', 'signup_code_filter_bp');

//------------------------------------------------------------------------//
//---Functions------------------------------------------------------------//
//------------------------------------------------------------------------//

function signup_code_init() {
	if ( ! is_multisite() )
		add_action( 'admin_notices', 'signup_code_display_not_multisite_admin_notice' );

	load_plugin_textdomain('signup_code', false, dirname(plugin_basename(__FILE__)).'/languages');
}

function signup_code_plug_pages() {

	if ( ! is_network_admin() )
		return;
	
	$page_id = add_submenu_page(
		'settings.php',
		__('Signup Code', 'signup_code'),
		__('Signup Code', 'signup_code'),
		'manage_network_options',
		'signup_code',
		'signup_code_site_admin_options'
	);
	

	add_action( 'load-' . $page_id, 'signup_code_sanitize_options_form' );

}



function signup_code_display_not_multisite_admin_notice() {
	?>
	    <div class="error">
	        <p><?php _e( 'The Signup Code plugin is only compatible with WordPress Multisite.', 'signup_code' ); ?></p>
	    </div>
    <?php
}

//------------------------------------------------------------------------//
//---Output Functions-----------------------------------------------------//
//------------------------------------------------------------------------//


function signup_code_site_admin_options() {
	if( ! current_user_can( 'manage_network_options' ) ) {
		echo "<p>" . __( 'Nice Try...', 'signup_code' ) . "</p>";  //If accessed properly, this message doesn't appear.
		wp_die();
	}

	if ( isset( $_GET['updated'] ) ) {
		?>
			<div id="message" class="updated fade">
				<p><?php _e('Changes saved.', 'signup_code'); ?></p>
			</div>
		<?php
	}

	?>
		<div class="wrap">
			<h2><?php echo esc_html( get_admin_page_title() ); ?></h2>
			<form method="post" action="">
				<table class="form-table">
					<tr valign="top">
						<th scope="row"><?php _e('Code', 'signup_code') ?></th>
						<td><input name="signup_code" class="large-text" type="text" id="signup_code" value="<?php echo get_site_option('signup_code'); ?>"/>
							<br />
							<span class="description"><?php _e('Users must enter this code in order to signup. Letters and numbers only.', 'signup_code') ?></span>
						</td>
					</tr>
					<tr valign="top">
						<th scope="row"><?php _e('Signup Code Branding', 'signup_code') ?></th>
						<td><input name="signup_code_branding" class="large-text" type="text" id="signup_code_branding" value="<?php echo get_site_option('signup_code_branding', 'Signup Code'); ?>"/>
							<br />
							<span class="description"><?php _e('This is the text that will be displayed on the signup form. Ex: Invite Code', 'signup_code') ?></span>
						</td>
					</tr>
				</table>

				<?php wp_nonce_field( 'signup-code-settings' ); ?>

				<p class="submit">
					<?php submit_button( null, 'primary', 'submit-signup-code-settings', false ); ?>
					<?php submit_button( __( 'Reset', 'signup_code'), 'secondary', 'reset-signup-code-settings', false ); ?>
				</p>
        	</form>
		</div>
	<?php
}

function signup_code_sanitize_options_form() {
	if ( empty( $_POST['submit-signup-code-settings'] ) && empty( $_POST['reset-signup-code-settings'] ) )
		return;

	check_admin_referer( 'signup-code-settings' );

	if ( ! empty( $_POST['submit-signup-code-settings'] ) ) {
		// Submitting
		$signup_code = stripslashes( sanitize_text_field( $_POST['signup_code'] ) );
		$signup_code_branding = stripslashes( sanitize_text_field( $_POST['signup_code_branding'] ) );
	}

	if ( ! empty( $_POST['reset-signup-code-settings'] ) ) {
		// Resetting
		$signup_code = '';
		$signup_code_branding = '';
	}

	update_site_option( 'signup_code', $signup_code );
	update_site_option( 'signup_code_branding', $signup_code_branding );

	$redirect = add_query_arg(
		array(
			'page' => 'signup_code',
			'updated' => 'true'
		),
		network_admin_url( 'settings.php' )
	);
	
	wp_redirect( $redirect );
	exit();


}

//------------------------------------------------------------------------//
//---Page Output Functions------------------------------------------------//
//------------------------------------------------------------------------//

function signup_code_field_wpmu( $errors ) {
	if ( ! empty( $errors ) )
		$error = $errors->get_error_message( 'signup_code' );
	else
		$error = false;

	$signup_code = get_site_option( 'signup_code' );

	if ( empty( $signup_code ) )
		return;

	$submitted_code = ! empty( $_POST['signup_code'] ) ? stripslashes( $_POST['signup_code'] ) : '';

	?>
		<label for="password"><?php echo get_site_option( 'signup_code_branding', 'Signup Code' ); ?>:</label>
		<?php if( $error ): ?>
				<p class="error"><?php echo esc_html( $error ); ?></p>
		<?php endif; ?>
	
		<input type="text" name="signup_code" id="signup_code" value="<?php echo $submitted_code; ?>" />

		<?php signup_code_display_css(); ?>
	<?php
}

function signup_code_field_bp() {
	
	$signup_code = get_site_option('signup_code');

	if ( empty( $signup_code ) )
		return;

	$submitted_code = ! empty( $_POST['signup_code'] ) ? stripslashes( $_POST['signup_code'] ) : '';
	?>
    	<div class="register-section" id="blog-details-section">
	    	<label for="password"><?php echo get_site_option('signup_code_branding', 'Signup Code'); ?>:</label>
			<?php do_action( 'bp_signup_code_errors' ) ?>
			<input type="text" name="signup_code" id="signup_code" value="<?php echo $submitted_code; ?>" />
	    </div>
	    <?php signup_code_display_css(); ?>
	<?php
}

function signup_code_display_css() {
	?>
		<style>
			.mu_register #signup_code { width:100%; font-size: 24px; margin:5px 0; }
		</style>
	<?php
}

function signup_code_filter_wpmu( $content ) {
	$signup_code = get_site_option( 'signup_code' );
	
	if ( empty( $signup_code ) )
		return $content;
	
	if ( ! isset( $_POST['signup_code'] ) || $signup_code != stripslashes( $_POST['signup_code'] ) )
		$content['errors']->add('signup_code', sprintf( __( 'Invalid %s.', 'signup_code' ), strtolower( get_site_option( 'signup_code_branding', 'Signup Code' ) ) ) );
	
	return $content;
}

function signup_code_filter_bp() {
	global $bp;
	$signup_code = get_site_option('signup_code');

	if ( empty( $signup_code ) )
		return;
	
	if( $signup_code != stripslashes( $_POST['signup_code'] ) && isset( $_POST['signup_username'] ) )
		$bp->signup->errors['signup_code'] = sprintf( __( 'Invalid %s.', 'signup_code' ), strtolower( get_site_option( 'signup_code_branding', 'Signup Code' ) ) );
}

global $wpmudev_notices;
$wpmudev_notices[] = array( 'id'=> 98, 'name'=> 'Signup Code', 'screens' => array( 'settings_page_signup_code-network' ) );
include_once( plugin_dir_path( __FILE__ ).'external/dash-notice/wpmudev-dash-notification.php' );

