<?php

/*function cron_users()
{
	global $wpdb;

	$site_name = get_bloginfo('name');

	$users = get_users(array('fields' => 'all'));

	foreach($users as $user)
	{
		$user_id = $user->ID;

		$profile_reminder = get_the_author_meta('profile_reminder', $user_id);

		if($profile_reminder != '')
		{
			$user_last_active = get_user_meta($user_id, 'last_active', true);

			if($user_last_active > DEFAULT_DATE)
			{
				$reminder_cutoff = date("Y-m-d H:i:s", strtotime($user_last_active." -1 ".$profile_reminder));

				if($reminder_cutoff > date("Y-m-d H:i:s"))
				{
					$array = apply_filters('get_user_reminders', array('user_id' => $user_id, 'cutoff' => $reminder_cutoff, 'reminder' => array()));

					$mail_content = "";

					foreach($array['reminder'] as $reminder)
					{
						$mail_content .= "";
					}

					if($mail_content != '')
					{
						$user_data = get_userdata($user_id);

						$mail_to = $user_data->user_email;
						$mail_subject = sprintf(__("Here comes the latest updates from %s", 'lang_users'), $site_name);

						$data = array('to' => $mail_to, 'subject' => $mail_subject, 'content' => $mail_content);

						do_log("Send: ".var_export($data, true));
						$sent = true;

						//$sent = send_email($data);

						if($sent)
						{
							update_user_meta($user_id, 'last_active', date("Y-m-d H:i:s"));
						}
					}
				}
			}
		}
	}
}*/

function init_users()
{
	global $wpdb, $wp_roles;

	update_option($wpdb->prefix.'user_roles_orig', $wp_roles->roles);

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
			$username = replace_spaces_users($username);
		}

		$user = apply_filters('authenticate', null, $username, $password);

		if($user == null)
		{
			$user = new WP_Error('authentication_failed', __("Incorrect username or password", 'lang_users'));
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

function replace_spaces_users($in)
{
	return str_replace(" ", "-", $in);
}

function register_form_users()
{
	if(get_option('setting_users_register_name'))
	{
		$full_name = check_var('full_name');

		echo "<p>
			<label for='first_name'>".__("Full Name", 'lang_users')."</label><br>
			<input type='text' name='full_name' value='".$full_name."' class='regular-text' required>
		</p>";
	}
}

function save_register_users($user_id, $password = "", $meta = array())
{
	if(get_option('setting_users_register_name'))
	{
		$full_name = check_var('full_name');

		@list($first_name, $last_name) = explode(" ", $full_name, 2);

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

	/*$meta_value = check_var('profile_reminder');
	update_user_meta($user_id, 'profile_reminder', $meta_value);*/
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
			$meta_text = __("Phone Number", 'lang_users');

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
			$meta_text = __("Profile Picture", 'lang_users');

			$out .= "<tr class='".str_replace("_", "-", $meta_key)."-wrap'>
				<th><label for='".$meta_key."'>".$meta_text."</label></th>
				<td>".get_file_button(array('name' => $meta_key, 'value' => $meta_value))."</td>
			</tr>";
		}

		if(in_array('edit_page_per_page', $option))
		{
			$meta_key = 'edit_page_per_page';
			$meta_value = get_the_author_meta($meta_key, $user->ID);
			$meta_text = __("Rows per page", 'lang_users');

			if(!($meta_value > 0))
			{
				$meta_value = 20;
			}

			$out .= "<tr class='".str_replace("_", "-", $meta_key)."-wrap'>
				<th><label for='".$meta_key."'>".$meta_text."</label></th>
				<td>".show_textfield(array('type' => 'number', 'name' => $meta_key, 'value' => $meta_value))."</td>
			</tr>";
		}

		/*if(in_array('password', $option))
		{
			$arr_remove['password'] = true;

			$meta_key = 'profile_password';
			$meta_text = __("New Password", 'lang_users');

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

	/*if(IS_SUPER_ADMIN)
	{
		$meta_key = 'profile_reminder';
		$meta_value = get_the_author_meta($meta_key, $user->ID);
		$meta_text = __("Send Updates", 'lang_users');

		$arr_data = array(
			'' => "-- ".__("Choose here", 'lang_users')." --",
			'day' => __("Daily", 'lang_users'),
			'week' => __("Weekly", 'lang_users'),
			'month' => __("Monthly", 'lang_users'),
		);

		echo "<table class='form-table'>
			<tr class='".str_replace("_", "-", $meta_key)."-wrap'>
				<th><label for='".$meta_key."'>".$meta_text."</label></th>
				<td>".show_select(array('data' => $arr_data, 'name' => $meta_key, 'value' => $meta_value, 'description' => __("This will send you an update of what has happened since you last was online or got an update last time", 'lang_users')))."</td>
			</tr>
		</table>";
	}*/

	if(count($arr_remove) > 0)
	{
		mf_enqueue_script('script_users', plugin_dir_url(__FILE__)."script_remove.js", $arr_remove, get_plugin_version(__FILE__));
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
			$avatar = "<img src='".$meta_value."' class='avatar avatar-".$size." photo' alt='".$alt."'>";
		}
	}

	return $avatar;
}

