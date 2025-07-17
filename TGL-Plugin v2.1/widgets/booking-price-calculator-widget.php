<?php
class Booking_Price_Calculator_Widget extends \Elementor\Widget_Base {

	public function get_name() {
		return 'booking_price_calculator';
	}

	public function get_title() {
		return esc_html__( 'booking_price_calculator', 'elementor-addon' );
	}

	public function get_icon() {
		return 'eicon-table';
	}

	public function get_categories() {
		return [ 'basic' ];
	}

	protected function render() {
		echo do_shortcode( '[price_calculator]' );
	}

	protected function content_template() {
		echo do_shortcode( '[price_calculator]' );
	}
}
?>
