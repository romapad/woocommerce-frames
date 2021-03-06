<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Grmpd_Frame_Admin class.
 */
class Grmpd_Frame_Admin {

	/**
	 * __construct function.
	 *
	 * @return void
	 */
	public function __construct() {
		add_action( 'admin_enqueue_scripts', array( $this, 'styles' ), 100 );
		add_action( 'admin_menu', array( $this, 'admin_menu' ), 9 );
		add_filter( 'woocommerce_screen_ids', array( $this, 'add_screen_id' ) );
		add_action( 'woocommerce_product_write_panel_tabs', array( $this, 'tab' ) );
		add_action( 'woocommerce_product_write_panels', array( $this, 'panel' ) );
		add_action( 'woocommerce_process_product_meta', array( $this, 'process_meta_box' ), 1 );
	}

	/**
	 * Add menus
	 */
	public function admin_menu() {
		$page = add_submenu_page( 'edit.php?post_type=product', __( 'Frames', 'grmpd-frames' ), __( 'Frames', 'grmpd-frames' ), 'manage_woocommerce', 'grmpd_frames', array( $this, 'grmpd_frames_admin' ) );

		add_action( 'admin_print_styles-'. $page, array( &$this, 'admin_enqueue' ) );
	}

	/**
	 * admin_enqueue function.
	 *
	 * @return void
	 */
	public function admin_enqueue() {
		if ( version_compare( WC_VERSION, '2.3.0', '<' ) ) {
			wp_enqueue_script( 'chosen' );
		}
	}

	/**
	 * styles function.
	 *
	 * @return void
	 */
	public function styles() {
		wp_enqueue_style( 'grmpd_frames_css', plugins_url( basename( dirname( dirname( __FILE__ ) ) ) ) . '/assets/css/admin.css' );
	}

	/**
	 * Add screen id to WooCommerce
	 *
	 * @param array $screen_ids
	 */
	public function add_screen_id( $screen_ids ) {
		$screen_ids[] = 'product_page_grmpd_frames';

		return $screen_ids;
	}

	/**
	 * Controls the global addons admin page
	 * @return void
	 */
	public function grmpd_frames_admin() {
		if ( ! empty( $_GET['add'] ) || ! empty( $_GET['edit'] ) ) {

			if ( $_POST ) {

				if ( $edit_id = $this->save_grmpd_frames() ) {
					echo '<div class="updated"><p>' . __( 'Frames saved successfully', 'grmpd-frames' ) . '</p></div>';
				}

				$reference      = woocommerce_clean( $_POST['addon-reference'] );
				$priority       = absint( $_POST['addon-priority'] );
				$objects        = ! empty( $_POST['addon-objects'] ) ? array_map( 'absint', $_POST['addon-objects'] ) : array();
				$frames = array_filter( (array) $this->get_posted_frames() );
			}

			if ( ! empty( $_GET['edit'] ) ) {

				$edit_id      = absint( $_GET['edit'] );
				$global_addon = get_post( $edit_id );

				if ( ! $global_addon ) {
					echo '<div class="error">' . __( 'Error: Global Frame not found', 'grmpd-frames' ) . '</div>';
					return;
				}

				$reference      = $global_addon->post_title;
				$priority       = get_post_meta( $global_addon->ID, '_priority', true );
				$objects        = (array) wp_get_post_terms( $global_addon->ID, apply_filters( 'grmpd_frames_global_post_terms', array( 'product_cat' ) ), array( 'fields' => 'ids' ) );
				$frames = array_filter( (array) get_post_meta( $global_addon->ID, '_frames', true ) );

				if ( get_post_meta( $global_addon->ID, '_all_products', true ) == 1 ) {
					$objects[] = 0;
				}

			} elseif ( ! empty( $edit_id ) ) {

				$global_addon   = get_post( $edit_id );
				$reference      = $global_addon->post_title;
				$priority       = get_post_meta( $global_addon->ID, '_priority', true );
				$objects        = (array) wp_get_post_terms( $global_addon->ID, apply_filters( 'grmpd_frames_global_post_terms', array( 'product_cat' ) ), array( 'fields' => 'ids' ) );
				$frames = array_filter( (array) get_post_meta( $global_addon->ID, '_frames', true ) );

				if ( get_post_meta( $global_addon->ID, '_all_products', true ) == 1 ) {
					$objects[] = 0;
				}

			} else {

				$grmpd_frames_count = wp_count_posts( 'global_frames' );
				$reference           = __( 'Frames Group' ) . ' #' . ( $grmpd_frames_count->publish + 1 );
				$priority            = 10;
				$objects             = array( 0 );
				$frames      = array();

			}

			include( 'html-global-admin-add.php' );
		} else {

			if ( ! empty( $_GET['delete'] ) && wp_verify_nonce( $_REQUEST['_wpnonce'], 'delete_addon' ) ) {
				wp_delete_post( absint( $_GET['delete'] ), true );
				echo '<div class="updated"><p>' . __( 'Frame deleted successfully', 'grmpd-frames' ) . '</p></div>';
			}

			include( 'html-global-admin.php' );
		}
	}

