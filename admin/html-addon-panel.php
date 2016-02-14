<div id="frames_data" class="panel woocommerce_options_panel wc-metaboxes-wrapper">
	<?php do_action( 'grmpd-frames_panel_start' ); ?>

	<p class="toolbar">
		<a href="#" class="close_all"><?php _e( 'Close all', 'grmpd-frames' ); ?></a> / <a href="#" class="expand_all"><?php _e( 'Expand all', 'grmpd-frames' ); ?></a>
	</p>

	<div class="woocommerce_frames wc-metaboxes">

		<?php
			$loop = 0;

			foreach ( $frames as $addon ) {
				include( 'html-addon.php' );

				$loop++;
			}
		?>

	</div>

	<div class="toolbar">
		<button type="button" class="button add_new_addon button-primary"><?php _e( 'New Frame Group', 'grmpd-frames' ); ?></button>
	</div>
	<?php if( isset($post->ID) ):?>
    <div class="options_group">
		<p class="form-field">
        <label for="_frames_exclude_global"><?php _e( 'Frames Exclusion', 'grmpd-frames' ); ?></label>
        <input id="_frames_exclude_global" name="_frames_exclude_global" class="checkbox" type="checkbox" value="1" <?php checked( get_post_meta( $post->ID, '_frames_exclude_global', TRUE ), 1 ); ?>/><span class="description"><?php _e( 'Check this to exclude this product from all FRames', 'grmpd-frames' ); ?></span>
		</p>
	</div>
	<?php endif; ?>
</div>
<script type="text/javascript">
	jQuery(function(){

		<?php if ( version_compare( WC_VERSION, '2.3.0', '<' ) ) { ?>
			jQuery( 'select.chosen_select' ).chosen();
		<?php } ?>

		jQuery('#frames_data')
		.on( 'change', '.addon_name input', function() {
			if ( jQuery(this).val() )
				jQuery(this).closest('.grmpd_frame').find('span.group_name').text( '"' + jQuery(this).val() + '"' );
			else
				jQuery(this).closest('.grmpd_frame').find('span.group_name').text('');

			// Count the number of options.  If one (or less), disable the remove option buttons
			var removeAddOnOptionButtons = jQuery(this).closest('.grmpd_frame').find('button.remove_addon_option');
			if ( 2 > removeAddOnOptionButtons.length ) {
				removeAddOnOptionButtons.attr('disabled', 'disabled');
			} else {
				removeAddOnOptionButtons.removeAttr('disabled');
			}
		})
		.on( 'click', 'button.add_addon_option', function() {

			var loop = jQuery(this).closest('.grmpd_frame').index('.grmpd_frame');

			var html = '<?php
				ob_start();

				$option = Grmpd_Frame_Admin::get_new_addon_option();
				$loop = "{loop}";

				include( 'html-addon-option.php' );

				$html = ob_get_clean();
				echo str_replace( array( "\n", "\r" ), '', str_replace( "'", '"', $html ) );
			?>';

			html = html.replace( /{loop}/g, loop );

			jQuery(this).closest('.grmpd_frame .data').find('tbody').append( html );

			return false;
		})
		.on( 'click', '.add_new_addon', function() {

			var loop = jQuery('.woocommerce_frames .grmpd_frame').size();

			var html = '<?php
				ob_start();

				$addon['name'] 			= '';
				$addon['description']	= '';
				$addon['required'] 		= '';
				$addon['options'] 		= array(
					Grmpd_Frame_Admin::get_new_addon_option()
				);
				$loop = "{loop}";

				include( 'html-addon.php' );

				$html = ob_get_clean();
				echo str_replace( array( "\n", "\r" ), '', str_replace( "'", '"', $html ) );
			?>';

			html = html.replace( /{loop}/g, loop );

			jQuery('.woocommerce_frames').append( html );

			return false;
		})
		.on( 'click', '.remove_addon', function() {

			var answer = confirm('<?php _e('Are you sure you want remove this add-on?', 'grmpd-frames'); ?>');

			if (answer) {
				var addon = jQuery(this).closest('.grmpd_frame');
				jQuery(addon).find('input').val('');
				jQuery(addon).hide();
			}

			return false;
		})

		// Sortable
		jQuery('.woocommerce_frames').sortable({
			items:'.grmpd_frame',
			cursor:'move',
			axis:'y',
			handle:'h3',
			scrollSensitivity:40,
			helper:function(e,ui){
				return ui;
			},
			start:function(event,ui){
				ui.item.css('border-style','dashed');
			},
			stop:function(event,ui){
				ui.item.removeAttr('style');
				addon_row_indexes();
			}
		});

		function addon_row_indexes() {
			jQuery('.woocommerce_frames .grmpd_frame').each(function(index, el){ jQuery('.grmpd_frame_position', el).val( parseInt( jQuery(el).index('.woocommerce_frames .grmpd_frame') ) ); });
		};

		// Sortable options
		jQuery('.grmpd_frame .data table tbody').sortable({
			items:'tr',
			cursor:'move',
			axis:'y',
			scrollSensitivity:40,
			helper:function(e,ui){
				ui.children().each(function(){
					jQuery(this).width(jQuery(this).width());
				});
				return ui;
			},
			start:function(event,ui){
				ui.item.css('background-color','#f6f6f6');
			},
			stop:function(event,ui){
				ui.item.removeAttr('style');
			}
		});

		// Remove option
		jQuery('button.remove_addon_option').live('click', function(){

			var answer = confirm('<?php _e('Are you sure you want delete this option?', 'grmpd-frames'); ?>');

			if (answer) {
				var addOn = jQuery(this).closest('.grmpd_frame');
				jQuery(this).closest('tr').remove();
			}

			return false;

		});

	});
</script>
