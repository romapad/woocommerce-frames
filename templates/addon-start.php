<div class="<?php if ( 1 == $required ) echo 'required-grmpd-frame'; ?> grmpd-frame grmpd-frame-<?php echo sanitize_title( $name ); ?>">

	<?php do_action( 'wc_grmpd_frame_start', $addon ); ?>

	<?php if ( $name ) : ?>
		<h3 class="addon-name"><?php echo wptexturize( $name ); ?> <?php if ( 1 == $required ) echo '<abbr class="required" title="' . __( 'Required field', 'grmpd-frames' ) . '">*</abbr>'; ?></h3>
	<?php endif; ?>

	<?php if ( $description ) : ?>
		<?php echo '<div class="addon-description">' . wpautop( wptexturize( $description ) ) . '</div>'; ?>
	<?php endif; ?>

	<?php if ( $formula ) : ?>
		<?php echo '<div class="addon-formula">' . wpautop( wptexturize( $formula ) ) . '</div>'; ?>
	<?php endif; ?>

	<?php do_action( 'wc_grmpd_frame_options', $addon ); ?>