	/**
	 * tab function.
	 *
	 * @return void
	 */
	public function tab() {
		?><li class="addons_tab frames"><a href="#frames_data"><?php _e( 'Frames', 'grmpd-frames' ); ?></a></li><?php
	}

	/**
	 * panel function.
	 *
	 * @return void
	 */
	public function panel() {
		global $post;

		$frames = array_filter( (array) get_post_meta( $post->ID, '_frames', true ) );

		include( 'html-addon-panel.php' );
	}

	/**
	 * Save global addons
	 *
	 * @return bool success or failure
	 */
	public function save_grmpd_frames() {
		$edit_id        = ! empty( $_POST['edit_id'] ) ? absint( $_POST['edit_id'] ) : '';
		$reference      = woocommerce_clean( $_POST['addon-reference'] );
		$priority       = absint( $_POST['addon-priority'] );
		$objects        = ! empty( $_POST['addon-objects'] ) ? array_map( 'absint', $_POST['addon-objects'] ) : array();
		$frames = $this->get_posted_frames();

		if ( ! $reference ) {
			$grmpd_frames_count = wp_count_posts( 'global_frames' );
			$reference           = __( 'Frames Group' ) . ' #' . ( $grmpd_frames_count->publish + 1 );
		}

		if ( ! $priority && $priority !== 0 ) {
			$priority = 10;
		}

		if ( $edit_id ) {

			$edit_post               = array();
			$edit_post['ID']         = $edit_id;
			$edit_post['post_title'] = $reference;

			wp_update_post( $edit_post );
			wp_set_post_terms( $edit_id, $objects, 'product_cat', false );
			do_action( 'woocommerce_frames_global_edit_addons', $edit_post, $objects );

		} else {

			$edit_id = wp_insert_post( apply_filters( 'woocommerce_frames_global_insert_post_args', array(
				'post_title'    => $reference,
				'post_status'   => 'publish',
				'post_type'		=> 'global_frames',
				'tax_input'     => array(
					'product_cat' => $objects
				)
			), $reference, $objects ) );

		}

		if ( in_array( 0, $objects ) ) {
			update_post_meta( $edit_id, '_all_products', 1 );
		} else {
			update_post_meta( $edit_id, '_all_products', 0 );
		}

		update_post_meta( $edit_id, '_priority', $priority );
		update_post_meta( $edit_id, '_frames', $frames );

		return $edit_id;
	}

	/**
	 * Process meta box
	 *
	 * @param int $post_id
	 */
	public function process_meta_box( $post_id ) {
		// Save addons as serialised array
		$frames                = $this->get_posted_frames();
		$frames_exclude_global = isset( $_POST['_frames_exclude_global'] ) ? 1 : 0;

		update_post_meta( $post_id, '_frames', $frames );
		update_post_meta( $post_id, '_frames_exclude_global', $frames_exclude_global );
	}


