<?php

class Daily_Booking_Cleaner {

    public function __construct() {
        // Programar cron si no existe
        if ( ! wp_next_scheduled( 'daily_bookings_cleanup' ) ) {
            wp_schedule_event( strtotime('midnight'), 'daily', 'daily_bookings_cleanup' );
        }

        // Hook para ejecutar la limpieza de bookings
        add_action( 'daily_bookings_cleanup', array( $this, 'remove_all_bookings' ) );
    }

    public function remove_all_bookings() {
        $bookings = jet_abaf_get_bookings();
        if ( empty( $bookings ) ) {
            return;
        }

        foreach ( $bookings as $booking ) {
            jet_abaf()->db->delete_booking( [ 'booking_id' => $booking->get_id() ] );
        }
    }

    public static function deactivate() {
        $timestamp = wp_next_scheduled( 'daily_bookings_cleanup' );
        if ( $timestamp ) {
            wp_unschedule_event( $timestamp, 'daily_bookings_cleanup' );
        }
    }

    public static function add_cron_intervals( $schedules ) {
        $schedules['every_five_minutes'] = array(
            'interval' => 300, // 300 segundos = 5 minutos
            'display'  => __( 'Cada 5 minutos', 'jetbooking' ),
        );
        return $schedules;
    }
}
