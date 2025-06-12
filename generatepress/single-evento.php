<?php
/**
 * The Template for displaying all single posts.
 *
 * @package GeneratePress
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

// ------------------------------------------------------
// - PROCESAMIENTO DE FORMULARIOS DE INSCRIPCIÓN / BAJA -
// ------------------------------------------------------
$errores_inscripcion = array();
$mensaje_inscripcion = '';

// Comprobamos si se ha enviado el formulario de inscripción
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['inscribir_miembros_nonce'])) {
    if (wp_verify_nonce($_POST['inscribir_miembros_nonce'], 'inscribir_miembros_accion')) {
        
        $id_evento = intval($_POST['evento_id']);
        $id_asociacion_actual = get_current_user_id();
        $miembros_seleccionados = isset($_POST['miembros_a_inscribir']) ? array_map('intval', (array)$_POST['miembros_a_inscribir']) : array();

        if ($id_asociacion_actual > 0 && $id_evento > 0) {
            
            // --- INICIO DE LA NUEVA VALIDACIÓN GLOBAL ---
            
            // 1. Obtener TODOS los miembros ya inscritos por OTRAS asociaciones
            global $wpdb;
            $miembros_ya_inscritos_por_otros = array();
            $meta_keys_otras_asociaciones = $wpdb->get_col($wpdb->prepare(
                "SELECT meta_key FROM $wpdb->postmeta WHERE post_id = %d AND meta_key LIKE %s AND meta_key != %s",
                $id_evento, 'inscripcion_asociacion_%', 'inscripcion_asociacion_' . $id_asociacion_actual
            ));

            foreach ($meta_keys_otras_asociaciones as $meta_key) {
                $ids_miembros = get_post_meta($id_evento, $meta_key, true);
                if (is_array($ids_miembros)) {
                    $miembros_ya_inscritos_por_otros = array_merge($miembros_ya_inscritos_por_otros, $ids_miembros);
                }
            }
            $miembros_ya_inscritos_por_otros = array_unique($miembros_ya_inscritos_por_otros);

            // 2. Comprobar si alguno de los miembros seleccionados ya está en esa lista
            foreach ($miembros_seleccionados as $id_miembro_a_inscribir) {
                if (in_array($id_miembro_a_inscribir, $miembros_ya_inscritos_por_otros)) {
                    // Si encontramos un duplicado, añadimos un error
                    $nombre_miembro_duplicado = get_the_title($id_miembro_a_inscribir);
                    $errores_inscripcion[] = "El miembro '" . esc_html($nombre_miembro_duplicado) . "' ya está inscrito en este evento por otra asociación.";
                }
            }
            
            // --- FIN DE LA NUEVA VALIDACIÓN GLOBAL ---

            // 3. Si no se encontraron errores, procedemos a guardar
            if (empty($errores_inscripcion)) {
                if (!empty($miembros_seleccionados)) {
                    update_post_meta($id_evento, 'inscripcion_asociacion_' . $id_asociacion_actual, $miembros_seleccionados);
                    $mensaje_inscripcion = '<div class="aviso-exito">¡Inscripción actualizada con éxito!</div>';
                } else {
                    delete_post_meta($id_evento, 'inscripcion_asociacion_' . $id_asociacion_actual);
                    $mensaje_inscripcion = '<div class="aviso-exito">Inscripción anulada correctamente.</div>';
                }
            }
        }
    }
}
/**
 * LÓGICA PARA MANEJAR LA DESCARGA DEL CSV
 */
