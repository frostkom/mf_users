<?php
/*
Plugin Name: MF Users
Plugin URI: https://github.com/frostkom/mf_users
Description: 
Version: 4.4.3
Licence: GPLv2 or later
Author: Martin Fors
Author URI: https://frostkom.se
Text Domain: lang_users
Domain Path: /lang

Depends: MF Base
GitHub Plugin URI: frostkom/mf_users
*/

include_once("include/classes.php");

$obj_users = new mf_users();

add_action('cron_base', 'activate_users', mt_rand(1, 10));
add_action('cron_base', array($obj_users, 'cron_base'), mt_rand(1, 10));

if(is_admin())
{
	register_activation_hook(__FILE__, 'activate_users');
	register_uninstall_hook(__FILE__, 'uninstall_users');

	add_action('init', array($obj_users, 'init'));
	add_action('admin_init', array($obj_users, 'settings_users'));
	add_action('admin_init', array($obj_users, 'admin_init'), 0);
	add_action('pre_get_posts', array($obj_users, 'pre_get_posts'));

	add_action('admin_action_inactivate_user', array($obj_users, 'admin_action_inactivate_user'), 10);
	add_action('user_row_actions', array($obj_users, 'user_row_actions'), 10, 2);
	add_action('ms_user_row_actions', array($obj_users, 'user_row_actions'), 10, 2);

	add_action('show_user_profile', array($obj_users, 'edit_user_profile'));
	add_action('edit_user_profile', array($obj_users, 'edit_user_profile'));
	add_action('personal_options_update', array($obj_users, 'edit_user_profile_update'));
	add_action('edit_user_profile_update', array($obj_users, 'edit_user_profile_update'));

	add_filter('get_user_option_admin_color', array($obj_users, 'get_user_option_admin_color'));

	add_action('admin_footer', array($obj_users, 'admin_footer'), 0);
}

else
{
	add_action('register_form', array($obj_users, 'register_form'), 0);
	add_action('user_register', array($obj_users, 'user_register'));

	add_filter('filter_profile_fields', array($obj_users, 'filter_profile_fields'));

	if(get_site_option('setting_users_no_spaces'))
	{
		add_action('registration_errors', array($obj_users, 'registration_errors'), 10, 3);
	}

	add_action('wp_head', array($obj_users, 'wp_head'), 0);
	add_action('wp_footer', array($obj_users, 'wp_footer'), 0);
}

add_filter('get_avatar', array($obj_users, 'get_avatar'), 1, 5);

add_action('wp_login', array($obj_users, 'wp_login'));
add_action('wp_logout', array($obj_users, 'wp_logout'));

add_action('widgets_init', array($obj_users, 'widgets_init'));

load_plugin_textdomain('lang_users', false, dirname(plugin_basename(__FILE__)).'/lang/');

function activate_users()
{
	replace_user_meta(array('old' => 'last_active', 'new' => 'meta_last_active'));
}

function uninstall_users()
{
	global $wpdb;

	mf_uninstall_plugin(array(
		'options' => array('setting_users_roles_hidden', 'setting_users_roles_names', 'setting_users_show_own_media', 'setting_users_no_spaces', 'setting_users_register_name', 'setting_add_profile_fields', 'setting_remove_profile_fields', 'setting_admin_color', $wpdb->prefix.'user_roles_orig'),
		'meta' => array('meta_last_active', 'meta_profile_reminder'),
	));
}