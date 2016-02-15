<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Grmpd_Frame_Display class.
 */
class Grmpd_Frame_Display {

	/**
	 * __construct function.
	 *
	 * @access public
	 * @return void
	 */
	public function __construct() {
		// Styles
		add_action( 'get_header', array( $this, 'styles' ) );
		add_action( 'wc_quick_view_enqueue_scripts', array( $this, 'addon_scripts' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'quick_view_single_compat' ) );

		// Addon display
		add_action( 'woocommerce_before_add_to_cart_button', array( $this, 'display' ), 10 );
		add_action( 'grmpd-frames_end', array( $this, 'totals' ), 10 );

		// Change buttons/cart urls
		add_filter( 'add_to_cart_text', array( $this, 'add_to_cart_text'), 15 );
		add_filter( 'woocommerce_product_add_to_cart_text', array( $this, 'add_to_cart_text'), 15 );
		add_filter( 'woocommerce_add_to_cart_url', array( $this, 'add_to_cart_url' ), 10, 1 );
		add_filter( 'woocommerce_product_add_to_cart_url', array( $this, 'add_to_cart_url' ), 10, 1 );
		add_filter( 'woocommerce_product_supports', array( $this, 'ajax_add_to_cart_supports' ), 10, 3 );
		add_filter( 'woocommerce_is_purchasable', array( $this, 'prevent_purchase_at_grouped_level' ), 10, 2 );

	}

	/**
	 * styles function.
	 *
	 * @access public
	 * @return void
	 */
	public function styles() {
		if ( is_singular( 'product' ) || class_exists( 'WC_Quick_View' ) ) {
			wp_enqueue_style( 'woocommerce-addons-css', plugins_url( basename( dirname( dirname( __FILE__ ) ) ) ) . '/assets/css/frontend.css' );
		}
	}

	/**
	 * Get the plugin path
	 */
	public function plugin_path() {
		return $this->plugin_path = untrailingslashit( plugin_dir_path( dirname( __FILE__ ) ) );
	}

	/**
	 * Enqueue addon scripts
	 */
	public function addon_scripts() {
		$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

		wp_register_script( 'accounting', plugins_url( basename( dirname( dirname( __FILE__ ) ) ) ) . '/assets/js/accounting' . $suffix . '.js', '', '0.3.2' );

		wp_enqueue_script( 'woocommerce-addons', plugins_url( basename( dirname( dirname( __FILE__ ) ) ) ) . '/assets/js/addons' . $suffix . '.js', array( 'jquery', 'accounting' ), '1.0', true );

		$params = array(
			'price_display_suffix'         => esc_attr( get_option( 'woocommerce_price_display_suffix' ) ),
			'ajax_url'                     => WC()->ajax_url(),
			'i18n_addon_total'             => __( 'Options total:', 'grmpd-frames' ),
			'i18n_grand_total'             => __( 'Grand total:', 'grmpd-frames' ),
			'i18n_remaining'               => __( 'characters remaining', 'grmpd-frames' ),
			'currency_format_num_decimals' => absint( get_option( 'woocommerce_price_num_decimals' ) ),
			'currency_format_symbol'       => get_woocommerce_currency_symbol(),
			'currency_format_decimal_sep'  => esc_attr( stripslashes( get_option( 'woocommerce_price_decimal_sep' ) ) ),
			'currency_format_thousand_sep' => esc_attr( stripslashes( get_option( 'woocommerce_price_thousand_sep' ) ) ),
		);

		if ( ! function_exists( 'get_woocommerce_price_format' ) ) {
			$currency_pos = get_option( 'woocommerce_currency_pos' );

			switch ( $currency_pos ) {
				case 'left' :
					$format = '%1$s%2$s';
				break;
				case 'right' :
					$format = '%2$s%1$s';
				break;
				case 'left_space' :
					$format = '%1$s&nbsp;%2$s';
				break;
				case 'right_space' :
					$format = '%2$s&nbsp;%1$s';
				break;
			}

			$params['currency_format'] = esc_attr( str_replace( array( '%1$s', '%2$s' ), array( '%s', '%v' ), $format ) );
		} else {
			$params['currency_format'] = esc_attr( str_replace( array( '%1$s', '%2$s' ), array( '%s', '%v' ), get_woocommerce_price_format() ) );
		}

		wp_localize_script( 'woocommerce-addons', 'grmpd_frames_params', $params );
	}

	public function quick_view_single_compat() {
		if ( is_singular( 'product' ) && class_exists( 'WC_Quick_View' ) ) {
			$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
			wp_enqueue_script( 'woocommerce-addons-quickview-compat', plugins_url( basename( dirname( dirname( __FILE__ ) ) ) ) . '/assets/js/quickview' . $suffix . '.js', array( 'jquery' ), '1.0', true );
		}
	}

	/**
	 * display function.
	 *
	 * @access public
	 * @param bool $post_id (default: false)
	 * @return void
	 */
	public function display( $post_id = false, $prefix = false ) {
		global $product;

		if ( ! $post_id ) {
			global $post;
			$post_id = $post->ID;
		}

		$this->addon_scripts();

		$frames = get_frames( $post_id, $prefix );

		if ( is_array( $frames ) && sizeof( $frames ) > 0 ) {

			do_action( 'grmpd-frames_start', $post_id );

			foreach ( $frames as $addon ) {

				if ( ! isset( $addon['field-name'] ) )
					continue;

				woocommerce_get_template( 'addon-start.php', array(
						'addon'       => $addon,
						'required'    => $addon['required'],
						'name'        => $addon['name'],
						'description' => $addon['description'],
                        'formula' => $addon['formula'],
					), 'grmpd-frames', $this->plugin_path() . '/templates/' );

				echo $this->get_addon_html( $addon );

				woocommerce_get_template( 'addon-end.php', array(
						'addon'    => $addon,
					), 'grmpd-frames', $this->plugin_path() . '/templates/' );
			}

			do_action( 'grmpd-frames_end', $post_id );
		}
	}

