<?php
/**
 * CoCart REST API controller
 *
 * Handles requests to the cart endpoint.
 *
 * @author   Sébastien Dumont
 * @category API
 * @package  CoCart/API
 * @since    3.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * CoCart REST API v2 controller class.
 *
 * @package CoCart REST API/API
 */
class CoCart_Cart_V2_Controller extends CoCart_API_Controller {

	/**
	 * Endpoint namespace.
	 *
	 * @var string
	 */
	protected $namespace = 'cocart/v2';

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $rest_base = 'cart';

	/**
	 * Register the routes for cart.
	 *
	 * @access public
	 */
	public function register_routes() {
		// Get Cart - cocart/v2/cart (GET)
		register_rest_route( $this->namespace, '/' . $this->rest_base, array(
			array(
				'methods'  => WP_REST_Server::READABLE,
				'callback' => array( $this, 'get_cart' ),
				'args'     => $this->get_collection_params()
			),
			'schema' => array( $this, 'get_item_schema' )
		) );

		// Get Cart in Session - cocart/v2/cart/1654654321 (GET)
		register_rest_route( $this->namespace, '/' . $this->rest_base . '/(?P<id>[\w]+)', array(
			array(
				'methods'  => WP_REST_Server::READABLE,
				'callback' => array( $this, 'get_cart_in_session' ),
				'args'     => $this->get_collection_params()
			),
			'schema' => array( $this, 'get_item_schema' )
		) );
	} // register_routes()

	/**
	 * Gets the cart instance so we only call it once in the API.
	 *
	 * @access public
	 * @since  3.0.0
	 * @return WC_Cart
	 */
	public function get_cart_instance() {
		$cart = WC()->cart;

		if ( ! $cart || ! $cart instanceof \WC_Cart ) {
			throw new CoCart_Data_Exception( 'cocart_cart_error', __( 'Unable to retrieve cart.', 'cart-rest-api-for-woocommerce' ), 500 );
		}

		return $cart;
	} // END get_cart_instance()

	/**
	 * Return a cart item from the cart.
	 *
	 * @access  public
	 * @since   2.1.0
	 * @version 3.0.0
	 * @param   string $item_id   - The item we are looking up in the cart.
	 * @param   string $condition - Default is 'add', other conditions are: container, update, remove, restore
	 * @return  array  $item      - Returns details of the item in the cart if it exists.
	 */
	public function get_cart_item( $item_id, $condition = 'add' ) {
		$item = isset( WC()->cart->cart_contents[ $item_id ] ) ? WC()->cart->cart_contents[ $item_id ] : array();

		return apply_filters( 'cocart_get_cart_item', $item, $condition );
	} // EMD get_cart_item()

	/**
	 * Returns all cart items.
	 *
	 * @param callable $callback Optional callback to apply to the array filter.
	 * @return array
	 */
	public function get_cart_items( $callback = null ) {
		return $callback ? array_filter( WC()->cart->get_cart(), $callback ) : array_filter( WC()->cart->get_cart() );
	} // END get_cart_items()

	/**
	 * Get cart.
	 *
	 * @access public
	 * @param  array  $request
	 * @param  string $cart_item_key
	 * @return array|WP_REST_Response
	 */
	public function get_cart( $request = array(), $cart_item_key = '' ) {
		$cart_contents = ! $this->get_cart_instance()->is_empty() ? array_filter( $this->get_cart_instance()->get_cart() ) : array();

		$show_raw = ! empty( $request['raw'] ) ? $request['raw'] : false;

		// Return cart contents raw if requested.
		if ( $show_raw ) {
			return $cart_contents;
		}

		/**
		 * Deprecated action hook `cocart_get_cart`.
		 *
		 * @reason Better filtering for cart contents later on.
		 */
		wc_deprecated_hook( 'cocart_get_cart', '3.0.0', null, null );

		$cart_contents = $this->return_cart_contents( $request, $cart_contents, $cart_item_key );

		return new WP_REST_Response( $cart_contents, 200 );
	} // END get_cart()

