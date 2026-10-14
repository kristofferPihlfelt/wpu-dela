( function ( wp ) {
	var el = wp.element.createElement;
	var useBlockProps = wp.blockEditor.useBlockProps;
	var ServerSideRender = wp.serverSideRender;

	wp.blocks.registerBlockType( 'wpu/dela', {
		edit: function ( props ) {
			return el( 'div', useBlockProps(),
				el( ServerSideRender, {
					block: 'wpu/dela',
					attributes: props.attributes,
					urlQueryArgs: { post_id: props.context.postId || 0 },
				} )
			);
		},
		save: function () { return null; },
	} );
} )( window.wp );
