import { registerPlugin } from '@wordpress/plugins';
import { PluginDocumentSettingPanel } from '@wordpress/editor';
import { CheckboxControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { useSelect, useDispatch } from '@wordpress/data';

const XMLCacheSettings = () => {
    const { metaValue } = useSelect( ( select ) => {
		const meta = select( 'core/editor' ).getEditedPostAttribute( 'meta' );
		return {
			metaValue: meta ? meta['_xml_cache_enabled'] : true,
		}
	}, ['_xml_cache_enabled'] );

	const { editPost } = useDispatch( 'core/editor' );

    return (
        <PluginDocumentSettingPanel
            className="xml-cache-settings"
            title="XML Cache"
            name="xml-cache"
        >
            <CheckboxControl
                label={ __( 'Enable', 'xml-cache' ) }
                help={ __( 'Enable XML cache sitemap for this post?', 'xml-cache' ) }
                checked={ metaValue }
                onChange={ ( value ) => editPost( { meta: { ['_xml_cache_enabled']: value } } ) }
            />
        </PluginDocumentSettingPanel>
    );
};

registerPlugin( 'xml-cache-settings', { render: XMLCacheSettings } );