	/**
	 * Return cart contents.
	 *
	 * @access  public
	 * @since   2.0.0
	 * @version 3.0.0
	 * @param   array  $request
	 * @param   array  $cart_contents
	 * @param   string $cart_item_key
	 * @param   bool   $from_session
	 * @return  array  $cart
	 */
	public function return_cart_contents( $request = array(), $cart_contents = array(), $cart_item_key = '', $from_session = false ) {
		$controller = new CoCart_Count_Items_v2_Controller();

		if ( $controller->get_cart_contents_count( array( 'return' => 'numeric' ), $cart_contents ) <= 0 || empty( $cart_contents ) ) {
			/**
			 * Filter response for empty cart.
			 *
			 * @param $empty_cart
			 */
			$empty_cart = apply_filters( 'cocart_empty_cart', $cart_contents );

			return $empty_cart;
		}

		// Find the cart item key in the existing cart.
		if ( ! empty( $cart_item_key ) ) {
			$cart_item_key = $this->find_product_in_cart( $cart_item_key );

			return $cart_contents[ $cart_item_key ];
		}

		/**
		 * Return the default cart data if set to true.
		 *
		 * @since 3.0.0
		 */
		if ( ! empty( $request['default'] ) && $request['default'] ) {
			return $cart_contents;
		}

		$show_thumb = ! empty( $request['thumb'] ) ? $request['thumb'] : false;

		$cart = array(
			'cart_hash'      => $this->get_cart_instance()->get_cart_hash(),
			'cart_key'       => $this->get_cart_key( $request ),
			'currency'       => $this->get_store_currency(),
			'items'          => array(),
			'item_count'     => $this->get_cart_instance()->get_cart_contents_count(),
			'items_weight'   => wc_get_weight( $this->get_cart_instance()->get_cart_contents_weight(), get_option( 'woocommerce_weight_unit' ) ),
			'needs_payment'  => $this->get_cart_instance()->needs_payment(),
			'needs_shipping' => $this->get_cart_instance()->needs_shipping(),
			'coupons'        => array(),
			'totals' => array(
				'total_items'        => $this->prepare_money_response( $this->get_cart_instance()->get_subtotal(), wc_get_price_decimals() ),
				'total_item_tax'     => $this->prepare_money_response( $this->get_cart_instance()->get_subtotal_tax(), wc_get_price_decimals() ),
				'total_fees'         => $this->prepare_money_response( $this->get_cart_instance()->get_fee_total(), wc_get_price_decimals() ),
				'total_fees_tax'     => $this->prepare_money_response( $this->get_cart_instance()->get_fee_tax(), wc_get_price_decimals() ),
				'total_discount'     => $this->prepare_money_response( $this->get_cart_instance()->get_discount_total(), wc_get_price_decimals() ),
				'total_discount_tax' => $this->prepare_money_response( $this->get_cart_instance()->get_discount_tax(), wc_get_price_decimals() ),
				'total_shipping'     => $this->prepare_money_response( $this->get_cart_instance()->get_shipping_total(), wc_get_price_decimals() ),
				'total_shipping_tax' => $this->prepare_money_response( $this->get_cart_instance()->get_shipping_tax(), wc_get_price_decimals() ),
				'total_price'        => $this->prepare_money_response( $this->get_cart_instance()->get_total( 'view' ), wc_get_price_decimals() ),
				'total_tax'          => $this->prepare_money_response( $this->get_cart_instance()->get_total_tax(), wc_get_price_decimals() ),
				'tax_lines'          => $this->get_tax_lines( $this->get_cart_instance() )
			),
			'fees'            => $this->get_fees( $this->get_cart_instance() ),
		);

		// Returns each coupon applied and coupon total applied if store has coupons enabled.
		$coupons = wc_coupons_enabled() ? $this->get_cart_instance()->get_applied_coupons() : array();

		if ( ! empty( $coupons ) ) {
			foreach ( $coupons as $code => $coupon ) {
				$cart['coupons'][ $code ] = array(
					'coupon'      => esc_attr( sanitize_title( $coupon ) ),
					'label'       => esc_attr( wc_cart_totals_coupon_label( $coupon, false ) ),
					'saving'      => $this->coupon_html( $coupon, false ),
					'saving_html' => $this->coupon_html( $coupon )
				);
			}
		}

		$weight_unit = get_option( 'woocommerce_weight_unit' );

		foreach ( $cart_contents as $item_key => $cart_item ) {
			// If product data is missing then get product data and apply.
			if ( ! isset( $cart_item['data'] ) ) {
				$cart_item['data'] = wc_get_product( $cart_item['variation_id'] ? $cart_item['variation_id'] : $cart_item['product_id'] );
				$cart['items'][ $item_key ]['data'] = $cart_item['data'];
			}

			$_product = apply_filters( 'cocart_item_product', $cart_item['data'], $cart_item, $item_key );

			// If product is no longer purchasable then don't return it and notify customer.
			if ( ! $_product->is_purchasable() ) {
				/* translators: %s: product name */
				$message = sprintf( __( '%s has been removed from your cart because it can no longer be purchased. Please contact us if you need assistance.', 'cart-rest-api-for-woocommerce' ), $_product->get_name() );

				/**
				 * Filter message about item removed from the cart.
				 *
				 * @since 2.1.0
				 * @param string     $message Message.
				 * @param WC_Product $_product Product data.
				 */
				$message = apply_filters( 'cocart_cart_item_removed_message', $message, $_product );

				$this->get_cart_instance()->set_quantity( $item_key, 0 ); // Sets item quantity to zero so it's removed from the cart.

				wc_add_notice( $message, 'error' );
			} else {
				$cart['items'][ $item_key ] = array(
					'id'         => $_product->get_id(),
					'name'       => apply_filters( 'cocart_product_name', $_product->get_name(), $_product, $cart_item, $item_key ),
					'title'      => apply_filters( 'cocart_product_title', $_product->get_title(), $_product, $cart_item, $item_key ),
					'price'      => wc_format_decimal( $_product->get_price(), wc_get_price_decimals() ),
					'quantity'   => $cart_item['quantity'],
					'meta' => array(
						'sku'                   => $_product->get_sku(),
						'dimensions'            => array(),
						'min_purchase_quantity' => $_product->get_min_purchase_quantity(),
						'max_purchase_quantity' => $_product->get_max_purchase_quantity(),
						'weight'                => wc_get_weight( $_product->get_weight() * $cart_item['quantity'], $weight_unit )
					),
					'cart_item_data'            => apply_filters( 'cocart_cart_item_data', array() )
				);

				// Item dimensions.
				$dimensions = $_product->get_dimensions( false );
				if ( ! empty( $dimensions ) ) {
					$cart['items'][ $item_key ]['meta']['dimensions'] = array(
						'length' => $dimensions['length'],
						'width'  => $dimensions['width'],
						'height' => $dimensions['height'],
						'unit'   => get_option( 'woocommerce_dimension_unit' )
					);
				}

				// Variation data.
				$cart['items'][ $item_key ]['meta']['variation'] = $this->format_variation_data( $cart_item['variation'], $_product );

				// If thumbnail is requested then add it to each item in cart.
				if ( $show_thumb ) {
					$thumbnail_id = apply_filters( 'cocart_item_thumbnail', $_product->get_image_id(), $cart_item, $item_key );

					$thumbnail_src = wp_get_attachment_image_src( $thumbnail_id, apply_filters( 'cocart_item_thumbnail_size', 'woocommerce_thumbnail' ) );

					/**
					 * Filters the source of the product thumbnail.
					 *
					 * @since 2.1.0
					 * @param string $thumbnail_src URL of the product thumbnail.
					 */
					$thumbnail_src = apply_filters( 'cocart_item_thumbnail_src', $thumbnail_src[0], $cart_item, $item_key );

					// Add main featured image.
					$cart['items'][ $item_key ]['featured_image'] = esc_url( $thumbnail_src );
				}

				// This filter allows additional data to be returned for a specific item in cart.
				$cart['items'] = apply_filters( 'cocart_cart_items', $cart['items'], $item_key, $cart_item, $_product );
			}
		}

		/**
		 * Return cart items from session if set.
		 *
		 * @since   2.1.0
		 * @version 3.0.0
		 * @param   $cart['items']
		 */
		if ( $from_session ) {
			$cart['items'] = apply_filters( 'cocart_cart_session', $cart['items'] );
		} else {
			$cart['items'] = apply_filters( 'cocart_cart', $cart['items'] );
		}

		return $cart;
	} // END return_cart_contents()

