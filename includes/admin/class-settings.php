<?php
defined( 'ABSPATH' ) || exit();

class WCSV_Settings {

	/**
	 * The single instance of the class.
	 *
	 * @var self
	 * @since  1.0.0
	 */
	private static $instance = null;

	/**
	 * Allows for accessing single instance of class. Class should only be constructed once per call.
	 *
	 * @return self Main instance.
	 * @since  1.0.0
	 * @static
	 */
	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * WCSV_Settings constructor.
	 * @since  1.0.0
	 */

	function __construct() {

		add_filter( 'woocommerce_settings_tabs_array', array( $this, 'add_settings_tab' ), 50 );
		add_action( 'woocommerce_settings_tabs_single_variation_tab', array( $this, 'settings_fields' ) );
		add_action( 'woocommerce_update_options_single_variation_tab', array( $this, 'update_settings_fields' ) );
	}

	/**
	 * @param $settings_tabs
	 *
	 * @return mixed
	 * @since  1.0.0
	 */

	public function add_settings_tab( $settings_tabs ) {
		$settings_tabs['single_variation_tab'] = __( 'Single Variation Settings', 'wc-show-single-variations' );

		return $settings_tabs;
	}

	/**
	 * Add settings fields in the settings tab
	 * @since  1.0.0
	 */
	public function settings_fields() {
		woocommerce_admin_fields( $this->get_settings() );
	}

	/**
	 * Update the settings fields
	 * @since  1.0.0
	 */
	public function update_settings_fields() {
		woocommerce_update_options( $this->get_settings() );
	}

