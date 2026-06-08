import { addFilter } from '@wordpress/hooks';
import { SelectControl } from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import { useEntityProp } from '@wordpress/core-data';
import { __ } from '@wordpress/i18n';

const WP_PRIMARY_TERM_DATA = window.wpPrimaryTerm || {};
const TAXONOMIES = WP_PRIMARY_TERM_DATA.taxonomies
	? Object.keys( WP_PRIMARY_TERM_DATA.taxonomies )
	: [];
const SELECT_LABEL =
	WP_PRIMARY_TERM_DATA.labels?.select ||
	__( 'Select primary term', 'wp-primary-term' );
const META_LABEL_TEMPLATE =
	// translators: %s = singular term label.
	WP_PRIMARY_TERM_DATA.labels?.meta || __( 'Primary %s', 'wp-primary-term' );
const META_KEY_TEMPLATE = WP_PRIMARY_TERM_DATA.metaKeyTemplate || '_primary_%s';

/**
 * Get meta key for a taxonomy.
 *
 * @param {string} taxonomy Taxonomy slug.
 */
const getMetaKey = ( taxonomy ) => META_KEY_TEMPLATE.replace( '%s', taxonomy );

/**
 * Get meta label for a taxonomy.
 *
 * @param {string} taxonomy Taxonomy slug.
 */
const getMetaLabel = ( taxonomy ) => {
	const label =
		WP_PRIMARY_TERM_DATA.taxonomies?.[ taxonomy ]?.label || taxonomy;
	return META_LABEL_TEMPLATE.replace( '%s', label );
};

const withPrimaryTermField = ( OriginalComponent ) => {
	return ( props ) => {
		if ( ! TAXONOMIES.includes( props.slug ) ) {
			return <OriginalComponent { ...props } />;
		}

		const taxonomySlug = props.slug;

		const metaLabel = getMetaLabel( taxonomySlug );
		const metaKey = getMetaKey( taxonomySlug );

		const postType = useSelect( ( select ) =>
			select( 'core/editor' ).getCurrentPostType()
		);

		const selectedTerms = useSelect(
			( select ) => {
				return (
					select( 'core/editor' ).getEditedPostAttribute(
						taxonomySlug
					) || []
				);
			},
			[ taxonomySlug ]
		);

		const termDetails = useSelect(
			( select ) => {
				return selectedTerms
					.map( ( termId ) => {
						return select( 'core' ).getEntityRecord(
							'taxonomy',
							taxonomySlug,
							termId
						);
					} )
					.filter( Boolean );
			},
			[ selectedTerms, taxonomySlug ]
		);

		const [ meta, setMeta ] = useEntityProp( 'postType', postType, 'meta' );
		const selectedPrimaryTerm = meta?.[ metaKey ] || 0;

		const updatePrimaryTerm = ( value ) => {
			setMeta( {
				...meta,
				[ metaKey ]: parseInt( value, 10 ) || 0,
			} );
		};

		const options = [
			{ label: '-- ' + SELECT_LABEL, value: 0 },
			...termDetails.map( ( term ) => ( {
				label: term.name,
				value: term.id,
			} ) ),
		];

		return (
			<div>
				<OriginalComponent { ...props } />
				{ selectedTerms.length > 1 && (
					<div style={ { marginTop: '8px' } }>
						<SelectControl
							label={ metaLabel }
							value={ selectedPrimaryTerm }
							onChange={ updatePrimaryTerm }
							options={ options }
						/>
					</div>
				) }
			</div>
		);
	};
};

// Register the filter
addFilter(
	'editor.PostTaxonomyType',
	'wp-primary-term/primary-term-field',
	withPrimaryTermField
);