	/**
	 * Prepares a list of store currency data to return in responses.
	 *
	 * @access protected
	 * @return array
	 */
	protected function get_store_currency() {
		$position = get_option( 'woocommerce_currency_pos' );
		$symbol   = html_entity_decode( get_woocommerce_currency_symbol() );
		$prefix   = '';
		$suffix   = '';

		switch ( $position ) {
			case 'left_space':
				$prefix = $symbol . ' ';
				break;
			case 'left':
				$prefix = $symbol;
				break;
			case 'right_space':
				$suffix = ' ' . $symbol;
				break;
			case 'right':
				$suffix = $symbol;
				break;
		}

		return array(
			'currency_code'               => get_woocommerce_currency(),
			'currency_symbol'             => $symbol,
			'currency_minor_unit'         => wc_get_price_decimals(),
			'currency_decimal_separator'  => wc_get_price_decimal_separator(),
			'currency_thousand_separator' => wc_get_price_thousand_separator(),
			'currency_prefix'             => $prefix,
			'currency_suffix'             => $suffix,
		);
	} // END get_store_currency()

	/**
	 * Returns the cart key.
	 *
	 * @access public
	 * @param  $request
	 * @return string
	 */
	public function get_cart_key( $request ) {
		if ( ! class_exists( 'CoCart_Session_Handler' ) || ! WC()->session instanceof CoCart_Session_Handler ) {
			return;
		}

		// Current user ID.
		$current_user_id = strval( get_current_user_id() );

		// Get cart cookie... if any.
		$cookie = WC()->session->get_session_cookie();

		// Does a cookie exist?
		if ( $cookie ) {
			$cart_key = $cookie[0];
		}

		// Check if we requested to load a specific cart.
		if ( isset( $request['cart_key'] ) ) {
			$cart_key = $request['cart_key'];
		}

		// Override cookie check to force load the authenticated users cart if switched without logging out first.
		$override_cookie_check = apply_filters( 'cocart_override_cookie_check', false );

		if ( is_numeric( $current_user_id ) && $current_user_id > 0 ) {
			if ( $override_cookie_check || ! $cookie ) {
				$cart_key = $current_user_id;
			}
		}

		return $cart_key;
	} // END get_cart_key()

