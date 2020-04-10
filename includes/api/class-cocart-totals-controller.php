<?php
/**
 * CoCart - Totals controller
 *
 * Handles the request to get the totals of the cart with /totals endpoint.
 *
 * @author   Sébastien Dumont
 * @category API
 * @package  CoCart/API
 * @since    2.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * REST API Totals controller class.
 *
 * @package CoCart/API
 */
class CoCart_Totals_Controller extends CoCart_API_Controller {

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $rest_base = 'totals';

	/**
	 * Register routes.
	 *
	 * @access public
	 */
	public function register_routes() {
		// Get Cart Totals - cocart/v1/totals (GET)
		register_rest_route( $this->namespace, '/' . $this->rest_base, array(
			'methods'  => WP_REST_Server::READABLE,
			'callback' => array( $this, 'get_totals' ),
			'args'     => array(
				'html' => array(
					'description' => __( 'Returns the totals pre-formatted.', 'cart-rest-api-for-woocommerce' ),
					'default' => false,
					'type'    => 'boolean',
				),
			),
		) );
	} // register_routes()

	/**
	 * Returns all calculated totals.
	 *
	 * @access  public
	 * @since   1.0.0
	 * @version 2.0.1
	 * @param   array $data
	 * @return  WP_REST_Response
	 */
	public function get_totals( $data = array() ) {
		if ( ! empty( WC()->cart->totals ) ) {
			$totals = WC()->cart->get_totals();
		} else {
			$totals = WC()->session->get( 'cart_totals' );
		}

		$pre_formatted = ! empty( $data['html'] ) ? $data['html'] : false;

		if ( $pre_formatted ) {
			$new_totals = array();

			$ignore_convert = array(
				'shipping_taxes',
				'cart_contents_taxes',
				'fee_taxes'
			);

			foreach( $totals as $type => $sum ) {
				if ( in_array( $type, $ignore_convert ) ) {
					$new_totals[$type] = $sum;
				} else {
					if ( is_string( $sum ) ) {
						$new_totals[$type] = html_entity_decode( strip_tags( wc_price( $sum ) ) );
					}
					else {
						$new_totals[$type] = html_entity_decode( strip_tags( wc_price( strval( $sum ) ) ) );
					}
				}
			}

			$totals = $new_totals;
		}

		return new WP_REST_Response( $totals, 200 );
	} // END get_totals()

} // END class