if (isset($_GET['descargar_listado']) && $_GET['descargar_listado'] == 'csv' && isset($_GET['evento_id']) && isset($_GET['nonce'])) {
    
    $id_evento = intval($_GET['evento_id']);
    $nonce = sanitize_text_field($_GET['nonce']);

    // 1. Verificación de seguridad (Nonce)
    if (wp_verify_nonce($nonce, 'descargar_listado_evento_' . $id_evento)) {

        // 2. Obtener los datos (reutilizamos la misma lógica que para mostrar la lista)
        global $wpdb;
        $meta_keys = $wpdb->get_col($wpdb->prepare(
            "SELECT meta_key FROM $wpdb->postmeta WHERE post_id = %d AND meta_key LIKE %s",
            $id_evento, 'inscripcion_asociacion_%'
        ));

        if (!empty($meta_keys)) {
            // 3. Preparar el nombre del archivo y las cabeceras HTTP para la descarga
            $nombre_evento_slug = get_post_field('post_name', $id_evento);
            $nombre_archivo = 'listado-' . $nombre_evento_slug . '-' . date('Y-m-d') . '.csv';

            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="' . $nombre_archivo . '"');

            // 4. Crear un puntero de salida de PHP para escribir el CSV
            $output = fopen('php://output', 'w');

            // 5. Escribir la fila de las cabeceras del CSV
            fputcsv($output, array('Asociacion', 'Nombre Miembro', 'DNI'));

            // 6. Iterar sobre los datos y escribir cada fila en el CSV
            foreach ($meta_keys as $meta_key) {
                $id_asociacion = str_replace('inscripcion_asociacion_', '', $meta_key);
                $datos_asociacion = get_userdata($id_asociacion);
                $nombre_asociacion = $datos_asociacion ? $datos_asociacion->display_name : 'Asociación Desconocida';

                $ids_miembros_inscritos = get_post_meta($id_evento, $meta_key, true);

                if (!empty($ids_miembros_inscritos)) {
                    $query_miembros_inscritos = new WP_Query(['post_type' => 'miembro', 'post__in' => $ids_miembros_inscritos]);
                    if ($query_miembros_inscritos->have_posts()) {
                        while ($query_miembros_inscritos->have_posts()) {
                            $query_miembros_inscritos->the_post();
                            // Escribimos una línea por cada miembro
                            fputcsv($output, array(
                                $nombre_asociacion,
                                get_the_title(),
                                get_field('miembro_dni')
                            ));
                        }
                    }
                    wp_reset_postdata();
                }
            }
            
            // 7. Detener la ejecución del script para que no se renderice el resto de la página
            exit();
        }
    }
}

get_header(); ?>
<!--
<div id="primary" class="content-area grid-container container">
    <main id="main" class="site-main">