	/**
	 * Get tax lines from the cart and format to match schema.
	 *
	 * @access protected
	 * @param  WC_Cart $cart Cart class instance.
	 * @return array
	 */
	protected function get_tax_lines( $cart ) {
		$cart_tax_totals = $cart->get_tax_totals();
		$tax_lines       = [];

		foreach ( $cart_tax_totals as $cart_tax_total ) {
			$tax_lines[] = array(
				'name'  => $cart_tax_total->label,
				'price' => $this->prepare_money_response( $cart_tax_total->amount, wc_get_price_decimals() ),
			);
		}

		return $tax_lines;
	} // END get_tax_lines()

	/**
	 * Convert monetary values from WooCommerce to string based integers, using
	 * the smallest unit of a currency.
	 *
	 * @access protected
	 * @param  string|float $amount Monetary amount with decimals.
	 * @param  int          $decimals Number of decimals the amount is formatted with.
	 * @param  int          $rounding_mode Defaults to the PHP_ROUND_HALF_UP constant.
	 * @return string      The new amount.
	 */
	protected function prepare_money_response( $amount, $decimals = 2, $rounding_mode = PHP_ROUND_HALF_UP ) {
		return (string) intval(
			round(
				wc_format_decimal( $amount ) * ( 10 ** $decimals ),
				0,
				absint( $rounding_mode )
			)
		);
	} // END prepare_money_response()

	/**
	 * Format variation data, for example convert slugs such as attribute_pa_size to Size.
	 *
	 * @access protected
	 * @param  array      $variation_data Array of data from the cart.
	 * @param  WC_Product $product Product data.
	 * @return array
	 */
	protected function format_variation_data( $variation_data, $product ) {
		$return = array();

		if ( empty( $variation_data ) ) {
			return $return;
		}

		foreach ( $variation_data as $key => $value ) {
			$taxonomy = wc_attribute_taxonomy_name( str_replace( 'attribute_pa_', '', urldecode( $key ) ) );

			if ( taxonomy_exists( $taxonomy ) ) {
				// If this is a term slug, get the term's nice name.
				$term = get_term_by( 'slug', $value, $taxonomy );
				if ( ! is_wp_error( $term ) && $term && $term->name ) {
					$value = $term->name;
				}
				$label = wc_attribute_label( $taxonomy );
			} else {
				// If this is a custom option slug, get the options name.
				$value = apply_filters( 'cocart_variation_option_name', $value, null, $taxonomy, $product );
				$label = wc_attribute_label( str_replace( 'attribute_', '', $name ), $product );
			}

			$return[ $label ] = $value;
		}

		return $return;
	} // END format_variation_data()

	/**
	 * Get cart fees.
	 *
	 * @access public
	 * @param  WC_Cart $cart Cart class instance.
	 * @return array
	 */
	public function get_fees( $cart ) {
		$cart_fees = $cart->get_fees();

		$fees = array();

		if ( ! empty( $cart_fees ) ) {
			foreach ( $cart_fees as $key => $fee ) {
				$fees[ $key ] = array(
					'name' => esc_html( $fee->name ),
					'fee'  => html_entity_decode( strip_tags( $this->fee_html( $cart, $fee ) ) )
				);
			}
		}

		return $fees;
	} // END get_fees()

