<?php

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

function user_register_users($user_id, $password = "", $meta = array())
{
	$first_name = check_var('first_name');
	$last_name = check_var('last_name');

	update_usermeta($user_id, 'first_name', $first_name);
	update_usermeta($user_id, 'last_name', $last_name);
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
		"setting_users_register_name" => __("Collect name of user in registration form", 'lang_users'),
		"setting_users_no_spaces" => __("Prevent Username Spaces", 'lang_users'),
		"setting_users_show_own_media" => __("Only show users own files", 'lang_users'),
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

	//Updates DB with custom roles
	$wp_user_roles_orig = get_option('wp_user_roles_orig');

	if($wp_user_roles_orig == '')
	{
		$wp_user_roles_orig = get_option('wp_user_roles');

		update_option('wp_user_roles_orig', $wp_user_roles_orig);
	}

	$option = get_option('setting_users_roles_names');

	if(is_array($option))
	{
		foreach($option as $key => $value)
		{
			if($value != '')
			{
				$wp_roles->roles[$key]['name'] = $wp_roles->role_names[$key] = $value;

				$wp_user_roles_orig[$key]['name'] = $value;
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

			unset($wp_user_roles_orig[$key]);
		}
	}

	update_option('wp_user_roles', $wp_user_roles_orig);
}

function setting_users_roles_hidden_callback()
{
	$option = get_option('setting_users_roles_hidden');

	$roles = get_all_roles(array('orig' => true));

	foreach($roles as $key => $value)
	{
		$option_value = isset($option[$key]) ? $option[$key] : "";

		echo show_checkbox(array('name' => "setting_users_roles_hidden[".$key."]", 'text' => __($value), 'value' => 1, 'compare' => $option_value));
	}
}

function setting_users_roles_names_callback()
{
	$option = get_option('setting_users_roles_names');

	$roles = get_all_roles();

	foreach($roles as $key => $value)
	{
		$option_value = isset($option[$key]) ? $option[$key] : "";

		echo "<p>"
			.show_textfield(array('name' => "setting_users_roles_names[".$key."]", 'value' => $option_value, 'placeholder' => __($value)))
			."<span class='description'>".__("Change name for", 'lang_users')." ".__($value)."</span>
		</p>";
	}
}

function setting_users_no_spaces_callback()
{
	$option = get_option('setting_users_no_spaces');

	echo show_checkbox(array('name' => 'setting_users_no_spaces', 'value' => 1, 'compare' => $option));
}

function setting_users_register_name_callback()
{
	$option = get_option('setting_users_register_name');

	echo show_checkbox(array('name' => 'setting_users_register_name', 'value' => 1, 'compare' => $option));
}

function setting_users_show_own_media_callback()
{
	$option = get_option('setting_users_show_own_media');
	$roles = get_all_roles();

	echo "<label>
		<select name='setting_users_show_own_media'>
			<option value=''>-- ".__("Choose here", 'lang_users')." --</option>";

			foreach($roles as $key => $value)
			{
				$key = get_role_first_capability($key);

				echo "<option value='".$key."'".($key == $option ? " selected" : "").">".__($value)."</option>";
			}

		echo "</select>
		<p class='description'>".__("Every user below this role only sees their own files in the Media Library", 'lang_users')."</p>
	</label>";
}