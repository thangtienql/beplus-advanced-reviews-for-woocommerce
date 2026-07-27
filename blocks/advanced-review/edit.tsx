/**
 * Editor script for the Advanced Reviews block.
 *
 * @package BeplusAdvancedReviewsForWoocommerce
 */

import { registerBlockType } from '@wordpress/blocks';
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import {
	PanelBody,
	ToggleControl,
	RangeControl,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import blockJson from './block.json';

registerBlockType( blockJson.name, {
	...blockJson,
	edit: ( { attributes, setAttributes } ) => {
		const {
			showDistribution,
			showFilterBar,
			showSubmitForm,
			showImages,
			showAvatar,
			reviewsPerLoad,
			enableLazyLoad,
			showVerifiedFilter,
		} = attributes;

		const blockProps = useBlockProps( {
			className: 'beplus-advanced-reviews-for-woocommerce beplus-advanced-reviews-for-woocommerce--editor-preview',
		} );

		return (
			<>
				<InspectorControls>
					<PanelBody title={ __( 'Display Options', 'beplus-advanced-reviews-for-woocommerce' ) }>
						<ToggleControl
							label={ __( 'Show star distribution', 'beplus-advanced-reviews-for-woocommerce' ) }
							checked={ showDistribution }
							onChange={ ( val ) => setAttributes( { showDistribution: val } ) }
						/>
						<ToggleControl
							label={ __( 'Show filter bar', 'beplus-advanced-reviews-for-woocommerce' ) }
							checked={ showFilterBar }
							onChange={ ( val ) => setAttributes( { showFilterBar: val } ) }
						/>
						<ToggleControl
							label={ __( 'Show submit form', 'beplus-advanced-reviews-for-woocommerce' ) }
							checked={ showSubmitForm }
							onChange={ ( val ) => setAttributes( { showSubmitForm: val } ) }
						/>
						<ToggleControl
							label={ __( 'Show images in reviews', 'beplus-advanced-reviews-for-woocommerce' ) }
							checked={ showImages }
							onChange={ ( val ) => setAttributes( { showImages: val } ) }
						/>
						<ToggleControl
							label={ __( 'Show reviewer avatar', 'beplus-advanced-reviews-for-woocommerce' ) }
							checked={ showAvatar }
							onChange={ ( val ) => setAttributes( { showAvatar: val } ) }
						/>
						<ToggleControl
							label={ __( 'Show verified purchase filter', 'beplus-advanced-reviews-for-woocommerce' ) }
							checked={ showVerifiedFilter }
							onChange={ ( val ) => setAttributes( { showVerifiedFilter: val } ) }
						/>
						<RangeControl
							label={ __( 'Reviews per load', 'beplus-advanced-reviews-for-woocommerce' ) }
							value={ reviewsPerLoad }
							onChange={ ( val ) => setAttributes( { reviewsPerLoad: val } ) }
							min={ 1 }
							max={ 50 }
						/>
						<ToggleControl
							label={ __( 'Enable lazy load', 'beplus-advanced-reviews-for-woocommerce' ) }
							checked={ enableLazyLoad }
							onChange={ ( val ) => setAttributes( { enableLazyLoad: val } ) }
						/>
					</PanelBody>
				</InspectorControls>
				<div { ...blockProps }>
					<div className="beplus-advanced-reviews-for-woocommerce__editor-placeholder">
						<span className="dashicons dashicons-star-filled"></span>
						<h3>{ __( 'Advanced Reviews', 'beplus-advanced-reviews-for-woocommerce' ) }</h3>
						<p>{ __( 'Reviews will display here on the product page.', 'beplus-advanced-reviews-for-woocommerce' ) }</p>
					</div>
				</div>
			</>
		);
	},
	save: () => null,
} );
