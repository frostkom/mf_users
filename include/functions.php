<?php

function init_users()
{
	global $wp_roles;

	update_option('wp_user_roles_orig', $wp_roles->roles);

	$option = get_option('setting_users_roles_names');

	if(is_array($option))
	{
		foreach($option as $key => $value)
		{
			if($value != '')
			{
				$wp_roles->roles[$key]['name'] = $wp_roles->role_names[$key] = $value;
			}
		}
	}

	$option = get_option('setting_users_roles_hidden');

	if(is_array($option))
	{
		foreach($option as $key => $value)
		{
			unset($wp_roles->roles[$key]);
			unset($wp_roles->role_names[$key]);
		}
	}
}

if(!function_exists('wp_authenticate'))
{
	function wp_authenticate($username, $password)
	{
		$username = sanitize_user($username);
		$password = trim($password);

		//This is the extra line for replacing spaces in the username
		if(get_option('setting_users_no_spaces'))
		{
			$username = replace_spaces($username);
		}

		$user = apply_filters('authenticate', null, $username, $password);

		if($user == null)
		{
			$user = new WP_Error('authentication_failed', __('Incorrect username or password', 'lang_users'));
		}

		$ignore_codes = array('empty_username', 'empty_password');

		if(is_wp_error($user) && !in_array($user->get_error_code(), $ignore_codes))
		{
			do_action('wp_login_failed', $username);
		}

		return $user;
	}
}

function replace_spaces($in)
{
	return str_replace(" ", "-", $in);
}

function register_form_users()
{
	if(get_option('setting_users_register_name'))
	{
		$first_name = check_var('first_name');
		$last_name = check_var('last_name');

		echo "<p>
			<label for='first_name'>".__('First Name', 'lang_users').":</label><br>
			<input type='text' name='first_name' value='".$first_name."' class='regular-text' required>
		</p>
		<p>
			<label for='last_name'>".__('Last Name', 'lang_users').":</label><br>
			<input type='text' name='last_name' value='".$last_name."' class='regular-text' required>
		</p>";
	}
}

function save_register_users($user_id, $password = "", $meta = array())
{
	if(get_option('setting_users_register_name'))
	{
		$first_name = check_var('first_name');
		$last_name = check_var('last_name');

		update_user_meta($user_id, 'first_name', $first_name);
		update_user_meta($user_id, 'last_name', $last_name);
	}

	$option = get_option('setting_add_profile_fields');

	if(in_array("phone", $option))
	{
		$profile_phone = check_var('profile_phone');

		update_user_meta($user_id, 'profile_phone', $profile_phone);
	}
}

function show_profile_users($user)
{
	$option = get_option('setting_remove_profile_fields');

	if(is_array($option) && count($option) > 0)
	{
		$arr_remove = array();

		foreach($option as $remove)
		{
			$arr_remove[$remove] = true;
		}

		if(count($arr_remove) > 0)
		{
			mf_enqueue_script('script_users', plugin_dir_url(__FILE__)."/script_remove.js", $arr_remove);
		}
	}

	$option = get_option('setting_add_profile_fields');

	if(is_array($option))
	{
		$out = "";

		if(in_array("phone", $option))
		{
			$meta_key = 'profile_phone';
			$meta_value = get_the_author_meta('profile_phone', $user->ID);
			$meta_text = __('Phone number', 'lang_users');

			$out .= "<tr class='".str_replace("_", "-", $meta_key)."-wrap'>
				<th><label for='".$meta_key."'>".$meta_text."</label></th>
				<td><input type='text' name='".$meta_key."' value='".$meta_value."'></td>
			</tr>";
		}

		if($out != '')
		{
			echo "<table class='form-table'>".$out."</table>";
		}
	}
}

function save_profile_users($user_id)
{
	if(current_user_can('edit_user', $user_id))
	{
		save_register_users($user_id);
	}
}

function register_errors_users($errors, $user_login, $user_email)
{
	if(preg_match('/ /', $user_login))
	{
		$errors->add('user_login', __("Username must not contain spaces", 'lang_users'));
	}
	
	return $errors;
}

function save_display_name($user)
{
	if($user->first_name != '' && $user->last_name != '')
	{
		$display_name = $user->first_name." ".$user->last_name;

		if($user->display_name != $display_name)
		{
			wp_update_user(array('ID' => $user->ID, 'display_name' => $display_name));
		}
	}
}

function wp_login_users($username)
{
	$user = get_user_by('login', $username);

	save_display_name($user);
}

function admin_head_users()
{
	$users = get_users(array('fields' => 'all'));

	foreach($users as $user)
	{
		$user = get_userdata($user->ID);

		save_display_name($user);
	}
}

function own_media_users($wp_query)
{
	global $current_user;

	$wp_query = $wp_query;
	
	if(isset($wp_query->query['post_type']))
	{
		$option = get_option('setting_users_show_own_media');

		if($option != '')
		{
			if(!current_user_can($option) && (is_admin() && $wp_query->query['post_type'] === 'attachment'))
			{
				$wp_query->set('author', $current_user->ID);
			}
		}
	}
}

