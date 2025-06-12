<?php
/**
 * The template for displaying Archive pages.
 *
 * @package GeneratePress
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

get_header(); ?>

<div id="primary" class="content-area grid-container container">
    <main id="main" class="site-main">

        <header class="page-header">
            <h1 class="page-title">Archivo de Eventos</h1>
        </header>

        <section class="seccion-eventos">
            <h2>Eventos Destacados</h2>
            <div class="generate-columns-container">
                <?php
                $hoy = date('Ymd');
                $args_destacados = array(
                    'post_type' => 'evento',
                    'posts_per_page' => 3,
                    'meta_key' => 'fecha_inicio',
                    'orderby' => 'meta_value',
                    'order' => 'ASC',
                    'meta_query' => array( array( 'key' => 'fecha_inicio', 'value' => $hoy, 'compare' => '>=' ) )
                );
                $query_destacados = new WP_Query($args_destacados);
                if ($query_destacados->have_posts()) :
                    while ($query_destacados->have_posts()) : $query_destacados->the_post();
                        // Incluimos la plantilla de la tarjeta de evento
                        get_template_part('template-parts/content', 'evento-card');
                    endwhile;
                else:
                    echo '<p>No hay eventos destacados próximamente.</p>';
                endif;
                wp_reset_postdata();
                ?>
            </div>
        </section>

        <hr>

        <section class="seccion-eventos">
            <h2>Todos los Eventos</h2>
            <div class="generate-columns-container">
                 <?php
                $paged = ( get_query_var( 'paged' ) ) ? get_query_var( 'paged' ) : 1;
                $args_todos = array(
                    'post_type' => 'evento',
                    'posts_per_page' => 6, 
                    'paged' => $paged,
                    'meta_key' => 'fecha_inicio',
                    'orderby' => 'meta_value',
                    'order' => 'DESC', 
                );
                $query_todos = new WP_Query($args_todos);
                if ($query_todos->have_posts()) :
                    while ($query_todos->have_posts()) : $query_todos->the_post();
                        get_template_part('template-parts/content', 'evento-card');
                    endwhile;
                else:
                     echo '<p>No se han encontrado eventos.</p>';
                endif;
                ?>
            </div>
            
            <div class="pagination">
                <?php
                echo paginate_links(array(
                    'total' => $query_todos->max_num_pages,
                    'current' => $paged,
                ));
                ?>
            </div>
            <?php wp_reset_postdata(); ?>
        </section>

    </main>
</div>

<?php
/**
 * generate_after_primary_content_area hook.
 *
 * @since 2.0
 */
do_action( 'generate_after_primary_content_area' );

get_footer();
