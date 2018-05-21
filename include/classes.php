<?php

class mf_users
{
	function __construct(){}

	function admin_init()
	{
		$this->wp_head();
	}

	function wp_head()
	{
		$option = get_option('setting_add_profile_fields');

		$meta_key = 'profile_birthday';
		if(is_array($option) && in_array($meta_key, $option))
		{
			$meta_value = get_the_author_meta($meta_key, get_current_user_id());

			if(date('m-d', strtotime($meta_value)) == date('m-d'))
			{
				$user_data = get_userdata(get_current_user_id());

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
				$plugin_version = get_plugin_version(__FILE__);

				mf_enqueue_style('style_users_birthday', $plugin_include_url."style_birthday.css", $plugin_version);
			}
		}
	}

	function admin_footer()
	{
		$this->wp_footer();
	}

	function wp_footer()
	{
		if(isset($this->footer_output) && $this->footer_output != '')
		{
			echo $this->footer_output;
		}
	}
}