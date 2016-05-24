<?php

function init_users()
{
	global $wp_roles;

	update_option('wp_user_roles_orig', $wp_roles->roles);

	rename_roles();
	hide_roles();
}

function rename_roles()
{
	global $wp_roles;

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
}

function hide_roles()
{
	global $wp_roles;

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

function admin_color_users($color)
{
	$option = get_option('setting_admin_color');

	if($option != '')
	{
		$color = $option;
	}

	return $color;
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

	if(is_array($option) && in_array('phone', $option))
	{
		$meta_value = check_var('profile_phone');

		update_user_meta($user_id, 'profile_phone', $meta_value);
	}

	if(is_array($option) && in_array('edit_page_per_page', $option))
	{
		$meta_value = check_var('edit_page_per_page');

		update_user_meta($user_id, 'edit_page_per_page', $meta_value);
	}

	if(is_array($option) && in_array('profile_picture', $option))
	{
		$meta_value = check_var('profile_picture');

		update_user_meta($user_id, 'profile_picture', $meta_value);
	}

	//Does not seam to work with special characters
	/*if(is_array($option) && in_array('password', $option))
	{
		$meta_value = check_var('profile_password');

		if($meta_value != '')
		{
			//$meta_value = wp_hash_password($meta_value);

			//update_user_meta($user_id, 'user_pass', $meta_value);
			//wp_update_user(array('ID' => $user_id, 'user_pass' => $meta_value));

			wp_set_password($meta_value, $user_id);
		}
	}*/
}

function show_profile_users($user)
{
	$arr_remove = array();

	$option = get_option('setting_remove_profile_fields');

	if(is_array($option) && count($option) > 0)
	{
		foreach($option as $remove)
		{
			$arr_remove[$remove] = true;
		}
	}

	$option = get_option('setting_add_profile_fields');

	if(is_array($option) && count($option) > 0)
	{
		$out = "";

		if(in_array('phone', $option))
		{
			$meta_key = 'profile_phone';
			$meta_value = get_the_author_meta($meta_key, $user->ID);
			$meta_text = __('Phone Number', 'lang_users');

			$out .= "<tr class='".str_replace("_", "-", $meta_key)."-wrap'>
				<th><label for='".$meta_key."'>".$meta_text."</label></th>
				<td>".show_textfield(array('name' => $meta_key, 'value' => $meta_value, 'xtra' => "class='regular-text'"))."</td>
			</tr>";
		}

		if(in_array('profile_picture', $option))
		{
			$arr_remove['profile_picture'] = true;

			$meta_key = 'profile_picture';
			$meta_value = get_the_author_meta($meta_key, $user->ID);
			$meta_text = __('Profile Picture', 'lang_users');

			$out .= "<tr class='".str_replace("_", "-", $meta_key)."-wrap'>
				<th><label for='".$meta_key."'>".$meta_text."</label></th>
				<td>".get_file_button(array('setting_key' => $meta_key, 'option' => $meta_value))."</td>
			</tr>";
		}

		if(in_array('edit_page_per_page', $option))
		{
			$meta_key = 'edit_page_per_page';
			$meta_value = get_the_author_meta($meta_key, $user->ID);
			$meta_text = __('Rows per page', 'lang_users');

			$out .= "<tr class='".str_replace("_", "-", $meta_key)."-wrap'>
				<th><label for='".$meta_key."'>".$meta_text."</label></th>
				<td>".show_textfield(array('type' => 'number', 'name' => $meta_key, 'value' => $meta_value))."</td>
			</tr>";
		}

		/*if(in_array('password', $option))
		{
			$arr_remove['password'] = true;

			$meta_key = 'profile_password';
			$meta_text = __('New Password', 'lang_users');

			$out .= "<tr class='".str_replace("_", "-", $meta_key)."-wrap'>
				<th><label for='".$meta_key."'>".$meta_text."</label></th>
				<td>".show_password_field(array('name' => $meta_key, 'placeholder' => __("Enter a new password here", 'lang_users'), 'xtra' => "class='regular-text'"))."</td>
			</tr>";
		}*/

		if($out != '')
		{
			echo "<table class='form-table'>".$out."</table>";
		}
	}

	if(count($arr_remove) > 0)
	{
		mf_enqueue_script('script_users', plugin_dir_url(__FILE__)."script_remove.js", $arr_remove);
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

function avatar_users($avatar, $id_or_email, $size, $default, $alt)
{
	$meta_key = 'profile_picture';

	$user = false;

	if(is_numeric($id_or_email))
	{
		$id = (int) $id_or_email;
		$user = get_user_by('id', $id);
	}
	
	else if(is_object($id_or_email))
	{
		if($id_or_email->user_id > 0)
		{
			$id = (int) $id_or_email->user_id;
			$user = get_user_by('id', $id);
		}
	}
	
	else
	{
		$user = get_user_by('email', $id_or_email);   
	}

	if($user && is_object($user))
	{
		$meta_value = get_user_meta($user->ID, $meta_key, true);

		if(isset($meta_value) && $meta_value != '')
		{
			//$upload_dir = wp_upload_dir();
			//$custom_avatar = $upload_dir['baseurl'].$meta_value;

			$avatar = "<img src='".$meta_value."' class='avatar avatar-".$size." photo' alt='".$alt."'>"; // height='".$size."' width='".$size."'
		}
	}

	return $avatar;
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
	$options_area = __FUNCTION__;

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
	
	$option = get_option('setting_remove_profile_fields');

	if(is_array($option) && in_array('admin_color', $option))
	{
		$arr_settings['setting_admin_color'] = __("Change Admin Color", 'lang_users');
	}

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
	$option = get_option($setting_key, 1);

	echo show_select(array('data' => get_yes_no_for_select(array('return_integer' => true)), 'name' => $setting_key, 'compare' => $option));
}

function setting_users_register_name_callback()
{
	$setting_key = get_setting_key(__FUNCTION__);
	$option = get_option($setting_key);

	echo show_select(array('data' => get_yes_no_for_select(array('return_integer' => true)), 'name' => $setting_key, 'compare' => $option));
}

function setting_users_show_own_media_callback()
{
	$setting_key = get_setting_key(__FUNCTION__);
	$option = get_option($setting_key);

	$arr_data = get_roles_for_select(array('add_choose_here' => true));

	echo show_select(array('data' => $arr_data, 'name' => $setting_key, 'compare' => $option, 'description' => __("Every user below this role only sees their own files in the Media Library", 'lang_users')));
}

function setting_add_profile_fields_callback()
{
	$setting_key = get_setting_key(__FUNCTION__);
	$option = get_option($setting_key);

	$arr_data = array(
		'phone' => __("Phone Number", 'lang_users'),
		'profile_picture' => __("Profile Picture", 'lang_users'),
		'edit_page_per_page' => __("Rows per page", 'lang_users'),
		//'password' => __("Password", 'lang_users'),
	);

	echo show_select(array('data' => $arr_data, 'name' => $setting_key."[]", 'compare' => $option));
}

function setting_remove_profile_fields_callback()
{
	$setting_key = get_setting_key(__FUNCTION__);
	$option = get_option($setting_key);

	$arr_data = array(
		'headings' => __("Headings", 'lang_users'),
		'rich_editing' => __("Visual Editor", 'lang_users'),
		'admin_color' => __("Admin Color Scheme", 'lang_users'),
		'comment_shortcuts' => __("Keyboard Shortcuts", 'lang_users'),
		'show_admin_bar' => __("Toolbar", 'lang_users'),
		'user_login' => __("Username", 'lang_users'),
		'nickname' => __("Nickname", 'lang_users'),
		'display_name' => __("Display name", 'lang_users'),
		'url' => __("Website", 'lang_users'),
		'aim' => __("AIM", 'lang_users'),
		'yim' => __("Yahoo IM", 'lang_users'),
		'jabber' => __("Jabber", 'lang_users'),
		'description' => __("Biographical Info", 'lang_users'),
	);

	$option_add = get_option('setting_add_profile_fields');

	if(is_array($option_add))
	{
		if(!in_array('profile_picture', $option_add))
		{
			$arr_data['profile_picture'] = __("Profile Picture", 'lang_users');
		}

		/*if(!in_array('password', $option_add))
		{
			$arr_data['password'] = __("Password", 'lang_users');
		}*/
	}

	$arr_data['sessions'] = __("Sessions", 'lang_users');

	echo show_select(array('data' => $arr_data, 'name' => $setting_key."[]", 'compare' => $option));
}

function setting_admin_color_callback()
{
	$setting_key = get_setting_key(__FUNCTION__);
	$option = get_option($setting_key);

	$arr_data = array(
		'' => "-- ".__("Choose here", 'lang_users')." --",
		'blue' => __("Blue", 'lang_users'),
		'fresh' => __("Fresh", 'lang_users')." (".__("Default", 'lang_users').")",
		'ectoplasm' => __("Ectoplasm", 'lang_users'),
		'light' => __("Light", 'lang_users'),
		'coffee' => __("Coffee", 'lang_users'),
		'midnight' => __("Midnight", 'lang_users'),
		'ocean' => __("Ocean", 'lang_users'),
		'sunrise' => __("Sunrise", 'lang_users'),
	);

	echo show_select(array('data' => $arr_data, 'name' => $setting_key, 'compare' => $option));
}