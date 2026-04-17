<?php
/**
 * Block asset dependencies.
 *
 * @package WPRobo_DocuMerge
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

return array(
	'dependencies' => array(
		'wp-blocks',
		'wp-block-editor',
		'wp-components',
		'wp-element',
		'wp-i18n',
		'wp-server-side-render',
	),
	'version'      => WPROBO_DOCUMERGE_VERSION ?? '1.0.0',
);