	/**
	 * Get coupon in HTML.
	 *
	 * @access public
	 * @param  string|WC_Coupon $coupon Coupon data or code.
	 * @param  bool             $formatted Formats the saving amount.
	 * @return string           The coupon in HTML.
	 */
	public function coupon_html( $coupon, $formatted = true ) {
		if ( is_string( $coupon ) ) {
			$coupon = new WC_Coupon( $coupon );
		}

		$discount_amount_html = '';

		$amount = $this->get_cart_instance()->get_coupon_discount_amount( $coupon->get_code(), $this->get_cart_instance()->display_cart_ex_tax );

		if ( $formatted ) {
			$savings = wc_price( $amount );
		}
		else {
			$savings = wc_format_decimal( $amount, wc_get_price_decimals() );
		}

		$discount_amount_html = '-' . html_entity_decode( strip_tags( $savings ) );

		if ( $coupon->get_free_shipping() && empty( $amount ) ) {
			$discount_amount_html = __( 'Free shipping coupon', 'cocart-get-cart-enhanced' );
		}

		$discount_amount_html = apply_filters( 'cocart_coupon_discount_amount_html', $discount_amount_html, $coupon );

		return $discount_amount_html;
	} // END coupon_html()

	/**
	 * Get the fee value.
	 * 
	 * @access public
	 * @param object $cart
	 * @param object $fee Fee data.
	 */
	public function fee_html( $cart, $fee ) {
		$cart_totals_fee_html = $cart->display_prices_including_tax() ? wc_price( $fee->total + $fee->tax ) : wc_price( $fee->total );

		return apply_filters( 'cocart_cart_totals_fee_html', $cart_totals_fee_html, $fee );
	} // END fee_html()

	/**
	 * Validates a product object for the cart.
	 *
	 * @throws CoCart_Data_Exception Exception if invalid data is detected.
	 *
	 * @access public
	 * @param  WC_Product       Passes the product object if valid.
	 * @return WC_Product|Error Returns a product object if purchasable.
	 */
	public function validate_product_for_cart( $product ) {
		// Check if the product exists before continuing.
		if ( ! $product || 'trash' === $product->get_status() ) {
			$message = __( 'This product cannot be added to the cart.', 'cart-rest-api-for-woocommerce' );

			CoCart_Logger::log( $message, 'error' );

			/**
			 * Filters message about product that cannot be added to cart.
			 *
			 * @param string     $message - Message.
			 * @param WC_Product $product - Product data.
			 */
			$message = apply_filters( 'cocart_product_cannot_be_added_message', $message, $product );

			throw new CoCart_Data_Exception( 'cocart_invalid_product', $message, 400 );
		}

		return $product;
	} // END get_product_for_cart()

	/**
	 * Validates item quantity and checks if sold individually.
	 *
	 * @throws RouteException Exception if invalid data is detected.
	 *
	 * @access public
	 * @param  WC_Product $product Product object associated with the cart item.
	 * @param  array      $request Add to cart request params.
	 * @param  float      $quantity
	 * @return float      $quantity
	 */
	public function validate_item_quantity( $product, $quantity ) {
		// Force quantity to 1 if sold individually and check for existing item in cart.
		if ( $product->is_sold_individually() ) {
			/**
			 * Quantity for sold individual products can be filtered.
			 *
			 * @since 2.0.13
			 */
			$quantity = apply_filters( 'cocart_add_to_cart_sold_individually_quantity', 1 );

			$cart_contents = $this->get_cart();

			$found_in_cart = apply_filters( 'cocart_add_to_cart_sold_individually_found_in_cart', $cart_item_key && $cart_contents[ $cart_item_key ]['quantity'] > 0, $product_id, $variation_id, $cart_item_data, $cart_id );

			if ( $found_in_cart ) {
				/* translators: %s: Product Name */
				$message = sprintf( __( 'You cannot add another "%s" to your cart.', 'cart-rest-api-for-woocommerce' ), $product->get_name() );

				CoCart_Logger::log( $message, 'error' );

				/**
				 * Filters message about product not being allowed to add another.
				 *
				 * @param string     $message - Message.
				 * @param WC_Product $product - Product data.
				 */
				$message = apply_filters( 'cocart_product_can_not_add_another_message', $message, $product );

				throw new CoCart_Data_Exception( 'cocart_product_sold_individually', $message, 403 );
			}
		}

		return $quantity;
	} // END validate_item_quantity()

