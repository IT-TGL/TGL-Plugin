<?php
class Seasonal_Prices_Checkout_Price_Widget extends \Elementor\Widget_Base {

	public function get_name() {
		return 'seasonal_prices_checkout_price_widget';
	}

	public function get_title() {
		return esc_html__( 'Seasonal Prices Checkout Price', 'elementor-addon' );
	}

	public function get_icon() {
		return 'eicon-table';
	}

	public function get_categories() {
		return [ 'basic' ];
	}

	protected function render() {
		echo do_shortcode( '[seasonal_prices_checkout_price]' );
	}

	protected function content_template() {
		echo do_shortcode( '[seasonal_prices_checkout_price]' );
	}
}
?>
