<?php
defined( 'ABSPATH' ) || exit();

class WCSV_Admin {

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
	 * Admin constructor.
	 * @since  1.0.0
	 */
	public function __construct() {

		add_action( 'woocommerce_product_after_variable_attributes', array(
			$this,
			'add_single_variation_additional_fields',
		), 10, 3 );
		add_action( 'woocommerce_save_product_variation', array( $this, 'save_product_variations_data' ), 10, 2 );

		add_action( 'set_object_terms', array( $this, 'save_variation_terms' ), 10, 6 );
		add_action( 'save_post', array( $this, 'save_variation_product' ), 100, 1 );


		add_action( 'woocommerce_save_product_variation', array( $this, 'product_variation_save' ), 100, 2 );
		add_action( 'woocommerce_new_product_variation', array( $this, 'product_variation_save' ), 10 );
		add_action( 'woocommerce_update_product_variation', array( $this, 'product_variation_save' ), 10 );

		add_filter( 'woocommerce_product_data_tabs', array( $this, 'single_variation_tabs' ) );
		add_action( 'admin_head', array( $this, 'tabs_custom_style' ) );
		add_action( 'woocommerce_product_data_panels', array( $this, 'single_variation_product_data_fields' ) );
		add_action( 'woocommerce_process_product_meta_variable', array( $this, 'single_variations_data_fields_save' ) );

	}

	/**
	 * Add single variation additional fields
	 *
	 * @param $loop
	 * @param $variation_data
	 * @param $variation
	 *
	 * @return void
	 * @since  1.0.0
	 */
	public function add_single_variation_additional_fields( $loop, $variation_data, $variation ) {


		woocommerce_wp_text_input(
			array(
				'id'    => '_custom_title[' . $variation->ID . ']',
				'label' => __( 'Custom Title for the product', 'wc-show-single-variations' ),
				'value' => get_post_meta( $variation->ID, '_custom_title', true ),
			)
		);

		woocommerce_wp_checkbox(
			array(
				'id'          => '_hide_variation[' . $variation->ID . ']',
				'label'       => __( 'Hide Variations', 'wc-show-single-variations' ),
				'value'       => get_post_meta( $variation->ID, '_hide_variation', true ),
				'style'       => 'margin-right: 5px !important',
				'description' => __( 'Hide variations in the whole site', 'wc-show-single-variations' ),
				'desc_tip'    => true,
			)
		);

		woocommerce_wp_checkbox(
			array(
				'id'            => '_disable_add_to_cart[' . $variation->ID . ']',
				'label'         => __( 'Disable Add to cart button', 'wc-show-single-variations' ),
				'value'         => get_post_meta( $variation->ID, '_disable_add_to_cart', true ),
				'style'         => 'margin-right: 5px !important',
				'wrapper_class' => apply_filters( 'wcsv_single_variations_wrapper_class', 'wcsv-checkbox bt-field pro' ),
				'description'   => __( 'You can disable the add_to_cart for this variations.Then Select Options Button Will show', 'wc-show-single-variations' ),
				'desc_tip'      => true,

			)
		);
		woocommerce_wp_checkbox(
			array(
				'id'          => '_hide_catalog[' . $variation->ID . ']',
				'label'       => __( 'Hide in Catalog', 'wc-show-single-variations' ),
				'value'       => get_post_meta( $variation->ID, '_hide_catalog', true ),
				'style'       => 'margin-right: 5px !important',
				'default'     => 'no',
				'description' => __( 'You can hide this variations in the catalog.', 'wc-show-single-variations' ),
				'desc_tip'    => true,
			)
		);
		woocommerce_wp_checkbox(
			array(
				'id'            => '_hide_search[' . $variation->ID . ']',
				'label'         => __( 'Hidden in Search Results', 'wc-show-single-variations' ),
				'value'         => get_post_meta( $variation->ID, '_hide_search', true ),
				'style'         => 'margin-right: 5px !important',
				'wrapper_class' => apply_filters( 'wcsv_single_variations_wrapper_class', 'wcsv-checkbox bt-field pro' ),
				'description'   => __( 'You can hide this variation in the search results', 'wc-show-single-variations' ),
				'desc_tip'      => true,
			)
		);
		woocommerce_wp_checkbox(
			array(
				'id'            => '_hide_filter[' . $variation->ID . ']',
				'label'         => __( 'Hidden in Filter Results', 'wc-show-single-variations' ),
				'value'         => get_post_meta( $variation->ID, '_hide_filter', true ),
				'style'         => 'margin-right: 5px !important',
				'wrapper_class' => apply_filters( 'wcsv_single_variations_wrapper_class', 'wcsv-checkbox bt-field pro' ),
				'description'   => __( 'You can hide the this variation in the filtered results.', 'wc-show-single-variations' ),
				'desc_tip'      => true,
			)
		);
		woocommerce_wp_checkbox(
			array(
				'id'          => '_is_featured[' . $variation->ID . ']',
				'label'       => __( 'Featured', 'wc-show-single-variations' ),
				'value'       => get_post_meta( $variation->ID, '_is_featured', true ),
				'style'       => 'margin-right: 5px !important',
				'description' => __( 'You can make this variation featured by checking the checkbox', 'wc-show-single-variations' ),
				'desc_tip'    => true,
			)
		);

		woocommerce_wp_select(
			array(
				'id'                => '_cat[' . $variation->ID . '][]',
				'label'             => __( 'Variation Category', 'wc-show-single-variations' ),
				'value'             => get_post_meta( $variation->ID, '_cat', true ) ? get_post_meta( $variation->ID, '_cat', true ) : $this->get_variation_object_terms( $variation->ID, 'product_cat' ),
				'style'             => 'margin-right: 5px !important',
				'wrapper_class'     => apply_filters( 'wcsv_single_variations_wrapper_class', 'wcsv-select2 bt-field pro' ),
				'class'             => 'wc-enhanced-select',
				'display_type'      => 'dropdown',
				'multiple'          => 'true',
				'options'           => $this->get_categories(),
				'custom_attributes' => array( 'multiple' => 'multiple' ),
				'description'       => __( 'You can add or remove categories for the variations. By default parent product category is set', 'wc-show-single-variations' ),
				'desc_tip'          => true,

			)
		);
		woocommerce_wp_select(
			array(
				'id'                => '_tag[' . $variation->ID . '][]',
				'label'             => __( 'Variation Tag', 'wc-show-single-variations' ),
				'value'             => get_post_meta( $variation->ID, '_tag', true ) ? get_post_meta( $variation->ID, '_tag', true ) : $this->get_variation_object_terms( $variation->ID, 'product_tag' ),
				'style'             => 'margin-right: 5px !important',
				'wrapper_class'     => apply_filters( 'wcsv_single_variations_wrapper_class', 'wcsv-select2 bt-field pro' ),
				'class'             => 'wc-enhanced-select',
				'display_type'      => 'dropdown',
				'multiple'          => 'true',
				'options'           => $this->get_tags(),
				'custom_attributes' => array( 'multiple' => 'multiple' ),
				'description'       => __( 'You can add or remove tags for the variations. By default parent product tag is set', 'wc-show-single-variations' ),
				'desc_tip'          => true,
			)
		);


	}

