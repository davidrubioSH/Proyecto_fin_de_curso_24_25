<?php
$hoy = date('Ymd');
$fecha_inicio = get_field('fecha_inicio'); // Formato YYYYMMDD
$fecha_fin = get_field('fecha_fin'); // Formato YYYYMMDD

$status_texto = '';
$status_class = '';

if ($hoy > $fecha_fin) {
    $status_texto = 'Finalizado';
    $status_class = 'status-finalizado';
} elseif ($hoy >= $fecha_inicio && $hoy <= $fecha_fin) {
    $status_texto = 'En curso';
    $status_class = 'status-en-curso';
} else {
    $status_texto = 'Próximo';
    $status_class = 'status-proximo';
}
?>

<article id="post-<?php the_ID(); ?>" <?php post_class('evento-card'); ?>>
    
    <span class="status-label <?php echo $status_class; ?>"><?php echo $status_texto; ?></span>

    <div class="evento-card-imagen">
        <a href="<?php the_permalink(); ?>">
            <?php 
            $poster_url = get_field('poster_evento');
            if ($poster_url) :
                echo '<img src="' . esc_url($poster_url) . '" alt="' . esc_attr(get_the_title()) . '">';
            else:
                echo '<img src="https://via.placeholder.com/400x250.png?text=Evento" alt="Evento sin poster">';
            endif;
            ?>
        </a>
    </div>

    <div class="evento-card-contenido">
        <h3><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
        <div class="fecha">
            <strong>Inicio:</strong> <?php echo esc_html($fecha_inicio); ?>
        </div>
        <div class="lugar">
            <strong>Lugar:</strong> 
            <?php echo esc_html(get_field('localizacion_ciudad')); ?>
        </div>
        <a href="<?php the_permalink(); ?>" class="leer-mas">Ver Detalles</a>
    </div>

</article>