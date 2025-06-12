<?php
/**
 * Template Name: Formulario para Crear Evento
 */

$evento_enviado_ok = false;
$errores = array();

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['crear_evento_nonce'])) {
    if (wp_verify_nonce($_POST['crear_evento_nonce'], 'crear_evento_accion')) {

        $titulo_evento = sanitize_text_field($_POST['evento_titulo']);
        $descripcion_corta = wp_kses_post($_POST['descripcion_corta']);
        $poster_url = esc_url_raw($_POST['poster_evento']); 
        $pais = sanitize_text_field($_POST['localizacion_pais']);
        $ciudad = sanitize_text_field($_POST['localizacion_ciudad']);
        $fecha_inicio = sanitize_text_field($_POST['fecha_inicio']);
        $fecha_fin = sanitize_text_field($_POST['fecha_fin']);
        
        if (empty($titulo_evento)) $errores[] = "El nombre del evento es obligatorio.";
        if (empty($fecha_inicio)) $errores[] = "La fecha de inicio es obligatoria.";
        if (empty($fecha_fin)) $errores[] = "La fecha de finalización es obligatoria.";

        if (count($errores) == 0) {
            $id_organizador = get_current_user_id();

            $args_nuevo_evento = array(
                'post_title'    => $titulo_evento,
                'post_content'  => '', 
                'post_status'   => 'pending', 
                'post_type'     => 'evento',
                'post_author'   => $id_organizador,
            );

            $nuevo_evento_id = wp_insert_post($args_nuevo_evento);

            if ($nuevo_evento_id && !is_wp_error($nuevo_evento_id)) {
                update_field('organizador', $id_organizador, $nuevo_evento_id);
                update_field('descripcion_corta', $descripcion_corta, $nuevo_evento_id);
                update_field('poster_evento', $poster_url, $nuevo_evento_id);
                update_field('localizacion_pais', $pais, $nuevo_evento_id);
                update_field('localizacion_ciudad', $ciudad, $nuevo_evento_id);
                update_field('fecha_inicio', $fecha_inicio, $nuevo_evento_id);
                update_field('fecha_fin', $fecha_fin, $nuevo_evento_id);
                
                $evento_enviado_ok = true;
            } else {
                $errores[] = "Hubo un error al guardar el evento.";
            }
        }
    } else {
        $errores[] = "Error de seguridad. Inténtalo de nuevo.";
    }
}


get_header();
?>

<style>
.form-crear-evento .form-group { margin-bottom: 20px; }
.form-crear-evento label { display: block; font-weight: bold; margin-bottom: 5px; }
.form-crear-evento input[type="text"], .form-crear-evento input[type="date"], .form-crear-evento input[type="url"], .form-crear-evento textarea {
    width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 5px;
}
.form-crear-evento .aviso-exito { padding: 20px; background-color: #d4edda; border: 1px solid #c3e6cb; color: #155724; border-radius: 5px; }
.form-crear-evento .aviso-error { padding: 20px; background-color: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; border-radius: 5px; margin-bottom: 20px; }
</style>

<div id="primary" class="content-area">
    <main id="main" class="site-main">
        <article class="page">
            <header class="entry-header">
                <h1 class="entry-title">Crear un Nuevo Evento</h1>
            </header>

            <div class="entry-content">
                <?php if (!is_user_logged_in()) : ?>
                    <p>Debes <a href="<?php echo esc_url(wp_login_url(get_permalink())); ?>">iniciar sesión</a> para poder crear un evento.</p>
                <?php elseif ($evento_enviado_ok) : ?>
                    <div class="aviso-exito">
                        <p><strong>¡Evento enviado correctamente!</strong> Será revisado por un administrador antes de su publicación.</p>
                        <p><a href="<?php echo get_post_type_archive_link('evento'); ?>">Volver al archivo de eventos</a> o <a href="<?php echo get_permalink(); ?>">crear otro evento</a>.</p>
                    </div>
                <?php else : ?>
                
                    <?php if (!empty($errores)) : ?>
                        <div class="aviso-error">
                            <strong>Por favor, corrige los siguientes errores:</strong>
                            <ul>
                                <?php foreach ($errores as $error) : ?>
                                    <li><?php echo $error; ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <form id="crear-evento" class="form-crear-evento" method="POST" action="">
                        <div class="form-group">
                            <label for="evento_titulo">Nombre del Evento</label>
                            <input type="text" name="evento_titulo" required>
                        </div>
                        <div class="form-group">
                            <label for="descripcion_corta">Descripción Corta</label>
                            <textarea name="descripcion_corta" rows="5" required></textarea>
                        </div>
                        <div class="form-group">
                            <label for="poster_evento">URL del Póster del Evento</label>
                            <input type="url" name="poster_evento" placeholder="https://ejemplo.com/imagen.jpg">
                            <small><em>De momento, pega la URL de una imagen ya subida. La subida de archivos se implementara en futuras versiones.</em></small>
                        </div>
                        <div class="form-group">
                            <label for="localizacion_pais">País</label>
                            <input type="text" name="localizacion_pais" required>
                        </div>
                        <div class="form-group">
                            <label for="localizacion_ciudad">Ciudad</label>
                            <input type="text" name="localizacion_ciudad" required>
                        </div>
                         <div class="form-group">
                            <label for="fecha_inicio">Fecha de Inicio</label>
                            <input type="date" name="fecha_inicio" required>
                        </div>
                        <div class="form-group">
                            <label for="fecha_fin">Fecha de Finalización</label>
                            <input type="date" name="fecha_fin" required>
                        </div>
                        
                        <?php wp_nonce_field('crear_evento_accion', 'crear_evento_nonce'); ?>
                        
                        <div class="form-group">
                            <button type="submit">Enviar Evento para Revisión</button>
                        </div>
                    </form>
                <?php endif; ?>
            </div>
        </article>
    </main>
</div>

<?php
get_footer();
?>