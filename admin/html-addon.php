<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

global $post;
?>
<div class="woocommerce_product_addon wc-metabox closed">
	<h3>
		<button type="button" class="remove_addon button"><?php _e( 'Remove', 'grmpd-frames' ); ?></button>
		<div class="handlediv" title="<?php _e( 'Click to toggle', 'grmpd-frames' ); ?>"></div>
		<strong><?php _e( 'Group', 'grmpd-frames' ); ?> <span class="group_name"><?php if ( $addon['name'] ) echo '"' . esc_attr( $addon['name'] ) . '"'; ?></span> &mdash; </strong>
		<input type="hidden" name="product_addon_position[<?php echo $loop; ?>]" class="product_addon_position" value="<?php echo $loop; ?>" />
	</h3>
	<table cellpadding="0" cellspacing="0" class="wc-metabox-content">
		<tbody>
			<tr>
				<td class="addon_name" width="50%">
					<label for="addon_name_<?php echo $loop; ?>"><?php _e( 'Group Name', 'grmpd-frames' ); ?></label>
					<input type="text" id="addon_name_<?php echo $loop; ?>" name="product_addon_name[<?php echo $loop; ?>]" value="<?php echo esc_attr( $addon['name'] ) ?>" />
				</td>
				<td class="addon_required" width="50%">
					<label for="addon_required_<?php echo $loop; ?>"><?php _e( 'Required fields?', 'grmpd-frames' ); ?></label>
					<input type="checkbox" id="addon_required_<?php echo $loop; ?>" name="product_addon_required[<?php echo $loop; ?>]" <?php checked( $addon['required'], 1 ) ?> />
				</td>
			</tr>
			<tr>
				<td class="addon_description" colspan="2">
					<label for="addon_description_<?php echo $loop; ?>"><?php _e( 'Group Description', 'grmpd-frames' ); ?></label>
					<textarea cols="20" id="addon_description_<?php echo $loop; ?>" rows="3" name="product_addon_description[<?php echo $loop; ?>]"><?php echo esc_textarea( $addon['description'] ) ?></textarea>
				</td>
			</tr>
			<?php do_action( 'woocommerce_product_addons_panel_before_options', $post, $addon, $loop ); ?>
			<tr>
				<td class="data" colspan="3">
					<table cellspacing="0" cellpadding="0">
						<thead>
							<tr>
								<th><?php _e('Frame Label', 'grmpd-frames'); ?></th>
								<th class="price_column"><?php _e('Frame Price/metr', 'grmpd-frames'); ?></th>
								<th><?php _e('Width', 'grmpd-frames'); ?></th>
								<th><?php _e('Preview', 'grmpd-frames'); ?></th>
								<th><?php _e('Frame part', 'grmpd-frames'); ?></th>
								<th width="1%"></th>
							</tr>
						</thead>
						<tfoot>
							<tr>
								<td colspan="6"><button type="button" class="add_addon_option button"><?php _e('New&nbsp;Option', 'grmpd-frames'); ?></button></td>
							</tr>
						</tfoot>
						<tbody>
							<?php
							foreach ( $addon['options'] as $option )
								include( 'html-addon-option.php' );
							?>
						</tbody>
					</table>
				</td>
			</tr>
		</tbody>
	</table>
</div>
