/**
 * WPRobo DocuMerge — Gutenberg Block: Form Embed
 *
 * @package WPRobo_DocuMerge
 * @since   1.4.0
 */

( function( blocks, blockEditor, components, i18n, element, serverSideRender ) {
    'use strict';

    var el = element.createElement;
    var Fragment = element.Fragment;
    var __ = i18n.__;
    var useBlockProps     = blockEditor.useBlockProps;
    var InspectorControls = blockEditor.InspectorControls;
    var BlockControls     = blockEditor.BlockControls;
    var SelectControl     = components.SelectControl;
    var PanelBody         = components.PanelBody;
    var Placeholder       = components.Placeholder;
    var ToolbarGroup      = components.ToolbarGroup;
    var ToolbarButton     = components.ToolbarButton;

    blocks.registerBlockType( 'wprobo-documerge/form-embed', {
        edit: function( props ) {
            var blockProps = useBlockProps();
            var formId     = props.attributes.formId;
            var allForms   = ( typeof wprobo_documerge_block_vars !== 'undefined' && wprobo_documerge_block_vars.forms ) ? wprobo_documerge_block_vars.forms : [];
            var adminUrl   = ( typeof wprobo_documerge_block_vars !== 'undefined' ) ? wprobo_documerge_block_vars.admin_url : '';

            var options = [ { label: __( '— Select a form —', 'wprobo-documerge' ), value: 0 } ];
            allForms.forEach( function( form ) {
                options.push( { label: form.title + ' (ID: ' + form.id + ')', value: parseInt( form.id, 10 ) } );
            });

            // Find selected form details.
            var selectedForm = null;
            if ( formId ) {
                allForms.forEach( function( form ) {
                    if ( parseInt( form.id, 10 ) === formId ) {
                        selectedForm = form;
                    }
                });
            }

            // Main content.
            var mainContent;
            if ( ! formId ) {
                if ( allForms.length === 0 ) {
                    mainContent = el( Placeholder, {
                        icon: 'media-document',
                        label: __( 'DocuMerge Form', 'wprobo-documerge' ),
                        className: 'wdm-block-placeholder',
                    },
                        el( 'p', {}, __( 'No forms created yet.', 'wprobo-documerge' ) ),
                        adminUrl ? el( 'a', {
                            href: adminUrl + '?page=wprobo-documerge-forms&action=new',
                            target: '_blank',
                            className: 'components-button is-primary',
                            style: { marginTop: '8px' }
                        }, __( 'Create Your First Form', 'wprobo-documerge' ) ) : null
                    );
                } else {
                    mainContent = el( Placeholder, {
                        icon: 'media-document',
                        label: __( 'DocuMerge Form', 'wprobo-documerge' ),
                        className: 'wdm-block-placeholder',
                    },
                        el( SelectControl, {
                            value: formId,
                            options: options,
                            onChange: function( val ) {
                                props.setAttributes( { formId: parseInt( val, 10 ) } );
                            },
                        })
                    );
                }
            } else {
                mainContent = el( 'div', { className: 'wdm-block-preview-wrap' },
                    el( serverSideRender, {
                        block: 'wprobo-documerge/form-embed',
                        attributes: props.attributes,
                    })
                );
            }

            // Return single root element with useBlockProps — this enables toolbar + drag handle.
            return el( 'div', blockProps,
                // Inspector sidebar.
                el( InspectorControls, {},
                    el( PanelBody, { title: __( 'Form Settings', 'wprobo-documerge' ), initialOpen: true },
                        el( SelectControl, {
                            label: __( 'Select Form', 'wprobo-documerge' ),
                            value: formId,
                            options: options,
                            onChange: function( val ) {
                                props.setAttributes( { formId: parseInt( val, 10 ) } );
                            },
                        }),
                        selectedForm ? el( 'div', { className: 'wdm-block-form-info' },
                            el( 'p', {}, el( 'strong', {}, __( 'Form: ', 'wprobo-documerge' ) ), selectedForm.title ),
                            selectedForm.field_count ? el( 'p', {}, el( 'strong', {}, __( 'Fields: ', 'wprobo-documerge' ) ), selectedForm.field_count ) : null,
                            selectedForm.template_name ? el( 'p', {}, el( 'strong', {}, __( 'Template: ', 'wprobo-documerge' ) ), selectedForm.template_name ) : null
                        ) : null,
                        adminUrl ? el( 'p', { style: { marginTop: '12px' } },
                            el( 'a', { href: adminUrl + '?page=wprobo-documerge-forms', target: '_blank', rel: 'noopener noreferrer' },
                                __( 'Manage Forms →', 'wprobo-documerge' )
                            )
                        ) : null
                    )
                ),
                // Block toolbar.
                formId ? el( BlockControls, {},
                    el( ToolbarGroup, {},
                        el( ToolbarButton, {
                            icon: 'update',
                            label: __( 'Change Form', 'wprobo-documerge' ),
                            onClick: function() {
                                props.setAttributes( { formId: 0 } );
                            },
                        })
                    )
                ) : null,
                // Content.
                mainContent
            );
        },

        save: function() {
            return null;
        },
    });

})( window.wp.blocks, window.wp.blockEditor, window.wp.components, window.wp.i18n, window.wp.element, window.wp.serverSideRender );
