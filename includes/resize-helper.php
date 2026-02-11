<?php
add_action('chatgpt_image_ready_for_resize', function($file_path) {
    $width = (int) get_option('chatgpt_resize_width', 1024);
    $height = (int) get_option('chatgpt_resize_height', 768);
    $crop = get_option('chatgpt_crop', 1) == 1;

    $image = wp_get_image_editor($file_path);
    if (!is_wp_error($image)) {
        $image->resize($width, $height, $crop);
        $image->save($file_path);
    }
});