-->
<div <?php generate_do_attr( 'content' ); ?>>
	<main <?php generate_do_attr( 'main' ); ?>>
        <?php
        if (have_posts()) :
            while (have_posts()) : the_post();
        ?>

        <article id="post-<?php the_ID(); ?>" <?php post_class('evento-single'); ?>>

            <div class="evento-contenedor">

                <div class="evento-poster">
                    <?php
                    $poster_url = get_field('poster_evento');
                    if ($poster_url) :
                    ?>
                        <img src="<?php echo esc_url($poster_url); ?>" alt="Poster de <?php the_title_attribute(); ?>">
                    <?php else : ?>
                        <img src="<?php echo get_template_directory_uri(); ?>/images/default-poster.jpg" alt="Evento sin poster">
                    <?php endif; ?>
                </div>

                <div class="evento-datos">

                    <h1><?php the_title();?></h1>

                    <div class="info-item">
					<strong>Organizador:</strong>
					<span>
						<?php
						$organizador_data = get_field('organizador');

						if ($organizador_data) {
							echo esc_html($organizador_data['display_name']);
						}
						?>
					</span>
					<!-- Opcion a largo plazo
					<strong>Organizador:</strong>
					<span>
						<?php/*
						$organizador_data = get_field('organizador');

						if ($organizador_data) {
							// Obtenemos el enlace a la página de autor de ese usuario
							$enlace_organizador = get_author_posts_url($organizador_data['ID']);
							$nombre_organizador = esc_html($organizador_data['display_name']);
							
							// Imprimimos el nombre como un enlace
							echo '<a href="' . esc_url($enlace_organizador) . '">' . $nombre_organizador . '</a>';
						}*/
						?>
					</span>
					-->
                    </div>

                    <div class="info-item">
                        <strong>Lugar:</strong>
                        <span>
                            <?php echo esc_html(get_field('localizacion_ciudad')); ?>,
                            <?php echo esc_html(get_field('localizacion_pais')); ?>
                        </span>
                    </div>

                    <div class="info-item">
                        <strong>Fechas:</strong>
                        <span>
                            Del <?php echo esc_html(get_field('fecha_inicio')); ?>
                            al <?php echo esc_html(get_field('fecha_fin')); ?>
                        </span>
                    </div>
                    
                    <div class="info-item">
                        <strong>Descripción:</strong>
                        <div><?php echo wp_kses_post(get_field('descripcion_corta')); // wp_kses_post para permitir HTML seguro ?></div>
                    </div>

                </div>

            </div> <hr>

            <div class="bloque-inscripciones">
				<h2>Inscripciones</h2>

				<?php 
				// Mostramos mensajes de éxito o error
				if (!empty($mensaje_inscripcion)) { echo $mensaje_inscripcion; }
				if (!empty($errores_inscripcion)) {
					echo '<div class="aviso-error"><strong>No se pudo procesar la inscripción:</strong><ul>';
					foreach ($errores_inscripcion as $error) {
						echo '<li>' . $error . '</li>';
					}
					echo '</ul></div>';
				}
				?>

				<?php
				$id_evento_actual = get_the_ID();
				$id_usuario_actual = get_current_user_id();

				// Comprobamos si el usuario actual ya tiene una inscripción en este evento
				$inscripcion_guardada = get_post_meta($id_evento_actual, 'inscripcion_asociacion_' . $id_usuario_actual, true);
				if (!is_array($inscripcion_guardada)) { $inscripcion_guardada = array(); }

				// Si hubo un error en el envío, las selecciones previas son las del POST. Si no, las de la BBDD.
				$selecciones_actuales = isset($_POST['miembros_a_inscribir']) ? array_map('intval', (array)$_POST['miembros_a_inscribir']) : $inscripcion_guardada;


				if (is_user_logged_in()) :
					$ids_mis_miembros = get_user_meta($id_usuario_actual, 'id_miembros_asociados', true);

					if (empty($ids_mis_miembros)) : ?>
						<p>Primero debes <a href="/mi-area-de-asociacion/">añadir miembros en tu área de asociación</a> para poder inscribirlos en un evento.</p>
					<?php else : ?>
						<form method="POST" action="">
							<h3>Selecciona los miembros que asistirán:</h3>
							<input type="hidden" name="evento_id" value="<?php echo esc_attr($id_evento_actual); ?>">
							<?php wp_nonce_field('inscribir_miembros_accion', 'inscribir_miembros_nonce'); ?>

							<?php
							$query_mis_miembros = new WP_Query(['post_type' => 'miembro', 'posts_per_page' => -1, 'post__in' => $ids_mis_miembros, 'orderby' => 'title', 'order' => 'ASC']);
							if ($query_mis_miembros->have_posts()) {
								while ($query_mis_miembros->have_posts()) {
									$query_mis_miembros->the_post();
									$miembro_id = get_the_ID();
									// La casilla estará marcada si el ID está en el array de selecciones actuales
									$checked = in_array($miembro_id, $selecciones_actuales) ? 'checked' : '';
									echo '<div><label><input type="checkbox" name="miembros_a_inscribir[]" value="' . esc_attr($miembro_id) . '" ' . $checked . '> ' . get_the_title() . '</label></div>';
								}
							}
							wp_reset_postdata();
							?>

							<p style="margin-top: 20px;">
								<button type="submit" class="boton-inscripcion">Confirmar / Actualizar Inscripción</button>
							</p>
							<small><em>Para anular la inscripción completa, desmarca a todos los miembros y confirma.</em></small>
						</form>
					<?php endif; ?>

				<?php else : ?>
					<p><a href="<?php echo esc_url(wp_login_url(get_permalink())); ?>">Inicia sesión</a> para inscribir a tu asociación.</p>
				<?php endif; ?>
			</div>
            
        </article>
		
		<?php
            endwhile;
        endif;
        ?>

    </main></div>

		<?php
		// Comprobamos si el usuario actual es el autor (organizador) del evento
		if (get_current_user_id() == get_the_author_meta('ID')) :
			global $wpdb;
			$id_evento_actual = get_the_ID();
			
			// Obtenemos todas las meta_keys de inscripciones para este evento
			$meta_keys = $wpdb->get_col( $wpdb->prepare(
				"SELECT meta_key FROM $wpdb->postmeta WHERE post_id = %d AND meta_key LIKE %s",
				$id_evento_actual,
				'inscripcion_asociacion_%' // El patrón que buscamos
			) );
		?>
			<div class="listado-organizador" style="margin-top: 40px; padding: 20px; background-color: #fff8e5; border: 1px solid #ffeeba;">
				<h2>Área del Organizador: Listado de Inscritos</h2>
				<?php if (!empty($meta_keys)) : 
					$contador_miembros_total = 0;
				?>
					<p>Total de asociaciones inscritas: <strong><?php echo count($meta_keys); ?></strong></p>
					<hr>
					<?php 
					// Iteramos sobre cada inscripción encontrada
					foreach ($meta_keys as $meta_key) {
						// Extraemos el ID de la asociación desde la meta_key
						$id_asociacion = str_replace('inscripcion_asociacion_', '', $meta_key);
						$datos_asociacion = get_userdata($id_asociacion);
						
						// Obtenemos el array de IDs de miembros para esta inscripción
						$ids_miembros_inscritos = get_post_meta($id_evento_actual, $meta_key, true);

						if ($datos_asociacion && !empty($ids_miembros_inscritos)) {
							$contador_miembros_total += count($ids_miembros_inscritos);
							echo "<h4>Asociación: " . esc_html($datos_asociacion->display_name) . " (" . count($ids_miembros_inscritos) . " miembros)</h4>";
							
							// Hacemos una consulta para obtener los datos de los miembros inscritos
							$query_miembros_inscritos = new WP_Query(['post_type' => 'miembro', 'post__in' => $ids_miembros_inscritos]);
							if($query_miembros_inscritos->have_posts()){
								echo "<ul>";
								while($query_miembros_inscritos->have_posts()){
									$query_miembros_inscritos->the_post();
									echo "<li>" . get_the_title() . " (DNI: " . get_field('miembro_dni') . ")</li>";
								}
								echo "</ul>";
							}
							wp_reset_postdata();
						}
					}
					echo "<hr><p><strong>TOTAL MIEMBROS INSCRITOS EN EL EVENTO: " . $contador_miembros_total . "</strong></p>";
					$nonce_descarga = wp_create_nonce('descargar_listado_evento_' . $id_evento_actual);
					// Creamos la URL con los parámetros
					$url_descarga = add_query_arg([
						'descargar_listado' => 'csv',
						'evento_id' => $id_evento_actual,
						'nonce' => $nonce_descarga
					], get_permalink());
					?>
					<a href="<?php echo esc_url($url_descarga); ?>" class="boton-inscripcion" style="margin-top:20px; display:inline-block;">Descargar Listado (CSV)</a>
				else : ?>
					<p>Aún no hay ninguna asociación inscrita en tu evento.</p>
				<?php endif; ?>
			</div>
		<?php endif; ?>

	<?php
	/**
	 * generate_after_primary_content_area hook.
	 *
	 * @since 2.0
	 */
	do_action( 'generate_after_primary_content_area' );

	get_footer();