function wp_login_users($username)
{
	$user = get_user_by('login', $username);

	update_user_meta($user->ID, 'last_active', date("Y-m-d H:i:s"));

	$setting_users_register_name = get_option('setting_users_register_name');

	if($setting_users_register_name)
	{
		save_display_name($user);
	}
}

function wp_logout_users()
{
	update_user_meta(get_current_user_id(), 'last_active', date("Y-m-d H:i:s"));
}

function admin_head_users()
{
	global $wpdb;

	if(IS_ADMIN)
	{
		$setting_users_no_spaces = get_option('setting_users_no_spaces');
		$setting_users_register_name = get_option('setting_users_register_name');

		if($setting_users_no_spaces)
		{
			$users = get_users(array('fields' => 'all'));

			foreach($users as $user)
			{
				$user_data = get_userdata($user->ID);

				$username = replace_spaces_users($user_data->user_login);

				if($username != $user_data->user_login)
				{
					//wp_update_user(array('ID' => $user->ID, 'user_login' => $username)); //Does not work
					$wpdb->query($wpdb->prepare("UPDATE ".$wpdb->users." SET user_login = %s WHERE ID = '%d'", $username, $user->ID));
				}
			}
		}

		if($setting_users_register_name)
		{
			$users = get_users(array('fields' => 'all'));

			foreach($users as $user)
			{
				$user = get_userdata($user->ID);

				save_display_name($user);
			}
		}
	}
}

function own_media_users($wp_query)
{
	global $current_user;

	$wp_query = $wp_query;

	if(isset($wp_query->query['post_type']))
	{
		$option = get_option('setting_users_show_own_media');

		if($option != '' && !current_user_can($option) && (is_admin() && $wp_query->query['post_type'] === 'attachment'))
		{
			$wp_query->set('author', $current_user->ID);
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

	show_settings_fields(array('area' => $options_area, 'settings' => $arr_settings));
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

	echo show_select(array('data' => get_yes_no_for_select(array('return_integer' => true)), 'name' => $setting_key, 'value' => $option));
}

function setting_users_register_name_callback()
{
	$setting_key = get_setting_key(__FUNCTION__);
	$option = get_option($setting_key);

	echo show_select(array('data' => get_yes_no_for_select(array('return_integer' => true)), 'name' => $setting_key, 'value' => $option));
}

function setting_users_show_own_media_callback()
{
	$setting_key = get_setting_key(__FUNCTION__);
	$option = get_option($setting_key);

	$arr_data = get_roles_for_select(array('add_choose_here' => true));

	echo show_select(array('data' => $arr_data, 'name' => $setting_key, 'value' => $option, 'description' => __("Every user below this role only sees their own files in the Media Library", 'lang_users')));
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

	echo show_select(array('data' => $arr_data, 'name' => $setting_key."[]", 'value' => $option));
}

function setting_remove_profile_fields_callback()
{
	$setting_key = get_setting_key(__FUNCTION__);
	$option = get_option($setting_key, array('headings', 'rich_editing', 'syntax_highlight', 'admin_color', 'comment_shortcuts', 'show_admin_bar', 'language', 'user_login', 'nickname', 'url', 'aim', 'yim', 'jabber', 'description', 'profile_picture', 'sessions'));

	$arr_data = array(
		'headings' => __("Headings", 'lang_users'),
		'rich_editing' => __("Visual Editor", 'lang_users'),
		'syntax_highlight' => __("Syntax Highlighting", 'lang_users'),
		'admin_color' => __("Admin Color Scheme", 'lang_users'),
		'comment_shortcuts' => __("Keyboard Shortcuts", 'lang_users'),
		'show_admin_bar' => __("Toolbar", 'lang_users'),
		'language' => __("Language", 'lang_users'),
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

	if(is_array($option_add) && !in_array('profile_picture', $option_add) || !is_array($option_add))
	{
		$arr_data['profile_picture'] = __("Profile Picture", 'lang_users');
	}

	/*if(is_array($option_add) && !in_array('password', $option_add) || !is_array($option_add))
	{
		$arr_data['password'] = __("Password", 'lang_users');
	}*/

	$arr_data['sessions'] = __("Sessions", 'lang_users');

	echo show_select(array('data' => $arr_data, 'name' => $setting_key."[]", 'value' => $option));
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

	echo show_select(array('data' => $arr_data, 'name' => $setting_key, 'value' => $option));
}