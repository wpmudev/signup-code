<?php
/*
Plugin Name: Signup Code
Plugin URI: http://premium.wpmudev.org/project/signup-code
Description: Limit who can sign up for a blog or user account at your site by requiring a special code that you can easily configure yourself
Author: S H Mohanjith (Incsub), Andrew Billits (Incsub)
Version: 1.0.3.1
Author URI: http://premium.wpmudev.org
Network: true
WDP ID: 98
Text Domain: signup_code
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

global $signup_code_settings_page, $signup_code_settings_page_long;

if ( version_compare($wp_version, '3.0.9', '>') ) {
	$signup_code_settings_page = 'settings.php';
	$signup_code_settings_page_long = 'network/settings.php';
} else {
	$signup_code_settings_page = 'ms-admin.php';
	$signup_code_settings_page_long = 'ms-admin.php';
}

//------------------------------------------------------------------------//
//---Hook-----------------------------------------------------------------//
//------------------------------------------------------------------------//
add_action('init', 'signup_code_init');
add_action('admin_menu', 'signup_code_plug_pages');
add_action('network_admin_menu', 'signup_code_plug_pages');
add_action('signup_extra_fields', 'signup_code_field_wpmu');
add_action('bp_after_account_details_fields', 'signup_code_field_bp');
add_filter('wpmu_validate_user_signup', 'signup_code_filter_wpmu');
add_filter('bp_signup_validate', 'signup_code_filter_bp');
add_action('wp_head', 'signup_code_stylesheet');
//------------------------------------------------------------------------//
//---Functions------------------------------------------------------------//
//------------------------------------------------------------------------//

function signup_code_init() {
	if ( !is_multisite() )
		exit( 'The Signup Code plugin is only compatible with WordPress Multisite.' );
		
	load_plugin_textdomain('signup_code', false, dirname(plugin_basename(__FILE__)).'/languages');
}

function signup_code_plug_pages() {
	global $wpdb, $wp_roles, $current_user, $wp_version, $signup_code_settings_page, $signup_code_settings_page_long;
	if ( version_compare($wp_version, '3.0.9', '>') ) {
	    if ( is_network_admin() ) {
		add_submenu_page($signup_code_settings_page, __('Signup Code', 'signup_code'), __('Signup Code', 'signup_code'), 'manage_network_options', 'signup_code', 'signup_code_site_admin_options');
	    }
	} else {
	    if ( is_super_admin() ) {
		add_submenu_page($signup_code_settings_page, __('Signup Code', 'signup_code'), __('Signup Code', 'signup_code'), 'manage_network_options', 'signup_code', 'signup_code_site_admin_options');
	    }   
	}
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
	global $wpdb, $wp_roles, $current_user, $signup_code_settings_page;
	
	if(!current_user_can('manage_options')) {
		echo "<p>" . __('Nice Try...', 'signup_code') . "</p>";  //If accessed properly, this message doesn't appear.
		return;
	}
	if (isset($_GET['updated'])) {
		?><div id="message" class="updated fade"><p><?php _e(urldecode($_GET['updatedmsg']), 'signup_code') ?></p></div><?php
	}
	echo '<div class="wrap">';
	switch( $_GET[ 'action' ] ) {
		//---------------------------------------------------//
		default:
	?>
	<h2><?php _e('Signup Code', 'signup_code') ?></h2>
	<form method="post" action="<?php print $signup_code_settings_page; ?>?page=signup_code&action=process">
	<table class="form-table">
		<tr valign="top"> 
			<th scope="row"><?php _e('Code', 'signup_code') ?></th> 
			<td><input name="signup_code" type="text" id="signup_code" value="<?php echo get_site_option('signup_code'); ?>" style="width: 95%"/>
				<br />
				<?php _e('Users must enter this code in order to signup. Letters and numbers only.', 'signup_code') ?>
			</td>
		</tr>
		<tr valign="top"> 
			<th scope="row"><?php _e('Signup Code Branding', 'signup_code') ?></th> 
			<td><input name="signup_code_branding" type="text" id="signup_code_branding" value="<?php echo stripslashes(get_site_option('signup_code_branding', 'Signup Code')); ?>" style="width: 95%"/>
				<br />
				<?php _e('This is the text that will be displayed on the signup form. Ex: Invite Code', 'signup_code') ?>
			</td>
		</tr>
	</table>
		<p class="submit">
			<input type="submit" name="Submit" value="<?php _e('Save Changes', 'signup_code') ?>" />
			<input type="submit" name="Reset" value="<?php _e('Reset', 'signup_code') ?>" />
		</p>
        </form>
	<?php
		break;
	case "process":
			if ( isset( $_POST[ 'Reset' ] ) ) {
				update_site_option( 'signup_code', "");
				update_site_option( 'signup_code_branding', "");
				echo "
				<SCRIPT LANGUAGE='JavaScript'>
				window.location='{$signup_code_settings_page}?page=signup_code&updated=true&updatedmsg=" . urlencode(__('Changes saved.', 'signup_code')) . "';
				</script>
				";			
			} else {
				update_site_option( 'signup_code' , $_POST['signup_code'] );
				update_site_option( 'signup_code_branding' , $_POST['signup_code_branding'] );
				echo "
				<SCRIPT LANGUAGE='JavaScript'>
				window.location='{$signup_code_settings_page}?page=signup_code&updated=true&updatedmsg=" . urlencode(__('Changes saved.', 'signup_code')) . "';
				</script>
				";
			}
		break;
	}
	echo '</div>';
}

//------------------------------------------------------------------------//
//---Page Output Functions------------------------------------------------//
//------------------------------------------------------------------------//

function signup_code_field_wpmu($errors) {
	if (!empty($errors)) {
		$error = $errors->get_error_message('signup_code');
	} else {
		$error = false;
	}
	$signup_code = get_site_option('signup_code');
	if ( !empty( $signup_code ) ) {
	?>
	<label for="password"><?php _e(stripslashes(get_site_option('signup_code_branding', 'Signup Code')), 'signup_code'); ?>:</label>
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
    <label for="password"><?php _e(stripslashes(get_site_option('signup_code_branding', 'Signup Code')), 'signup_code'); ?>:</label>
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
			$content['errors']->add('signup_code', __('Invalid ' . strtolower(stripslashes(get_site_option('signup_code_branding', 'Signup Code'))) . '.', 'signup_code'));
		}
	}
	return $content;
}

function signup_code_filter_bp() {
	global $bp;
	$signup_code = get_site_option('signup_code');
	if ( !empty( $signup_code ) ) {
		if($signup_code != $_POST['signup_code'] && isset($_POST['signup_username'])) {
			$bp->signup->errors['signup_code'] = __('Invalid ' . strtolower(stripslashes(get_site_option('signup_code_branding', 'Signup Code'))) . '.', 'signup_code');
		}
	}
	return $content;
}

if ( !function_exists( 'wdp_un_check' ) ) {
	add_action( 'admin_notices', 'wdp_un_check', 5 );
	add_action( 'network_admin_notices', 'wdp_un_check', 5 );

	function wdp_un_check() {
		if ( !class_exists( 'WPMUDEV_Update_Notifications' ) && current_user_can( 'edit_users' ) )
			echo '<div class="error fade"><p>' . __('Please install the latest version of <a href="http://premium.wpmudev.org/project/update-notifications/" title="Download Now &raquo;">our free Update Notifications plugin</a> which helps you stay up-to-date with the most stable, secure versions of WPMU DEV themes and plugins. <a href="http://premium.wpmudev.org/wpmu-dev/update-notifications-plugin-information/">More information &raquo;</a>', 'wpmudev') . '</a></p></div>';
	}
}
