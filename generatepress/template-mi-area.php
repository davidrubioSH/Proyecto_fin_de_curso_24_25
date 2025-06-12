<?php
/**
 * Template Name: Mi Área de Asociación
 */

// BLOQUE 1: LÓGICA DE PROCESAMIENTO "SEARCH-OR-CREATE-THEN-LINK"
// ----------------------------------------------------------------

$mensaje_exito = '';
$errores_miembro = array();

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_miembro_nonce'])) {
    if (wp_verify_nonce($_POST['add_miembro_nonce'], 'add_miembro_accion')) {
        
        $nombre_miembro = sanitize_text_field($_POST['miembro_nombre']);
        $dni_miembro = sanitize_text_field($_POST['miembro_dni']);
        $id_asociacion = get_current_user_id();

        // 1. Validaciones básicas
        if (empty($nombre_miembro)) $errores_miembro[] = 'El nombre del miembro no puede estar vacío.';
        if (empty($dni_miembro)) $errores_miembro[] = 'El DNI del miembro no puede estar vacío.';

        if (count($errores_miembro) == 0) {
            $id_miembro_a_asociar = 0;

            // 2. Buscar si ya existe un miembro con ese DNI en todo el sistema
            $args_check_dni = array(
                'post_type' => 'miembro', 'post_status' => 'publish', 'posts_per_page' => 1,
                'meta_query' => array( array( 'key' => 'miembro_dni', 'value' => $dni_miembro, 'compare' => '=' ) )
            );
            $existing_members = get_posts($args_check_dni);

            if (!empty($existing_members)) {
                // 3a. SI EXISTE: Usamos el ID del miembro encontrado
                $id_miembro_a_asociar = $existing_members[0]->ID;
                $mensaje_exito = "El miembro ya existía en el sistema y ha sido asociado a tu cuenta.";

            } else {
                // 3b. SI NO EXISTE: Creamos un nuevo miembro en el directorio global
                $args_nuevo_miembro = array(
                    'post_title'  => $nombre_miembro, 'post_type' => 'miembro', 'post_status' => 'publish',
                );
                $id_miembro_a_asociar = wp_insert_post($args_nuevo_miembro);

                if ($id_miembro_a_asociar) {
                    update_field('miembro_dni', $dni_miembro, $id_miembro_a_asociar);
                    $mensaje_exito = "Miembro nuevo creado y asociado a tu cuenta.";
                } else {
                    $errores_miembro[] = "Hubo un error creando al nuevo miembro.";
                }
            }

            // 4. Si tenemos un ID de miembro (existente o nuevo), lo asociamos al usuario
            if ($id_miembro_a_asociar > 0) {
                // Obtenemos la lista actual de IDs de miembros de la asociación
                $ids_actuales = get_user_meta($id_asociacion, 'id_miembros_asociados', true);
                if (!is_array($ids_actuales)) {
                    $ids_actuales = array();
                }

                // Añadimos el nuevo ID solo si no está ya en la lista
                if (!in_array($id_miembro_a_asociar, $ids_actuales)) {
                    $ids_actuales[] = $id_miembro_a_asociar;
                    // Guardamos el array actualizado en el perfil del usuario
                    update_user_meta($id_asociacion, 'id_miembros_asociados', $ids_actuales);
                } else {
                    $mensaje_exito = "Este miembro ya estaba asociado a tu cuenta.";
                }
            }
        }
    }
}
//Borrado de usuarios
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['baja_miembro_nonce'])) {
    if (wp_verify_nonce($_POST['baja_miembro_nonce'], 'baja_miembro_accion')) {
        $id_miembro_a_borrar = intval($_POST['miembro_id_a_borrar']);
        $id_asociacion = get_current_user_id();

        if ($id_miembro_a_borrar > 0) {
            // Obtenemos la lista actual de IDs de miembros
            $ids_actuales = get_user_meta($id_asociacion, 'id_miembros_asociados', true);
            if (!is_array($ids_actuales)) {
                $ids_actuales = array();
            }

            // Buscamos el ID a borrar y lo eliminamos del array
            if (($key = array_search($id_miembro_a_borrar, $ids_actuales)) !== false) {
                unset($ids_actuales[$key]);
            }

            // Guardamos el array actualizado
            update_user_meta($id_asociacion, 'id_miembros_asociados', $ids_actuales);
            $mensaje_exito = "Miembro dado de baja de tu asociación correctamente.";
        }
    }
}


