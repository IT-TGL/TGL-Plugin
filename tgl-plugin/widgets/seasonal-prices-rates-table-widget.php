<?php
class Seasonal_Prices_Rates_Table_Widget extends \Elementor\Widget_Base {

	public function get_name() {
		return 'seasonal_prices_rates_table_widget'; 
	}

	public function get_title() {
		return esc_html__( 'Seasonal Prices Table', 'elementor-addon' );
	}

	public function get_icon() {
		return 'eicon-table';
	}

	public function get_categories() {
		return [ 'basic' ];
	}

	protected function render() {
		echo do_shortcode( '[seasonal_prices_rates]' );
	}

	protected function content_template() {
		echo do_shortcode( '[seasonal_prices_rates]' );
	}
}
?>
