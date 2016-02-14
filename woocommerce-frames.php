<?php
/*
Plugin Name: WooCommerce Frames
Plugin URI: http://romapad.ru/
Description: WooCommerce Frames lets you cell frames in your paintings or photo store. User may choose wich frame is suitable for thier chosen picture, see preview of picture in frame and finally buy it.
Version: 0.1
Author: romapad
Author URI: http://romapad.ru
Requires at least: 3.8
Tested up to: 4.3
WC tested up to: 2.4
	License: GNU General Public License v3.0
	License URI: http://www.gnu.org/licenses/gpl-3.0.html
*/

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Required functions
 */
if ( ! function_exists( 'woothemes_queue_update' ) ) {
	require_once( 'woo-includes/woo-functions.php' );
}


if ( is_woocommerce_active() ) {

	/**
	 * Main class
	 */
	class WC_Frames {

		/**
		 * Constructor
		 */
		public function __construct() {
			if ( is_admin() ) {
				include_once( 'admin/class-frame-admin.php' );
			}

			include_once( 'classes/class-frame-display.php' );
			include_once( 'classes/class-frame-cart.php' );
			include_once( 'classes/class-wc-addons-ajax.php' );

			add_action( 'plugins_loaded', array( $this, 'load_plugin_textdomain' ) );
			add_action( 'init', array( $this, 'init_post_types' ), 20 );
		}

		/**
		 * Localisation
		 */
		public function load_plugin_textdomain() {
			load_plugin_textdomain( 'grmpd-frames', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
		}

		/**
		 * Init post types used for addons
		 */
		public function init_post_types() {
			register_post_type( "global_frames",
				array(
					'public'              => false,
					'show_ui'             => false,
					'capability_type'     => 'product',
					'map_meta_cap'        => true,
					'publicly_queryable'  => false,
					'exclude_from_search' => true,
					'hierarchical'        => false,
					'rewrite'             => false,
					'query_var'           => false,
					'supports'            => array( 'title' ),
					'show_in_nav_menus'   => false
				)
			);

			register_taxonomy_for_object_type( 'product_cat', 'global_frames' );
		}
	}

	new WC_Frames();

	/**
	 * Gets addons assigned to a product by ID
	 *
	 * @param  int $post_id ID of the product to get addons for
	 * @param  string $prefix for addon field names. Defaults to postid-
	 * @param  bool $inc_parent Set to false to not include parent frames.
	 * @param  bool $inc_global Set to false to not include global addons.
	 * @return array array of addons
	 */
	function get_frames( $post_id, $prefix = false, $inc_parent = true, $inc_global = true ) {
		if ( ! $post_id ) {
			return array();
		}

		$addons        = array();
		$raw_addons    = array();
		$product_terms = apply_filters( 'get_frames_product_terms', wp_get_post_terms( $post_id, 'product_cat', array( 'fields' => 'ids' ) ), $post_id );
		$exclude       = get_post_meta( $post_id, '_frames_exclude_global', true );

		// Product Parent Level Addons
		if ( $inc_parent && $parent_id = wp_get_post_parent_id( $post_id ) ) {
			$raw_addons[10]['parent'] = apply_filters( 'get_parent_frames_fields', get_frames( $parent_id, $parent_id . '-', false, false ), $post_id, $parent_id );
		}

		// Product Level Addons
		$raw_addons[10]['product'] = apply_filters( 'get_frames_fields', array_filter( (array) get_post_meta( $post_id, '_frames', true ) ), $post_id );

		// Global level addons (all products)
		if ( '1' !== $exclude && $inc_global ) {
			$args = array(
				'posts_per_page'   => -1,
				'orderby'          => 'meta_value',
				'order'            => 'ASC',
				'meta_key'         => '_priority',
				'post_type'        => 'global_frames',
				'post_status'      => 'publish',
				'suppress_filters' => true,
				'meta_query' => array(
					array(
						'key'   => '_all_products',
						'value' => '1',
					)
				)
			);

			$grmpd_frames = get_posts( $args );

			if ( $grmpd_frames ) {
				foreach ( $grmpd_frames as $global_addon ) {
					$priority                                     = get_post_meta( $global_addon->ID, '_priority', true );
					$raw_addons[ $priority ][ $global_addon->ID ] = apply_filters( 'get_frames_fields', array_filter( (array) get_post_meta( $global_addon->ID, '_frames', true ) ), $global_addon->ID );
				}
			}

			// Global level addons (categories)
			if ( $product_terms ) {
				$args = apply_filters( 'get_frames_global_query_args', array(
					'posts_per_page'   => -1,
					'orderby'          => 'meta_value',
					'order'            => 'ASC',
					'meta_key'         => '_priority',
					'post_type'        => 'global_frames',
					'post_status'      => 'publish',
					'suppress_filters' => true,
					'tax_query'        => array(
						array(
							'taxonomy'         => 'product_cat',
							'field'            => 'id',
							'terms'            => $product_terms,
							'include_children' => false
						)
					)
				), $product_terms );

				$grmpd_frames = get_posts( $args );

				if ( $grmpd_frames ) {
					foreach ( $grmpd_frames as $global_addon ) {
						$priority                                     = get_post_meta( $global_addon->ID, '_priority', true );
						$raw_addons[ $priority ][ $global_addon->ID ] = apply_filters( 'get_frames_fields', array_filter( (array) get_post_meta( $global_addon->ID, '_frames', true ) ), $global_addon->ID );
					}
				}
			}

		}

		ksort( $raw_addons );

		foreach ( $raw_addons as $addon_group ) {
			if ( $addon_group ) {
				foreach ( $addon_group as $addon ) {
					$addons = array_merge( $addons, $addon );
				}
			}
		}

		// Generate field names with unqiue prefixes
		if ( ! $prefix ) {
			$prefix = apply_filters( 'frames_field_prefix', "{$post_id}-", $post_id );
		}

		// Let's avoid exceeding the suhosin default input element name limit of 64 characters
		$max_addon_name_length = 45 - strlen( $prefix );

		// if the frames_field_prefix filter results in a very long prefix, then
		// go ahead and enforce sanity, exceed the default suhosin limit, and just use
		// the prefix and the field counter for the input element name
		if ( $max_addon_name_length < 0 ) {
			$max_addon_name_length = 0;
		}

		$addon_field_counter = 0;

		foreach ( $addons as $addon_key => $addon ) {
			if ( empty( $addon['name'] ) ) {
				unset( $addons[ $addon_key ] );
				continue;
			}
			if ( empty( $addons[ $addon_key ]['field-name'] ) ) {
				$addon_name = substr( $addon['name'], 0, $max_addon_name_length );
				$addons[ $addon_key ]['field-name'] = sanitize_title( $prefix . $addon_name . "-" . $addon_field_counter );
				$addon_field_counter++;
			}
		}

		return apply_filters( 'get_frames', $addons );
	}

	/**
	 * Display prices according to shop settings
	 *
	 * @param  float $price
	 *
	 * @return float
	 */
	function get_frame_price_for_display( $price ) {
		global $product;

		if ( $price === '' || $price == '0' ) {
			return;
		}

		if ( is_object( $product ) ) {
			$tax_display_mode = get_option( 'woocommerce_tax_display_shop' );
			$display_price    = $tax_display_mode == 'incl' ? $product->get_price_including_tax( 1, $price ) : $product->get_price_excluding_tax( 1, $price );
		} else {
			$display_price    = $price;
		}

		return $display_price;
	}
}
