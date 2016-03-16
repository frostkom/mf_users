<?php 
/*
Plugin Name: MF Users
Plugin URI: 
Description: 
Version: 1.4.0
Author: Martin Fors
Author URI: http://frostkom.se
*/

include_once("include/functions.php");

if(is_admin())
{
	register_activation_hook(__FILE__, 'activate_users');
	register_deactivation_hook(__FILE__, 'deactivate_users');
	register_uninstall_hook(__FILE__, 'uninstall_users');

	add_action('admin_init', 'settings_users');
	add_action('pre_get_posts', 'own_media_users');
}

else
{
	if(get_option('setting_users_register_name'))
	{
		add_action('register_form', 'register_form_users');
		add_action('user_register', 'user_register_users');
	}

	if(get_option('setting_users_no_spaces'))
	{
		add_action('registration_errors', 'register_errors_users', 10, 3);
	}
}

if(get_option('setting_users_register_name'))
{
	add_action('wp_login', 'wp_login_users');
}

load_plugin_textdomain('lang_users', false, dirname(plugin_basename(__FILE__)).'/lang/');

function activate_users()
{
	global $wpdb;

	if(get_option('setting_users_no_spaces'))
	{
		$users = get_users(array('fields' => 'all'));

		foreach($users as $user)
		{
			$user_data = get_userdata($user->ID);

			$username = replace_spaces($user_data->user_login);

			if($username != $user_data->user_login)
			{
				//wp_update_user(array('ID' => $user->ID, 'user_login' => $username)); //Does not work
				$wpdb->query($wpdb->prepare("UPDATE ".$wpdb->users." SET user_login = %s WHERE ID = '%d'", $username, $user->ID));
			}
		}
	}

	if(get_option('setting_users_register_name'))
	{
		add_action('admin_head', 'admin_head_users');
	}
}

function deactivate_users()
{
	$wp_user_roles_orig = get_option('wp_user_roles_orig');

	if($wp_user_roles_orig != '')
	{
		update_option('wp_user_roles', $wp_user_roles_orig);
		delete_option('wp_user_roles_orig');
	}
}

function uninstall_users()
{
	mf_uninstall_plugin(array(
		'options' => array('setting_users_roles_hidden', 'setting_users_roles_names', 'setting_users_register_name', 'setting_users_no_spaces', 'setting_users_show_own_media'),
	));
}