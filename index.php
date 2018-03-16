<?php
/*
Plugin Name: MF Users
Plugin URI: https://github.com/frostkom/mf_users
Description: 
Version: 4.2.2
Licence: GPLv2 or later
Author: Martin Fors
Author URI: http://frostkom.se
Text Domain: lang_users
Domain Path: /lang

Depends: MF Base
GitHub Plugin URI: frostkom/mf_users
*/

include_once("include/functions.php");

add_action('cron_base', 'activate_users', mt_rand(1, 10));
//add_action('cron_base', 'cron_users', mt_rand(1, 10));

if(is_admin())
{
	register_activation_hook(__FILE__, 'activate_users');
	register_uninstall_hook(__FILE__, 'uninstall_users');

	add_action('init', 'init_users');
	add_action('admin_init', 'settings_users');
	add_action('pre_get_posts', 'own_media_users');

	add_action('show_user_profile', 'show_profile_users');
	add_action('edit_user_profile', 'show_profile_users');
	add_action('personal_options_update', 'save_profile_users');
	add_action('edit_user_profile_update', 'save_profile_users');

	add_filter('get_user_option_admin_color', 'admin_color_users');

	add_action('admin_head', 'admin_head_users');
	add_action('admin_footer', 'footer_users', 0);
}

else
{
	add_action('register_form', 'register_form_users', 0);
	add_action('user_register', 'save_register_users');

	if(get_site_option('setting_users_no_spaces'))
	{
		add_action('registration_errors', 'register_errors_users', 10, 3);
	}

	add_action('wp_footer', 'footer_users', 0);
}

add_filter('get_avatar', 'avatar_users', 1, 5);

add_action('wp_login', 'wp_login_users');
add_action('wp_logout', 'wp_logout_users');

load_plugin_textdomain('lang_users', false, dirname(plugin_basename(__FILE__)).'/lang/');

function activate_users()
{
	replace_user_meta(array('old' => 'last_active', 'new' => 'meta_last_active'));
	replace_user_meta(array('old' => 'profile_reminder', 'new' => 'meta_profile_reminder'));
}

function uninstall_users()
{
	mf_uninstall_plugin(array(
		'options' => array('setting_users_roles_hidden', 'setting_users_roles_names', 'setting_users_show_own_media', 'setting_users_no_spaces', 'setting_users_register_name', 'setting_add_profile_fields', 'setting_remove_profile_fields', 'setting_admin_color', $wpdb->prefix.'user_roles_orig'),
		'meta' => array('meta_last_active', 'meta_profile_reminder'),
	));
}