	/**
	 * Validates item and check for errors before added to cart.
	 *
	 * @throws RouteException Exception if invalid data is detected.
	 *
	 * @access  public
	 * @since   2.1.0
	 * @version 3.0.0
	 * @param   WC_Product $product Product object associated with the cart item.
	 * @param   array      $request Add to cart request params.
	 */
	public function validate_add_to_cart( $product, $quantity ) {
		// Product is purchasable check.
		if ( ! $product->is_purchasable() ) {
			$message = __( 'Sorry, this product cannot be purchased.', 'cart-rest-api-for-woocommerce' );

			CoCart_Logger::log( $message, 'error' );

			/**
			 * Filters message about product unable to be purchased.
			 *
			 * @param string     $message - Message.
			 * @param WC_Product $product - Product data.
			 */
			$message = apply_filters( 'cocart_product_cannot_be_purchased_message', $message, $product );

			throw new CoCart_Data_Exception( 'cocart_cannot_be_purchased', $message, 403 );
		}

		// Stock check - only check if we're managing stock and backorders are not allowed.
		if ( ! $product->is_in_stock() ) {
			/* translators: %s: Product name */
			$message = sprintf( __( 'You cannot add "%s" to the cart because the product is out of stock.', 'cart-rest-api-for-woocommerce' ), $product->get_name() );

			CoCart_Logger::log( $message, 'error' );

			/**
			 * Filters message about product is out of stock.
			 *
			 * @param string     $message - Message.
			 * @param WC_Product $product - Product data.
			 */
			$message = apply_filters( 'cocart_product_is_out_of_stock_message', $message, $product );

			throw new CoCart_Data_Exception( 'cocart_product_out_of_stock', $message, 404 );
		}

		if ( ! $product->has_enough_stock( $quantity ) ) {
			/* translators: 1: Quantity Requested, 2: Product Name, 3: Quantity in Stock */
			$message = sprintf( __( 'You cannot add a quantity of %1$s for "%2$s" to the cart because there is not enough stock. - only %3$s remaining!', 'cart-rest-api-for-woocommerce' ), $quantity, $product->get_name(), wc_format_stock_quantity_for_display( $product->get_stock_quantity(), $product ) );

			CoCart_Logger::log( $message, 'error' );

			throw new CoCart_Data_Exception( 'cocart_not_enough_in_stock', $message, 403 );
		}

		// Stock check - this time accounting for whats already in-cart.
		if ( $product->managing_stock() ) {
			$products_qty_in_cart = WC()->cart->get_cart_item_quantities();

			if ( isset( $products_qty_in_cart[ $product->get_stock_managed_by_id() ] ) && ! $product->has_enough_stock( $products_qty_in_cart[ $product->get_stock_managed_by_id() ] + $quantity ) ) {
				/* translators: 1: Quantity in Stock, 2: Quantity in Cart */
				$message = sprintf(
					__( 'You cannot add that amount to the cart &mdash; we have %1$s in stock and you already have %2$s in your cart.', 'cart-rest-api-for-woocommerce' ),
					wc_format_stock_quantity_for_display( $product->get_stock_quantity(), $product ),
					wc_format_stock_quantity_for_display( $products_qty_in_cart[ $product->get_stock_managed_by_id() ], $product )
				);

				CoCart_Logger::log( $message, 'error' );

				throw new CoCart_Data_Exception( 'cocart_not_enough_stock_remaining', $message, 403 );
			}
		}

		_deprecated_hook( 'cocart_ok_to_add_response', '3.0.0', null, 'This filter is no longer used in the API.' );
		_deprecated_hook( 'cocart_ok_to_add', '3.0.0', null, 'This filter is no longer used in the API.' );
	} // END validate_add_to_cart()

