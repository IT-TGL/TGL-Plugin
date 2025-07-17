<?php

class Seasonal_Prices_Table {

   public function __construct() {
        add_shortcode( 'seasonal_prices_rates', [$this, 'show_seasonal_prices_table'] );
        add_action( 'wp_enqueue_scripts', [$this, 'enqueue_styles'] );
   }
   
   public function enqueue_styles() {
        wp_enqueue_style( 'seasonal-prices-table-style', plugin_dir_url( __FILE__ ) . '../assets/css/seasonal-prices-table.css' );
   }

   public function get_default_price() {
      $prices = jet_engine()->listings->data->get_meta('jet_abaf_price');
      return !empty($prices['_apartment_price']) ? json_encode($prices['_apartment_price']) : false;
   }

   public function get_seasonal_prices() {
      $prices = jet_engine()->listings->data->get_meta('jet_abaf_price');
      if (!is_array($prices) || empty($prices['_seasonal_prices']) || !is_array($prices['_seasonal_prices'])) {
         return false;
      }
      return $prices['_seasonal_prices'];
   }

   public function get_seasonal_prices_json() {
      $seasonalPrices = $this->get_seasonal_prices();
      return $seasonalPrices ? json_encode($seasonalPrices) : false;
   }

   public function show_seasonal_prices_table($atts) {
      if (!function_exists('jet_engine')) {
         return '';
      }
      $seasonalPrices = $this->get_seasonal_prices();
      if (!$seasonalPrices) {
         return 'There are no seasonal rates available for this house.';
      }

      $output = '<table class="seasonal-prices-table"><tbody>';
      foreach ($seasonalPrices as $season) {
         $start_date = !empty($season['startTimestamp']) ? date('m/d/Y', $season['startTimestamp']) : '-';
         $end_date = !empty($season['endTimestamp']) ? date('m/d/Y', $season['endTimestamp']) : '-';
         $price = !empty($season['price']) ? '$' . number_format($season['price'], 0) . '/night' : 'N/A';

         $output .= '<tr>';
         $output .= '<td class="season-title">' . esc_html($season['title'] ?? 'Rates') . '</td>';
         $output .= '<td class="season-dates">from ' . esc_html($start_date) . ' to ' . esc_html($end_date) . '</td>';
         $output .= '<td class="season-price">' . esc_html($price) . '</td>';
         $output .= '</tr>';
      }
      $output .= '</tbody></table>';
      return $output;
   }
}