function settings_users()
{
	$options_area = "settings_users";

	add_settings_section($options_area, "", $options_area."_callback", BASE_OPTIONS_PAGE);

	$arr_settings = array(
		"setting_users_roles_hidden" => __("Hide Roles", 'lang_users'),
		"setting_users_roles_names" => __("Change Role Names", 'lang_users'),
		"setting_users_show_own_media" => __("Only show users own files", 'lang_users'),
		"setting_users_no_spaces" => __("Prevent Username Spaces", 'lang_users'),
		"setting_users_register_name" => __("Collect name of user in registration form", 'lang_users'),
		"setting_add_profile_fields" => __("Add fields to profile", 'lang_users'),
		"setting_remove_profile_fields" => __("Remove fields from profile", 'lang_users'),
	);

	foreach($arr_settings as $handle => $text)
	{
		add_settings_field($handle, $text, $handle."_callback", BASE_OPTIONS_PAGE, $options_area);

		register_setting(BASE_OPTIONS_PAGE, $handle);
	}
}

function settings_users_callback()
{
	$setting_key = get_setting_key(__FUNCTION__);

	echo settings_header($setting_key, __("Users", 'lang_users'));
}

function setting_users_roles_hidden_callback()
{
	$setting_key = get_setting_key(__FUNCTION__);
	$option = get_option($setting_key);
	$roles = get_all_roles(array('orig' => true));

	foreach($roles as $key => $value)
	{
		$option_value = isset($option[$key]) ? $option[$key] : "";

		echo show_checkbox(array('name' => "setting_users_roles_hidden[".$key."]", 'text' => __($value), 'value' => 1, 'compare' => $option_value));
	}
}

function setting_users_roles_names_callback()
{
	$setting_key = get_setting_key(__FUNCTION__);
	$option = get_option($setting_key);
	$roles = get_all_roles();

	foreach($roles as $key => $value)
	{
		$option_value = isset($option[$key]) ? $option[$key] : "";

		echo show_textfield(array('name' => $setting_key."[".$key."]", 'value' => $option_value, 'placeholder' => $value));
	}
}

function setting_users_no_spaces_callback()
{
	$setting_key = get_setting_key(__FUNCTION__);
	$option = get_option($setting_key);

	$arr_data = array();

	$arr_data[] = array(0, __("No", 'lang_users'));
	$arr_data[] = array(1, __("Yes", 'lang_users'));

	echo show_select(array('data' => $arr_data, 'name' => $setting_key, 'compare' => $option));

	//echo show_checkbox(array('name' => $setting_key, 'value' => 1, 'compare' => $option));
}

function setting_users_register_name_callback()
{
	$setting_key = get_setting_key(__FUNCTION__);
	$option = get_option($setting_key);

	$arr_data = array();

	$arr_data[] = array(0, __("No", 'lang_users'));
	$arr_data[] = array(1, __("Yes", 'lang_users'));

	echo show_select(array('data' => $arr_data, 'name' => $setting_key, 'compare' => $option));

	//echo show_checkbox(array('name' => $setting_key, 'value' => 1, 'compare' => $option));
}

function setting_users_show_own_media_callback()
{
	$setting_key = get_setting_key(__FUNCTION__);
	$option = get_option($setting_key);

	$arr_data = array();

	get_roles_for_select($arr_data, true, false);

	echo show_select(array('data' => $arr_data, 'name' => $setting_key, 'compare' => $option, 'description' => __("Every user below this role only sees their own files in the Media Library", 'lang_users')));
}

function setting_add_profile_fields_callback()
{
	$setting_key = get_setting_key(__FUNCTION__);
	$option = get_option($setting_key);

	$arr_data = array();

	$arr_data[] = array('phone', __("Phone number", 'lang_users'));

	echo show_select(array('data' => $arr_data, 'name' => $setting_key."[]", 'compare' => $option));
}

function setting_remove_profile_fields_callback()
{
	$setting_key = get_setting_key(__FUNCTION__);
	$option = get_option($setting_key);

	$arr_data = array();

	$arr_data[] = array('headings', __("Headings", 'lang_users'));
	$arr_data[] = array('rich_editing', __("Visual Editor", 'lang_users'));
	$arr_data[] = array('admin_color', __("Admin Color Scheme", 'lang_users'));
	$arr_data[] = array('comment_shortcuts', __("Keyboard Shortcuts", 'lang_users'));
	$arr_data[] = array('show_admin_bar', __("Toolbar", 'lang_users'));
	$arr_data[] = array('user_login', __("Username", 'lang_users'));
	$arr_data[] = array('nickname', __("Nickname", 'lang_users'));
	$arr_data[] = array('display_name', __("Display name", 'lang_users'));
	$arr_data[] = array('url', __("Website", 'lang_users'));
	$arr_data[] = array('description', __("Biographical Info", 'lang_users'));
	$arr_data[] = array('profile_picture', __("Profile Picture", 'lang_users'));
	$arr_data[] = array('password', __("Password", 'lang_users'));
	$arr_data[] = array('sessions', __("Sessions", 'lang_users'));

	echo show_select(array('data' => $arr_data, 'name' => $setting_key."[]", 'compare' => $option));
}