	/**
	 * Validate product before it is added to the cart, updated or removed.
	 *
	 * @access  protected
	 * @since   1.0.0
	 * @version 3.0.0
	 * @param   int    $product_id     - Contains the ID of the product.
	 * @param   int    $quantity       - Contains the quantity of the item.
	 * @param   int    $variation_id   - Contains the ID of the variation.
	 * @param   array  $variation      - Attribute values.
	 * @param   array  $cart_item_data - Extra cart item data we want to pass into the item.
	 * @param   string $product_type   - The product type.
	 * @return  array|WP_Error
	 */
	protected function validate_product( $product_id = null, $quantity = 1, $variation_id = 0, $variation = array(), $cart_item_data = array(), $product_type = '', $request = array() ) {
		// Validate request for product ID and quantity.
		self::validate_product_id( $product_id );
		$this->validate_quantity( $quantity );

		// Get product and validate product for the cart.
		$product = wc_get_product( $variation_id ? $variation_id : $product_id );
		$product = $this->validate_product_for_cart( $product );

		// Look up the product type if not passed.
		if ( empty( $product_type ) ) {
			$product_type = $product->get_type();
		}

		// Set correct product ID's if product type is a variation.
		if ( $product->is_type( 'variation' ) ) {
			$product_id   = $product->get_parent_id();
			$variation_id = $product->get_id();
		}

		// Validate variable/variation product.
		if ( $product_type === 'variable' || $product_type === 'variation' ) {
			$variation = $this->validate_variable_product( $product_id, $quantity, $variation_id, $variation, $cart_item_data, $product );

			if ( is_wp_error( $variation ) ) {
				return $variation;
			}
		}

		$passed_validation = apply_filters( 'cocart_add_to_cart_validation', true, $product_id, $quantity, $variation_id, $variation, $cart_item_data, $product_type, $request );

		/**
		 * If validation returned an error return error response.
		 *
		 * @param $passed_validation
		 */
		if ( is_wp_error( $passed_validation ) ) {
			return $passed_validation;
		}

		// If validation returned false.
		if ( ! $passed_validation ) {
			$message = __( 'Product did not pass validation!', 'cart-rest-api-for-woocommerce' );

			CoCart_Logger::log( $message, 'error' );

			/**
			 * Filters message about product failing validation.
			 *
			 * @param string     $message - Message.
			 * @param WC_Product $product - Product data.
			 */
			$message = apply_filters( 'cocart_product_failed_validation_message', $message, $product );

			throw new CoCart_Data_Exception( 'cocart_product_failed_validation', $message, 500 );
		}

		/**
		 * Filters the quantity for specified products.
		 *
		 * @param int   $quantity       - The original quantity of the item.
		 * @param int   $product_id     - The product ID.
		 * @param int   $variation_id   - The variation ID.
		 * @param array $variation      - The variation data.
		 * @param array $cart_item_data - The cart item data.
		 */
		$quantity = apply_filters( 'cocart_add_to_cart_quantity', $quantity, $product_id, $variation_id, $variation, $cart_item_data );

		// Validates the item quantity.
		$quantity = $this->validate_item_quantity( $product, $quantity );

		// Validates the item before adding to cart.
		$this->validate_add_to_cart( $product, $quantity );

		// Add cart item data - may be added by other plugins.
		$cart_item_data = (array) apply_filters( 'cocart_add_cart_item_data', $cart_item_data, $product_id, $variation_id, $quantity, $product_type, $request );

		// Generate an ID based on product ID, variation ID, variation data, and other cart item data.
		$cart_id = WC()->cart->generate_cart_id( $product_id, $variation_id, $variation, $cart_item_data );

		// Find the cart item key in the existing cart.
		$cart_item_key = $this->find_product_in_cart( $cart_id );

		// Returns all valid data.
		return array(
			'product_id'     => $product_id,
			'quantity'       => $quantity,
			'variation_id'   => $variation_id,
			'variation'      => $variation,
			'cart_item_data' => $cart_item_data,
			'cart_item_key'  => $cart_item_key,
			'product_data'   => $product,
			'request'        => $request
		);
	} // END validate_product()

	/**
	 * Filters additional requested data.
	 * 
	 * @access public
	 * @param  $request
	 * @return $request
	 */
	public function filter_request_data( $request ) {
		return apply_filters( 'cocart_filter_request_data', $request );
	} // END filter_request_data()

