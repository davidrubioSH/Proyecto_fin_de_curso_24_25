# Gestor de Eventos - Napoleonic Events

Esta aplicación web, construida sobre WordPress 6.8.1, permite la gestión de eventos de recreación histórica, incluyendo un sistema de registro de asociaciones y miembros, y un proceso de inscripción a eventos.

## Requisitos Previos

* Un servidor local como WAMP o XAMPP. (Se desarrollo usando WAMP)
* Una instalación nueva de la última versión de WordPress.

## Plugins Requeridos

Antes de empezar, instala y activa los siguientes plugins desde el panel de WordPress:

1.  **GeneratePress** (Tema)
2.  **Custom Post Type UI**
3.  **Advanced Custom Fields (ACF)**
4.  **Ultimate Member**

## Instrucciones de Instalación

1.  **Preparar WordPress:**
    * Instala una copia nueva de WordPress en tu servidor local.
    * Durante la instalación, crea una base de datos nueva y vacía (p. ej., `napoleonic_events_local`).

2.  **Copiar Archivos del Proyecto:**
    * Copia la carpeta del tema de este repositorio (`generatepress`) en la carpeta `wp-content/themes/` de tu nueva instalación de WordPress.
    * Activa el tema desde **Apariencia -> Temas**.
    * Borra la carpeta `uploads` que viene por defecto en `wp-content/` y copia la carpeta `uploads` de este repositorio en su lugar.

3.  **Importar la Base de Datos:**
    * Abre **phpMyAdmin** (`http://localhost/phpmyadmin`).
    * Selecciona tu base de datos nueva y vacía.
    * Ve a la pestaña **"Importar"**.
    * Haz clic en "Seleccionar archivo" y elige el archivo `proyecto.sql` incluido en este repositorio.
    * Haz clic en **"Importar"** al final de la página.

4.  **Actualizar la Base de Datos:**
    * Después de importar, la base de datos todavía piensa que el sitio está en `http://localhost/proyecto`. Necesitamos actualizar las URLs.
    * En phpMyAdmin, selecciona la base de datos y ve a la pestaña **"SQL"**.
    * Ejecuta las siguientes dos consultas, **reemplazando `http://localhost/tu-nueva-url`** con la URL de tu nueva instalación:

    ```sql
    UPDATE wp_options SET option_value = replace(option_value, 'http://localhost/proyecto', 'http://localhost/tu-nueva-url') WHERE option_name = 'home' OR option_name = 'siteurl';

    UPDATE wp_posts SET guid = replace(guid, 'http://localhost/proyecto','http://localhost/tu-nueva-url');

    UPDATE wp_posts SET post_content = replace(post_content, 'http://localhost/proyecto', 'http://localhost/tu-nueva-url');

    UPDATE wp_postmeta SET meta_value = replace(meta_value,'http://localhost/proyecto','http://localhost/tu-nueva-url');
    ```

5.  **Configuración Final:**
    * Ve a **Ajustes -> Enlaces Permanentes** en el panel de WordPress y simplemente haz clic en **"Guardar Cambios"** para regenerar las URLs. (NO TOCAR NINGUN AJUSTE)
