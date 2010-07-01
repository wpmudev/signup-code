<?php
/*
Plugin Name: Signup Code
Plugin URI: 
Description:
Author: Andrew Billits
Version: 1.0.1
Author URI:
*/

/* 
Copyright 2007-2009 Incsub (http://incsub.com)

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

add_action('wpmu_options', 'signup_code_site_admin_options');
add_action('update_wpmu_options', 'signup_code_site_admin_options_process');
add_action('signup_extra_fields', 'signup_code_field_wpmu');
add_action('bp_after_account_details_fields', 'signup_code_field_bp');
add_filter('wpmu_validate_user_signup', 'signup_code_filter_wpmu');
add_filter('bp_signup_validate', 'signup_code_filter_bp');
add_action('wp_head', 'signup_code_stylesheet');
//------------------------------------------------------------------------//
//---Functions------------------------------------------------------------//
//------------------------------------------------------------------------//

function signup_code_site_admin_options_process() {
	update_site_option( 'signup_code' , $_POST['signup_code'] );
	update_site_option( 'signup_code_branding' , $_POST['signup_code_branding'] );
}

//------------------------------------------------------------------------//
//---Output Functions-----------------------------------------------------//
//------------------------------------------------------------------------//

function signup_code_stylesheet() {
?>
<style type="text/css">
	.mu_register #signup_code { width:100%; font-size: 24px; margin:5px 0; }
</style>
<?php
}

function signup_code_site_admin_options() {
	?>
		<h3><?php _e('Signup Code') ?></h3> 
		<table class="form-table">
			<tr valign="top"> 
				<th scope="row"><?php _e('Code') ?></th> 
				<td><input name="signup_code" type="text" id="signup_code" value="<?php echo get_site_option('signup_code'); ?>" style="width: 95%"/>
					<br />
					<?php _e('Users must enter this code in order to signup. Letters and numbers only.') ?>
				</td>
			</tr>
			<tr valign="top"> 
				<th scope="row"><?php _e('Signup Code Branding') ?></th> 
				<td><input name="signup_code_branding" type="text" id="signup_code_branding" value="<?php echo stripslashes(get_site_option('signup_code_branding', 'Signup Code')); ?>" style="width: 95%"/>
					<br />
					<?php _e('This is the text that will be displayed on the signup form. Ex: Invite Code') ?>
				</td>
			</tr>
		</table>
	<?php
}

//------------------------------------------------------------------------//
//---Page Output Functions------------------------------------------------//
//------------------------------------------------------------------------//

function signup_code_field_wpmu($errors) {
	$error = $errors->get_error_message('signup_code');
	$signup_code = get_site_option('signup_code');
	if ( !empty( $signup_code ) ) {
	?>
    <label for="password"><?php _e(stripslashes(get_site_option('signup_code_branding', 'Signup Code'))); ?>:</label>
		<?php
        if($error) {
			echo '<p class="error">' . $error . '</p>';
        }
		?>
		<input type="text" name="signup_code" id="signup_code" value="<?php echo $_GET['code']; ?>" />
	<?php
	}
}

function signup_code_field_bp() {
	$signup_code = get_site_option('signup_code');
	if ( !empty( $signup_code ) ) {
	?>
    <div class="register-section" id="blog-details-section">
    <label for="password"><?php _e(stripslashes(get_site_option('signup_code_branding', 'Signup Code'))); ?>:</label>
		<?php do_action( 'bp_signup_code_errors' ) ?>
		<input type="text" name="signup_code" id="signup_code" value="<?php echo $_GET['code']; ?>" />
    </div>
	<?php
	}
}

function signup_code_filter_wpmu($content) {
	$signup_code = get_site_option('signup_code');
	if ( !empty( $signup_code ) ) {
		if($signup_code != $_POST['signup_code'] && $_POST['stage'] == 'validate-user-signup') {
			$content['errors']->add('signup_code', __('Invalid ' . strtolower(stripslashes(get_site_option('signup_code_branding', 'Signup Code'))) . '.'));
		}
	}
	return $content;
}

function signup_code_filter_bp() {
	global $bp;
	$signup_code = get_site_option('signup_code');
	if ( !empty( $signup_code ) ) {
		if($signup_code != $_POST['signup_code'] && isset($_POST['signup_username'])) {
			$bp->signup->errors['signup_code'] = __('Invalid ' . strtolower(stripslashes(get_site_option('signup_code_branding', 'Signup Code'))) . '.');
		}
	}
	return $content;
}

?>