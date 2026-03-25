import apiFetch from '@wordpress/api-fetch';
import { Spinner, Notice, Button, Card, CardHeader, CardBody, CardDivider, CardFooter, ToggleControl } from '@wordpress/components';
import { __, sprintf } from '@wordpress/i18n';
import { useCopyToClipboard } from '@wordpress/compose';
import { useState, useEffect } from '@wordpress/element';
import { dispatch } from '@wordpress/data';
import Notices from './Notices';

export default function Settings() {
    const [ options, setOptions ] = useState( null );
    const [ sitemapUrl, setSitemapUrl ] = useState( null );
    const [ error, setError ] = useState( null );

    const copyRef = useCopyToClipboard(
        () => sitemapUrl ?? '',
        () => {
            dispatch( 'core/notices' ).createNotice(
                'success',
                __( 'Sitemap URL copied to clipboard.', 'xml-cache' ),
                { type: 'snackbar', isDismissible: true }
            );
        }
    );

    const saveOptions = ( nextOptions = options ) => {
        apiFetch( {
            path: xmlCache.restApiNamespace + '/settings',
            method: 'POST',
            data: nextOptions,
        } ).then( ( result ) => {
            if ( result.success ) {
                dispatch( 'core/notices' ).createNotice(
                    'success',
                    __( 'Settings saved.', 'xml-cache' ),
                    {
                        type: 'snackbar',
                        isDismissible: true,
                    }
                );
            } else {
                dispatch( 'core/notices' ).createNotice(
                    'error',
                    __( "Settings could't be saved.", 'xml-cache' ),
                    {
                        type: 'snackbar',
                        isDismissible: true,
                    }
                );
            }
        } ).catch( ( error ) => {
            console.error( error );
        } );
    }

    const onChangeSetting = ( option, value ) => {
        if ( ! options || options === false ) { return; }
        const nextOptions = { ...options, [ option ]: value };
        setOptions( nextOptions );
        saveOptions( nextOptions );
    }

    useEffect(() => {
        apiFetch( {
            path: xmlCache.restApiNamespace + '/settings'
        } ).then( ( result ) => {
            if ( result.success ) {
                setOptions( result.data );
            } else {
                setOptions( false );
                setError( result.message );
            }
        } ).catch( ( error ) => {
            console.error( error );
            setOptions( false );
            setError( error.message );
        } );

        apiFetch( {
            path: xmlCache.restApiNamespace + '/xml-sitemap-url'
        } ).then( ( result ) => {
            if ( result.success ) {
                setSitemapUrl( result.data.sitemap_url );
            } else {
                setSitemapUrl( false );
                setError( result.message );
            }
        } ).catch( ( error ) => {
            setSitemapUrl( false );
            setError( error.message );
        } );
    }, []);

    if ( options === null || sitemapUrl === null ) {
        return <Spinner />;
    }

    return (
        <div className="wrap">

            <Notices />
            <Card style={ { maxWidth: '500px' } }>
                <CardHeader>
                    <h1>
                        { __( 'XML Cache Settings', 'xml-cache' ) }
                    </h1>
                </CardHeader>

                { error && (
                    <>
                    <CardBody>
                        <Notice status="error" isDismissible={ false }>
                            { error }
                        </Notice>
                    </CardBody>
                    <CardDivider />
                    </>
                ) }

                <CardBody>
                    <p>{ __( 'XML Cache generates an XML sitemap for cache plugins. Select which sections you want to include in the sitemap. URLs that are set to noindex are also included in the sitemap. You can specify the sitemap in your cache plugin\'s settings to automatically warm up your entire cache.', 'xml-cache' ) }</p>
                </CardBody>
                
                <CardDivider />

                <CardBody
                    style={ {
                        display: 'grid',
                        gridTemplateColumns: '1fr 1fr',
                        gap: '1rem'
                    } }
                >
                    <Button
                        variant="primary"
                        href={ sitemapUrl }
                        icon="admin-links"
                        size="compact"
                        target="_blank"
                        disabled={ options === false || sitemapUrl === false }
                    >
                        { __( 'Open Sitemap', 'xml-cache' ) }
                    </Button>

                    <Button
                        variant="secondary"
                        icon="clipboard"
                        size="compact"
                        ref={ copyRef }
                        disabled={ options === false || sitemapUrl === false }
                    >
                        { __( 'Copy Sitemap URL', 'xml-cache' ) }
                    </Button>
                </CardBody>
                
                <CardDivider />

                <CardBody
                    style={ {
                        display: 'grid',
                        gridTemplateColumns: '1fr 1fr',
                        gap: '1rem',
                        alignItems: 'center'
                    } }
                >
                    <ToggleControl
                        __nextHasNoMarginBottom
                        label={ __( 'Include posts', 'xml-cache' ) }
                        checked={ options.posts_enabled }
                        onChange={ ( state ) => onChangeSetting( 'posts_enabled', state ) }
                        disabled={ options === false || sitemapUrl === false }
                    />

                    <ToggleControl
                        __nextHasNoMarginBottom
                        label={ __( 'Include custom post types', 'xml-cache' ) }
                        checked={ options.custom_post_types_enabled ?? true }
                        onChange={ ( state ) => onChangeSetting( 'custom_post_types_enabled', state ) }
                        disabled={ options === false || sitemapUrl === false }
                    />

                    <ToggleControl
                        __nextHasNoMarginBottom
                        label={ __( 'Include categories', 'xml-cache' ) }
                        checked={ options.categories_enabled }
                        onChange={ ( state ) => onChangeSetting( 'categories_enabled', state ) }
                        disabled={ options === false || sitemapUrl === false }
                    />

                    <ToggleControl
                        __nextHasNoMarginBottom
                        label={ __( 'Include custom taxonomies', 'xml-cache' ) }
                        checked={ options.custom_taxonomies_enabled ?? true }
                        onChange={ ( state ) => onChangeSetting( 'custom_taxonomies_enabled', state ) }
                        disabled={ options === false || sitemapUrl === false }
                    />

                    <ToggleControl
                        __nextHasNoMarginBottom
                        label={ __( 'Include tags', 'xml-cache' ) }
                        checked={ options.tags_enabled }
                        onChange={ ( state ) => onChangeSetting( 'tags_enabled', state ) }
                        disabled={ options === false || sitemapUrl === false }
                    />

                    <ToggleControl
                        __nextHasNoMarginBottom
                        label={ __( 'Include author archives', 'xml-cache' ) }
                        checked={ options.authors_enabled ?? true }
                        onChange={ ( state ) => onChangeSetting( 'authors_enabled', state ) }
                        disabled={ options === false || sitemapUrl === false }
                    />

                    <ToggleControl
                        __nextHasNoMarginBottom
                        label={ __( 'Include post type archives', 'xml-cache' ) }
                        checked={ options.post_type_archives_enabled ?? true }
                        onChange={ ( state ) => onChangeSetting( 'post_type_archives_enabled', state ) }
                        disabled={ options === false || sitemapUrl === false }
                    />

                    <ToggleControl
                        __nextHasNoMarginBottom
                        label={ __( 'Include date archives', 'xml-cache' ) }
                        checked={ options.date_archives_enabled ?? true }
                        onChange={ ( state ) => onChangeSetting( 'date_archives_enabled', state ) }
                        disabled={ options === false || sitemapUrl === false }
                    />

                    <ToggleControl
                        __nextHasNoMarginBottom
                        label={ __( 'Include homepage', 'xml-cache' ) }
                        checked={ options.homepage_enabled ?? true }
                        onChange={ ( state ) => onChangeSetting( 'homepage_enabled', state ) }
                        disabled={ options === false || sitemapUrl === false }
                    />
                </CardBody>

                <CardFooter>
                    <p dangerouslySetInnerHTML={{
                        __html: sprintf(
                            __( 'Got questions, issues, or feature ideas? <a href="%s" target="_blank" rel="noopener">Visit the support forum</a>. Enjoying the plugin? <a href="%s" target="_blank" rel="noopener">Please leave a review</a> — it really helps. Developers are welcome to contribute on <a href="%s" target="_blank" rel="noopener">GitHub</a>.', 'xml-cache' ),
                            xmlCache.supportUrl,
                            xmlCache.reviewUrl,
                            xmlCache.githubUrl
                        )
                    }} />
                </CardFooter>
            </Card>
        </div>
    )
}