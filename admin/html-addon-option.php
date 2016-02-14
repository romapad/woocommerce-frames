<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
?>
<tr>
	<td><input type="text" name="product_addon_option_label[<?php echo $loop; ?>][]" value="<?php echo esc_attr( $option['label' ] ); ?>" placeholder="<?php esc_html_e( 'Default Frame Label', 'grmpd-frames'); ?>" /></td>
	<td class="price_column"><input type="text" name="product_addon_option_price[<?php echo $loop; ?>][]" value="<?php echo esc_attr( wc_format_localized_price( $option['price'] ) ); ?>" placeholder="0.00" class="wc_input_price" /></td>
	<td><input type="number" name="product_addon_option_width[<?php echo $loop; ?>][]" value="<?php echo esc_attr( $option['width'] ) ?>" placeholder="0.6" min="0" step="any" /></td>
	<td><input type="text" name="product_addon_option_preview[<?php echo $loop; ?>][]" value="<?php echo esc_attr( $option['preview'] ) ?>" placeholder="url" min="0" step="any" /></td>
	<td><input type="text" name="product_addon_option_part[<?php echo $loop; ?>][]" value="<?php echo esc_attr( $option['part'] ) ?>" placeholder="url" min="0" step="any" /></td>
	<td class="actions" width="1%"><button type="button" class="remove_addon_option button">x</button></td>
</tr>