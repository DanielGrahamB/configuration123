( function ( blocks, element, blockEditor, components, i18n, serverSideRender ) {
	'use strict';

	var el = element.createElement;
	var Fragment = element.Fragment;
	var __ = i18n.__;
	var InspectorControls = blockEditor.InspectorControls;
	var useBlockProps = blockEditor.useBlockProps;
	var PanelBody = components.PanelBody;
	var SelectControl = components.SelectControl;
	var ToggleControl = components.ToggleControl;
	var ServerSideRender = serverSideRender;
	var profileViews = [ 'owner-profile', 'designer-profile' ];
	var compactViews = [ 'owner-card', 'contact' ];

	blocks.registerBlockType( 'configuration123/display', {
		apiVersion: 3,
		title: __( 'Configuration123 Display', 'configuration123' ),
		description: __( 'Display information that stays synchronized with Configuration123.', 'configuration123' ),
		icon: 'admin-settings',
		category: 'configuration123',
		keywords: [
			__( 'identity', 'configuration123' ),
			__( 'contact', 'configuration123' ),
			__( 'profile', 'configuration123' )
		],
		attributes: {
			view: { type: 'string', default: 'site-identity' },
			compact: { type: 'boolean', default: false },
			showLabels: { type: 'boolean', default: true }
		},
		supports: {
			html: false,
			align: [ 'wide', 'full' ],
			anchor: true,
			spacing: { margin: true, padding: true }
		},
		edit: function ( props ) {
			var attributes = props.attributes;
			var controls = [
				el( SelectControl, {
					key: 'view',
					label: __( 'Information to display', 'configuration123' ),
					value: attributes.view,
					options: [
						{ label: __( 'Site identity', 'configuration123' ), value: 'site-identity' },
						{ label: __( 'Owner profile', 'configuration123' ), value: 'owner-profile' },
						{ label: __( 'Designer profile', 'configuration123' ), value: 'designer-profile' },
						{ label: __( 'Owner card', 'configuration123' ), value: 'owner-card' },
						{ label: __( 'Contact methods', 'configuration123' ), value: 'contact' },
						{ label: __( 'Location', 'configuration123' ), value: 'location' },
						{ label: __( 'Designer services', 'configuration123' ), value: 'services' },
						{ label: __( 'Social profiles', 'configuration123' ), value: 'socials' },
						{ label: __( 'Designer attribution', 'configuration123' ), value: 'attribution' },
						{ label: __( 'Copyright', 'configuration123' ), value: 'copyright' }
					],
					onChange: function ( value ) {
						props.setAttributes( { view: value } );
					}
				} )
			];

			if ( profileViews.indexOf( attributes.view ) !== -1 ) {
				controls.push( el( ToggleControl, {
					key: 'labels',
					label: __( 'Show field labels', 'configuration123' ),
					checked: attributes.showLabels,
					onChange: function ( value ) {
						props.setAttributes( { showLabels: value } );
					}
				} ) );
			}

			if ( compactViews.indexOf( attributes.view ) !== -1 ) {
				controls.push( el( ToggleControl, {
					key: 'compact',
					label: __( 'Use compact presentation', 'configuration123' ),
					checked: attributes.compact,
					onChange: function ( value ) {
						props.setAttributes( { compact: value } );
					}
				} ) );
			}

			return el(
				Fragment,
				{},
				el(
					InspectorControls,
					{},
					el(
						PanelBody,
						{ title: __( 'Configuration123 display', 'configuration123' ), initialOpen: true },
						controls
					)
				),
				el(
					'div',
					useBlockProps( { className: 'configuration123-display-editor' } ),
					el( ServerSideRender, {
						block: 'configuration123/display',
						attributes: attributes
					} )
				)
			);
		},
		save: function () {
			return null;
		}
	} );
} )(
	window.wp.blocks,
	window.wp.element,
	window.wp.blockEditor,
	window.wp.components,
	window.wp.i18n,
	window.wp.serverSideRender
);