	/**
	 * save single variation additional fields
	 *
	 * @param $post_id
	 *
	 * @return void
	 * @since  1.0.0
	 */
	public function save_product_variations_data( $post_id ) {

		// Text Field

		$custom_title = isset( $_POST['_custom_title'][ $post_id ] ) ? sanitize_text_field( $_POST['_custom_title'][ $post_id ] ) : '';
		update_post_meta( $post_id, '_custom_title', $custom_title );

		$hide_variation = isset( $_POST['_hide_variation'][ $post_id ] ) ? 'yes' : 'no';
		update_post_meta( $post_id, '_hide_variation', $hide_variation );

		$disable_add_to_cart = isset( $_POST['_disable_add_to_cart'][ $post_id ] ) ? 'yes' : 'no';
		update_post_meta( $post_id, '_disable_add_to_cart', $disable_add_to_cart );

		$hide_catalog = isset( $_POST['_hide_catalog'][ $post_id ] ) ? 'yes' : 'no';
		update_post_meta( $post_id, '_hide_catalog', $hide_catalog );

		$hide_search = isset( $_POST['_hide_search'][ $post_id ] ) ? 'yes' : 'no';
		update_post_meta( $post_id, '_hide_search', $hide_search );

		$hide_filter = isset( $_POST['_hide_filter'][ $post_id ] ) ? 'yes' : 'no';
		update_post_meta( $post_id, '_hide_filter', $hide_filter );

		$is_featured = isset( $_POST['_is_featured'][ $post_id ] ) ? 'yes' : 'no';
		update_post_meta( $post_id, '_is_featured', $is_featured );

		$category = isset( $_POST['_cat'][ $post_id ] ) ? $_POST['_cat'][ $post_id ] : array();
		update_post_meta( $post_id, '_cat', $category );

		$tags = isset( $_POST['_tag'][ $post_id ] ) ? $_POST['_tag'][ $post_id ] : array();
		update_post_meta( $post_id, '_tag', $tags );


	}