	/**
	 * Generate a filterable default new addon option
	 *
	 * @return array
	 */
	public static function get_new_addon_option() {
		$new_addon_option = array(
			'label'   => '',
			'price'   => '',
			'width'   => '',
			'preview' => '',
            'part'    => ''
		);

		return apply_filters( 'woocommerce_frames_new_addon_option', $new_addon_option );
	}

	/**
	 * Put posted addon data into an array
	 *
	 * @return array
	 */
	private function get_posted_frames() {
		$frames = array();

		if ( isset( $_POST[ 'grmpd_frame_name' ] ) ) {
			 $addon_name         = $_POST['grmpd_frame_name'];
			 $addon_description  = $_POST['grmpd_frame_description'];
			 $addon_formula  = $_POST['grmpd_frame_formula'];
			 $addon_position     = $_POST['grmpd_frame_position'];
			 $addon_required     = isset( $_POST['grmpd_frame_required'] ) ? $_POST['grmpd_frame_required'] : array();

			 $addon_option_label = $_POST['grmpd_frame_option_label'];
			 $addon_option_price = $_POST['grmpd_frame_option_price'];

			 $addon_option_width   = $_POST['grmpd_frame_option_width'];
			 $addon_option_preview   = $_POST['grmpd_frame_option_preview'];
			 $addon_option_part   = $_POST['grmpd_frame_option_part'];            

			 for ( $i = 0; $i < sizeof( $addon_name ); $i++ ) {

				if ( ! isset( $addon_name[ $i ] ) || ( '' == $addon_name[ $i ] ) ) {
					continue;
				}

				$addon_options 	= array();
				$option_label  	= $addon_option_label[ $i ];
				$option_price  	= $addon_option_price[ $i ];
				$option_width	= $addon_option_width[ $i ];
				$option_preview	= $addon_option_preview[ $i ];
                $option_part	= $addon_option_part[ $i ]; 

				for ( $ii = 0; $ii < sizeof( $option_label ); $ii++ ) {
					$label 	= sanitize_text_field( stripslashes( $option_label[ $ii ] ) );
					$price 	= wc_format_decimal( sanitize_text_field( stripslashes( $option_price[ $ii ] ) ) );
					$width	= sanitize_text_field( stripslashes( $option_width[ $ii ] ) );
					$preview	= sanitize_text_field( stripslashes( $option_preview[ $ii ] ) );
                    $part	= sanitize_text_field( stripslashes( $option_part[ $ii ] ) );

					$addon_options[] = array(
						'label'     => $label,
						'price'     => $price,
						'width'	    => $width,
						'preview'	=> $preview,
                        'part'	=> $part
					);
				}

				if ( sizeof( $addon_options ) == 0 ) {
					continue; // Needs options
				}

				$data                = array();
				$data['name']        = sanitize_text_field( stripslashes( $addon_name[ $i ] ) );
				$data['description'] = wp_kses_post( stripslashes( $addon_description[ $i ] ) );
                 $data['formula'] = wp_kses_post( stripslashes( $addon_formula[ $i ] ) );
				$data['position']    = absint( $addon_position[ $i ] );
				$data['options']     = $addon_options;
				$data['required']    = isset( $addon_required[ $i ] ) ? 1 : 0;

				// Add to array
				$frames[] = apply_filters( 'woocommerce_frames_save_data', $data, $i );
			}
		}

		uasort( $frames, array( $this, 'addons_cmp' ) );

		return $frames;
	}

	/**
	 * Sort addons
	 *
	 * @param  array $a
	 * @param  array $b
	 * @return bool
	 */
	private function addons_cmp( $a, $b ) {
		if ( $a['position'] == $b['position'] ) {
			return 0;
		}

		return ( $a['position'] < $b['position'] ) ? -1 : 1;
	}
}

$GLOBALS['Grmpd_Frame_Admin'] = new Grmpd_Frame_Admin();
