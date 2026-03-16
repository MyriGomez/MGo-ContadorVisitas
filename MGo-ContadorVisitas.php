<?php
/**
 * Plugin Name: MGo - Contador Visitas
 * Description: Contador visitas por día y url, sin guardar ningún dato del usuario.
 * Version: 1.3.7
 * Author: Myriam Gómez
 */

if (!defined('ABSPATH')) exit;

// Asegurarnos de que PHP use la zona horaria correcta
date_default_timezone_set('Europe/Madrid');

define('MI_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('MI_PLUGIN_URL', plugin_dir_url(__FILE__));
define('CONTADOR_VERSION', '1.3.7');

// Cargar clases básicas
require_once MI_PLUGIN_DIR . 'includes/class-db-options.php';


// Registrar assets
add_action('wp_enqueue_scripts', function(){
    wp_enqueue_style('contador-style', MI_PLUGIN_URL . 'includes/assets/MGoContadorStyle.css');
    
    if (!is_admin()) {
        wp_enqueue_script('contador-script', MI_PLUGIN_URL . 'includes/assets/MGoContadorScript.js', [], CONTADOR_VERSION, true);
        global $post;
        wp_localize_script('contador-script', 'contador_ajax', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('contador_visitas_nonce'),
            'post_id' => $post ? $post->ID : 0

        ]);
    }

});

// Activación / desactivación
register_activation_hook(__FILE__, array('\Contador\Contador_DB_Options','on_activation'));
register_deactivation_hook(__FILE__, array('\Contador\Contador_DB_Options','on_deactivation'));

//botón y acción de actualizar plugin
add_filter('plugin_action_links_' . plugin_basename(__FILE__), function($links) {
    // Crear URL con nonce para seguridad
    $update_url = wp_nonce_url(admin_url('plugins.php?action=contador_update_plugin'), 'contador_update_plugin_nonce');

    // Añadir el enlace
    $links[] = '<a href="' . esc_url($update_url) . '">Actualizar</a>';
    return $links;
});
add_action('admin_init', function() {
    if (isset($_GET['action']) && $_GET['action'] === 'contador_update_plugin') {

        // Verificar permisos y nonce
        if (!current_user_can('manage_options') || !check_admin_referer('contador_update_plugin_nonce', '_wpnonce')) {
            wp_die('No tienes permisos para esto');
        }

        // Ejecutar la actualización
        \Contador\Contador_DB_Options::maybe_update_plugin();

        // Guardar flag en transient para mostrar mensaje
        set_transient('contador_update_success', true, 30);

        // Redirigir de vuelta a la lista de plugins (para que el listado siga visible)
        wp_safe_redirect(admin_url('plugins.php'));
        exit;
    }
});
add_action('admin_notices', function() {
    if (get_transient('contador_update_success')) {
        echo '<div class="notice notice-success is-dismissible"><p>Plugin actualizado correctamente.</p></div>';
        delete_transient('contador_update_success'); // eliminar para que no se repita
    }
});


if (is_admin()) {
    require_once MI_PLUGIN_DIR . 'includes/admin/class-admin-page.php';
    \Contador\Admin\Admin_Page::init();
}

//guardar métricas por página abierta
add_action('wp_ajax_nopriv_registrar_visita', ['\Contador\Contador_DB_Options', 'registrar_visita']);
add_action('wp_ajax_registrar_visita', ['\Contador\Contador_DB_Options', 'registrar_visita']);

//disparar guardar métrica de uso real engaged
add_action('wp_ajax_nopriv_mg_engaged_visit', ['\Contador\Contador_DB_Options','mg_engaged_visit']);
add_action('wp_ajax_mg_engaged_visit', ['\Contador\Contador_DB_Options','mg_engaged_visit']);
