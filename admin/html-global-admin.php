<div class="wrap woocommerce">
	<div class="icon32 icon32-posts-product" id="icon-woocommerce"><br/></div>

    <h2><?php _e( 'Frames', 'grmpd-frames' ) ?> <a href="<?php echo add_query_arg( 'add', true, admin_url( 'edit.php?post_type=product&page=grmpd_frames' ) ); ?>" class="add-new-h2"><?php _e( 'Add Frames', 'grmpd-frames' ); ?></a></h2><br/>

	<table id="global-addons-table" class="wp-list-table widefat" cellspacing="0">
		<thead>
			<tr>
				<th scope="col"><?php _e( 'Reference', 'grmpd-frames' ); ?></th>
				<th><?php _e( 'Number of Fields', 'grmpd-frames' ); ?></th>
				<th><?php _e( 'Priority', 'grmpd-frames' ); ?></th>
				<th><?php _e( 'Applies to...', 'grmpd-frames' ); ?></th>
				<th><?php _e( 'Actions', 'grmpd-frames' ); ?></th>
			</tr>
		</thead>
		<tbody id="the-list">
			<?php
				$args = array(
					'posts_per_page'  => -1,
					'orderby'         => 'title',
					'order'           => 'ASC',
					'post_type'       => 'global_frames',
					'post_status'     => 'any',
					'suppress_filters' => true
				);

				$grmpd_frames = get_posts( $args );

				if ( $grmpd_frames ) {
					foreach ( $grmpd_frames as $global_addon ) {
						$reference      = $global_addon->post_title;
						$priority       = get_post_meta( $global_addon->ID, '_priority', true );
						$objects        = (array) wp_get_post_terms( $global_addon->ID, apply_filters( 'grmpd_frames_global_post_terms', array( 'product_cat' ) ), array( 'fields' => 'ids' ) );
						$frames = array_filter( (array) get_post_meta( $global_addon->ID, '_frames', true ) );
						if ( get_post_meta( $global_addon->ID, '_all_products', true ) == 1 ) {
							$objects[] = 0;
						}
						?>
						<tr>
							<td><?php echo $reference; ?></td>
							<td><?php echo sizeof( $frames ); ?></td>
							<td><?php echo $priority; ?></td>
							<td><?php

								if ( in_array( 0, $objects ) ) {
									_e( 'All Products', 'grmpd-frames' );
								} else {
									$term_names = array();
									foreach ( $objects as $object_id ) {
										$term = get_term_by( 'id', $object_id, 'product_cat' );
										if ( $term ) {
											$term_names[] = $term->name;
										}
									}

									$term_names = apply_filters( 'woocommerce_frames_global_display_term_names', $term_names, $objects );

									echo implode( ', ', $term_names );
								}

							?></td>
							<td>
								<a href="<?php echo add_query_arg( 'edit', $global_addon->ID, admin_url( 'edit.php?post_type=product&page=grmpd_frames' ) ); ?>" class="button"><?php _e( 'Edit', 'grmpd-frames' ); ?></a> <a href="<?php echo wp_nonce_url( add_query_arg( 'delete', $global_addon->ID, admin_url( 'edit.php?post_type=product&page=grmpd_frames' ) ), 'delete_addon' ); ?>" class="button"><?php _e( 'Delete', 'grmpd-frames' ); ?></a>
							</td>
						</tr>
						<?php
					}
				} else {
					?>
					<tr>
						<td colspan="5"><?php _e( 'No frames exists yet.', 'grmpd-frames' ); ?> <a href="<?php echo add_query_arg( 'add', true, admin_url( 'edit.php?post_type=product&page=grmpd_frames' ) ); ?>"><?php _e( 'Add one?', 'grmpd-frames' ); ?></a></td>
					</tr>
					<?php
				}
			?>
		</tbody>
	</table>
</div>
