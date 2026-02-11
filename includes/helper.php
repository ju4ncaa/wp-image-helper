<?php
function chatgpt_process_uploaded_image($upload) {
    $file_path = $upload['file'];
    $type = wp_check_filetype($file_path);
    if (!in_array($type['ext'], ['jpg', 'jpeg', 'png'])) return $upload;

    $api_key = get_option('chatgpt_api_key');
    if (!$api_key) return $upload;

    $image_data = base64_encode(file_get_contents($file_path));
    $response = wp_remote_post('https://api.openai.com/v1/chat/completions', [
        'headers' => [
            'Authorization' => 'Bearer ' . $api_key,
            'Content-Type' => 'application/json',
        ],
        'body' => json_encode([
            'model' => 'gpt-4-vision-preview',
            'messages' => [[
                'role' => 'user',
                'content' => [
                    ['type' => 'image_url', 'image_url' => ['url' => 'data:image/jpeg;base64,' . $image_data]],
                    ['type' => 'text', 'text' => 'Describe esta imagen y proporciona:
                        - Nombre sugerido (sin extensión)
                        - Título descriptivo
                        - Texto alternativo']
                ]
            ]],
            'max_tokens' => 150
        ])
    ]);

    if (is_wp_error($response)) return $upload;

    $body = json_decode(wp_remote_retrieve_body($response), true);
    $text = $body['choices'][0]['message']['content'];
    $lines = explode("\n", trim($text));
    $meta = ['filename' => '', 'title' => '', 'alt' => ''];
    foreach ($lines as $line) {
        if (stripos($line, 'nombre') !== false) $meta['filename'] = sanitize_file_name(trim(explode(':', $line)[1]));
        if (stripos($line, 'título') !== false) $meta['title'] = trim(explode(':', $line)[1]);
        if (stripos($line, 'alternativo') !== false) $meta['alt'] = trim(explode(':', $line)[1]);
    }

    if (!empty($meta['filename'])) {
        $new_path = dirname($file_path) . '/' . $meta['filename'] . '.' . $type['ext'];
        rename($file_path, $new_path);
        $upload['file'] = $new_path;
        $upload['url'] = str_replace(basename($upload['url']), basename($new_path), $upload['url']);
    }

    add_filter('wp_generate_attachment_metadata', function($metadata, $attachment_id) use ($meta) {
        if (!empty($meta['alt'])) update_post_meta($attachment_id, '_wp_attachment_image_alt', $meta['alt']);
        if (!empty($meta['title'])) wp_update_post(['ID' => $attachment_id, 'post_title' => $meta['title']]);
        return $metadata;
    }, 10, 2);

    do_action('chatgpt_image_ready_for_resize', $upload['file']);

    return $upload;
}
