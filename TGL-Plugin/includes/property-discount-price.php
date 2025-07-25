<?php

class Property_Discount_Price {

    public function __construct() {
        add_shortcode('tgl_discounted_price', [$this, 'display_discounted_price']);
        add_action( 'wp_enqueue_scripts', [$this, 'enqueue_styles'] );
    }

    // Define el método enqueue_styles para evitar el error fatal.
    // Puedes encolar un archivo CSS específico para este shortcode si lo necesitas.
    public function enqueue_styles() {
        // Ejemplo: Si este shortcode necesita un CSS, podrías añadir algo como esto:
        // wp_enqueue_style( 'property-discount-price', plugin_dir_url( __FILE__ ) . '../assets/css/property-discount-price.css' );
        // Si no necesitas estilos específicos, puedes dejar este método vacío o eliminar la línea del add_action en el constructor.
    }

    public function get_default_price() {
      $prices = jet_engine()->listings->data->get_meta('jet_abaf_price');
      return !empty($prices['_apartment_price']) ? json_encode($prices['_apartment_price']) : false;
    }

    /**
     * Método para mostrar solo el precio con descuento.
     * Utiliza el precio base del apartamento y el porcentaje de descuento.
     */
    public function display_discounted_price() {
        $post_id = get_the_ID(); // Obtiene el ID del post/apartamento actual

        // Obtener el precio original del metafield '_apartment_price'
        // Se accede directamente al meta 'jet_abaf_price' y luego al subcampo '_apartment_price'
        $prices_meta = jet_engine()->listings->data->get_meta('jet_abaf_price');
        $original_price = !empty($prices_meta['_apartment_price']) ? floatval($prices_meta['_apartment_price']) : 0;

        // Obtener el porcentaje de descuento del metafield 'discount_amount'
        $discount_percentage = get_post_meta($post_id, 'discount_amount', true);
        $discount_percentage = is_numeric($discount_percentage) ? floatval($discount_percentage) : 0;

        // Si no hay precio original o descuento válido, no mostrar nada
        if ($original_price === 0 || $discount_percentage <= 0 || $discount_percentage > 100) {
            return '';
        }

        // Calcular el precio con descuento
        $discounted_price = $original_price * (1 - ($discount_percentage / 100));

        // Formatear y devolver solo el precio con descuento
        // Se usa number_format para asegurar el formato de moneda sin decimales como en tu imagen
        return '<span style="font-weight: bold; color: #F28F52; font-size: 1.2em;">$' . number_format($discounted_price, 0, ',', '.') . '</span><span> /night</span>';
    }
}