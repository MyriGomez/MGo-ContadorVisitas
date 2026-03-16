<?php
namespace Contador\Admin;

if (!defined('ABSPATH')) exit;

class Admin_Page {
    
    public static function init() {
        add_action('admin_menu', [self::class, 'add_admin_menu']);
        add_action('admin_enqueue_scripts', [self::class, 'enqueue_assets']);
    }

    public static function add_admin_menu() {
        add_menu_page(
            'Estadísticas de Visitas',
            'Visitas MGo',
            'manage_options',
            'mgo-contador',
            [self::class, 'render_page'], // ← Aquí reutilizamos tu lógica
            'dashicons-chart-bar',
            30
        );
    }

    public static function enqueue_assets($hook) {
        if ($hook !== 'toplevel_page_mgo-contador') return;
        
        // Reutiliza tu CSS existente (las clases .mg-* ya funcionan)
        wp_enqueue_style(
            'mgo-contador-admin',
            MI_PLUGIN_URL . 'includes/assets/MGoContadorStyle.css',
            [], 
            CONTADOR_VERSION
        );
    }

    public static function render_page() {
        // 1️⃣ Permiso (simplificado para admin)
        if (!current_user_can('manage_options')) {
            echo '<div class="notice notice-error"><p>Acceso denegado.</p></div>';
            return;
        }

        // 2️⃣ Filtros (misma lógica que en tu widget, solo cambia el "action" del form)
        $hoy = date('Y-m-d');
        $post_id = isset($_GET['post_id']) && $_GET['post_id'] !== '' ? intval($_GET['post_id']) : null;
        $fecha_inicio = isset($_GET['inicio']) ? sanitize_text_field($_GET['inicio']) : $hoy;
        $fecha_fin    = isset($_GET['fin']) ? sanitize_text_field($_GET['fin']) : $hoy;

        // 3️⃣ Obtener datos ← ¡LLAMADAS DIRECTAS A TU CLASE DB!
        $ranking = \Contador\Contador_DB_Options::get_top_pages();
        $visitas = \Contador\Contador_DB_Options::get_visitas_por_fecha($post_id, $fecha_inicio, $fecha_fin);

        // 4️⃣ HTML wrapper de admin (lo único nuevo)
        ?>
        <div class="wrap">
            <h1>📊 MGo Contador de Visitas</h1>
            
            <!-- Aquí inyectamos el MISMO HTML que tu widget, con mínimos ajustes -->
            <div class="cardMGoContadorVisitas">
                <div class="inside mg-contador-widget">
                    <?php self::render_widget_content($ranking, $visitas, $post_id, $fecha_inicio, $fecha_fin); ?>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Este método contiene el MISMO HTML que tu widget_contador.php::render()
     * Solo cambiamos: 
     * - El action del formulario 
     * - El botón "Reiniciar" 
     * - Eliminamos la dependencia de $this->get_settings_for_display()
     */
    private static function render_widget_content($ranking, $visitas, $post_id, $fecha_inicio, $fecha_fin) {
        // Título fijo (ya no viene de controles de Elementor)
        $titulo = 'MGo Contador de visitas';

        // === BLOQUE 1: Ranking (COPIA EXACTA de tu widget, sin cambios) ===
        echo '<div class="mg-contador-widget">';
        if (!empty($ranking)) {
            echo '<div class="mg-ranking-columnas">';
            
            // Totales globales
            $total_visitas = array_sum(array_map(fn($p) => intval($p->total), $ranking));
            $total_reales  = array_sum(array_map(fn($p) => intval($p->engaged_visitas), $ranking));
            $width_total_real = $total_visitas ? ($total_reales / $total_visitas * 100) : 0;

            echo '<div class="mg-ranking-row">';
            echo '<span class="mg-ranking-label">Visitas reales: ' . $total_reales . ' | Total visitas: ' . $total_visitas . '</span>';
            echo '<div class="mg-ranking-bar-bg">';
            echo '<div class="mg-ranking-bar-fill" style="width:100%;"></div>';
            echo '<div class="mg-ranking-bar-fill-real" style="width:' . esc_attr($width_total_real) . '%;"></div>';
            echo '</div></div><br>';

            // Páginas individuales
            echo '<h4>Ranking por página</h4>';
            foreach ($ranking as $p) {
                $visitas_item = intval($p->total);
                $reales = min(intval($p->engaged_visitas ?? 0), $visitas_item);
                $width_total = $total_visitas ? round(($visitas_item / $total_visitas) * 100) : 0;
                $width_reales = $total_visitas ? round(($reales / $total_visitas) * 100) : 0;

                $post = get_post($p->post_id);
                if (!$post) continue;
                $titulo_actual = get_the_title($p->post_id);

                echo '<div class="mg-ranking-row">';
                echo '<span class="mg-ranking-label">' 
                    . esc_html($titulo_actual) 
                    . ' ( Reales: ' . $reales . ' | Total: ' . $visitas_item . ')</span>';
                echo '<div class="mg-ranking-bar-bg">';
                echo '<div class="mg-ranking-bar-fill" style="width:' . esc_attr($width_total) . '%;"></div>';
                echo '<div class="mg-ranking-bar-fill-real" style="width:' . esc_attr($width_reales) . '%;"></div>';
                echo '</div></div>';
            }
            echo '</div><br>';
        } else {
            echo '<p>No hay visitas registradas.</p>';
        }

        // === BLOQUE 2: Filtros (SOLO CAMBIA EL ACTION DEL FORM) ===
        $action_url = admin_url('admin.php?page=mgo-contador'); // ← CAMBIO CLAVE
        echo '<form method="get" action="' . esc_url($action_url) . '" class="mg-contador-filtro">';
        echo '<input type="hidden" name="page" value="mgo-contador">'; // ← Para que WP sepa dónde redirigir
        echo 'Desde: <input type="date" name="inicio" value="' . esc_attr($fecha_inicio) . '">';
        echo 'Hasta: <input type="date" name="fin" value="' . esc_attr($fecha_fin) . '">';
        echo '<select name="post_id">';
        echo '<option value="">Todas</option>';
        $paginas = get_posts(['post_type' => 'page', 'post_status' => 'publish', 'numberposts' => -1]);
        foreach($paginas as $p) {
            $selected = (isset($_GET['post_id']) && $_GET['post_id'] == $p->ID) ? 'selected' : '';
            echo '<option value="'.esc_attr($p->ID).'" '.$selected.'>'.esc_html($p->post_title).'</option>';
        }
        echo '</select>';
        echo ' <button type="submit">Filtrar</button>';
        
        // Botón Reiniciar ← CAMBIA get_permalink() por admin_url()
        echo ' <button type="button" onclick="window.location.href=\'' . esc_url(admin_url('admin.php?page=mgo-contador')) . '\'">Reiniciar</button>';
        echo '<br><button onclick="window.print()">Imprimir / PDF</button>';
        echo '</form><br>';

        // === BLOQUE 3: Tabla detallada (COPIA EXACTA de tu widget) ===
        if (!empty($visitas)) {
            echo '<h4>Listado detallado</h4>';
            echo '<table border="1" style="border-collapse:collapse; width:100%">';
            echo '<tr><th>Fecha</th><th>Página</th><th>Visitas</th><th>Reales</th><th class="ocultar-mvl">%</th></tr>';

            $total_visitas = 0; $total_reales = 0;
            foreach ($visitas as $v) {
                $post = get_post($v->post_id); 
                $titulo_actual = $post ? get_the_title($v->post_id) : ('Página eliminada: ' . $v->titulo);
                
                // 🔐 SECURITY: Sanitización defensiva - engaged nunca supera total
                $visitas_val = intval($v->visitas);
                $engaged_val = min(intval($v->engaged_visitas ?? 0), $visitas_val);
                $porcentaje = $visitas_val > 0 ? intval(($engaged_val * 100) / $visitas_val) : 0;
                
                echo '<tr>';
                echo '<td>' . esc_html($v->fecha) . '</td>';
                echo '<td>' . esc_html($titulo_actual) . '</td>';
                echo '<td>' . $visitas_val . '</td>';
                echo '<td>' . $engaged_val . '</td>';
                echo '<td class="ocultar-mvl">' . $porcentaje . '</td>';
                echo '</tr>';
                $total_visitas += $visitas_val;
                $total_reales += $engaged_val;
            }

            echo '<tr style="font-weight:bold"><td colspan="2">Total</td>';
            echo '<td>' . $total_visitas . '</td>';
            echo '<td>' . $total_reales . '</td>';
            $porcentaje_total = ($total_visitas > 0) ? intval(($total_reales*100)/$total_visitas) : 0;
            echo '<td class="ocultar-mvl">' . $porcentaje_total . '</td></tr>';
            echo '</table>';
        } else {
            echo '<p>No se encontraron visitas para los criterios seleccionados.</p>';
        }

        
        echo '</div>'; // .mg-contador-widget
    }
}