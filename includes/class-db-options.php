<?php
namespace Contador;

if (!defined('ABSPATH')) exit;


class Contador_DB_Options {

    public static function on_activation() {
        self::create_table();
        self::maybe_update_plugin();
    }

    public static function on_deactivation() {
        // No eliminamos tablas ni datos
    }

    public static function create_table() {
        global $wpdb;
        $tabla = $wpdb->prefix . 'mg_visitas';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS $tabla (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            post_id BIGINT UNSIGNED NOT NULL,
            titulo VARCHAR(255) NOT NULL,
            fecha DATE NOT NULL,
            visitas INT UNSIGNED NOT NULL DEFAULT 1,
            engaged_visitas INT UNSIGNED NOT NULL DEFAULT 0,
            PRIMARY KEY (id),
            UNIQUE KEY post_fecha (post_id, fecha)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    public static function maybe_update_plugin() {
        $current_version   = CONTADOR_VERSION;
        $installed_version = get_option('contador_plugin_version');

        if (!$installed_version) {
            update_option('contador_plugin_version', $current_version);
            return;
        }

        if (version_compare($installed_version, $current_version, '<')) {
            self::update_plugin($installed_version);
            update_option('contador_plugin_version', $current_version);
        }
    }

    public static function update_plugin($from_version) {
        //código para actualización    
    }

    // graba métrica por acceso url
    public static function registrar_visita() {
        // 🔐 SECURITY: Verificación de nonce (CSRF protection)
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'contador_visitas_nonce')) {
            wp_die('Nonce inválido');
        }

        // 🔐 SECURITY: Exclusión de administradores (no contaminar stats)
        if (current_user_can('administrator')) wp_die();

        // 🔐 SECURITY: Validar que existe id_post
        if (!isset($_POST['id_post'])) wp_die();

        $post_id = intval($_POST['id_post']);

        // 🔐 SECURITY: Validar que el post existe y es público
        $post = get_post($post_id);
        if (!$post || $post->post_status !== 'publish' || $post->post_type !== 'page') {
            wp_die();
        }

        // 🔐 SECURITY: Rate limiting por IP (máx 30 peticiones/minuto)
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $transient_key = 'mgo_rl_' . md5($ip);
        $requests = (int) get_transient($transient_key);
        if ($requests >= 30) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log("MGo Contador: Rate limit excedido para IP $ip");
            }
            wp_die();
        }
        set_transient($transient_key, $requests + 1, 60);

        // 🔐 SECURITY: Validación básica de Referer (capa extra contra llamadas directas)
        $referer = $_SERVER['HTTP_REFERER'] ?? '';
        if (empty($referer) || strpos($referer, home_url()) !== 0) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log("MGo Contador: Referer inválido desde $ip");
            }
            wp_die();
        }

        $titulo  = sanitize_text_field(get_the_title($post_id));
        $fecha   = current_time('Y-m-d');

        global $wpdb;
        $tabla = $wpdb->prefix . 'mg_visitas';

        $wpdb->query(
            $wpdb->prepare(
                "INSERT INTO $tabla (post_id, titulo, fecha, visitas)
                VALUES (%d, %s, %s, 1)
                ON DUPLICATE KEY UPDATE visitas = visitas + 1",
                $post_id, $titulo, $fecha
            )
        );

        wp_die();
    }

    //obtiene totales para ranking
    public static function get_top_pages() {
        global $wpdb;
        $tabla = $wpdb->prefix . 'mg_visitas';

        $ranking = $wpdb->get_results("
            SELECT post_id, SUM(visitas) as total, SUM(engaged_visitas) as engaged_visitas 
            FROM $tabla 
            GROUP BY post_id 
            ORDER BY total DESC
        ");
        return $ranking;
    }

    //obtiene info por filtro
    public static function get_visitas_por_fecha($post_id = null, $fecha_inicio = null, $fecha_fin = null) {
        global $wpdb;
        $tabla = $wpdb->prefix . 'mg_visitas';
        $sql = "SELECT * FROM $tabla WHERE 1=1";

        if ($post_id) {
            $sql .= $wpdb->prepare(" AND post_id = %d", $post_id);
        }
        if ($fecha_inicio) {
            $sql .= $wpdb->prepare(" AND fecha >= %s", $fecha_inicio);
        }
        if ($fecha_fin) {
            $sql .= $wpdb->prepare(" AND fecha <= %s", $fecha_fin);
        }

        $sql .= " ORDER BY fecha ASC, visitas DESC";
        return $wpdb->get_results($sql);
    }

    //guarda métrica de uso real (+7seg || scroll || fetch=true || refrescar=false)
    public static function mg_engaged_visit() {
        // 🔐 SECURITY: Verificación de nonce (CSRF protection)
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'contador_visitas_nonce')) {
            wp_die('Nonce inválido');
        }
        
        // 🔐 SECURITY: Exclusión de administradores (no contaminar stats)
        if (current_user_can('administrator')) {
            wp_die(); 
        }
        
        // 🔐 SECURITY: Validar que existe id_post
        if (!isset($_POST['id_post'])) wp_die();

        $post_id = intval($_POST['id_post']);

        // 🔐 SECURITY: Validar que el post existe y es público
        $post = get_post($post_id);
        if (!$post || $post->post_status !== 'publish' || $post->post_type !== 'page') {
            wp_die();
        }

        // 🔐 SECURITY: Rate limiting por IP (máx 30 peticiones/minuto)
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $transient_key = 'mgo_rl_' . md5($ip);
        $requests = (int) get_transient($transient_key);
        if ($requests >= 30) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log("MGo Contador: Rate limit excedido para IP $ip");
            }
            wp_die();
        }
        set_transient($transient_key, $requests + 1, 60);

        // 🔐 SECURITY: Validación básica de Referer (capa extra contra llamadas directas)
        $referer = $_SERVER['HTTP_REFERER'] ?? '';
        if (empty($referer) || strpos($referer, home_url()) !== 0) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log("MGo Contador: Referer inválido desde $ip");
            }
            wp_die();
        }

        global $wpdb;
        $fecha = current_time('Y-m-d');
        $tabla = $wpdb->prefix . 'mg_visitas';

        // 🔐 SECURITY: LEAST() garantiza que engaged_visitas NUNCA supera a visitas
        $wpdb->query(
            $wpdb->prepare(
                "UPDATE $tabla 
                SET engaged_visitas = LEAST(engaged_visitas + 1, visitas)
                WHERE post_id = %d AND fecha = %s",
                $post_id,
                $fecha
            )
        );

        wp_die();
    }
}