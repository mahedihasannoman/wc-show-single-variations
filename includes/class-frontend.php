<?php
// don't call the file directly
defined( 'ABSPATH' ) || exit();

class WCSV_Frontend {
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
	 * Frontend Class constructor
	 * @since 1.0.0
	 */
	public function __construct() {
		add_action( 'woocommerce_product_query', array( $this, 'single_product_query' ) );
		add_filter( 'posts_clauses', array( $this, 'single_posts_clauses' ), 10, 2 );
		add_action( 'pre_get_posts', array( $this, 'modify_search_query' ), 99 );
		add_filter( 'post_class', array( $this, 'adding_post_class_in_loop' ) );
		add_filter( 'the_title', array( $this, 'change_single_variation_title' ), 10, 2 );
		add_filter( 'woocommerce_loop_add_to_cart_link', array( $this, 'change_single_variation_cart_link' ), 10, 3 );
		add_filter( 'post_class', array( $this, 'single_variation_product_post_class' ), 20, 3 );
		add_filter( 'wcsv_shop_excluded_query', array( $this, 'shop_excluded_products' ) );
		add_filter( 'woocommerce_shortcode_products_query', array( $this, 'shortcode_products_query' ), 10, 3 );
	}

	/**
	 * Modify product query for woocommerce shortcode
	 *
	 * @param array $query_args
	 * @param array $atts
	 * @param string $loop_name
	 * @return array $query_args
	 */
	public function shortcode_products_query( $query_args, $atts, $loop_name ){
		global $wpdb;

		$variation_option  = WC_Admin_Settings::get_option( 'wc_single_variation_option', 'single' );
		$hide_parent       = WC_Admin_Settings::get_option( 'wc_single_variation_tab_hide_parent', 'yes' );

		$meta_query = array();
		if ( $variation_option !== 'dropdown' ) {

			$query_args['wcss_filter'] = 'yes';
			$query_args['post_type'] = array( 'product', 'product_variation' );
			$meta_query[] = array(
				'relation' => 'OR',
				array(
					'key'     => '_variation_description',
					'compare' => 'NOT EXISTS'
				),
				array(
					'key'     => '_hide_catalog',
					'compare' => 'NOT EXISTS'
				),
				array(
					'key'     => '_hide_catalog',
					'value'   => 'no',
					'compare' => '=='
				),
			);

			// hide this variation if hide variation is enabled from variation product
			$meta_query[] = array(
				'relation' => 'OR',
				array(
					'key'     => '_hide_variation',
					'compare' => '!=',
					'value'   => 'yes',
				),
				array(
					'key'     => '_hide_variation',
					'compare' => 'NOT EXISTS'
				),
			);
			// hide parent product for this variation
			$meta_query[] = array(
				'relation' => 'OR',
				array(
					'key'     => '_hide_parent_products',
					'compare' => '!=',
					'value'   => 'yes',
				),
				array(
					'key'     => '_hide_parent_products',
					'compare' => 'NOT EXISTS'
				),
			);

			$query_args['meta_query'] = apply_filters( 'wcsv_meta_query', $meta_query );

			if ( $hide_parent == 'yes' ){
				$exclude_ids = $wpdb->get_col( "
					SELECT DISTINCT p.post_parent
					FROM {$wpdb->prefix}posts as p
					WHERE p.post_type = 'product_variation'
					AND p.post_status LIKE 'publish'
				" );
				$query_args['post__not_in '] = apply_filters( 'wcsv_shop_excluded_query', $exclude_ids );
			}
			
		}
		return $query_args;
	}

	/**
	 * Single variation product query
	 *
	 * @param $args
	 *
	 * @return void
	 * 
	 * @since 1.0.0
	 */
	public function single_product_query( $args ) {

		$catalog_enable = WC_Admin_Settings::get_option( 'wc_single_variation_tab_show_in_catalog', 'yes' );
		$variation_option  = WC_Admin_Settings::get_option( 'wc_single_variation_option', 'single' );

		$meta_query   = (array) $args->get( 'meta_query' );
		$tax_query    = (array) $args->get( 'tax_query' );
		$posts_not_in = (array) $args->get( 'post__not_in' );

		if ( ( is_product_category() && $catalog_enable == 'yes' && $variation_option !== 'dropdown' ) || ( ! is_product_category() && $variation_option !== 'dropdown' ) ) {
			$args->set( 'wcss_filter', 'yes' );
			$args->set( 'post_type', array( 'product', 'product_variation' ) );
			$meta_query[] = array(
				'relation' => 'OR',
				array(
					'key'     => '_variation_description',
					'compare' => 'NOT EXISTS'
				),
				array(
					'key'     => '_hide_catalog',
					'compare' => 'NOT EXISTS'
				),
				array(
					'key'     => '_hide_catalog',
					'value'   => 'no',
					'compare' => '=='
				),
			);

			// hide this variation if hide variation is enabled from variation product
			$meta_query[] = array(
				'relation' => 'OR',
				array(
					'key'     => '_hide_variation',
					'compare' => '!=',
					'value'   => 'yes',
				),
				array(
					'key'     => '_hide_variation',
					'compare' => 'NOT EXISTS'
				),
			);

			// hide parent product for this variation
			$meta_query[] = array(
				'relation' => 'OR',
				array(
					'key'     => '_hide_parent_products',
					'compare' => '!=',
					'value'   => 'yes',
				),
				array(
					'key'     => '_hide_parent_products',
					'compare' => 'NOT EXISTS'
				),
			);
			
			$meta_query = apply_filters( 'wcsv_meta_query', $meta_query );
			$args->set( 'post__not_in', apply_filters( 'wcsv_shop_excluded_query', $posts_not_in ) );
		} else {
			$meta_query[] = array(
				'relation' => 'OR',
				array(
					'key'     => '_variation_description',
					'compare' => 'NOT EXISTS'
				)
			);
		}

		$args->set( 'meta_query', $meta_query );
	}

	/**
	 * Hide the parent product when hide parent is on
	 *
	 * @param $clauses
	 * @param $query
	 *
	 * @return Array
	 * 
	 * @since 1.0.0
	 */
	public function single_posts_clauses( $clauses, $query ) {
		global $wpdb;

		$catalog_enable    = WC_Admin_Settings::get_option( 'wc_single_variation_tab_show_in_catalog', 'yes' );
		$hide_parent       = WC_Admin_Settings::get_option( 'wc_single_variation_tab_hide_parent', 'yes' );
		$excluded_category = WC_Admin_Settings::get_option( 'wc_single_variation_tab_exclude_categories', array() );
		$variation_option  = WC_Admin_Settings::get_option( 'wc_single_variation_option', 'single' );

		if ( ! empty( $query->query_vars['wcss_filter'] ) && $query->query_vars['wcss_filter'] == 'yes' && $variation_option !== 'dropdown' ) {

			if ( $hide_parent == 'yes' ) {
				$clauses['where'] .= " AND  0 = (select count(*) as totalpart from {$wpdb->posts} as post_table where post_table.post_parent = {$wpdb->posts}.ID and post_table.post_type = 'product_variation' ) ";
			}

/* 			$clauses['join'] .= " LEFT JOIN {$wpdb->postmeta} as post_meta ON ({$wpdb->posts}.post_parent = post_meta.post_id AND post_meta.meta_key = '_hide_catalog' )";

			if ( empty( $catalog_enable ) ) {
				$clauses['where'] .= " AND ( post_meta.meta_value IS NULL OR post_meta.meta_value = 'no' OR post_meta.meta_value = 'yes' ) ";
			} else {
				$clauses['where'] .= " AND ( post_meta.meta_value IS NULL OR post_meta.meta_value = 'no' OR post_meta.meta_value = 'yes' ) ";
			} */

		}

		return $clauses;
	}

	/**
	 * Modify search query
	 *
	 * @param object $query
	 *
	 * @return void
	 * 
	 * @since 1.0.0
	 */
	function modify_search_query( $query ) {
		$show_in_search = WC_Admin_Settings::get_option( 'wc_single_variation_tab_show_search_result', 'yes' );
		$variation_option       = WC_Admin_Settings::get_option( 'wc_single_variation_option', 'single' );
		if ( is_admin() || ! $query->is_main_query() ) {
			return;
		}
		// Make sure this isn't the WooCommerce product search form
		if ( isset( $_GET['post_type'] ) && ( $_GET['post_type'] == 'product' ) ) {
			return;
		}

		if ( $query->is_search() ) {

			$meta_query[] = array(
				'relation' => 'OR',
				array(
					'key'     => '_hide_search',
					'compare' => 'NOT EXISTS'
				),
				array(
					'key'     => '_hide_search',
					'value'   => 'no',
					'compare' => '=='
				),
			);

			// hide this variation if hide variation is enabled from variation product
			$meta_query[] = array(
				'relation' => 'OR',
				array(
					'key'     => '_hide_variation',
					'compare' => '!=',
					'value'   => 'yes',
				),
				array(
					'key'     => '_hide_variation',
					'compare' => 'NOT EXISTS'
				),
			);

			if ( $show_in_search == 'yes' && $variation_option !== 'dropdown' ) {
				$in_search_post_types                      = get_post_types( array( 'exclude_from_search' => false ) );
				$in_search_post_types['product_variation'] = 'product_variation';
				$search_post_type                          = array();
				foreach ( $in_search_post_types as $key => $value ) {
					$search_post_type[] = $value;
				}
				$query->set( 'post_type', $search_post_type );
				$query->set( 'meta_query', $meta_query );
			}
		}
	}

	/**
	 * Add relevant product classes to loop
	 *
	 * @param array $classes
	 *
	 * @return array
	 * 
	 * @since 1.0.0
	 */
	public function adding_post_class_in_loop( $classes ) {
		global $product;
		if ( ! $product || ! is_object( $product ) || ! $product->is_type( 'variation' ) ) {
			return $classes;
		}

		$classes   = array_diff( $classes, array( 'hentry', 'post' ) );
		$classes[] = "product";
		$classes[] = "type-product";

		return $classes;
	}

	/**
	 * Change variation title
	 *
	 * @param string $title
	 * @param int|bool $id
	 *
	 * @return html
	 * 
	 * @since 1.0.0
	 */
	public function change_single_variation_title( $title, $id = false ) {
		$post_type = get_post_type( $id );
		$variation_option  = WC_Admin_Settings::get_option( 'wc_single_variation_option', 'single' );
		$custom_title  = WC_Admin_Settings::get_option( 'wc_single_variation_custom_title', 'no' );
		if ( $post_type == 'product_variation' && $variation_option !== 'dropdown' ) {
			$variation_id = $id;
			if ( ! $variation_id || $variation_id == '' ) {
				return "";
			}
			$variation            = wc_get_product( $variation_id );
			$variation_attributes = $variation->get_attributes();
			
			if ( $custom_title == 'yes' ) {

				$seo_title_template 		= WC_Admin_Settings::get_option( 'wc_single_variation_seo_title_template' );
				$attribute_template 		= WC_Admin_Settings::get_option( 'wc_single_variation_attribute_template' );
				$attribute_name_appendix 	= WC_Admin_Settings::get_option( 'wc_single_variation_attributes_names_appendix' );

				$finalattrs = array();
				foreach ( $variation_attributes as $attr_name => $attr_value ) {
					$finalattrs[] = str_replace( array( '{attributes_name}', '{attributes_value}' ), array( ucfirst( str_replace( 'pa_', '', $attr_name ) ), ucfirst( $attr_value ) ), $attribute_template );
				}
				
				if( $attribute_name_appendix != '' ) {
					$final_attributes = implode( ' '.$attribute_name_appendix.' ', $finalattrs );
				} else {
					$final_attributes = implode( ' and ', $finalattrs );
				}
				$variation_title = str_replace( array( '{title}', '{attributes}' ), array( $variation->get_title(), $final_attributes ), $seo_title_template );

			} else {
				$attributes           = implode( " ", $variation_attributes );
				$variation_title        = ( $variation->get_title() != "Auto Draft" ) ? $variation->get_title() . " - " . ucfirst( $attributes ) : "";
			}

			$variation_custom_title = get_post_meta( $variation->get_id(), '_custom_title', true );

			$title = ( $variation_custom_title ) ? $variation_custom_title : $variation_title;
		}

		return $title;

	}

	/**
	 * Display variation dropdown on shop page
	 * callback for woocommerce_loop_add_to_cart_link filter
	 *
	 * @since 1.0.0
	 * 
	 * @param html $anchor
	 * @param object $product
	 * @param array $args
	 * 
	 * @return mixed
	 */
	public function change_single_variation_cart_link( $anchor, $product, $args ) {

		$enable_add_to_cart = WC_Admin_Settings::get_option( 'wc_single_variation_tab_enable_add_to_cart', 'yes' );
		$variation_option  	= WC_Admin_Settings::get_option( 'wc_single_variation_option', 'single' );
		$product_id 		= $product->get_id();

		if ( empty( $product_id ) ) {
			return $anchor;
		}

		$product_type = method_exists( $product, 'get_type' ) ? $product->get_type() : $product->product_type;

		if( $variation_option == 'dropdown' ) {

			if( ! $product->is_type( 'variable' ) ){
				return $anchor;
			}
			wp_enqueue_script('wc-add-to-cart-variation');
			$attribute_keys = array_keys( $product->get_variation_attributes() );
			?>
				<form class="variations_form cart" method="post" enctype='multipart/form-data' data-product_id="<?php echo absint( $product->get_id() ); ?>" data-product_variations="<?php echo htmlspecialchars( json_encode( $product->get_available_variations() ) ) ?>">
					<?php do_action( 'woocommerce_before_variations_form' ); ?>
					<?php if ( empty( $product->get_available_variations() ) && false !== $product->get_available_variations() ) : ?>
						<p class="stock out-of-stock">
							<?php _e( 'This product is currently out of stock and unavailable.', 'wc-show-single-variations' ); ?>
						</p>
					<?php else : ?>
						<table class="variations" cellspacing="0">
							<tbody>
								<?php foreach ( $product->get_variation_attributes() as $attribute_name => $options ) : ?>
								<tr>
									<td class="label"><label for="<?php echo sanitize_title( $attribute_name ); ?>"><?php echo wc_attribute_label( $attribute_name ); ?></label></td>
									<td class="value">
										<?php
										$selected = isset( $_REQUEST[ 'attribute_' . sanitize_title( $attribute_name ) ] ) ? wc_clean( urldecode( $_REQUEST[ 'attribute_' . sanitize_title( $attribute_name ) ] ) ) : $product->get_variation_default_attribute( $attribute_name );
										wc_dropdown_variation_attribute_options( array( 'options' => $options, 'attribute' => $attribute_name, 'product' => $product, 'selected' => $selected ) );
										?>
									</td>
								</tr>
								<?php endforeach;?>
							</tbody>
						</table>
						<?php do_action( 'woocommerce_before_add_to_cart_button' ); ?>
						<div class="single_variation_wrap">
						<?php
							/**
							 * woocommerce_before_single_variation Hook.
							 */
							do_action( 'woocommerce_before_single_variation' );
							/**
							 * woocommerce_single_variation hook. Used to output the cart button and placeholder for variation data.
							 * @since 2.4.0
							 * @hooked woocommerce_single_variation - 10 Empty div for variation data.
							 * @hooked woocommerce_single_variation_add_to_cart_button - 20 Qty and cart button.
							 */
							do_action( 'woocommerce_single_variation' );
							/**
							 * woocommerce_after_single_variation Hook.
							 */
							do_action( 'woocommerce_after_single_variation' );
						?>
						</div>
						<?php do_action( 'woocommerce_after_add_to_cart_button' ); ?>
					<?php endif; ?>
					<?php do_action( 'woocommerce_after_variations_form' ); ?>
				</form>
			<?php 
		} else if( $variation_option == 'single' ) {

			if ( $product_type !== "variation" ) {
				return $anchor;
			}

			$parent_product_id       = $product->get_parent_id();
			$parent_hide_add_to_cart = get_post_meta( $parent_product_id, '_hide_add_to_cart', true );
			$disable_add_to_cart = get_post_meta( $product_id, '_disable_add_to_cart', true );
			$button_class        = $button_text = $url = '';
			if ( $enable_add_to_cart == 'yes' ) {
				if ( $parent_hide_add_to_cart == 'yes' ) {
					$url         = $this->get_variation_url( $product );
					$button_text = ( $product->is_in_stock() ) ? __( 'Select Options', 'wc-show-single-variations' ) : __( 'Read More', 'wc-show-single-variations' );
				} else {
					if ( $disable_add_to_cart == 'yes' ) {
						$url         = $this->get_variation_url( $product );
						$button_text = ( $product->is_in_stock() ) ? __( 'Select options', 'wc-show-single-variations' ) : __( 'Read More', 'wc-show-single-variations' );
	
					} else {
						return $anchor;
					}
				}
			} else {
				$url          = $this->get_variation_url( $product );
				$button_class = $product->is_in_stock() ? 'add_to_cart add_to_cart_button' : '';
				$button_text  = ( $product->is_in_stock() ) ? __( 'Select options', 'wc-show-single-variations' ) : __( 'Read More', 'wc-show-single-variations' );
			}

			$args = apply_filters( 'wcsv_button_args', array(
				'href'         => $url,
				'product_id'   => $product_id,
				'sku'          => $product->get_sku(),
				'qty'          => isset( $quantity ) ? $quantity : 1,
				'button_class' => $button_class,
				'product_type' => $product_type,
				'button_text'  => $button_text,
				'attributes'   => '',
			), $product );
	
			return sprintf( '<a href="%s" rel="nofollow" data-product_id="%s" data-product_sku="%s" data-quantity="%s" class="button %s product_type_%s" data-variation_id="%s">%s</a>',
				esc_url( $args['href'] ),
				esc_attr( $args['product_id'] ),
				esc_attr( $args['sku'] ),
				esc_attr( $args['qty'] ),
				esc_attr( apply_filters( 'wcsv_add_to_cart_button_class', $args['button_class'] ) ),
				esc_attr( $args['product_type'] ),
				esc_html( $args['product_id'] ),
				$args['button_text']
			);

		} else { 
	   
			echo sprintf( '<a href="%s" data-quantity="%s" class="%s" %s>%s</a>',
				esc_url( $product->add_to_cart_url() ),
				esc_attr( isset( $args['quantity'] ) ? $args['quantity'] : 1 ),
				esc_attr( isset( $args['class'] ) ? $args['class'] : 'button' ),
				isset( $args['attributes'] ) ? wc_implode_html_attributes( $args['attributes'] ) : '',
				esc_html( $product->add_to_cart_text() )
			);

		}
		
	}


	/**
	 * Add post classes the single variation
	 *
	 * @param $classes
	 * @param $class
	 * @param $post_id
	 *
	 * @return array
	 */
	public function single_variation_product_post_class( $classes, $class = '', $post_id = '' ) {
		
		$variation_option       = WC_Admin_Settings::get_option( 'wc_single_variation_option', 'single' );
		if ( ! $post_id || 'product_variation' !== get_post_type( $post_id ) ) {
			return $classes;
		}

		if( $variation_option == 'dropdown' ){
			return $classes;
		}

		$product        = wc_get_product( $post_id );
		$check_featured = get_post_meta( $post_id, '_is_featured', true );

		if ( $product ) {
			$product_type = method_exists( $product, 'get_type' ) ? $product->get_type() : $product->product_type;

			//$classes[] = wc_get_loop_class();

			$classes[] = method_exists( $product, 'get_stock_status' ) ? $product->get_stock_status() : $product->stock_status;

			if ( $product->is_on_sale() ) {
				$classes[] = 'sale';
			}
			if ( $product->is_featured() || ( $check_featured == 'yes' ) ) {
				$classes[] = 'featured';
			}
			if ( $product->is_downloadable() ) {
				$classes[] = 'downloadable';
			}
			if ( $product->is_virtual() ) {
				$classes[] = 'virtual';
			}
			if ( $product->is_sold_individually() ) {
				$classes[] = 'sold-individually';
			}
			if ( $product->is_taxable() ) {
				$classes[] = 'taxable';
			}
			if ( $product->is_shipping_taxable() ) {
				$classes[] = 'shipping-taxable';
			}
			if ( $product->is_purchasable() ) {
				$classes[] = 'purchasable';
			}
			if ( $product_type ) {
				$classes[] = "product-type-" . $product_type;
			}
		}

		if ( false !== ( $key = array_search( 'hentry', $classes ) ) ) {
			unset( $classes[ $key ] );
		}

		return $classes;
	}

	/**
	 *  Get variation URL
	 *
	 * @param string [$variation]
	 *
	 * @return string
	 *
	 * @since 1.0.0
	 */
	public function get_variation_url( $variation ) {
		$url                 = "";
		$variation_parent_id = method_exists( $variation, 'get_parent_id' ) ? $variation->get_parent_id() : $variation->parent->id;

		if ( $variation_parent_id ) {
			$variation_data     = array_filter( wc_get_product_variation_attributes( $variation->get_id() ) );
			$parent_product_url = get_the_permalink( $variation_parent_id );
			$url = add_query_arg( $variation_data, $parent_product_url );
		}

		return $url;
	}

	/**
	 *  Exclude variation products from shop query
	 *
	 * @param $posts_not_in
	 *
	 * @return Array
	 *
	 * @since 1.0.0
	 */
	public function shop_excluded_products( $posts_not_in ) {
		$excluded_category = WC_Admin_Settings::get_option( 'wc_single_variation_tab_exclude_categories', array() );
		$included_category = WC_Admin_Settings::get_option( 'wc_single_variation_tab_include_categories', array() );
		$variation_option  = WC_Admin_Settings::get_option( 'wc_single_variation_option', 'single' );

		if( $variation_option == 'dropdown' ){
			return $posts_not_in;
		}
		
		//hide single variation for this product in catelog
		if( ! is_search() && ! is_filtered() ){
			$products_to_hide_single_variation = get_posts(
				array(
					'post_type'      => array( 'product' ),
					'meta_query'      => array(
						array(
							'key'     => '_hide_single_variations_in_catalog',
							'value'   => 'yes',
							'compare' => '=='
						)
					),
					'posts_per_page' => - 1,
				)
			);
			$products_to_hide_single_variation_ids = array();
			if( ! empty( $products_to_hide_single_variation )) {
				foreach ( $products_to_hide_single_variation as $product_to_hide ) {
					$products_to_hide_single_variation_ids[] = $product_to_hide->ID;
				}
				if ( ! empty( $products_to_hide_single_variation_ids ) ){
					$variations_to_hide = get_posts(
						array(
							'post_type'      => array( 'product_variation' ),
							'post_parent__in'	=> $products_to_hide_single_variation_ids,
							'posts_per_page' => - 1,
						)
					);

					if( ! empty( $variations_to_hide ) ) {
						foreach ( $variations_to_hide as $variation_to_hide ) {
							$posts_not_in[] = $variation_to_hide->ID;
						}
					}
				}
			}
		}

		//hide single variation for this product in search
		if ( is_search() ) {
			$products_to_hide_single_variation = get_posts(
				array(
					'post_type'      => array( 'product' ),
					'meta_query'      => array(
						array(
							'key'     => '_hide_single_variations_in_search',
							'value'   => 'yes',
							'compare' => '=='
						)

					),
					'posts_per_page' => - 1,
				)
			);
			$products_to_hide_single_variation_ids = array();
			if( ! empty( $products_to_hide_single_variation )) {
				foreach ( $products_to_hide_single_variation as $product_to_hide ) {
					$products_to_hide_single_variation_ids[] = $product_to_hide->ID;
				}
				if ( ! empty( $products_to_hide_single_variation_ids ) ){
					$variations_to_hide = get_posts(
						array(
							'post_type'      => array( 'product_variation' ),
							'post_parent__in'	=> $products_to_hide_single_variation_ids,
							'posts_per_page' => - 1,
						)
					);

					if( ! empty( $variations_to_hide ) ) {
						foreach ( $variations_to_hide as $variation_to_hide ) {
							$posts_not_in[] = $variation_to_hide->ID;
						}
					}
				}
			}
		}

		//hide single variation for this product in filter
		if ( is_filtered() ) {
			$products_to_hide_single_variation = get_posts(
				array(
					'post_type'      => array( 'product' ),
					'meta_query'      => array(
						array(
							'key'     => '_hide_single_variations_in_filter',
							'value'   => 'yes',
							'compare' => '=='
						)

					),
					'posts_per_page' => - 1,
				)
			);
			$products_to_hide_single_variation_ids = array();
			if( ! empty( $products_to_hide_single_variation )) {
				foreach ( $products_to_hide_single_variation as $product_to_hide ) {
					$products_to_hide_single_variation_ids[] = $product_to_hide->ID;
				}
				if ( ! empty( $products_to_hide_single_variation_ids ) ){
					$variations_to_hide = get_posts(
						array(
							'post_type'      => array( 'product_variation' ),
							'post_parent__in'	=> $products_to_hide_single_variation_ids,
							'posts_per_page' => - 1,
						)
					);

					if( ! empty( $variations_to_hide ) ) {
						foreach ( $variations_to_hide as $variation_to_hide ) {
							$posts_not_in[] = $variation_to_hide->ID;
						}
					}
				}
			}
		}

		$get_excluded_category_posts = array();
		if ( $excluded_category != '' ) {
			$get_excluded_category_posts = get_posts(
				array(
					'post_type'      => array( 'product_variation' ),
					'tax_query'      => array(
						array(
							'taxonomy' => 'product_cat',
							'field'    => 'slug',
							'terms'    => $excluded_category,
						)

					),
					'posts_per_page' => - 1,
				)
			);

		} elseif ( $excluded_category == '' && $included_category[0] != 'all' ) {
			$get_excluded_category_posts = get_posts(
				array(
					'post_type'      => array( 'product_variation' ),
					'tax_query'      => array(
						array(
							'taxonomy' => 'product_cat',
							'field'    => 'slug',
							'terms'    => $included_category,
							'operator' => 'NOT IN',
						)

					),
					'posts_per_page' => - 1,
				)
			);
		}
		if ( empty( $get_excluded_category_posts ) ) {
			return $posts_not_in;
		}
		if ( is_array( $get_excluded_category_posts ) && count( $get_excluded_category_posts ) ) {
			foreach ( $get_excluded_category_posts as $single ) {
				$posts_not_in[] = $single->ID;
			}
		}
		return $posts_not_in;
	}

}

WCSV_Frontend::instance();