jQuery(function($)
{
	if(script_users.rich_editing == true)
	{
		$(".user-rich-editing-wrap").remove();
	}

	if(script_users.syntax_highlight == true)
	{
		$(".user-syntax-highlighting-wrap").remove();
	}

	if(script_users.admin_color == true)
	{
		$(".user-admin-color-wrap").remove();
	}

	if(script_users.comment_shortcuts == true)
	{
		$(".user-comment-shortcuts-wrap").remove();
	}

	if(script_users.show_admin_bar == true)
	{
		$(".show-admin-bar").remove();
	}

	if(script_users.language == true)
	{
		$(".user-language-wrap").remove();
	}

	if(script_users.user_login == true)
	{
		$(".user-user-login-wrap").hide();
	}

	if(script_users.display_name == true)
	{
		$(".user-display-name-wrap").hide();
	}

	if(script_users.nickname == true)
	{
		$(".user-nickname-wrap").hide();
	}

	if(script_users.url == true)
	{
		$(".user-url-wrap").remove();
	}

	if(script_users.aim == true)
	{
		$(".user-aim-wrap").remove();
	}

	if(script_users.yim == true)
	{
		$(".user-yim-wrap").remove();
	}

	if(script_users.jabber == true)
	{
		$(".user-jabber-wrap").remove();
	}

	if(script_users.description == true)
	{
		$(".user-description-wrap").remove();
	}

	if(script_users.profile_picture == true)
	{
		$(".user-profile-picture").remove();
	}

	if(script_users.password == true)
	{
		$(".user-pass1-wrap, .user-pass2-wrap, .pw-weak").remove();
	}

	if(script_users.application_password == true)
	{
		$("#application-passwords-section").remove();
	}

	if(script_users.sessions == true)
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

	if(script_users.headings == true)
	{
		$("#your-profile").find("h2, h3").remove();
	}
});