// Comienza la parte visual de la página
get_header();
?>

<div id="primary" class="content-area">
    <main id="main" class="site-main">
        <article class="page">
            <header class="entry-header">
                <h1 class="entry-title">Mi Área de Asociación</h1>
            </header>

            <div class="entry-content">
                <?php if (!is_user_logged_in()) : ?>
                    <p>Debes <a href="<?php echo esc_url(wp_login_url(get_permalink())); ?>">iniciar sesión</a> para acceder a tu área de asociación.</p>
                <?php else : ?>
                    <h2>Gestión de Miembros</h2>
                    <div class="gestion-miembros">
                        
                        <div class="lista-miembros">
                            <h3>Mis Miembros Asociados</h3>
                            <?php
                            // BLOQUE 2: NUEVA LÓGICA PARA MOSTRAR LA LISTA
                            // ----------------------------------------------------
                            // 1. Obtenemos el array de IDs de miembros del perfil del usuario
                            $ids_mis_miembros = get_user_meta(get_current_user_id(), 'id_miembros_asociados', true);

                            if (!empty($ids_mis_miembros) && is_array($ids_mis_miembros)) :
                                // 2. Hacemos una consulta para obtener solo los posts cuyo ID esté en nuestro array
                                $args_mis_miembros = array(
                                    'post_type' => 'miembro',
                                    'posts_per_page' => -1,
                                    'post__in' => $ids_mis_miembros, // ¡LA CLAVE!
                                    'orderby' => 'title',
                                    'order' => 'ASC'
                                );
                                $mis_miembros = new WP_Query($args_mis_miembros);
                            ?>
                                <table>
                                    <thead>
                                        <tr>
                                            <th>Nombre Completo</th>
                                            <th>DNI</th>
                                            <th>Acciones</th> </tr>
                                    </thead>
                                    <tbody>
                                        <?php while ($mis_miembros->have_posts()) : $mis_miembros->the_post(); ?>
                                            <tr>
                                                <td><?php the_title(); ?></td>
                                                <td><?php the_field('miembro_dni'); ?></td>
                                                <td>
                                                    <form method="POST" action="" style="margin:0;">
                                                        <input type="hidden" name="miembro_id_a_borrar" value="<?php echo get_the_ID(); ?>">
                                                        <?php wp_nonce_field('baja_miembro_accion', 'baja_miembro_nonce'); ?>
                                                        <button type="submit" class="boton-anular" onclick="return confirm('¿Estás seguro de que quieres dar de baja a este miembro de tu asociación?');">Dar de baja</button>
                                                    </form>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            <?php else : ?>
                                <p>Aún no has asociado ningún miembro.</p>
                            <?php endif; 
                            if(isset($mis_miembros)) { wp_reset_postdata(); }
                            ?>
                        </div>

                        <div class="form-add-miembro">
                            <h3>Asociar Miembro</h3>

                            <?php if ($mensaje_exito) echo '<div class="aviso-exito">' . $mensaje_exito . '</div>'; ?>
                            <?php if (!empty($errores_miembro)) : ?>
                                <div class="aviso-error">
                                    <?php foreach ($errores_miembro as $error) echo "<p>$error</p>"; ?>
                                </div>
                            <?php endif; ?>

                            <form id="add-miembro-form" method="POST" action="">
                                <p>Introduce los datos de un miembro. Si ya existe en el sistema, se vinculará a tu asociación. Si no, se creará uno nuevo.</p>
                                <div class="form-group">
                                    <label for="miembro_nombre">Nombre Completo</label>
                                    <input type="text" id="miembro_nombre" name="miembro_nombre" required>
                                </div>
                                <div class="form-group">
                                    <label for="miembro_dni">DNI</label>
                                    <input type="text" id="miembro_dni" name="miembro_dni" required>
                                </div>
                                <?php wp_nonce_field('add_miembro_accion', 'add_miembro_nonce'); ?>
                                <button type="submit">Añadir / Asociar Miembro</button>
                            </form>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </article>
    </main>
</div>

<?php
get_footer();
?>