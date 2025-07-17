<?php

class TGL_Rentals_Admin_Menu {

    public function __construct() {
        add_action( 'admin_menu', array( $this, 'add_admin_menu_page' ) );
    }

    public function add_admin_menu_page() {
        add_menu_page(
            'TGL Rentals Plugin', // Título de la página
            'TGL Rentals',        // Nombre del menú
            'manage_options',     // Capacidad
            'tgl-rentals-plugin', // Slug
            array( $this, 'tgl_rentals_settings_page' ), // Callback para mostrar el contenido
            'dashicons-admin-home',  // Icono
            20                     // Posición en el menú
        );
    }

    public function tgl_rentals_settings_page() {
        echo '<div class="wrap">';
        echo '<h1>TGL Rentals Configuraciones</h1>';
        echo '<p>Aquí puedes añadir futuras configuraciones para el plugin.</p>';
        echo '</div>';
    }
}
