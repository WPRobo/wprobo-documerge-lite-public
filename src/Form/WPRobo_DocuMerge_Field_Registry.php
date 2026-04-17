<?php
/**
 * Field Registry for managing form field types.
 *
 * Provides a central singleton registry that maps field type slugs
 * to their implementing class names, and instantiates them on demand.
 *
 * @package    WPRobo_DocuMerge
 * @subpackage Form
 * @since      1.0.0
 */

namespace WPRobo\DocuMerge\Form;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use WPRobo\DocuMerge\Form\Fields\WPRobo_DocuMerge_Field_Text;
use WPRobo\DocuMerge\Form\Fields\WPRobo_DocuMerge_Field_Textarea;
use WPRobo\DocuMerge\Form\Fields\WPRobo_DocuMerge_Field_Email;
use WPRobo\DocuMerge\Form\Fields\WPRobo_DocuMerge_Field_Phone;
use WPRobo\DocuMerge\Form\Fields\WPRobo_DocuMerge_Field_Number;
use WPRobo\DocuMerge\Form\Fields\WPRobo_DocuMerge_Field_Date;
use WPRobo\DocuMerge\Form\Fields\WPRobo_DocuMerge_Field_Dropdown;
use WPRobo\DocuMerge\Form\Fields\WPRobo_DocuMerge_Field_Radio;
use WPRobo\DocuMerge\Form\Fields\WPRobo_DocuMerge_Field_Checkbox;
use WPRobo\DocuMerge\Form\Fields\WPRobo_DocuMerge_Field_Url;
use WPRobo\DocuMerge\Form\Fields\WPRobo_DocuMerge_Field_Ip_Address;
use WPRobo\DocuMerge\Form\Fields\WPRobo_DocuMerge_Field_Tracking;

/**
 * Class WPRobo_DocuMerge_Field_Registry
 *
 * Singleton registry that maps field type slugs to their class names
 * and provides factory-style instantiation.
 *
 * @since 1.0.0
 */
class WPRobo_DocuMerge_Field_Registry {

	/**
	 * The single instance of this class.
	 *
	 * @since 1.0.0
	 * @var WPRobo_DocuMerge_Field_Registry|null
	 */
	private static $wprobo_documerge_instance = null;

	/**
	 * Registered field types mapped as type => class_name.
	 *
	 * @since 1.0.0
	 * @var array
	 */
	private $wprobo_documerge_fields = array();

	/**
	 * Private constructor to enforce singleton pattern.
	 *
	 * @since 1.0.0
	 */
	private function __construct() {
		$this->wprobo_documerge_register_default_fields();
	}

	/**
	 * Retrieve the singleton instance.
	 *
	 * @since 1.0.0
	 *
	 * @return WPRobo_DocuMerge_Field_Registry The singleton instance.
	 */
	public static function get_instance() {
		if ( null === self::$wprobo_documerge_instance ) {
			self::$wprobo_documerge_instance = new self();
		}

		return self::$wprobo_documerge_instance;
	}

	/**
	 * Register a field type with its implementing class name.
	 *
	 * @since 1.0.0
	 *
	 * @param string $type       The field type slug (e.g. 'text', 'email').
	 * @param string $class_name Fully qualified class name that implements the field.
	 * @return void
	 */
	public function wprobo_documerge_register_field( $type, $class_name ) {
		$this->wprobo_documerge_fields[ sanitize_key( $type ) ] = $class_name;
	}

	/**
	 * Get a new instance of the field class for the given type.
	 *
	 * @since 1.0.0
	 *
	 * @param string $type The field type slug.
	 * @return object|null A new instance of the field class, or null if not registered.
	 */
	public function wprobo_documerge_get_field( $type ) {
		$type = sanitize_key( $type );

		if ( ! isset( $this->wprobo_documerge_fields[ $type ] ) ) {
			return null;
		}

		$class_name = $this->wprobo_documerge_fields[ $type ];

		if ( ! class_exists( $class_name ) ) {
			return null;
		}

		return new $class_name();
	}

	/**
	 * Get all registered field types with their labels.
	 *
	 * Returns an associative array of type slugs mapped to human-readable labels.
	 *
	 * @since 1.0.0
	 *
	 * @return array Associative array of type => label pairs.
	 */
	public function wprobo_documerge_get_all_field_types() {
		$types = array();

		foreach ( $this->wprobo_documerge_fields as $type => $class_name ) {
			if ( class_exists( $class_name ) && method_exists( $class_name, 'wprobo_documerge_get_label' ) ) {
				$instance       = new $class_name();
				$types[ $type ] = $instance->wprobo_documerge_get_label();
			} else {
				$types[ $type ] = ucfirst( str_replace( '_', ' ', $type ) );
			}
		}

		return $types;
	}

	/**
	 * Register all default built-in field types.
	 *
	 * Registers the six basic field types: text, textarea, email, phone,
	 * number, and date.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function wprobo_documerge_register_default_fields() {
		$this->wprobo_documerge_register_field( 'text', WPRobo_DocuMerge_Field_Text::class );
		$this->wprobo_documerge_register_field( 'textarea', WPRobo_DocuMerge_Field_Textarea::class );
		$this->wprobo_documerge_register_field( 'email', WPRobo_DocuMerge_Field_Email::class );
		$this->wprobo_documerge_register_field( 'phone', WPRobo_DocuMerge_Field_Phone::class );
		$this->wprobo_documerge_register_field( 'number', WPRobo_DocuMerge_Field_Number::class );
		$this->wprobo_documerge_register_field( 'date', WPRobo_DocuMerge_Field_Date::class );
		$this->wprobo_documerge_register_field( 'dropdown', WPRobo_DocuMerge_Field_Dropdown::class );
		$this->wprobo_documerge_register_field( 'radio', WPRobo_DocuMerge_Field_Radio::class );
		$this->wprobo_documerge_register_field( 'checkbox', WPRobo_DocuMerge_Field_Checkbox::class );
		$this->wprobo_documerge_register_field( 'url', WPRobo_DocuMerge_Field_Url::class );
		$this->wprobo_documerge_register_field( 'ip_address', WPRobo_DocuMerge_Field_Ip_Address::class );
		$this->wprobo_documerge_register_field( 'tracking', WPRobo_DocuMerge_Field_Tracking::class );

		/**
		 * Filters the registered field types.
		 *
		 * Allows third-party code to add, remove, or replace field type
		 * registrations. Each entry maps a type slug to its fully-qualified
		 * class name.
		 *
		 * @since 1.2.0
		 *
		 * @param array $fields Associative array of type slug => class name.
		 */
		$this->wprobo_documerge_fields = apply_filters( 'wprobo_documerge_field_types', $this->wprobo_documerge_fields );
	}
}
