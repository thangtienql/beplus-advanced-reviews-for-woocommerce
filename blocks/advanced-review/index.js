/**
 * Editor script for the Advanced Reviews block.
 * WordPress loads this via editorScript in block.json.
 *
 * @package BeplusAdvancedReviewsForWoocommerce
 */

( function ( wp ) {
	var registerBlockType = wp.blocks.registerBlockType;
	var InspectorControls = wp.blockEditor.InspectorControls;
	var useBlockProps = wp.blockEditor.useBlockProps;
	var PanelBody = wp.components.PanelBody;
	var ToggleControl = wp.components.ToggleControl;
	var el = wp.element.createElement;
	var __ = wp.i18n.__;

	var blockJson = {
		apiVersion: 3,
		name: 'beplus-advanced-reviews/advanced-review',
		title: 'Advanced Reviews',
		category: 'beplus-advanced-reviews-for-woocommerce',
		icon: 'star-filled',
		description: 'Modern WooCommerce product reviews with images, star distribution, filtering, and load more.',
		textdomain: 'beplus-advanced-reviews-for-woocommerce',
		attributes: {
			showDistribution: { type: 'boolean', default: true },
			showFilterBar: { type: 'boolean', default: true },
			showSubmitForm: { type: 'boolean', default: true },
			showMedia: { type: 'boolean', default: true },
			showAvatar: { type: 'boolean', default: true },
			enableLazyLoad: { type: 'boolean', default: true },
			showVerifiedFilter: { type: 'boolean', default: true }
		},
		supports: { html: false, align: ['wide', 'full'] }
	};

	registerBlockType( blockJson.name, {
		title: blockJson.title,
		icon: blockJson.icon,
		category: blockJson.category,
		attributes: blockJson.attributes,
		supports: blockJson.supports,
		edit: function ( props ) {
			var attributes = props.attributes;
			var setAttributes = props.setAttributes;
			var blockProps = useBlockProps( {
				className: 'beplus-advanced-reviews-for-woocommerce beplus-advanced-reviews-for-woocommerce--editor-preview'
			} );

			return el(
				wp.element.Fragment,
				null,
				el(
					InspectorControls,
					null,
					el(
						PanelBody,
						{ title: __( 'Display Options', 'beplus-advanced-reviews-for-woocommerce' ) },
						el( ToggleControl, {
							label: __( 'Show star distribution', 'beplus-advanced-reviews-for-woocommerce' ),
							checked: attributes.showDistribution,
							onChange: function ( val ) { setAttributes( { showDistribution: val } ); }
						} ),
						el( ToggleControl, {
							label: __( 'Show filter bar', 'beplus-advanced-reviews-for-woocommerce' ),
							checked: attributes.showFilterBar,
							onChange: function ( val ) { setAttributes( { showFilterBar: val } ); }
						} ),
						el( ToggleControl, {
							label: __( 'Show submit form', 'beplus-advanced-reviews-for-woocommerce' ),
							checked: attributes.showSubmitForm,
							onChange: function ( val ) { setAttributes( { showSubmitForm: val } ); }
						} ),
						el( ToggleControl, {
							label: __( 'Show media in reviews', 'beplus-advanced-reviews-for-woocommerce' ),
							checked: attributes.showMedia,
							onChange: function ( val ) { setAttributes( { showMedia: val } ); }
						} ),
						el( ToggleControl, {
							label: __( 'Show reviewer avatar', 'beplus-advanced-reviews-for-woocommerce' ),
							checked: attributes.showAvatar,
							onChange: function ( val ) { setAttributes( { showAvatar: val } ); }
						} ),
						el( ToggleControl, {
							label: __( 'Show verified purchase filter', 'beplus-advanced-reviews-for-woocommerce' ),
							checked: attributes.showVerifiedFilter,
							onChange: function ( val ) { setAttributes( { showVerifiedFilter: val } ); }
						} ),
						el( ToggleControl, {
							label: __( 'Enable lazy load', 'beplus-advanced-reviews-for-woocommerce' ),
							checked: attributes.enableLazyLoad,
							onChange: function ( val ) { setAttributes( { enableLazyLoad: val } ); }
						} )
					)
				),
				el( 'div', blockProps,
					el( 'div', { className: 'beplus-advanced-reviews-for-woocommerce__editor-placeholder' },
						el( 'span', { className: 'dashicons dashicons-star-filled' } ),
						el( 'h3', null, __( 'Advanced Reviews', 'beplus-advanced-reviews-for-woocommerce' ) ),
						el( 'p', null, __( 'Reviews will display here on the product page.', 'beplus-advanced-reviews-for-woocommerce' ) )
					)
				)
			);
		},
		save: function () { return null; }
	} );
} )( window.wp );