	/**
	 * Settings fields in the settings array
	 *
	 * @return array Array of settings
	 * @since 1.0.0
	 */
	public function get_settings() {

		$settings = array(
			'section_title'      => array(
				'name' => __( 'Global Settings', 'wc-show-single-variations' ),
				'type' => 'title',
				'desc' => '',
				'id'   => 'wc_single_variation_tab_section_title'
			),
			'variation_option'       => array(
				'name'    => __( 'Variation Option', 'wc-show-single-variations' ),
				'type'    => 'radio',
				'id'      => 'wc_single_variation_option',
				'class'   => 'bt-field',
				'default' => 'single',
				'std'     => 'single', // WooCommerce < 2.0
				'options' => array(
					'single'	=> __( 'Show Variation as Single Product', 'wc-show-single-variations' ),
					'dropdown'	=> __( 'Show Variation Dropdown On Shop, Category & Search', 'wc-show-single-variations' ),
				  ),
			),
			'exclude_categories' => array(
				'name'     => __( 'Exclude Categories', 'wc-show-single-variations' ),
				'type'     => 'multiselect',
				'options'  => $this->get_categories(),
				'multiple' => true,
				'id'       => 'wc_single_variation_tab_exclude_categories',
				'class'    => 'wc-enhanced-select wcsv_select bt-field pro',
				'display_type' => 'dropdown',
			),
			'include_categories' => array(
				'name'    => __( 'Include Categories', 'wc-show-single-variations' ),
				'type'    => 'multiselect',
				'options' => $this->get_categories(),
				'id'      => 'wc_single_variation_tab_include_categories',
				'class'   => 'wc-enhanced-select bt-field',
				'display_type' => 'dropdown'
			),
			'hide_parents'       => array(
				'name'    => __( 'Hide Parent Product', 'wc-show-single-variations' ),
				'type'    => 'checkbox',
				'id'      => 'wc_single_variation_tab_hide_parent',
				'class'   => 'bt-field',
				'default' => 'yes',
				'desc'    => __( 'Hide Parent Product in the shop page', 'wc-show-single-variations' ),
			),
			'show_in_search'     => array(
				'name'    => __( 'Show in search result', 'wc-show-single-variations' ),
				'type'    => 'checkbox',
				'id'      => 'wc_single_variation_tab_show_search_result',
				'class'   => 'wcsv-checkbox bt-field pro',
				'default' => 'yes',
				'desc'    => __( 'Show products in the search result.', 'wc-show-single-variations' ),

			),
			'show_in_filter'     => array(
				'name'    => __( 'Show in filtered result', 'wc-show-single-variations' ),
				'type'    => 'checkbox',
				'id'      => 'wc_single_variation_tab_show_filtered_result',
				'class'   => 'wcsv-checkbox bt-field pro',
				'default' => 'yes',
				'desc'    => __( 'Show products in the filtered result', 'wc-show-single-variations' )
			),
			'show_in_catalog'    => array(
				'name'    => __( 'Show in catalog', 'wc-show-single-variations' ),
				'type'    => 'checkbox',
				'id'      => 'wc_single_variation_tab_show_in_catalog',
				'class'   => 'bt-field',
				'default' => 'yes',
				'desc'    => __( 'Show in catalog', 'wc-show-single-variations' ),
			),
			'enable_add_to_cart' => array(
				'name'    => __( 'Enable add to cart', 'wc-show-single-variations' ),
				'type'    => 'checkbox',
				'id'      => 'wc_single_variation_tab_enable_add_to_cart',
				'class'   => 'bt-field',
				'default' => 'yes',
				'desc'    => __( 'Enable the add to cart button. Otherwise select option button will be shown', 'wc-show-single-variations' ),
			),

			'section_end'        => array(
				'type' => 'sectionend',
				'id'   => 'wc_single_variation_tab_section_end'
			),
			'variation_custom_title'      => array(
				'name' => __( 'Variation Title', 'wc-show-single-variations' ),
				'type' => 'title',
				'desc' => '',
				'id'   => 'wc_single_variation_custom_title_section'
			),
			'custom_variation_title'    => array(
				'name'    => __( 'Enable Custom Variation Title', 'wc-show-single-variations' ),
				'type'    => 'checkbox',
				'id'      => 'wc_single_variation_custom_title',
				'class'   => 'bt-field',
				'default' => 'yes',
				'desc'    => __( 'This custom title can be overwritten in product variation settings', 'wc-show-single-variations' ),
			),
			'variation_title_template'    => array(
				'name'    => __( 'SEO Title Template', 'wc-show-single-variations' ),
				'type'    => 'text',
				'id'      => 'wc_single_variation_seo_title_template',
				'class'   => 'bt-field',
				'default' => '{title} in {attributes}',
				'desc'    => __( 'Here you can modify how the SEO title should be shown.<br> E.g. T-Shirt in Color Black => {title} in {attributes}<br> E.g. Black T-Shirt => {attributes} {title}', 'wc-show-single-variations' ),
			),
			'attributes_template'    => array(
				'name'    => __( 'Attributes Template', 'wc-show-single-variations' ),
				'type'    => 'text',
				'id'      => 'wc_single_variation_attribute_template',
				'class'   => 'bt-field',
				'default' => '{attributes_name} {attributes_value}',
				'desc'    => __( 'How attributes should appear.<br> E.g. T-Shirt in Color Black => {attributes_name} {attributes_value}<br> E.g. T-Shirt in Black => {attributes_value}<br> E.g. Black T-Shirt => {attributes_value}', 'wc-show-single-variations' ),
			),
			'attributes_names_appendix'    => array(
				'name'    => __( 'Attribute Names Appendix', 'wc-show-single-variations' ),
				'type'    => 'text',
				'id'      => 'wc_single_variation_attributes_names_appendix',
				'class'   => 'bt-field',
				'default' => 'and',
				'desc'    => __( 'If more then one attribute is selected.<br> E.g. T-Shirt in Color Black and Size S.', 'wc-show-single-variations' ),
			),
			'variation_custom_title_end'        => array(
				'type' => 'sectionend',
				'id'   => 'wc_single_variation_tab_variation_custom_title_end'
			),

		);

		return apply_filters( 'wc_single_variation_tab_settings', $settings );

	}

	/**
	 * Helper function: get categories
	 *
	 * @param null
	 *
	 * @return array
	 * @since  1.0.0
	 */
	public function get_categories() {
		$categories = get_terms(
			array(
				'taxonomy'   => 'product_cat',
				'hide_empty' => false,
			)
		);

		$category        = array();
		$category['']    = 'Select Category';
		$category['all'] = 'All';
		if ( is_array( $categories ) && count( $categories ) ) {
			foreach ( $categories as $single_category ) {
				$category[ $single_category->slug ] = $single_category->name;
			}
		}

		return $category;
	}
}

WCSV_Settings::instance();