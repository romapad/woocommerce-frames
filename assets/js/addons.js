jQuery( document ).ready( function($) {

	$.fn.init_addon_totals = function() {

		$(this).on( 'keyup change', '.grmpd-frame input, .grmpd-frame textarea', function() {

			if ( $(this).attr('maxlength') > 0 ) {

				var value = $(this).val();
				var remaining = $(this).attr('maxlength') - value.length;

				$(this).next('.chars_remaining').find('span').text( remaining );
			}

		} );

		$(this).find(' .addon-custom, .addon-custom-textarea' ).each( function() {

			if ( $(this).attr('maxlength') > 0 ) {

				$(this).after('<small class="chars_remaining"><span>' + $(this).attr('maxlength') + '</span> ' + grmpd_frames_params.i18n_remaining + '</small>' );

			}

		} );

		$(this).on( 'change', '.grmpd-frame input, .grmpd-frame textarea, .grmpd-frame select, input.qty', function() {

			$(this).trigger( 'grmpd-frames-update' );
		} );

		$(this).on( 'found_variation', function( event, variation ) {

			var $variation_form = $(this);
			var $totals         = $variation_form.find( '#grmpd-frames-total' );

			if ( typeof( variation.display_price ) !== 'undefined' ) {

				$totals.data( 'price', variation.display_price );

			} else if ( $( variation.price_html ).find('.amount:last').size() ) {

				product_price = $( variation.price_html ).find('.amount:last').text();
				product_price = product_price.replace( grmpd_frames_params.currency_format_symbol, '' );
				product_price = product_price.replace( grmpd_frames_params.currency_format_thousand_sep, '' );
				product_price = product_price.replace( grmpd_frames_params.currency_format_decimal_sep, '.' );
				product_price = product_price.replace(/[^0-9\.]/g, '');
				product_price = parseFloat( product_price );

				$totals.data( 'price', product_price );
			}

			$variation_form.trigger( 'grmpd-frames-update' );
		} );

		$(this).on( 'grmpd-frames-update', function() {

			var total         = 0;
			var $cart         = $(this);
			var $totals       = $cart.find( '#grmpd-frames-total' );
			var product_price = $totals.data( 'price' );
			var product_type  = $totals.data( 'type' );
			var product_id    = $totals.data( 'product-id' );

			// We will need some data about tax modes (both store and display)
			// and 'raw prices' (prices that don't take into account taxes) so we can use them in some
			// instances without making an ajax call to calculate taxes
			var product_raw      = $totals.data( 'raw-price' );
			var tax_mode         = $totals.data( 'tax-mode' );
			var tax_display_mode = $totals.data('tax-display-mode' );
			var total_raw         = 0;

			// Move totals
			if ( product_type == 'variable' || product_type == 'variable-subscription' ) {
				$cart.find('.single_variation').after( $totals );
			}

			$cart.find( '.addon' ).each( function() {
				var addon_cost = 0;
				var addon_cost_raw = 0;

				if ( $(this).is('.addon-custom-price') ) {
					addon_cost = $(this).val();
				} else if ( $(this).is('.addon-input_multiplier') ) {
					if( isNaN( $(this).val() ) || $(this).val() == "" ) { // Number inputs return blank when invalid
						$(this).val('');
						$(this).closest('p').find('.addon-alert').show();
					} else {
						if( $(this).val() != "" ){
							$(this).val( Math.ceil( $(this).val() ) );
						}
						$(this).closest('p').find('.addon-alert').hide();
					}
					addon_cost = $(this).data('price') * $(this).val();
					addon_cost_raw = $(this).data('raw-price') * $(this).val();
				} else if ( $(this).is('.addon-checkbox, .addon-radio') ) {
					if ( $(this).is(':checked') ) {
						addon_cost = $(this).data('price');
						addon_cost_raw = $(this).data('raw-price');
					}
				} else if ( $(this).is('.addon-select') ) {
					if ( $(this).val() ) {
						addon_cost = $(this).find('option:selected').data('price');
						addon_cost_raw = $(this).find('option:selected').data('raw-price');
					}
				} else {
					if ( $(this).val() ) {
						addon_cost = $(this).data('price');
						addon_cost_raw = $(this).data('raw-price');
					}
				}

				if ( ! addon_cost ) {
					addon_cost = 0;
				}
				if ( ! addon_cost_raw ) {
					addon_cost_raw = 0;
				}

				total = parseFloat( total ) + parseFloat( addon_cost );
				total_raw = parseFloat( total_raw ) + parseFloat( addon_cost_raw );
			} );

			if ( $cart.find('input.qty').size() ) {
				var qty = parseFloat( $cart.find('input.qty').val() );
			} else {
				var qty = 1;
			}

			if ( total > 0 && qty > 0 ) {

				total = parseFloat( total * qty );

				var formatted_addon_total = accounting.formatMoney( total, {
					symbol 		: grmpd_frames_params.currency_format_symbol,
					decimal 	: grmpd_frames_params.currency_format_decimal_sep,
					thousand	: grmpd_frames_params.currency_format_thousand_sep,
					precision 	: grmpd_frames_params.currency_format_num_decimals,
					format		: grmpd_frames_params.currency_format
				} );

				if ( product_price ) {

					product_total_price = parseFloat( product_price * qty );

					var formatted_grand_total = accounting.formatMoney( product_total_price + total, {
						symbol 		: grmpd_frames_params.currency_format_symbol,
						decimal 	: grmpd_frames_params.currency_format_decimal_sep,
						thousand	: grmpd_frames_params.currency_format_thousand_sep,
						precision 	: grmpd_frames_params.currency_format_num_decimals,
						format		: grmpd_frames_params.currency_format
					} );

				}

				var formatted_raw_total = accounting.formatMoney( total_raw + product_raw, {
					symbol 		: grmpd_frames_params.currency_format_symbol,
					decimal 	: grmpd_frames_params.currency_format_decimal_sep,
					thousand	: grmpd_frames_params.currency_format_thousand_sep,
					precision 	: grmpd_frames_params.currency_format_num_decimals,
					format		: grmpd_frames_params.currency_format
				} );

				var subscription_details = false;

				if ( $('.single_variation_wrap .subscription-details').length ) {
					subscription_details = $('.single_variation_wrap .subscription-details').clone().wrap('<p>').parent().html();
				} else if ( $('.product-type-subscription .subscription-details').length ) {
					subscription_details = $('.product-type-subscription .subscription-details').clone().wrap('<p>').parent().html();
				}

				if ( subscription_details ) {
					formatted_addon_total += subscription_details;
					if ( formatted_grand_total ) {
						formatted_grand_total += subscription_details;
					}
				}

				var html = '<dl class="grmpd-frame-totals"><dt>' + grmpd_frames_params.i18n_addon_total + '</dt><dd><strong><span class="amount">' + formatted_addon_total + '</span></strong></dd>';

				if ( formatted_grand_total && '1' == $totals.data( 'show-grand-total' ) ) {

					// To show our "price display suffix" we have to do some magic since the string can contain variables (excl/incl tax values)
					// so we have to take our grand total and find out what the tax value is, which we can do via an ajax call
					// if its a simple string, or no string at all, we can output the string without an extra call
					var price_display_suffix = '';

					// no sufix is present, so we can just output the total
					if ( ! grmpd_frames_params.price_display_suffix ) {
						html = html + '<dt>' + grmpd_frames_params.i18n_grand_total + '</dt><dd><strong><span class="amount">' + formatted_grand_total + '</span></strong></dd></dl>';
						$totals.html( html );
						$( 'body' ).trigger( 'updated_addons' );
						return;
					}

					// a suffix is present, but no special labels are used - meaning we don't need to figure out any other special values - just display the playintext value
					if ( false === ( grmpd_frames_params.price_display_suffix.indexOf( '{price_including_tax}' ) > -1 ) && false === ( grmpd_frames_params.price_display_suffix.indexOf( '{price_excluding_tax}' ) > -1 ) ) {
						html = html + '<dt>' + grmpd_frames_params.i18n_grand_total + '</dt><dd><strong><span class="amount">' + formatted_grand_total + '</span> ' + grmpd_frames_params.price_display_suffix + '</strong></dd></dl>';
						$totals.html( html );
						$( 'body' ).trigger( 'updated_addons' );
						return;
					}

					// If prices are entered exclusive of tax but display inclusive, we have enough data from our totals above
					// to do a simple replacement and output the totals string
					if (  'excl' === tax_mode && 'incl' === tax_display_mode ) {
						price_display_suffix = '<small class="woocommerce-price-suffix">' + grmpd_frames_params.price_display_suffix + '</small>';
						price_display_suffix = price_display_suffix.replace( '{price_including_tax}', formatted_grand_total );
						price_display_suffix = price_display_suffix.replace( '{price_excluding_tax}', formatted_raw_total );
						html = html + '<dt>' + grmpd_frames_params.i18n_grand_total + '</dt><dd><strong><span class="amount">' + formatted_grand_total + '</span> ' + price_display_suffix + ' </strong></dd></dl>';
						$totals.html( html );
						$( 'body' ).trigger( 'updated_addons' );
						return;
					}

					// Prices are entered inclusive of tax mode but displayed exclusive, we have enough data from our totals above
					// to do a simple replacement and output the totals string.
					if ( 'incl' === tax_mode && 'excl' === tax_display_mode ) {
						price_display_suffix = '<small class="woocommerce-price-suffix">' + grmpd_frames_params.price_display_suffix + '</small>';
						price_display_suffix = price_display_suffix.replace( '{price_including_tax}', formatted_raw_total );
						price_display_suffix = price_display_suffix.replace( '{price_excluding_tax}', formatted_grand_total );
						html = html + '<dt>' + grmpd_frames_params.i18n_grand_total + '</dt><dd><strong><span class="amount">' + formatted_grand_total + '</span> ' + price_display_suffix + ' </strong></dd></dl>';
						$totals.html( html );
						$( 'body' ).trigger( 'updated_addons' );
						return;
					}

					// Based on the totals/info and settings we have, we need to use the get_price_*_tax functions
					// to get accurate totals. We can get these values with a special Ajax function
					$.ajax( {
						type: 'POST',
						url:  grmpd_frames_params.ajax_url,
						data: {
							action: 'wc_frames_calculate_tax',
							total:  product_total_price + total,
							product_id: product_id
						},
						success: 	function( code ) {
							result = $.parseJSON( code );
							if ( result.result == 'SUCCESS' ) {
								price_display_suffix = '<small class="woocommerce-price-suffix">' + grmpd_frames_params.price_display_suffix + '</small>';
								var formatted_price_including_tax = accounting.formatMoney( result.price_including_tax, {
									symbol 		: grmpd_frames_params.currency_format_symbol,
									decimal 	: grmpd_frames_params.currency_format_decimal_sep,
									thousand	: grmpd_frames_params.currency_format_thousand_sep,
									precision 	: grmpd_frames_params.currency_format_num_decimals,
									format		: grmpd_frames_params.currency_format
								} );
								var formatted_price_excluding_tax = accounting.formatMoney( result.price_excluding_tax, {
									symbol 		: grmpd_frames_params.currency_format_symbol,
									decimal 	: grmpd_frames_params.currency_format_decimal_sep,
									thousand	: grmpd_frames_params.currency_format_thousand_sep,
									precision 	: grmpd_frames_params.currency_format_num_decimals,
									format		: grmpd_frames_params.currency_format
								} );
								price_display_suffix = price_display_suffix.replace( '{price_including_tax}', formatted_price_including_tax );
								price_display_suffix = price_display_suffix.replace( '{price_excluding_tax}', formatted_price_excluding_tax );
								html = html + '<dt>' + grmpd_frames_params.i18n_grand_total + '</dt><dd><strong><span class="amount">' + formatted_grand_total + '</span> ' + price_display_suffix + ' </strong></dd></dl>';
								$totals.html( html );
								$( 'body' ).trigger( 'updated_addons' );
							} else {
								console.log( result );
								html = html + '<dt>' + grmpd_frames_params.i18n_grand_total + '</dt><dd><strong><span class="amount">' + formatted_grand_total + '</span></strong></dd></dl>';
								$totals.html( html );
								$( 'body' ).trigger( 'updated_addons' );
							}
						},
						error: function() {
							html = html + '<dt>' + grmpd_frames_params.i18n_grand_total + '</dt><dd><strong><span class="amount">' + formatted_grand_total + '</span></strong></dd></dl>';
							$totals.html( html );
							$( 'body' ).trigger( 'updated_addons' );
						}
					} );
				}
			} else {
				$totals.empty();
			}

		} );

		$(this).find( '.addon-custom, .addon-custom-textarea, .grmpd-frame input, .grmpd-frame textarea, .grmpd-frame select, input.qty' ).change();

		// When default variation exists, 'found_variation' must be triggered
		$(this).find( '.variations select' ).change();
	}

	// Quick view
	$( 'body' ).on( 'quick-view-displayed', function() {
		$(this).find( '.cart:not(.cart_group)' ).each( function() {
			$(this).init_addon_totals();
		} );
	} );

	// Composites
	$( 'body .component' ).on( 'wc-composite-component-loaded', function() {
		$(this).find( '.cart' ).each( function() {
			$(this).init_addon_totals();
		} );
	} );

	// Initialize
	$( 'body' ).find( '.cart:not(.cart_group)' ).each( function() {
		$(this).init_addon_totals();
	} );

} );
