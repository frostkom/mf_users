(function()
{
	var el = wp.element.createElement,
		registerBlockType = wp.blocks.registerBlockType,
		SelectControl = wp.components.SelectControl,
		TextControl = wp.components.TextControl,
		InspectorControls = wp.blockEditor.InspectorControls;

	registerBlockType('mf/users',
	{
		title: script_users_block_wp.block_title,
		description: script_users_block_wp.block_description,
		icon: 'groups',
		category: 'widgets',
		'attributes':
		{
			'align':
			{
				'type': 'string',
				'default': ''
			},
			'user_ids':
			{
                'type': 'array',
                'default': ''
            }
		},
		'supports':
		{
			'html': false,
			'multiple': false,
			'align': true,
			'spacing':
			{
				'margin': true,
				'padding': true
			},
			'color':
			{
				'background': true,
				'gradients': false,
				'text': true
			},
			'defaultStylePicker': true,
			'typography':
			{
				'fontSize': true,
				'lineHeight': true
			},
			"__experimentalBorder":
			{
				"radius": true
			}
		},
		edit: function(props)
		{
			return el(
				'div',
				{className: 'wp_mf_block_container'},
				[
					el(
						InspectorControls,
						'div',
						el(
							SelectControl,
							{
								label: script_users_block_wp.user_ids_label,
								value: props.attributes.user_ids,
								options: convert_php_array_to_block_js(script_users_block_wp.user_ids),
								multiple: true,
								onChange: function(value)
								{
									props.setAttributes({user_ids: value});
								}
							}
						)
					),
					el(
						'strong',
						{className: props.className},
						script_users_block_wp.block_title
					)
				]
			);
		},
		save: function()
		{
			return null;
		}
	});

	registerBlockType('mf/userprofile',
	{
		title: script_users_block_wp.block_title2,
		description: script_users_block_wp.block_description2,
		icon: 'groups',
		category: 'widgets',
		'attributes':
		{
			'align':
			{
				'type': 'string',
				'default': ''
			}
		},
		'supports':
		{
			'html': false,
			'multiple': false,
			'align': true,
			'spacing':
			{
				'margin': true,
				'padding': true
			},
			'color':
			{
				'background': true,
				'gradients': false,
				'text': true
			},
			'defaultStylePicker': true,
			'typography':
			{
				'fontSize': true,
				'lineHeight': true
			},
			"__experimentalBorder":
			{
				"radius": true
			}
		},
		edit: function(props)
		{
			return el(
				'div',
				{className: 'wp_mf_block_container'},
				[
					el(
						'strong',
						{className: props.className},
						script_users_block_wp.block_title2
					)
				]
			);
		},
		save: function()
		{
			return null;
		}
	});
})();