	/**
	 * totals function.
	 *
	 * @access public
	 * @return void
	 */
	public function totals( $post_id ) {
		global $product;

		if ( ! isset( $product ) || $product->id != $post_id ) {
			$the_product = get_product( $post_id );
		} else {
			$the_product = $product;
		}

		if ( is_object( $the_product ) ) {
			$tax_display_mode = get_option( 'woocommerce_tax_display_shop' );
			$display_price    = $tax_display_mode == 'incl' ? $the_product->get_price_including_tax() : $the_product->get_price_excluding_tax();
		} else {
			$display_price    = '';
			$raw_price        = 0;
		}

		if ( get_option( 'woocommerce_prices_include_tax' ) === 'no' ) {
			$tax_mode = 'excl';
			$raw_price = $the_product->get_price_excluding_tax();
		} else {
			$tax_mode = 'incl';
			$raw_price = $the_product->get_price_including_tax();
		}

		echo '<div id="grmpd-frames-total" data-show-grand-total="' . ( apply_filters( 'woocommerce_frames_show_grand_total', true, $the_product ) ? 1 : 0 ) . '" data-type="' . esc_attr( $the_product->product_type ) . '" data-tax-mode="' . esc_attr( $tax_mode ) . '" data-tax-display-mode="' . esc_attr( $tax_display_mode ) . '" data-price="' . esc_attr( $display_price )  . '" data-raw-price="' . esc_attr( $raw_price ) . '" data-product-id="' . esc_attr( $post_id ) . '"></div>';
	}

	/**
	 * get_addon_html function.
	 *
	 * @access public
	 * @param mixed $addon
	 * @return void
	 */
	public function get_addon_html( $addon ) {
		ob_start();

		$method_name   = 'get_frontend_html';

		if ( method_exists( $this, $method_name ) ) {
			$this->$method_name( $addon );
		}

		do_action( 'grmpd-frames_get_frontend_html', $addon );

		return ob_get_clean();
	}

	/**
	 * get_checkbox_html function.
	 *
	 * @access public
	 * @return void
	 */
	public function get_frontend_html( $addon ) {
		woocommerce_get_template( 'frontend.php', array(
				'addon' => $addon,
			), 'grmpd-frames', $this->plugin_path() . '/templates/' );
	}

	/**
	 * check_required_addons function.
	 *
	 * @access private
	 * @param mixed $product_id
	 * @return void
	 */
	private function check_required_addons( $product_id ) {
		$addons = get_frames( $product_id, false, false, true ); // No parent addons, but yes to global

		if ( $addons && ! empty( $addons ) ) {
			foreach ( $addons as $addon ) {
				if ( '1' == $addon['required'] ) {
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * add_to_cart_text function.
	 *
	 * @access public
	 * @param mixed $text
	 * @return void
	 */
	public function add_to_cart_text( $text ) {
		global $product;

		if ( ! is_single( $product->id ) ) {
			if ( $this->check_required_addons( $product->id ) ) {
				if ( version_compare( WOOCOMMERCE_VERSION, '2.5.0', '<' ) ) {
					$product->product_type = 'addons';
				}
				$text = apply_filters( 'addons_add_to_cart_text', __( 'Select options', 'grmpd-frames' ) );
			}
		}

		return $text;
	}

	/**
	 * Removes ajax-add-to-cart functionality in WC 2.5 when a product has required add-ons.
	 *
	 * @access public
	 * @param  boolean $supports
	 * @param  string  $feature
	 * @param  object  $product
	 * @return boolean
	 */
	public function ajax_add_to_cart_supports( $supports, $feature, $product ) {

		if ( 'ajax_add_to_cart' === $feature && $this->check_required_addons( $product->id ) ) {
			$supports = false;
		}

		return $supports;
	}

	/**
	 * add_to_cart_url function.
	 *
	 * @access public
	 * @param mixed $url
	 * @return void
	 */
	public function add_to_cart_url( $url ) {
		global $product;

		if ( ! is_single( $product->id ) && in_array( $product->product_type, apply_filters( 'woocommerce_frames_add_to_cart_product_types', array( 'subscription', 'simple' ) ) ) && ( ! isset( $_GET['wc-api'] ) || $_GET['wc-api'] !== 'WC_Quick_View' ) ) {
			if ( $this->check_required_addons( $product->id ) ) {
				if ( version_compare( WOOCOMMERCE_VERSION, '2.5.0', '<' ) ) {
					$product->product_type = 'addons';
				}
				$url = apply_filters( 'addons_add_to_cart_url', get_permalink( $product->id ) );
			}
		}

		return $url;
	}

	/**
	 * Don't let products with required addons be added to cart when viewing grouped products.
	 * @param  bool $purchasable
	 * @param  object $product
	 * @return bool
	 */
	public function prevent_purchase_at_grouped_level( $purchasable, $product ) {
		if ( $product && $product->get_parent() && is_single( $product->get_parent() ) && $this->check_required_addons( $product->id ) ) {
			$purchasable = false;
		}
		return $purchasable;
	}

}

$GLOBALS['Grmpd_Frame_Display'] = new Grmpd_Frame_Display();