	/**
	 * Save parent Variation terms to child variation
	 *
	 * @param $object_id
	 * @param $terms
	 * @param $tt_ids
	 * @param $taxonomy
	 * @param $append
	 * @param $old_tt_ids
	 *
	 * @since 1.0.0
	 */
	public function save_variation_terms( $object_id, $terms, $tt_ids, $taxonomy, $append, $old_tt_ids ) {
		$post_type = get_post_type( $object_id );

		if ( $post_type === "product" ) {
			return;
		}

		$taxonomies = apply_filters( 'wcsv_product_additional_taxonomies', array( 'product_cat', 'product_tag' ) );

		if ( empty( $taxonomies ) ) {
			return;
		}

		if ( in_array( $taxonomy, $taxonomies ) ) {
			$variations = get_children( array(
				'post_parent' => $object_id,
				'post_type'   => 'product_variation',
			), ARRAY_A );

			if ( $variations && ! empty( $variations ) ) {
				$variation_ids = array_keys( $variations );

				foreach ( $variation_ids as $single_variation_id ) {
					wp_set_object_terms( $single_variation_id, $terms, $taxonomy, $append );
				}
			}
		}
	}

	/**
	 * save variation products
	 *
	 * @param $post_id
	 *
	 * @return void
	 * @since  1.0.0
	 */
	public function save_variation_product( $post_id ) {
		if ( wp_is_post_revision( $post_id ) ) {
			return;
		}

		$post_type = get_post_type( $post_id );

		if ( $post_type != "product" ) {
			return;
		}

		$product = wc_get_product( $post_id );

		if ( ! $product ) {
			return;
		}

		if ( ! $product->is_type( 'variable' ) ) {
			return;
		}

		$children = $product->get_children();

		if ( empty( $children ) ) {
			return;
		}

		foreach ( $children as $variation_id ) {
			$this->set_taxonomies( $variation_id );
		}
	}

	/**
	 * Product variation save
	 *
	 * @param $variation_id
	 * @param int|bool $i
	 *
	 * @since 1.0.0
	 */

	public function product_variation_save( $variation_id, $i = false ) {
		$action = filter_input( INPUT_POST, 'action' );

		if ( $action === 'woocommerce_save_variations' && $i === false ) {
			return;
		}

		$this->set_taxonomies( $variation_id );

		$tags = get_post_meta( $variation_id, '_tag', true );
		$cats = get_post_meta( $variation_id, '_cat', true );

		if ( is_array( $tags ) && count( $tags ) ) {
			$tag_ids = array();
			foreach ( $tags as $tag ) {
				$tag_objects = get_term_by( 'slug', $tag, 'product_tag' );
				$tag_ids[]   = $tag_objects->term_id;
			}
			wp_set_post_terms( $variation_id, $tag_ids, 'product_tag' );
		}

		if ( is_array( $cats ) && count( $cats ) ) {
			$cat_ids = array();
			foreach ( $cats as $cat ) {
				$cat_objects = get_term_by( 'slug', $cat, 'product_cat' );
				$cat_ids[]   = $cat_objects->term_id;
			}
			wp_set_post_terms( $variation_id, $cat_ids, 'product_cat' );
		}


		// We need to send all the hook parameters to avoid issues with
		// other plugins/themes listening to this action
		$current_theme = wp_get_theme();
		do_action( 'switch_theme', $current_theme->get( 'Name' ), $current_theme, $current_theme );
	}


	/**
	 * Add global variation tabs in the variable products
	 *
	 * @param $product_data_tabs
	 * return Array
	 *
	 * @return mixed
	 * @since 1.0.0
	 */
	public function single_variation_tabs( $product_data_tabs ) {
		$product_data_tabs['single-variation-tab'] = array(
			'label'  => __( 'Single Variation Settings', 'wc-show-single-variations' ),
			'target' => 'single_variation_data_tabs',
			'class'  => array( 'show_if_variable ' ),
		);

		return $product_data_tabs;
	}

