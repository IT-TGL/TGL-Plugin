<?php
/*
Plugin Name: TGL Rentals Plugin
Description: Custom tools for TGL Rentals
Version: 2.0
Author: Esposito Franco
*/

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Salir si se accede directamente
}

// Incluir los archivos de las clases
require_once plugin_dir_path( __FILE__ ) . 'includes/daily-booking-cleaner.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/seasonal-prices-table.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/tgl-admin-menu.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/booking-form-price-calculator.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/property-discount-price.php';

// Inicializar las clases
add_action( 'plugins_loaded', function() {
    new Daily_Booking_Cleaner();
    new Seasonal_Prices_Table();
    new TGL_Rentals_Admin_Menu();
    new Booking_Form_Price_Calculator();
    new Property_Discount_Price();
});

// Añadir el intervalo de cinco minutos al cron
add_filter( 'cron_schedules', array( 'Daily_Booking_Cleaner', 'add_cron_intervals' ) );

// Hook para desactivar el cron job cuando el plugin se desactive
register_deactivation_hook( __FILE__, array( 'Daily_Booking_Cleaner', 'deactivate' ) );


 
//lo desactive por el momento, no se estaba usando y estaba generando problemas con los templates y Elementor

function register_elementor_widget( $widgets_manager ) {

    // Asegurarse de que las clases existen
    require_once( __DIR__ . '/widgets/booking-price-calculator-widget.php' );

    // Registrar los widgets con nombres únicos
    $widgets_manager->register( new \Booking_Price_Calculator_Widget() );
}

add_action( 'elementor/widgets/register', 'register_elementor_widget' );