	/**
	 * Get the schema for returning the cart, conforming to JSON Schema.
	 *
	 * @access public
	 * @since  2.1.2
	 * @return array
	 */
	public function get_item_schema() {
		$schema = array(
			'schema'     => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'CoCart - ' . __( 'Cart', 'cart-rest-api-for-woocommerce' ),
			'type'       => 'object',
			'properties' => array(
				'items'  => array(
					'description' => __( 'List of cart items.', 'cart-rest-api-for-woocommerce' ),
					'type'        => 'string',
					'properties'  => array(
						'key'             => array(
							'description' => __( 'Unique identifier for the item within the cart.', 'cart-rest-api-for-woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view' ),
							'readonly'    => true,
						),
						'product_id'      => array(
							'description' => __( 'Unique identifier for the product.', 'cart-rest-api-for-woocommerce' ),
							'type'        => 'integer',
							'context'     => array( 'view' ),
							'readonly'    => true,
						),
						'variation_id' => array(
							'description' => __( 'Unique identifier for the variation.', 'cart-rest-api-for-woocommerce' ),
							'type'        => 'integer',
							'context'     => array( 'view' ),
							'readonly'    => true,
						),
						'variation'       => array(
							'description' => __( 'Chosen attributes (for variations).', 'cart-rest-api-for-woocommerce' ),
							'type'        => 'array',
							'context'     => array( 'view' ),
							'readonly'    => true,
							'items'       => array(
								'type'       => 'object',
								'properties' => array(
									'attribute' => array(
										'description' => __( 'Variation attribute slug.', 'cart-rest-api-for-woocommerce' ),
										'type'        => 'string',
										'context'     => array( 'view' ),
										'readonly'    => true,
									),
									'value'     => array(
										'description' => __( 'Variation attribute value.', 'cart-rest-api-for-woocommerce' ),
										'type'        => 'string',
										'context'     => array( 'view' ),
										'readonly'    => true,
									),
								),
							),
						),
						'quantity'        => array(
							'description' => __( 'Quantity of this item in the cart.', 'cart-rest-api-for-woocommerce' ),
							'type'        => 'float',
							'context'     => array( 'view' ),
							'readonly'    => true,
						),
						'line_tax_data'   => array(
							'description' => '',
							'type'        => 'array',
							'context'     => array( 'view' ),
							'readonly'    => true,
							'items'       => array(
								'type'    => 'object',
								'properties' => array(
									'subtotal' => array(
										'description' => __( 'Line subtotal tax data.', 'cart-rest-api-for-woocommerce' ),
										'type'        => 'integer',
										'context'     => array( 'view' ),
										'readonly'    => true,
									),
									'total' => array(
										'description' => __( 'Line total tax data.', 'cart-rest-api-for-woocommerce' ),
										'type'        => 'integer',
										'context'     => array( 'view' ),
										'readonly'    => true,
									),
								)
							)
						),
						'line_subtotal' => array(
							'description' => __( 'Line subtotal (the price of the product before coupon discounts have been applied).', 'cart-rest-api-for-woocommerce' ),
							'type'        => 'integer',
							'context'     => array( 'view' ),
							'readonly'    => true,
						),
						'line_subtotal_tax' => array(
							'description' => __( 'Line subtotal tax.', 'cart-rest-api-for-woocommerce' ),
							'type'        => 'integer',
							'context'     => array( 'view' ),
							'readonly'    => true,
						),
						'line_total' => array(
							'description' => __( 'Line total (the price of the product after coupon discounts have been applied).', 'cart-rest-api-for-woocommerce' ),
							'type'        => 'integer',
							'context'     => array( 'view' ),
							'readonly'    => true,
						),
						'line_tax' => array(
							'description' => __( 'Line total tax.', 'cart-rest-api-for-woocommerce' ),
							'type'        => 'integer',
							'context'     => array( 'view' ),
							'readonly'    => true,
						),
						'product_name'    => array(
							'description' => __( 'Product name.', 'cart-rest-api-for-woocommerce' ),
							'type'        => 'string',
							'context'     => ( 'view' ),
							'readonly'    => true,
						),
						'product_price'   => array(
							'description' => __( 'Current product price.', 'cart-rest-api-for-woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view' ),
							'readonly'    => true,
						),
					),
					'readonly'          => true,
				),
			)
		);

		$schema['properties'] = apply_filters( 'cocart_cart_schema', $schema['properties'] );

		return $schema;
	} // END get_item_schema()

	/**
	 * Get the query params for getting the cart.
	 *
	 * @access  public
	 * @since   2.1.0
	 * @version 3.0.0
	 * @return  array $params
	 */
	public function get_collection_params() {
		$params = array(
			'cart_key' => array(
				'description' => __( 'Unique identifier for the cart/customer.', 'cart-rest-api-for-woocommerce' ),
				'type'        => 'string',
			),
			'thumb'    => array(
				'description' => __( 'Returns the thumbnail of the featured product image URL for each item in cart.', 'cart-rest-api-for-woocommerce' ),
				'default'     => true,
				'type'        => 'boolean',
			),
			'default'  => array(
				'description' => __( 'Return the default cart data if set to true.', 'cart-rest-api-for-woocommerce' ),
				'default'     => false,
				'type'        => 'boolean',
			)
		);

		return $params;
	} // END get_collection_params()

} // END class