	/**
	 * Add css to the custom tabs
	 * @since 1.0.0
	 */
	public function tabs_custom_style() {
		?>
        <style>
            #woocommerce-product-data ul.wc-tabs li.single-variation-tab_options a:before {
                font-family: 'Dashicons';
                content: "\f468";
            }
        </style>
		<?php
	}

	/**
	 * Add fields in the single variation tabs
	 * @since 1.0.0
	 */
	public function single_variation_product_data_fields() {
		global $post; ?>
        <div id="single_variation_data_tabs" class="panel woocommerce_options_panel">
            <div class="options_group">
				<?php
				woocommerce_wp_checkbox(
					array(
						'id'            => '_hide_single_variations_in_catalog',
						'label'         => __( 'Hide Single Variations in catalog', 'wc-show-single-variations' ),
						'wrapper_class' => 'show_if_variable',
						'description'   => __( 'Hide All Single Variations of this product in the catalog', 'wc-show-single-variations' ),
						'desc_tip'      => false,
					)
				);
				
				woocommerce_wp_checkbox(
					array(
						'id'            => '_hide_single_variations_in_search',
						'label'         => __( 'Hide Single Variations in search', 'wc-show-single-variations' ),
						'wrapper_class' => 'show_if_variable',
						'description'   => __( 'Hide All Single Variations of this product in search', 'wc-show-single-variations' ),
						'desc_tip'      => false,
					)
				);
				woocommerce_wp_checkbox(
					array(
						'id'          => '_hide_parent_products',
						'label'       => __( 'Hide Parents Products for this variations', 'wc-show-single-variations' ),
						'wrapper'     => 'show_if_variable',
						'description' => __( 'Hide Parent Product for this variation', 'wc-show-single-variations' ),
						'desc_tip'    => false,
					)
				);
				woocommerce_wp_checkbox(
					array(
						'id'          => '_hide_single_variations_in_filter',
						'label'       => __( 'Hide Single Variations in filter', 'wc-show-single-variations' ),
						'wrapper'     => 'show_if_variable',
						'description' => __( 'Hide All Single Variations of this product in the filter results', 'wc-show-single-variations' ),
						'desc_tip'    => false,
					)
				);
				woocommerce_wp_checkbox(
					array(
						'id'          => '_hide_add_to_cart',
						'label'       => __( 'Disable add to cart button button', 'wc-show-single-variations' ),
						'wrapper'     => 'show_if_variable',
						'description' => __( 'Hide add to cart buttton in all single variations of this product', 'wc-show-single-variations' ),
						'desc_tip'    => false,
					)
				);
				?>
            </div>
        </div>
		<?php
	}

	/**
	 * Save the data of the fields in single variation tabs
	 *
	 * @param $post_id
	 *
	 * @return void
	 * @since 1.0.0
	 */

	public function single_variations_data_fields_save( $post_id ) {
		
		$hide_in_catalog = isset( $_POST['_hide_single_variations_in_catalog'] ) ? 'yes' : 'no';
		update_post_meta( $post_id, '_hide_single_variations_in_catalog', $hide_in_catalog );

		$hide_in_search = isset( $_POST['_hide_single_variations_in_search'] ) ? 'yes' : 'no';
		update_post_meta( $post_id, '_hide_single_variations_in_search', $hide_in_search );

		$hide_parent_products = isset( $_POST['_hide_parent_products'] ) ? 'yes' : 'no';
		update_post_meta( $post_id, '_hide_parent_products', $hide_parent_products );

		$hide_in_filter = isset( $_POST['_hide_single_variations_in_filter'] ) ? 'yes' : 'no';
		update_post_meta( $post_id, '_hide_single_variations_in_filter', $hide_in_filter );

		$hide_add_to_cart = isset( $_POST['_hide_add_to_cart'] ) ? 'yes' : 'no';
		update_post_meta( $post_id, '_hide_add_to_cart', $hide_add_to_cart );


	}


	/**
	 * get product tags
	 * @return array
	 * @since  1.0.0
	 */

	public function get_tags() {
		$tags = get_terms(
			array(
				'taxonomy'   => 'product_tag',
				'hide_empty' => false,
			)
		);

		$product_tag = array();
		if ( is_array( $tags ) && count( $tags ) ) {
			foreach ( $tags as $single_tag ) {
				$product_tag[ $single_tag->slug ] = $single_tag->name;
			}
		}

		return $product_tag;
	}

	/**
	 * set product taxonomies
	 *
	 * @param $variation_id
	 *
	 * @return void
	 * @since  1.0.0
	 */
	public function set_taxonomies( $variation_id ) {
		$taxonomies = apply_filters( 'wcsv_product_additional_taxonomies', array( 'product_cat', 'product_tag' ) );

		if ( empty( $taxonomies ) ) {
			return;
		}

		$parent_product_id = wp_get_post_parent_id( $variation_id );

		if ( $parent_product_id ) {
			foreach ( $taxonomies as $taxonomy ) {
				$terms = (array) wp_get_post_terms( $parent_product_id, $taxonomy, array( "fields" => "ids" ) );
				wp_set_post_terms( $variation_id, $terms, $taxonomy );
			}
		}
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

		$category = array();
		if ( is_array( $categories ) && count( $categories ) ) {
			foreach ( $categories as $single_category ) {
				$category[ $single_category->slug ] = $single_category->name;
			}
		}

		return $category;
	}

	/**
	 * Get object terms
	 *
	 * @param $variation_id
	 * @param $taxonomy_name
	 *
	 * @return array
	 *
	 * @since 1.0.0
	 */
	public function get_variation_object_terms( $variation_id, $taxonomy_name ) {

		$object_terms = wp_get_object_terms( $variation_id, $taxonomy_name );
		$terms        = array();
		foreach ( $object_terms as $single ) {
			$terms[] = $single->slug;
		}

		return $terms;
	}

}

WCSV_Admin::instance();