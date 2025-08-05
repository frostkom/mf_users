jQuery(function($)
{
	if(script_users_profile.rich_editing == true)
	{
		$(".user-rich-editing-wrap").remove();
	}

	if(script_users_profile.syntax_highlight == true)
	{
		$(".user-syntax-highlighting-wrap").remove();
	}

	if(script_users_profile.admin_color == true)
	{
		$(".user-admin-color-wrap").remove();
	}

	if(script_users_profile.comment_shortcuts == true)
	{
		$(".user-comment-shortcuts-wrap").remove();
	}

	if(script_users_profile.show_admin_bar == true)
	{
		$(".show-admin-bar").remove();
	}

	if(script_users_profile.language == true)
	{
		$(".user-language-wrap").remove();
	}

	if(script_users_profile.user_login == true)
	{
		$(".user-user-login-wrap").hide();
	}

	if(script_users_profile.display_name == true)
	{
		$(".user-display-name-wrap").hide();
	}

	if(script_users_profile.nickname == true)
	{
		$(".user-nickname-wrap").hide();
	}

	if(script_users_profile.url == true)
	{
		$(".user-url-wrap").remove();
		$("#url").parents(".form-field").remove();
	}

	if(script_users_profile.aim == true)
	{
		$(".user-aim-wrap").remove();
	}

	if(script_users_profile.yim == true)
	{
		$(".user-yim-wrap").remove();
	}

	if(script_users_profile.jabber == true)
	{
		$(".user-jabber-wrap").remove();
	}

	if(script_users_profile.description == true)
	{
		$(".user-description-wrap").remove();
	}

	if(script_users_profile.profile_picture == true)
	{
		$(".user-profile-picture").remove();
	}

	if(script_users_profile.password == true)
	{
		$(".user-pass1-wrap, .user-pass2-wrap, .pw-weak").remove();
	}

	if(script_users_profile.application_password == true)
	{
		$("#application-passwords-section").remove();
	}

	if(script_users_profile.sessions == true)
	{
		$(".user-sessions-wrap").remove();
	}

	$(".form-table").each(function()
	{
		if($(this).find("tr").length == 0)
		{
			$(this).prev("h2").remove();
			$(this).remove();
		}
	});

	if(script_users_profile.headings == true)
	{
		$("#your-profile").find("h2, h3").remove();
	}
});