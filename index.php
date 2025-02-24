<?php
/*
Plugin Name: MF Users
Plugin URI: https://github.com/frostkom/mf_users
Description:
Version: 4.7.13
Licence: GPLv2 or later
Author: Martin Fors
Author URI: https://martinfors.se
Text Domain: lang_users
Domain Path: /lang

Depends: MF Base
GitHub Plugin URI: frostkom/mf_users
*/

if(!function_exists('is_plugin_active') || function_exists('is_plugin_active') && is_plugin_active("mf_base/index.php"))
{
	include_once("include/classes.php");

	$obj_users = new mf_users();

	add_action('cron_base', 'activate_users', mt_rand(1, 10));
	add_action('cron_base', array($obj_users, 'cron_base'), mt_rand(1, 10));

	add_action('init', array($obj_users, 'init'));

	if(is_admin())
	{
		register_activation_hook(__FILE__, 'activate_users');
		register_uninstall_hook(__FILE__, 'uninstall_users');

		add_action('admin_init', array($obj_users, 'settings_users'));
		add_action('admin_init', array($obj_users, 'admin_init'), 0);

		//add_action('pre_user_query', array($obj_users, 'pre_user_query'));
		add_action('pre_get_posts', array($obj_users, 'pre_get_posts'));

		add_action('admin_action_inactivate_user', array($obj_users, 'admin_action_inactivate_user'), 10);

		add_action('manage_users_columns', array($obj_users, 'manage_users_columns'));
		add_action('manage_users_custom_column', array($obj_users, 'manage_users_custom_column'), 10, 3);
		add_action('user_row_actions', array($obj_users, 'user_row_actions'), 10, 2);

		if(is_multisite())
		{
			add_filter('wpmu_users_columns', array($obj_users, 'manage_users_columns'));
			add_action('ms_user_row_actions', array($obj_users, 'ms_user_row_actions'), 10, 2);
		}

		add_action('show_user_profile', array($obj_users, 'edit_user_profile'));
		add_action('edit_user_profile', array($obj_users, 'edit_user_profile'));
		add_action('user_new_form', array($obj_users, 'edit_user_profile'));
		add_action('profile_update', array($obj_users, 'profile_update'));

		add_action('admin_footer', array($obj_users, 'admin_footer'), 0);
	}

	else
	{
		add_filter('wp_sitemaps_add_provider', array($obj_users, 'wp_sitemaps_add_provider'), 10, 2);
		add_filter('template_redirect', array($obj_users, 'template_redirect'), 1, 0);

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

	if(get_option('setting_users_send_password_change_notification') != 'yes')
	{
		// Disables sending message to the user
		//add_filter('send_password_change_email', '__return_false');

		// Disables sending message to admin
		if(!function_exists('wp_password_change_notification'))
		{
			function wp_password_change_notification(){}
		}

		// ...or...
		//remove_action('after_password_reset', 'wp_password_change_notification');
	}

	add_filter('get_avatar', array($obj_users, 'get_avatar'), 1, 5);

	add_action('wp_login', array($obj_users, 'wp_login'));
	add_action('wp_logout', array($obj_users, 'wp_logout'));

	if(wp_is_block_theme() == false)
	{
		add_action('widgets_init', array($obj_users, 'widgets_init'));
	}

	function activate_users()
	{
		replace_option(array('old' => 'setting_theme_core_display_author_pages', 'new' => 'setting_users_display_author_pages'));
		replace_option(array('old' => 'setting_add_profile_fields', 'new' => 'setting_users_add_profile_fields'));
		replace_option(array('old' => 'setting_remove_profile_fields', 'new' => 'setting_users_remove_profile_fields'));

		mf_uninstall_plugin(array(
			'options' => array('setting_users_last_logged_in', 'setting_admin_color', 'setting_users_admin_color'),
			'meta' => array('meta_last_logged_in'),
		));
	}

	function uninstall_users()
	{
		global $wpdb;

		mf_uninstall_plugin(array(
			'options' => array('setting_users_show_own_media', 'setting_users_no_spaces', 'setting_users_register_name', 'setting_users_send_password_change_notification', 'setting_users_display_author_pages', 'setting_users_send_registration_notification', 'setting_users_roles_hidden', 'setting_users_roles_names', 'setting_users_add_profile_fields', 'setting_users_remove_profile_fields', $wpdb->prefix.'user_roles_orig'),
			'meta' => array('meta_last_logged_in', 'meta_last_active', 'meta_last_logged_out', 'meta_profile_reminder'),
		));
	}
}