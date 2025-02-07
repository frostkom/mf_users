<?php

class mf_users
{
	var $footer_output = "";

	function __construct(){}

	function wp_authenticate($username, $password)
	{
		$username = sanitize_user($username);
		$password = trim($password);

		//This is the extra line for replacing spaces in the username
		if(get_site_option('setting_users_no_spaces'))
		{
			$obj_users = new mf_users();

			$username = $obj_users->replace_spaces($username);
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

	function save_display_name($user)
	{
		if(isset($user->first_name) && $user->first_name != '' && isset($user->last_name) && $user->last_name != '')
		{
			$display_name = $user->first_name." ".$user->last_name;

			if($user->display_name != $display_name)
			{
				wp_update_user(array('ID' => $user->ID, 'display_name' => $display_name));
			}
		}
	}

	function replace_spaces($in)
	{
		return str_replace(" ", "-", $in);
	}

	function rename_roles()
	{
		global $wp_roles;

		$setting_users_roles_names = get_site_option('setting_users_roles_names');

		if(is_array($setting_users_roles_names))
		{
			foreach($setting_users_roles_names as $key => $value)
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

		$setting_users_roles_hidden = get_site_option('setting_users_roles_hidden');

		if(is_array($setting_users_roles_hidden))
		{
			foreach($setting_users_roles_hidden as $key => $value)
			{
				if($value == 1) // old way
				{
					unset($wp_roles->roles[$key]);
					unset($wp_roles->role_names[$key]);
				}

				else // new way
				{
					unset($wp_roles->roles[$value]);
					unset($wp_roles->role_names[$value]);
				}
			}
		}
	}

	function cron_base()
	{
		global $wpdb;

		$obj_cron = new mf_cron();
		$obj_cron->start(__CLASS__);

		if($obj_cron->is_running == false)
		{
			if(get_site_option('setting_users_no_spaces'))
			{
				$users = get_users(array('fields' => 'all'));

				foreach($users as $user)
				{
					$user_data = get_userdata($user->ID);

					if(isset($user_data->user_login))
					{
						$username = $this->replace_spaces($user_data->user_login);

						if($username != $user_data->user_login)
						{
							$wpdb->query($wpdb->prepare("UPDATE ".$wpdb->users." SET user_login = %s WHERE ID = '%d'", $username, $user->ID));
						}
					}
				}
			}

			if(get_option('setting_users_register_name'))
			{
				$users = get_users(array('fields' => 'all'));

				foreach($users as $user)
				{
					$user = get_userdata($user->ID);

					$this->save_display_name($user);
				}
			}

			/*$site_title = get_bloginfo('name');

			$users = get_users(array('fields' => 'all'));

			foreach($users as $user)
			{
				$user_id = $user->ID;

				$profile_reminder = get_user_meta($user_id, 'meta_profile_reminder');

				if($profile_reminder != '')
				{
					$user_last_active = get_user_meta($user_id, 'meta_last_active', true);

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

								$data = array(
									'to' => $user_data->user_email,
									'subject' => sprintf("Here comes the latest updates from %s", $site_title),
									'content' => $mail_content,
								);

								do_log("Send: ".var_export($data, true));
								$sent = true;

								//$sent = send_email($data);
							}
						}
					}
				}
			}*/
		}

		$obj_cron->end();
	}

	function send_new_user_notifications($user_id, $notify = 'user')
	{
		/*if(empty($notify) || $notify == 'admin')
		{
			return;
		}

		else if($notify == 'both')
		{
			// Only send the new user their email, not the admin
			$notify = 'user';

			wp_send_new_user_notifications($user_id, $notify);
		}*/

		if($notify == 'both')
		{
			// Only send the new user their email, not the admin
			$notify = 'user';

			wp_send_new_user_notifications($user_id, $notify);
		}

		else
		{
			return;
		}
	}

	function block_render_callback($attributes)
	{
		if(!isset($attributes['user_heading'])){		$attributes['user_heading'] = '';}
		if(!isset($attributes['user_ids'])){			$attributes['user_ids'] = array();}

		$out = "";

		$user_amount = count($attributes['user_ids']);

		if($user_amount > 0)
		{
			if($user_amount > 1)
			{
				$date_week = (int) date("W"); //, strtotime("2019-01-01")
				$date_weeks = 52; //date("w", strtotime(date("Y-12-31")))
				$user_keys = ($user_amount - 1);

				//$user_id_key = mt_rand(1, $user_keys);
				//$user_id_key = ($user_keys >= $date_weeks ? ($user_keys % $date_weeks) : ($user_keys % $date_week));
				$user_id_key = $date_week;

				while($user_id_key > $user_keys)
				{
					$user_id_key -= $user_keys;
				}

				$user_id = $attributes['user_ids'][$user_id_key];
			}

			else
			{
				$user_id = $attributes['user_ids'][0];
			}

			$profile_name = get_user_info(array('id' => $user_id));

			if($profile_name != '')
			{
				$profile_picture = get_the_author_meta('profile_picture', $user_id);
				$profile_description = apply_filters('filter_profile_description', get_the_author_meta('description', $user_id), $user_id);

				$out .= "<div".parse_block_attributes(array('class' => "widget user", 'attributes' => $attributes)).">";

					if($attributes['user_heading'] != '')
					{
						$attributes['user_heading'] = apply_filters('widget_title', $attributes['user_heading'], $attributes, $this->id_base);

						$out .= "<h3>".$attributes['user_heading']."</h3>";
					}

					$out .= "<div class='section'>
						<h4>".$profile_name."</h4>";

						if($profile_picture != '')
						{
							$out .= "<div class='image'><img src='".$profile_picture."'></div>";
						}

						$out .= "<div>"
							.apply_filters('the_content', $profile_description)
						."</div>"
					."</div>
				</div>";
			}

			else
			{
				do_log("The user ".$user_id." could not be found in widget_user()");
			}
		}

		return $out;
	}

	function filter_user_info_callback($data, $user, $arr_data)
	{
		if(get_the_author_meta('description', $user->ID) != '')
		{
			$arr_data[$user->ID] = $user->display_name;
		}

		return $arr_data;
	}

	function init()
	{
		load_plugin_textdomain('lang_users', false, str_replace("/include", "", dirname(plugin_basename(__FILE__)))."/lang/");

		// 
		#######################
		global $wpdb, $wp_roles;

		update_option($wpdb->prefix.'user_roles_orig', $wp_roles->roles, 'no');

		$this->rename_roles();
		$this->hide_roles();

		if(get_option('setting_users_send_registration_notification') != 'yes')
		{
			//Remove original user created emails
			remove_action('register_new_user', 'wp_send_new_user_notifications');
			remove_action('edit_user_created_user', 'wp_send_new_user_notifications', 10, 2);

			//Add new function to take over email creation
			add_action('register_new_user', array($this, 'send_new_user_notifications'));
			add_action('edit_user_created_user', array($this, 'send_new_user_notifications'), 10, 2);
		}
		#######################

		// Blocks
		#######################
		$plugin_include_url = plugin_dir_url(__FILE__);
		$plugin_version = get_plugin_version(__FILE__);

		wp_register_script('script_users_block_wp', $plugin_include_url."block/script_wp.js", array('wp-blocks', 'wp-element', 'wp-components', 'wp-editor', 'wp-block-editor'), $plugin_version, true);

		wp_localize_script('script_users_block_wp', 'script_users_block_wp', array(
			'block_title' => __("User", 'lang_users'),
			'block_description' => __("Display information about a user", 'lang_users'),
			'user_heading_label' => __("Heading", 'lang_users'),
			'user_ids_label' => __("Users", 'lang_users'),
			'user_ids' => get_users_for_select(array('callback' => array($this, 'filter_user_info_callback'))),
		));

		register_block_type('mf/users', array(
			'editor_script' => 'script_users_block_wp',
			'editor_style' => 'style_base_block_wp',
			'render_callback' => array($this, 'block_render_callback'),
			//'style' => 'style_base_block_wp',
		));
		#######################
	}

	function settings_users()
	{
		$options_area_orig = $options_area = __FUNCTION__;

		// Generic
		############################
		add_settings_section($options_area, "", array($this, $options_area."_callback"), BASE_OPTIONS_PAGE);

		$arr_settings = array();

		$arr_settings['setting_users_show_own_media'] = __("Only show users own files", 'lang_users');

		if(IS_SUPER_ADMIN)
		{
			$arr_settings['setting_users_no_spaces'] = __("Prevent Username Spaces", 'lang_users');
		}

		$arr_settings['setting_users_register_name'] = __("Collect name of user in registration form", 'lang_users');

		if(IS_SUPER_ADMIN)
		{
			$arr_settings['setting_users_send_registration_notification'] = __("Send User Registration Notification to Admin", 'lang_users');
			$arr_settings['setting_users_send_password_change_notification'] = __("Send Password Changed Notification", 'lang_users');
		}

		$arr_settings['setting_users_display_author_pages'] = __("Display Author Pages", 'lang_users');

		show_settings_fields(array('area' => $options_area, 'object' => $this, 'settings' => $arr_settings));
		############################

		if(IS_SUPER_ADMIN)
		{
			// Roles
			############################
			$options_area = $options_area_orig."_roles";

			add_settings_section($options_area, "", array($this, $options_area."_callback"), BASE_OPTIONS_PAGE);

			$arr_settings = array();
			$arr_settings['setting_users_roles_hidden'] = __("Hide Roles", 'lang_users');
			$arr_settings['setting_users_roles_names'] = __("Change Role Names", 'lang_users');

			show_settings_fields(array('area' => $options_area, 'object' => $this, 'settings' => $arr_settings));
			############################
		}

		// Profile
		############################
		$options_area = $options_area_orig."_profile";

		add_settings_section($options_area, "", array($this, $options_area."_callback"), BASE_OPTIONS_PAGE);

		$arr_settings = array();
		$arr_settings['setting_add_profile_fields'] = __("Add fields to profile", 'lang_users');
		$arr_settings['setting_remove_profile_fields'] = __("Remove fields from profile", 'lang_users');

		$setting_remove_profile_fields = get_option('setting_remove_profile_fields');

		if(is_array($setting_remove_profile_fields) && in_array('admin_color', $setting_remove_profile_fields))
		{
			$arr_settings['setting_admin_color'] = __("Change Admin Color", 'lang_users');
		}

		show_settings_fields(array('area' => $options_area, 'object' => $this, 'settings' => $arr_settings));
		############################
	}

	function settings_users_callback()
	{
		$setting_key = get_setting_key(__FUNCTION__);

		echo settings_header($setting_key, __("Users", 'lang_users'));
	}

		function setting_users_show_own_media_callback()
		{
			$setting_key = get_setting_key(__FUNCTION__);
			$option = get_option($setting_key);

			echo show_select(array('data' => get_roles_for_select(array('add_choose_here' => true)), 'name' => $setting_key, 'value' => $option, 'description' => __("Every user below this role only sees their own files in the Media Library", 'lang_users')));
		}

		function setting_users_no_spaces_callback()
		{
			$setting_key = get_setting_key(__FUNCTION__);
			settings_save_site_wide($setting_key);
			$option = get_site_option($setting_key, get_option($setting_key, 1));

			echo show_select(array('data' => get_yes_no_for_select(array('return_integer' => true)), 'name' => $setting_key, 'value' => $option));
		}

		function setting_users_register_name_callback()
		{
			$setting_key = get_setting_key(__FUNCTION__);
			$option = get_option($setting_key);

			echo show_select(array('data' => get_yes_no_for_select(array('return_integer' => true)), 'name' => $setting_key, 'value' => $option));
		}

		function setting_users_send_registration_notification_callback()
		{
			$setting_key = get_setting_key(__FUNCTION__);
			settings_save_site_wide($setting_key);
			$option = get_site_option($setting_key, get_option($setting_key, 'no'));

			echo show_select(array('data' => get_yes_no_for_select(), 'name' => $setting_key, 'value' => $option));
		}

		function setting_users_send_password_change_notification_callback()
		{
			$setting_key = get_setting_key(__FUNCTION__);
			settings_save_site_wide($setting_key);
			$option = get_site_option($setting_key, get_option($setting_key, 'no'));

			echo show_select(array('data' => get_yes_no_for_select(), 'name' => $setting_key, 'value' => $option));
		}

		function setting_users_display_author_pages_callback()
		{
			$setting_key = get_setting_key(__FUNCTION__);
			$option = get_option_or_default($setting_key, 'no');

			echo show_select(array('data' => get_yes_no_for_select(), 'name' => $setting_key, 'value' => $option));
		}

	function settings_users_roles_callback()
	{
		$setting_key = get_setting_key(__FUNCTION__);

		echo settings_header($setting_key, __("Users", 'lang_users')." - ".__("Roles", 'lang_users'));
	}

		function setting_users_roles_hidden_callback()
		{
			$setting_key = get_setting_key(__FUNCTION__);
			settings_save_site_wide($setting_key);
			$option = get_site_option($setting_key, get_option($setting_key));

			$roles = get_all_roles(array('orig' => true));

			$arr_data = array();

			foreach($roles as $key => $value)
			{
				if(isset($option[$key]) && $option[$key] == 1) // Convert from old to new way
				{
					$option[] = $key;
				}

				$arr_data[$key] = __($value);
			}

			echo show_select(array('data' => $arr_data, 'name' => $setting_key."[]", 'value' => $option));
		}

		function setting_users_roles_names_callback()
		{
			$setting_key = get_setting_key(__FUNCTION__);
			settings_save_site_wide($setting_key);
			$option = get_site_option($setting_key, get_option($setting_key));

			$roles = get_all_roles();

			foreach($roles as $key => $value)
			{
				$option_value = (isset($option[$key]) ? $option[$key] : '');

				echo show_textfield(array('name' => $setting_key."[".$key."]", 'value' => $option_value, 'placeholder' => $value));
			}
		}

	function settings_users_profile_callback()
	{
		$setting_key = get_setting_key(__FUNCTION__);

		echo settings_header($setting_key, __("Users", 'lang_users')." - ".__("Profile", 'lang_users'));
	}

		function setting_add_profile_fields_callback()
		{
			$setting_key = get_setting_key(__FUNCTION__);
			$option = get_option($setting_key);

			$arr_data = array(
				'profile_birthday' => __("Birthday", 'lang_users'),
				'phone' => __("Phone Number", 'lang_users'),
				'profile_company' => __("Company", 'lang_users'),
				'profile_address' => __("Address", 'lang_users'),
				'profile_picture' => __("Profile Picture", 'lang_users'),
			);

			if(is_plugin_active("mf_address/index.php"))
			{
				$arr_data['profile_country'] = __("Country", 'lang_users');
			}

			$arr_data['edit_page_per_page'] = __("Rows / Page", 'lang_users');

			echo show_select(array('data' => $arr_data, 'name' => $setting_key."[]", 'value' => $option));
		}

		function setting_remove_profile_fields_callback()
		{
			$setting_key = get_setting_key(__FUNCTION__);
			$option = get_option($setting_key, array('headings', 'rich_editing', 'syntax_highlight', 'admin_color', 'comment_shortcuts', 'show_admin_bar', 'language', 'user_login', 'nickname', 'url', 'aim', 'yim', 'jabber', 'description', 'profile_picture', 'application_password', 'sessions'));

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
				'aim' => "AIM",
				'yim' => "Yahoo IM",
				'jabber' => "Jabber",
				'description' => __("Biographical Info", 'lang_users'),
			);

			$option_add = get_option('setting_add_profile_fields');

			if(is_array($option_add) && !in_array('profile_picture', $option_add) || !is_array($option_add))
			{
				$arr_data['profile_picture'] = __("Profile Picture", 'lang_users');
			}

			$arr_data['application_password'] = __("Application Password", 'lang_users');
			$arr_data['sessions'] = __("Sessions", 'lang_users');

			if(!function_exists('is_plugin_active') || function_exists('is_plugin_active') && is_plugin_active("backwpup/backwpup.php"))
			{
				$arr_data['backwpup'] = sprintf(__("Add %s Role", 'lang_users'), "BackWPup");
			}

			echo show_select(array('data' => $arr_data, 'name' => $setting_key."[]", 'value' => $option));
		}

		function setting_admin_color_callback()
		{
			$setting_key = get_setting_key(__FUNCTION__);
			$option = get_option($setting_key);

			$arr_data = array(
				'' => "-- ".__("Choose Here", 'lang_users')." --",
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

	function admin_init()
	{
		global $wpdb, $pagenow;

		if($pagenow == 'user-new.php')
		{
			$register_url = wp_registration_url();

			/*if(strpos($register_url, $pagenow) === false)
			{
				mf_redirect($register_url);
			}*/

			if(is_multisite() && IS_SUPER_ADMIN)
			{
				mf_redirect(network_admin_url("site-users.php?id=".$wpdb->blogid."#add-existing-user"));
			}

			else if(current_user_can('list_users') == false)
			{
				wp_logout();

				mf_redirect($register_url);
			}
		}

		$this->wp_head();
	}

	/*function pre_user_query($wp_user_query)
	{
		if(strpos($wp_user_query->query_where, '@') === false && isset($_GET['s']) && $_GET['s'] != '')
		{
			global $wpdb;

			$arr_users = array();

			$search = strtolower(check_var('s'));

			if(preg_match('/\s/', $search))
			{
				$pieces = explode(" ", $search);

				$result = $wpdb->get_results("SELECT DISTINCT user_id FROM ".$wpdb->usermeta." WHERE (meta_key = 'first_name' AND LOWER(meta_value) LIKE '%".$pieces[0]."%')");

				foreach($result as $user)
				{
					if(strtolower(get_user_meta($user->user_id, 'last_name', true)) == strtolower($pieces[1]))
					{
						array_push($arr_users, $user->user_id);
					}
				}
			}

			else
			{
				$result = $wpdb->get_results("SELECT DISTINCT user_id FROM ".$wpdb->usermeta." WHERE (meta_key = 'first_name' OR meta_key = 'last_name') AND LOWER(meta_value) LIKE '%".$search."%'");

				foreach($result as $user)
				{
					array_push($arr_users, $user->user_id);
				}
			}

			if(count($arr_users) > 0)
			{
				$id_string = implode(",", $arr_users);

				$wp_user_query->query_where = str_replace("WHERE 1=1 AND (", "WHERE (ID IN (".$id_string.") OR ", $wp_user_query->query_where);

				do_log("Test: ".var_export($wp_user_query, true));
			}
		}

		return $wp_user_query;
	}*/

	function pre_get_posts($wp_query)
	{
		global $current_user;

		$wp_query = $wp_query;

		if(isset($wp_query->query['post_type']) && $wp_query->query['post_type'] === 'attachment') //(is_admin() && )
		{
			$option = get_option('setting_users_show_own_media');

			if($option != '' && !current_user_can($option))
			{
				$wp_query->set('author', $current_user->ID);
			}
		}
	}

	function admin_action_inactivate_user()
	{
		global $wpdb;

		check_admin_referer('inactivate_user');

		if(current_user_can('edit_user', get_current_user_id()))
		{
			$user_id = check_var('user_id', 'int');

			if(is_multisite() && $wpdb->blogid > 1)
			{
				$wp_capabilities = "wp_".$wpdb->blogid."_capabilities";
				$wp_user_level = "wp_".$wpdb->blogid."_user_level";
			}

			else
			{
				$wp_capabilities = "wp_capabilities";
				$wp_user_level = "wp_user_level";
			}

			update_user_meta($user_id, $wp_capabilities, array());
			update_user_meta($user_id, $wp_user_level, 0);
		}
	}

	function manage_users_columns($cols)
	{
		unset($cols['posts']);

		$cols['meta_last_active'] = __("Last Logged In", 'lang_users');

		return $cols;
	}

	function manage_users_custom_column($value, $col, $id)
	{
		switch($col)
		{
			case 'meta_last_active':
				$meta_last_logged_in = get_the_author_meta('meta_last_logged_in', $id);
				$meta_last_active = get_the_author_meta($col, $id);

				$out = "";

				if($meta_last_logged_in > DEFAULT_DATE)
				{
					$meta_last_logged_out = get_the_author_meta('meta_last_logged_out', $id);

					$out = format_date($meta_last_logged_in);

					if($meta_last_logged_out > $meta_last_logged_in)
					{
						if(format_date($meta_last_logged_out) > $out)
						{
							$out .= " - ".format_date($meta_last_logged_out);
						}

						else
						{
							$out .= date("H:i", strtotime($meta_last_logged_in))." - ".date("H:i", strtotime($meta_last_logged_out));
						}
					}

					else
					{
						if(format_date($meta_last_active) > $out)
						{
							$out .= " - ".format_date($meta_last_active);
						}

						else if(date("Y-m-d", strtotime($out)) < date("Y-m-d", strtotime("-6 day")))
						{
							$out .= date("H:i", strtotime($meta_last_logged_in))." - ".date("H:i", strtotime($meta_last_active));
						}
					}
				}

				else if($meta_last_active > DEFAULT_DATE)
				{
					$out = format_date($meta_last_active);
				}

				else
				{
					$user_data = get_userdata($id);

					$out = "<span class='grey' title='".__("Created", 'lang_users')."'>".format_date($user_data->user_registered)."</span>";
				}

				if($id == get_current_user_id())
				{
					$out .= "<i class='set_tr_color' rel='green'></i>";
				}

				return $out;
			break;
		}

		return $value;
	}

	function user_row_actions($actions, $user, $is_multisite = false)
	{
		unset($actions['view']);
		unset($actions['resetpassword']);

		if($is_multisite == false && (!isset($user->roles[0]) || $user->roles[0] == ''))
		{
			$actions['inactive'] = "<span class='grey'>".__("Inactive", 'lang_users')."</span><i class='set_tr_color' rel='red'></i>";

			/*if(IS_SUPER_ADMIN)
			{
				$actions['inactive'] .= " (".var_export($user->roles, true).")";
			}*/
		}

		else if(get_current_user_id() != $user->ID && current_user_can('edit_user'))
		{
			$site_id = (isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0);
			$url = ('site-users-network' == get_current_screen()->id ? add_query_arg(array('id' => $site_id), 'site-users.php') : 'users.php');

			$url = wp_nonce_url(add_query_arg(array(
				'action' => 'inactivate_user',
				'user_id' => $user->ID,
			), $url), 'inactivate_user');

			$actions['inactivate'] = "<a href='".esc_url($url)."' rel='confirm'>".__("Inactivate", 'lang_users')."</a>";
		}

		return $actions;
	}

	function ms_user_row_actions($actions, $user)
	{
		return $this->user_row_actions($actions, $user, true);
	}

	function edit_user_profile($user)
	{
		global $obj_address;

		$arr_remove = array();

		$setting_remove_profile_fields = get_option('setting_remove_profile_fields');

		if(is_array($setting_remove_profile_fields) && count($setting_remove_profile_fields) > 0)
		{
			foreach($setting_remove_profile_fields as $remove)
			{
				$arr_remove[$remove] = true;
			}
		}

		$setting_add_profile_fields = get_option('setting_add_profile_fields');

		if(is_array($setting_add_profile_fields) && count($setting_add_profile_fields) > 0 && isset($user->ID))
		{
			$out = "";

			$meta_key = 'profile_birthday';
			if(in_array($meta_key, $setting_add_profile_fields))
			{
				$meta_value = get_the_author_meta($meta_key, $user->ID);
				$meta_text = __("Birthday", 'lang_users');

				$out .= "<tr class='".str_replace("_", "-", $meta_key)."-wrap'>
					<th><label for='".$meta_key."'>".$meta_text."</label></th>
					<td>".show_textfield(array('type' => 'date', 'name' => $meta_key, 'value' => $meta_value, 'xtra' => "class='regular-text'"))."</td>
				</tr>";
			}

			if(in_array('phone', $setting_add_profile_fields))
			{
				$meta_key = 'profile_phone';
				$meta_value = get_the_author_meta($meta_key, $user->ID);
				$meta_text = __("Phone Number", 'lang_users');

				$out .= "<tr class='".str_replace("_", "-", $meta_key)."-wrap'>
					<th><label for='".$meta_key."'>".$meta_text."</label></th>
					<td>".show_textfield(array('name' => $meta_key, 'value' => $meta_value, 'xtra' => "class='regular-text'"))."</td>
				</tr>";
			}

			$meta_key = 'profile_company';
			if(in_array($meta_key, $setting_add_profile_fields))
			{
				$meta_value = get_the_author_meta($meta_key, $user->ID);
				$meta_text = __("Company", 'lang_users');

				$out .= "<tr class='".str_replace("_", "-", $meta_key)."-wrap'>
					<th><label for='".$meta_key."'>".$meta_text."</label></th>
					<td>".show_textfield(array('name' => $meta_key, 'value' => $meta_value, 'xtra' => "class='regular-text'"))."</td>
				</tr>";
			}

			$meta_key = 'profile_address';
			if(in_array($meta_key, $setting_add_profile_fields))
			{
				$meta_key_temp = $meta_key.'_street';
				$meta_value = get_the_author_meta($meta_key_temp, $user->ID);
				$meta_text = __("Street Address", 'lang_users');

				$out .= "<tr class='".str_replace("_", "-", $meta_key_temp)."-wrap'>
					<th><label for='".$meta_key_temp."'>".$meta_text."</label></th>
					<td>".show_textfield(array('name' => $meta_key_temp, 'value' => $meta_value, 'xtra' => "class='regular-text'"))."</td>
				</tr>";

				$meta_key_temp = $meta_key.'_zipcode';
				$meta_value = get_the_author_meta($meta_key_temp, $user->ID);
				$meta_text = __("Zipcode", 'lang_users');

				$out .= "<tr class='".str_replace("_", "-", $meta_key_temp)."-wrap'>
					<th><label for='".$meta_key_temp."'>".$meta_text."</label></th>
					<td>".show_textfield(array('name' => $meta_key_temp, 'value' => $meta_value, 'xtra' => "class='regular-text'"))."</td>
				</tr>";

				$meta_key_temp = $meta_key.'_city';
				$meta_value = get_the_author_meta($meta_key_temp, $user->ID);
				$meta_text = __("City", 'lang_users');

				$out .= "<tr class='".str_replace("_", "-", $meta_key_temp)."-wrap'>
					<th><label for='".$meta_key_temp."'>".$meta_text."</label></th>
					<td>".show_textfield(array('name' => $meta_key_temp, 'value' => $meta_value, 'xtra' => "class='regular-text'"))."</td>
				</tr>";
			}

			$meta_key = 'profile_picture';
			if(in_array($meta_key, $setting_add_profile_fields))
			{
				$arr_remove[$meta_key] = true;

				$meta_value = get_the_author_meta($meta_key, $user->ID);
				$meta_text = __("Profile Picture", 'lang_users');

				$out .= "<tr class='".str_replace("_", "-", $meta_key)."-wrap'>
					<th><label for='".$meta_key."'>".$meta_text."</label></th>
					<td>".get_media_library(array('type' => 'image', 'name' => $meta_key, 'value' => $meta_value))."</td>
				</tr>";
			}

			$meta_key = 'profile_country';
			if(in_array($meta_key, $setting_add_profile_fields) && is_plugin_active("mf_address/index.php"))
			{
				$meta_value = get_the_author_meta($meta_key, $user->ID);
				$meta_text = __("Country", 'lang_users');

				if(!isset($obj_address))
				{
					$obj_address = new mf_address();
				}

				$out .= "<tr class='".str_replace("_", "-", $meta_key)."-wrap'>
					<th><label for='".$meta_key."'>".$meta_text."</label></th>
					<td>".show_select(array('data' => $obj_address->get_countries_for_select(), 'name' => $meta_key, 'value' => $meta_value, 'xtra' => "class='regular-text'"))."</td>
				</tr>";
			}

			$meta_key = 'edit_page_per_page';
			if(in_array($meta_key, $setting_add_profile_fields))
			{
				$meta_value = get_the_author_meta($meta_key, $user->ID);
				$meta_text = __("Rows / Page", 'lang_users');

				if(!($meta_value > 0))
				{
					$meta_value = 20;
				}

				$out .= "<tr class='".str_replace("_", "-", $meta_key)."-wrap'>
					<th><label for='".$meta_key."'>".$meta_text."</label></th>
					<td>".show_textfield(array('type' => 'number', 'name' => $meta_key, 'value' => $meta_value))."</td>
				</tr>";
			}

			if($out != '')
			{
				echo "<table class='form-table'>".$out."</table>";
			}
		}

		/*if(IS_SUPER_ADMIN)
		{
			$meta_key = 'meta_profile_reminder';
			$meta_value = get_user_meta($user->ID, $meta_key);
			$meta_text = "Send Updates";

			$arr_data = array(
				'' => "-- "."Choose Here"." --",
				'day' => "Daily",
				'week' => "Weekly",
				'month' => "Monthly",
			);

			echo "<table class='form-table'>
				<tr class='".str_replace("_", "-", $meta_key)."-wrap'>
					<th><label for='".$meta_key."'>".$meta_text."</label></th>
					<td>".show_select(array('data' => $arr_data, 'name' => $meta_key, 'value' => $meta_value, 'description' => "This will send you an update of what has happened since you last was online or got an update last time"))."</td>
				</tr>
			</table>";
		}*/

		if(count($arr_remove) > 0)
		{
			mf_enqueue_script('script_users', plugin_dir_url(__FILE__)."script_remove.js", $arr_remove, get_plugin_version(__FILE__)); //Should be moved to admin_init
		}
	}

	function profile_update($user_id)
	{
		if(current_user_can('edit_user', $user_id))
		{
			$this->user_register($user_id);
		}
	}

	function get_user_option_admin_color($color)
	{
		$setting_admin_color = get_option('setting_admin_color');

		if($setting_admin_color != '')
		{
			$color = $setting_admin_color;
		}

		return $color;
	}

	function admin_footer()
	{
		$this->wp_footer();
	}

	function wp_sitemaps_add_provider($provider, $name)
	{
		return ('users' === $name && get_option_or_default('setting_users_display_author_pages', 'no') == 'no' ? false : $provider);
	}

	function template_redirect()
	{
		if(is_author() && get_option_or_default('setting_users_display_author_pages', 'no') == 'no')
		{
			wp_redirect(get_option('home'), 301);
			exit;
		}
	}

	function register_form()
	{
		if(get_option('setting_users_register_name'))
		{
			$meta_key = 'full_name';
			$meta_value = check_var($meta_key);
			$meta_text = __("Full Name", 'lang_users');

			$post_id = apply_filters('get_block_search', 'mf/users');

			if(!($post_id > 0))
			{
				$post_id = apply_filters('get_widget_search', 'registration-widget');
			}

			if($post_id > 0)
			{
				echo show_textfield(array('name' => $meta_key, 'text' => $meta_text, 'value' => $meta_value, 'required' => true));
			}

			else
			{
				echo "<p>
					<label for='".$meta_key."'>".$meta_text."</label><br>
					<input type='text' name='".$meta_key."' value='".$meta_value."' class='regular-text' required>
				</p>";
			}
		}
	}

	function user_register($user_id, $password = "", $meta = array())
	{
		if(get_option('setting_users_register_name') && isset($_REQUEST['full_name']))
		{
			$full_name = check_var('full_name');

			@list($first_name, $last_name) = explode(" ", $full_name, 2);

			update_user_meta($user_id, 'first_name', $first_name);
			update_user_meta($user_id, 'last_name', $last_name);
		}

		$setting_add_profile_fields = get_option('setting_add_profile_fields');

		$meta_key = 'profile_birthday';
		if(is_array($setting_add_profile_fields) && in_array($meta_key, $setting_add_profile_fields))
		{
			$meta_value = check_var($meta_key);

			update_user_meta($user_id, $meta_key, $meta_value);
		}

		if(is_array($setting_add_profile_fields) && in_array('phone', $setting_add_profile_fields))
		{
			$meta_key = 'profile_phone';
			$meta_value = check_var($meta_key);

			update_user_meta($user_id, $meta_key, $meta_value);
		}

		$meta_key = 'profile_company';
		if(is_array($setting_add_profile_fields) && in_array($meta_key, $setting_add_profile_fields))
		{
			$meta_value = check_var($meta_key);

			update_user_meta($user_id, $meta_key, $meta_value);
		}

		$meta_key = 'profile_address';
		if(is_array($setting_add_profile_fields) && in_array($meta_key, $setting_add_profile_fields))
		{
			$meta_key_temp = $meta_key.'_street';
			$meta_value = check_var($meta_key_temp);

			update_user_meta($user_id, $meta_key_temp, $meta_value);

			$meta_key_temp = $meta_key.'_zipcode';
			$meta_value = check_var($meta_key_temp);

			update_user_meta($user_id, $meta_key_temp, $meta_value);

			$meta_key_temp = $meta_key.'_city';
			$meta_value = check_var($meta_key_temp);

			update_user_meta($user_id, $meta_key_temp, $meta_value);
		}

		$meta_key = 'edit_page_per_page';
		if(is_array($setting_add_profile_fields) && in_array($meta_key, $setting_add_profile_fields))
		{
			$meta_value = check_var($meta_key);

			update_user_meta($user_id, $meta_key, $meta_value);
		}

		$meta_key = 'profile_picture';
		if(is_array($setting_add_profile_fields) && in_array($meta_key, $setting_add_profile_fields))
		{
			$meta_value = check_var($meta_key);

			update_user_meta($user_id, $meta_key, $meta_value);
		}

		$meta_key = 'profile_country';
		if(is_array($setting_add_profile_fields) && in_array($meta_key, $setting_add_profile_fields) && is_plugin_active("mf_address/index.php"))
		{
			$meta_value = check_var($meta_key);

			update_user_meta($user_id, $meta_key, $meta_value);
		}

		/*$meta_value = check_var('meta_profile_reminder');
		update_user_meta($user_id, 'meta_profile_reminder', $meta_value);*/
	}

	function filter_profile_fields($arr_fields)
	{
		global $obj_address;

		$arr_remove = array();

		$setting_remove_profile_fields = get_option('setting_remove_profile_fields', array());

		if(is_array($setting_remove_profile_fields) && count($setting_remove_profile_fields) > 0)
		{
			foreach($setting_remove_profile_fields as $remove)
			{
				$arr_remove[$remove] = true;
			}
		}

		$meta_key = 'description';
		if(is_array($setting_remove_profile_fields) && !in_array($meta_key, $setting_remove_profile_fields))
		{
			$arr_fields[] = array('type' => 'textarea', 'name' => $meta_key, 'text' => __("Biographical Info", 'lang_users'));
		}

		$setting_add_profile_fields = get_option('setting_add_profile_fields');

		if(is_array($setting_add_profile_fields) && count($setting_add_profile_fields) > 0)
		{
			$meta_key = 'profile_birthday';
			if(in_array($meta_key, $setting_add_profile_fields))
			{
				$arr_fields[] = array('type' => 'date', 'name' => $meta_key, 'text' => __("Birthday", 'lang_users'));
			}

			if(in_array('phone', $setting_add_profile_fields))
			{
				$meta_key = 'profile_phone';

				$arr_fields[] = array('type' => 'text', 'name' => $meta_key, 'text' => __("Phone Number", 'lang_users'));
			}

			$meta_key = 'profile_company';
			if(in_array($meta_key, $setting_add_profile_fields))
			{
				$arr_fields[] = array('type' => 'text', 'name' => $meta_key, 'text' => __("Company", 'lang_users'));
			}

			$meta_key = 'profile_address';
			if(in_array($meta_key, $setting_add_profile_fields))
			{
				$arr_fields[] = array('type' => 'text', 'name' => $meta_key.'_street', 'text' => __("Street Address", 'lang_users'));
				$arr_fields[] = array('type' => 'text', 'name' => $meta_key.'_zipcode', 'text' => __("Zipcode", 'lang_users'));
				$arr_fields[] = array('type' => 'text', 'name' => $meta_key.'_city', 'text' => __("City", 'lang_users'));
			}

			$meta_key = 'profile_picture';
			if(in_array($meta_key, $setting_add_profile_fields))
			{
				$arr_fields[] = array('type' => 'media_image', 'name' => $meta_key, 'text' => __("Profile Picture", 'lang_users'));
			}

			$meta_key = 'profile_country';
			if(in_array($meta_key, $setting_add_profile_fields) && is_plugin_active("mf_address/index.php"))
			{
				if(!isset($obj_address))
				{
					$obj_address = new mf_address();
				}

				$arr_fields[] = array('type' => 'select', 'options' => $obj_address->get_countries_for_select(), 'name' => $meta_key, 'text' => __("Country", 'lang_users'));
			}

			$meta_key = 'edit_page_per_page';
			if(in_array($meta_key, $setting_add_profile_fields))
			{
				$arr_fields[] = array('type' => 'number', 'name' => $meta_key, 'text' => __("Rows / Page", 'lang_users'));
			}
		}

		foreach($arr_remove as $remove_key => $remove_value)
		{
			foreach($arr_fields as $field_key => $field_value)
			{
				if(isset($field_value['name']) && $remove_key == $field_value['name'])
				{
					unset($arr_fields[$field_key]);
				}
			}
		}

		return $arr_fields;
	}

	function registration_errors($errors, $user_login, $user_email)
	{
		if(preg_match('/ /', $user_login))
		{
			$errors->add('user_login', __("Username must not contain spaces", 'lang_users'));
		}

		return $errors;
	}

	function wp_head()
	{
		$setting_add_profile_fields = get_option('setting_add_profile_fields');

		$meta_key = 'profile_birthday';
		if(is_array($setting_add_profile_fields) && in_array($meta_key, $setting_add_profile_fields))
		{
			$user_id = get_current_user_id();

			if($user_id > 0)
			{
				$meta_value = get_the_author_meta($meta_key, $user_id);

				if(date("m-d", strtotime($meta_value)) == date("m-d"))
				{
					$user_data = get_userdata($user_id);

					if(isset($user_data->display_name))
					{
						$this->footer_output = "<div id='modal_birthday'>"
							."<div class='balloons'>
								<div></div>
								<div></div>
								<div></div>
								<div></div>
								<div></div>
							</div>
							<div class='content'>"
								//."<i class='fa fa-birthday-cake'></i> "
								.sprintf(__("Happy Birthday %s!", 'lang_users'), ($user_data->first_name != '' ? $user_data->first_name : $user_data->display_name))
							."</div>
						</div>";

						$plugin_include_url = plugin_dir_url(__FILE__);

						mf_enqueue_style('style_users_birthday', $plugin_include_url."style_birthday.css");
					}
				}
			}
		}

		if(apply_filters('get_block_search', 'mf/users') > 0 || !is_plugin_active("mf_widget_logic_select/index.php") || apply_filters('get_widget_search', 'user-widget') > 0)
		{
			$plugin_include_url = plugin_dir_url(__FILE__);

			mf_enqueue_style('style_user_widget', $plugin_include_url."style_widget.css");
		}
	}

	function wp_footer()
	{
		if($this->footer_output != '')
		{
			echo $this->footer_output;
		}

		if(is_user_logged_in())
		{
			$this->wp_active();
		}
	}

	function get_avatar($avatar, $id_or_email, $size, $default, $alt)
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

	function wp_login($username)
	{
		$user = get_user_by('login', $username);

		$user_id = $user->ID;
		$user_timestamp = date("Y-m-d H:i:s");

		update_user_meta($user_id, 'meta_last_logged_in', $user_timestamp);
		update_user_meta($user_id, 'meta_last_active', $user_timestamp);

		$this->save_display_name($user);
	}

	function wp_active()
	{
		$user_id = get_current_user_id();
		$user_timestamp = date("Y-m-d H:i:s");

		update_user_meta($user_id, 'meta_last_active', $user_timestamp);
	}

	function wp_logout()
	{
		$user_id = get_current_user_id();
		$user_timestamp = date("Y-m-d H:i:s");

		update_user_meta($user_id, 'meta_last_active', $user_timestamp);
		update_user_meta($user_id, 'meta_last_logged_out', $user_timestamp);
	}

	function widgets_init()
	{
		register_widget('widget_user');
	}
}

class widget_user extends WP_Widget
{
	var $widget_ops;
	var $arr_default = array(
		'user_heading' => "",
		'user_ids' => array(),
	);

	function __construct()
	{
		$this->widget_ops = array(
			'classname' => 'user',
			'description' => __("Display information about a user", 'lang_users'),
		);

		parent::__construct(str_replace("_", "-", $this->widget_ops['classname']).'-widget', __("User", 'lang_users'), $this->widget_ops);
	}

	function widget($args, $instance)
	{
		extract($args);
		$instance = wp_parse_args((array)$instance, $this->arr_default);

		$user_amount = count($instance['user_ids']);

		if($user_amount > 0)
		{
			if($user_amount > 1)
			{
				$date_week = (int) date("W"); //, strtotime("2019-01-01")
				$date_weeks = 52; //date("w", strtotime(date("Y-12-31")))
				$user_keys = ($user_amount - 1);

				//$user_id_key = mt_rand(1, $user_keys);
				//$user_id_key = ($user_keys >= $date_weeks ? ($user_keys % $date_weeks) : ($user_keys % $date_week));
				$user_id_key = $date_week;

				while($user_id_key > $user_keys)
				{
					$user_id_key -= $user_keys;
				}

				$user_id = $instance['user_ids'][$user_id_key];
			}

			else
			{
				$user_id = $instance['user_ids'][0];
			}

			$profile_name = get_user_info(array('id' => $user_id));

			if($profile_name != '')
			{
				$profile_picture = get_the_author_meta('profile_picture', $user_id);
				$profile_description = apply_filters('filter_profile_description', get_the_author_meta('description', $user_id), $user_id);

				echo apply_filters('filter_before_widget', $before_widget);

					if($instance['user_heading'] != '')
					{
						$instance['user_heading'] = apply_filters('widget_title', $instance['user_heading'], $instance, $this->id_base);

						echo $before_title
							.$instance['user_heading']
						.$after_title;
					}

					echo "<div class='section'>
						<h4>".$profile_name."</h4>";

						if($profile_picture != '')
						{
							echo "<div class='image'><img src='".$profile_picture."'></div>";
						}

						echo "<div>"
							.apply_filters('the_content', $profile_description)
						."</div>"
					."</div>"
				.$after_widget;
			}

			else
			{
				do_log("The user ".$user_id." could not be found in widget_user()");
			}
		}
	}

	function update($new_instance, $old_instance)
	{
		$instance = $old_instance;
		$new_instance = wp_parse_args((array)$new_instance, $this->arr_default);

		$instance['user_heading'] = sanitize_text_field($new_instance['user_heading']);
		$instance['user_ids'] = is_array($new_instance['user_ids']) ? $new_instance['user_ids'] : array();

		return $instance;
	}

	function filter_user_info_callback($data, $user, $arr_data)
	{
		if(get_the_author_meta('description', $user->ID) != '')
		{
			$arr_data[$user->ID] = $user->display_name;
		}

		return $arr_data;
	}

	function form($instance)
	{
		$instance = wp_parse_args((array)$instance, $this->arr_default);

		echo "<div class='mf_form'>"
			.show_textfield(array('name' => $this->get_field_name('user_heading'), 'text' => __("Heading", 'lang_users'), 'value' => $instance['user_heading'], 'xtra' => " id='".$this->widget_ops['classname']."-title'"))
			.show_select(array('data' => get_users_for_select(array('callback' => array($this, 'filter_user_info_callback'))), 'name' => $this->get_field_name('user_ids')."[]", 'text' => __("Users", 'lang_users'), 'value' => $instance['user_ids']))
		."</div>";
	}
}