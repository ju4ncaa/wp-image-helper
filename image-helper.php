<?php
/**
 * Plugin Name: Image Helper
 * Description: Renombra, redimensiona y etiqueta automáticamente imágenes subidas a WordPress usando la API de OpenAI.
 * Version: 1.0
 * Author URI: https://github.com/ju4ncaa
 * Author: Juan Carlos Rodríguez (a.k.a ju4ncaa)
 */

defined('ABSPATH') or die('Acceso denegado');

include_once plugin_dir_path(__FILE__) . 'includes/helper.php';
include_once plugin_dir_path(__FILE__) . 'includes/resize-helper.php';
include_once plugin_dir_path(__FILE__) . 'includes/image-meta.php';

// Hook al subir archivos
add_filter('wp_handle_upload', 'chatgpt_process_uploaded_image');

// Crear menú de ajustes
add_action('admin_menu', function () {
    add_options_page('Leitmotiv Image Helper', 'Leitmotiv Image Helper', 'manage_options', 'chatgpt-image-helper', 'chatgpt_image_helper_settings_page');
});

add_action('admin_init', function () {
    register_setting('chatgpt_image_helper_settings', 'chatgpt_api_key');
    register_setting('chatgpt_image_helper_settings', 'chatgpt_resize_width');
    register_setting('chatgpt_image_helper_settings', 'chatgpt_resize_height');
    register_setting('chatgpt_image_helper_settings', 'chatgpt_crop');
});

function chatgpt_image_helper_settings_page()
{
?>
    <div class="wrap">
        <h1>Leitmotiv Image Helper - Ajustes</h1>
        <form method="post" action="options.php">
            <?php settings_fields('chatgpt_image_helper_settings'); ?>
            <?php do_settings_sections('chatgpt_image_helper_settings'); ?>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row">API Key de OpenAI</th>
                    <td><input type="text" name="chatgpt_api_key" value="<?php echo esc_attr(get_option('chatgpt_api_key')); ?>" size="50"/></td>
                </tr>
                <tr valign="top">
                    <th scope="row">Ancho deseado</th>
                    <td><input type="number" name="chatgpt_resize_width" value="<?php echo esc_attr(get_option('chatgpt_resize_width', 1024)); ?>"/></td>
                </tr>
                <tr valign="top">
                    <th scope="row">Alto deseado</th>
                    <td><input type="number" name="chatgpt_resize_height" value="<?php echo esc_attr(get_option('chatgpt_resize_height', 768)); ?>"/></td>
                </tr>
                <tr valign="top">
                    <th scope="row">Recortar imagen</th>
                    <td><input type="checkbox" name="chatgpt_crop" value="1" <?php checked(1, get_option('chatgpt_crop'), true); ?> /> Activar recorte</td>
                </tr>
            </table>
            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}