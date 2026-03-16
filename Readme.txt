================================================================================
                    MGo - Contador Visitas
          Plugin WordPress de Analytics Privado y Seguro
================================================================================

DESCRIPCION
--------------------------------------------------------------------------------
Plugin personalizado de WordPress que permite registrar y visualizar 
estadisticas de visitas por pagina, diferenciando entre trafico bruto y 
visitas con engagement real. Diseñado con enfoque en privacidad, seguridad 
y cumplimiento GDPR.

Version: 1.3.7
Autor: Myriam Gomez
https://mmyriamgo.es


CARACTERISTICAS PRINCIPALES
--------------------------------------------------------------------------------
- Doble metrica: Registra visitas totales y visitas reales
- Privacidad first: No almacena datos personales, IPs ni informacion identificable
- Seguridad robusta: Nonces, prepared statements, rate limiting y validacion de origen
- Panel de administracion: Visualizacion de ranking, filtros por fecha/pagina y tablas
- Sin cookies de rastreo: Usa sessionStorage para deduplicacion tecnica
- Exclusion de admins: El trafico interno no contamina las estadisticas
- Exportable: Funcionalidad de impresion/guardado PDF desde el panel


ARQUITECTURA TECNICA
--------------------------------------------------------------------------------
MGo-ContadorVisitas/
├── MGo-ContadorVisitas.php      (Archivo principal del plugin)
├── includes/
│   ├── class-db-options.php     (Capa de datos - CRUD, queries)
│   ├── admin/
│   │   └── class-admin-page.php (Panel de administracion)
│   └── assets/
│       ├── MGoContadorStyle.css (Estilos frontend/admin)
│       └── MGoContadorScript.js (Tracking AJAX + sessionStorage)
└── README.txt                   (Este archivo)


MEDIDAS DE SEGURIDAD IMPLEMENTADAS
--------------------------------------------------------------------------------
CAPA                    PROTECCION                  IMPLEMENTACION
--------------------------------------------------------------------------------
CSRF                    Nonce verification          wp_verify_nonce() en AJAX
SQL Injection           Prepared statements         wpdb->prepare() en queries
XSS                     Output escaping             esc_html(), esc_attr(), esc_url()
Permisos                Capability checks           current_user_can('manage_options')
Integridad de datos     Validacion en BD            LEAST() garantiza engaged <= total
Rate limiting           Por IP                      Maximo 30 peticiones/minuto
Origen de peticiones    Referer check               Validacion HTTP_REFERER
Deduplicacion           sessionStorage              Evita contar recargas en sesion
Deteccion de bots       JS heuristics               navigator.webdriver + delays


DECISIONES DE PRIVACIDAD (GDPR)
--------------------------------------------------------------------------------
- No se recopilan datos personales: Ni IPs, ni user-agents, ni fingerprints
- No hay cookies de rastreo: sessionStorage es efimero (se borra al cerrar pestaña)
- No hay terceros: Todos los datos se almacenan localmente en tu servidor
- Exclusion de admins: El trafico interno no infla las metricas
- Transparencia: La politica de cookies del sitio refleja el funcionamiento

Base legal: Interes legitimo para metricas internas de funcionamiento del sitio 
(Art. 6.1.f RGPD). No requiere consentimiento explicito del usuario.


INSTALACION
--------------------------------------------------------------------------------
1. Sube la carpeta MGo-ContadorVisitas a /wp-content/plugins/
2. Activa el plugin desde el menu "Plugins" de WordPress
3. El plugin creara automaticamente la tabla wp_mg_visitas al activarse
4. Accede al panel desde "Visitas MGo" en el menu lateral de administracion

Requisitos minimos:
- WordPress 5.0 o superior
- PHP 7.4 o superior
- MySQL 5.7 o superior / MariaDB 10.2+


USO DEL PANEL DE ADMINISTRACION
--------------------------------------------------------------------------------
1. Ranking General: Visualiza el total de visitas y porcentaje de engagement
2. Filtros: Filtra por pagina especifica y rango de fechas
3. Listado Detallado: Tabla con desglose diario de visitas totales y reales
4. Imprimir/PDF: Genera un reporte imprimible de los datos filtrados

Metricas clave:
- Visitas totales: Cualquier carga de pagina registrada
- Visitas reales (engaged): Usuarios que permanecen >7s, hacen scroll o interactuan
- Tasa de engagement: (visitas_reales / visitas_totales) × 100


PRUEBAS Y VERIFICACION
--------------------------------------------------------------------------------
Para verificar que el tracking funciona correctamente:

1. Abre tu web en modo incognito (sin estar logueado como admin)
2. Visita una pagina y espera 7 segundos o haz scroll
3. Abre el panel de administracion "Visitas MGo"
4. Verifica que los contadores han aumentado en +1 (total) y +1 (engaged)

Para verificar que la exclusion de admins funciona:

1. Abre tu web en ventana normal (logueado como administrador)
2. Visita una pagina, espera, haz scroll
3. Revisa el panel: los contadores NO deberian haber cambiado


ACTUALIZACION DE LA BASE DE DATOS
--------------------------------------------------------------------------------
El plugin incluye un sistema de versionado interno. Al actualizar el plugin:

1. El boton "Actualizar" en el listado de plugins ejecuta migraciones
2. La version instalada se guarda en wp_options como contador_plugin_version
3. Las tablas existentes no se eliminan al desactivar el plugin


ESTRUCTURA DE LA BASE DE DATOS
--------------------------------------------------------------------------------
Tabla: prefix_mg_visitas

CAMPO               TIPO                    DESCRIPCION
--------------------------------------------------------------------------------
id                  BIGINT UNSIGNED         Primary key, autoincremental
post_id             BIGINT UNSIGNED         ID de la pagina/post visitado
titulo              VARCHAR(255)            Titulo de la pagina en momento de visita
fecha               DATE                    Fecha de la visita (formato Y-m-d)
visitas             INT UNSIGNED            Contador de visitas totales del dia
engaged_visitas     INT UNSIGNED            Contador de visitas engagadas del dia

Indice unico: post_id + fecha (evita filas duplicadas)


PERSONALIZACION
--------------------------------------------------------------------------------
Cambiar la zona horaria:
  En MGo-ContadorVisitas.php, linea ~12
  date_default_timezone_set('Europe/Madrid');

Modificar el umbral de engagement:
  En MGoContadorScript.js, linea ~55
  timer = setTimeout(function(){ enviarEngagement(); }, 7000);

Ajustar rate limiting:
  En class-db-options.php, metodo registrar_visita(), linea ~82
  if ($requests >= 30) { // 30 peticiones por minuto por IP



SOPORTE Y DESARROLLO
--------------------------------------------------------------------------------
Este es un plugin personalizado desarrollado para el portfolio de Myriam Gomez.
No esta disponible en el repositorio oficial de WordPress.


NOTAS PARA RECLUTADORES TECNICOS
--------------------------------------------------------------------------------
Este plugin fue desarrollado como proyecto de portfolio para demostrar:

1. Arquitectura modular: Separacion clara entre capas (DB, Admin UI, Frontend)
2. Seguridad por diseño: Nonces, prepared statements, escaping, capability checks
3. Privacidad first: Sin datos personales, sin cookies de rastreo, GDPR compliant
4. Calidad de datos: Distincion entre trafico bruto y engagement real
5. Buenas practicas WordPress: Hooks, $wpdb, Settings API, admin menus, AJAX

Tecnologias utilizadas: PHP 7.4+, WordPress Core API, MySQL, JavaScript (ES6+), 
AJAX, sessionStorage


================================================================================
                         Ultima actualizacion: Marzo 2026
================================================================================